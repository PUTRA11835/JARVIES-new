# Alur Pembuatan Tiket — JARVIES Backend

> Dokumen ini menjelaskan alur backend pembuatan tiket dari dua sumber: **Web Portal** (customer & admin) dan **Mobile API** (Flutter customer).

---

## Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Alur Customer via Web Portal](#alur-customer-via-web-portal)
3. [Alur Admin via Web Portal](#alur-admin-via-web-portal)
4. [Alur Mobile API (Flutter)](#alur-mobile-api-flutter)
5. [StagingTicketService — Detail Implementasi](#stagingticketservice--detail-implementasi)
6. [Model & Tabel](#model--tabel)
7. [Integrasi EcoSystem](#integrasi-ecosystem)
8. [Error Handling & Fallback](#error-handling--fallback)

---

## Gambaran Umum

Tiket di JARVIES tidak langsung masuk ke tabel `ticket`. Submission dari **customer** selalu melewati fase **staging** yang menunggu validasi admin. Hanya **admin** yang bisa membuat tiket langsung (bypass staging).

```
Customer (Web / Mobile)
    │
    ▼
[staging_tickets] ──► Admin Validasi (di EcoSystem)
                            │
                    Approve ▼          Reject ──► Customer notified
                      [ticket]
                          │
                    Employee "Take"
                          │
                    Admin Confirm
                          │
                    Status: in process
```

---

## Alur Customer via Web Portal

**Endpoint:** `POST /tickets`
**Controller:** `TicketController@store` ([app/Http/Controllers/TicketController.php](app/Http/Controllers/TicketController.php#L446))

### Validasi Input

```
description      required|string|max:5000
ticket_priority  nullable|in:Very High,High,Medium,Low
body             nullable|string
body_html        nullable|string          (dari Quill editor)
cc_emails        nullable|array|max:10    (array plain string email)
cc_emails.*      email
name             nullable|string|max:255
no_hp            nullable|string|max:255
module           nullable|string|max:255
client           nullable|string|max:255
attachments      nullable|array
attachments.*    file|max:20480 (20 MB per file)
for_customer_id  nullable|integer         (parent customer → end customer)
```

### Step-by-Step Backend

#### STEP 1 — Proses Inline Images dari Quill HTML

Body yang ditulis di Quill editor menyertakan gambar sebagai base64 data URI (hasil paste/upload inline). Backend mengekstrak setiap `<img src="data:image/...;base64,...">` dan mengonversinya ke format **CID (Content-ID)** untuk email:

- Setiap gambar diberi nama `img-{n}@jarvies`
- Data URI di `$emailBodyHtml` diganti `cid:img-{n}@jarvies`
- `$htmlBodyForDb` **tidak diubah** — tetap pakai base64 agar bisa dirender di browser JARVIES

```php
// Hasil: $emailInlineImages[] = ['name', 'content', 'mime', 'cid']
// $emailBodyHtml → versi email (cid:)
// $htmlBodyForDb → versi DB (base64 asli)
```

#### STEP 2 — Baca File Attachments

File attachment dari tombol `<input type="file">` dibaca binary sekarang karena dipakai dua kali:
- Upload ke M365 via Graph API (STEP 3)
- File lokal disimpan di `storage/app/staging-attachments/{stagingId}/` sebagai fallback download

```php
// $emailFileAttachments[] = ['name', 'content' (binary), 'mime']
```

#### STEP 3 — Kirim Email via GraphRelayService

Email dikirim dari akun helpdesk (Raditya) ke customer menggunakan **Microsoft Graph API**.

- **Subject:** `[No Reply] [Pending Validation] {description}`
- **Body:** HTML yang dibangun via `buildTicketEmailBody()` — berisi metadata (phone, module, client) + isi pesan
- Inline images dilampirkan sebagai attachment dengan `Content-ID`
- File attachment dilampirkan sebagai attachment biasa

Jika email **gagal** (exception atau `$emailResult === null`), sistem tidak berhenti — ada fallback ke STEP 4a.

```php
$graphService = new GraphRelayService();
$emailResult  = $graphService->sendStandaloneEmail(...);
// Return: ['internet_message_id', 'conversation_id', 'graph_message_id', ...]
// Return: null jika gagal
```

#### STEP 4a — Simpan Staging ke DB JARVIES

Dipanggil setelah Graph selesai (atau gagal). Reconnect MySQL dahulu karena koneksi bisa timeout saat Graph API memproses.

```php
DB::reconnect();
$service = new StagingTicketService();
$stagingRecord = $service->createFromWeb(
    array_merge($validated, [
        'email_thread_id'  => $conversationId,   // dari Graph (null jika email gagal)
        'email_message_id' => $internetMsgId,    // dari Graph (null jika email gagal)
        'channel'          => $emailResult ? 'email' : 'web',
        'end_customer_id'  => $forCustomerId,
    ]),
    $ticketCustomerId,  // selalu ID parent customer (bukan end customer)
    $senderEmail,
    $senderName,
    array_column($emailFileAttachments, 'name')
);
```

File attachment kemudian disimpan lokal:
```
storage/app/staging-attachments/{stagingRecord->id}/{safe_filename}
```

#### STEP 4b — POST ke EcoSystem API (kondisional)

Hanya dijalankan jika **email berhasil** dikirim. EcoSystem membutuhkan `internet_message_id` untuk `linkStagingToEmail()` — yaitu proses fetch body dan attachment dari M365 Sent Items.

```
POST {ECOSYSTEM_URL}/jarvies/staging-tickets
Content-Type: multipart/form-data
X-Api-Key: {ECOSYSTEM_API_KEY}

Fields:
  description, customer_id, submitted_by_email, body (HTML),
  internet_message_id, sender_name, ticket_priority, channel=email,
  end_customer_id (opsional), cc_emails (JSON), name, no_hp, module,
  client, contact_id (opsional)

Note: File attachment TIDAK dikirim ke EcoSystem —
      EcoSystem fetch langsung dari Graph API via internet_message_id.
```

Jika EcoSystem gagal → hanya dicatat di log, tidak rollback (non-critical).

#### Response

```json
// Email berhasil
{
  "success": true,
  "staging": true,
  "email_sent": true,
  "message": "Your ticket has been submitted and is awaiting admin validation."
}

// Email gagal (fallback web-channel)
{
  "success": true,
  "staging": true,
  "email_sent": false,
  "message": "Your ticket has been submitted and is awaiting admin validation."
}
```

---

## Alur Admin via Web Portal

**Endpoint:** `POST /tickets` (sama, dibedakan oleh `role_id == 1`)
**Controller:** `TicketController@store` ([app/Http/Controllers/TicketController.php](app/Http/Controllers/TicketController.php#L717))

Admin **bypass staging** — tiket langsung masuk tabel `ticket`.

### Validasi

```
description      required|string
ticket_priority  required|in:Very High,High,Medium,Low
customer_id      required|exists:customer,customer_id
body             nullable|string
```

### Proses

1. `Ticket::create($validated)` dengan `status = 'open'`, `channel = 'web'`
2. Jika ada `body`, buat `TicketMessage` pertama:
   - `sender_type = 'employee'`
   - `is_internal_note = false`
   - Update `ticket.last_message_at = now()`
3. Return `{ success: true, staging: false, data: ticket }`

---

## Alur Mobile API (Flutter)

Ada **dua endpoint** untuk customer mobile:

### Endpoint A — Simple (tanpa email)

**Endpoint:** `POST /api/tickets`
**Controller:** `Api\TicketController@store` ([app/Http/Controllers/Api/TicketController.php](app/Http/Controllers/Api/TicketController.php#L92))

```
description      required|string
ticket_priority  nullable|in:Low,Medium,High
body             nullable|string
```

**Proses:**
1. Panggil `StagingTicketService::createFromWeb()` secara langsung
2. Tidak ada pengiriman email, tidak ada POST ke EcoSystem
3. Channel = `web`

**Response:**
```json
{
  "success": true,
  "message": "Ticket submitted successfully and is awaiting admin validation.",
  "data": {
    "id": 42,
    "staging_ref": "STG-42",
    "description": "...",
    "ticket_priority": "Medium",
    "status": "unvalidated",
    "status_label": "Pending Validation",
    "created_at": "2026-06-09T..."
  }
}
```

### Endpoint B — Dengan Email (testing Postman)

**Endpoint:** `POST /api/tickets/submit-with-email`
**Controller:** `Api\TicketController@storeWithEmail` ([app/Http/Controllers/Api/TicketController.php](app/Http/Controllers/Api/TicketController.php#L155))

Alur identik dengan Web Portal customer (STEP 1–4), ditambah field:
```
body_html        nullable|string
cc_emails        nullable|array|max:10
attachments      nullable|array (file|max:20480)
```

Response menyertakan `debug_eco` (status HTTP + body dari EcoSystem API call).

---

## StagingTicketService — Detail Implementasi

**File:** [app/Services/StagingTicketService.php](app/Services/StagingTicketService.php)

### `createFromWeb(array $data, int $customerId, ...)`

#### Langkah 1 — Insert awal tanpa body

```php
$staging = StagingTicket::create([
    'customer_id'        => $customerId,
    'description'        => $data['description'],
    'body'               => null,          // diisi setelah proses gambar
    'ticket_priority'    => $data['ticket_priority'] ?? 'Medium',
    'status'             => 'unvalidated',
    'channel'            => 'web',         // atau 'email'
    'submitted_by_email' => $customerEmail,
    'email_thread_id'    => $data['email_thread_id'] ?? null,
    'email_message_id'   => $data['email_message_id'] ?? null,
    'attachment_names'   => json_encode($fileNames) ?? null,
    // + cc_emails, name, no_hp, module, client, end_customer_id, sender_name
]);
```

#### Langkah 2 — Ekstrak inline images dari body HTML

Method `extractAndSaveImages()` dipanggil setelah staging ID tersedia:

```php
private function extractAndSaveImages(?string $html, int $stagingId): array
```

- Mencari semua `src="data:{mime};base64,{data}"` via regex
- Menyimpan tiap gambar ke `storage/app/staging-images/{stagingId}/{uuid}.{ext}`
- Mengganti `src` di HTML dengan URL route: `route('tickets.staging.image.serve', ...)`
- Mencegah menyimpan base64 besar langsung ke DB (menghindari `max_allowed_packet` MySQL)

#### Langkah 3 — Update body

```php
$staging->body = $result['html'];  // HTML dengan URL gambar (bukan base64)
$staging->save();
```

---

## Model & Tabel

### `StagingTicket` — Tabel `staging_tickets`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | PK | Auto increment |
| `customer_id` | FK | Selalu parent customer (Sinergi) |
| `end_customer_id` | FK nullable | End customer jika parent pilih |
| `description` | string | Judul tiket (= email subject tanpa prefix) |
| `body` | text nullable | HTML body (inline images dikonversi ke URL) |
| `ticket_priority` | enum | Low / Medium / High / Very High |
| `status` | enum | `unvalidated` / `approved` / `rejected` |
| `channel` | string | `email` atau `web` |
| `email_thread_id` | string | M365 Conversation ID |
| `email_message_id` | string | Internet Message ID dari Graph |
| `submitted_by_email` | string | Email customer yang submit |
| `sender_name` | string | Nama customer |
| `validated_by` | FK nullable | Admin yang approve/reject |
| `validated_at` | datetime | Waktu approve/reject |
| `ticket_id` | FK nullable | Diisi setelah approved → link ke `ticket` |
| `attachment_names` | JSON | Nama-nama file attachment |

### `Ticket` — Tabel `ticket`

| Field | Tipe | Keterangan |
|---|---|---|
| `ticket_id` | PK | Auto increment |
| `ticket_number` | string | Nomor tiket (auto-generate) |
| `customer_id` | FK | Customer pemilik tiket |
| `end_customer_id` | FK nullable | End customer informatif |
| `employee_id` | FK nullable | PIC (diisi saat employee take) |
| `status` | string | open / in process / hold / closed / cancelled / dll |
| `ticket_priority` | string | Low / Medium / High / Very High |
| `channel` | string | email / web / mobile |
| `email_thread_id` | string | M365 Conversation ID untuk threading |
| `cc_emails` | JSON | Daftar CC email (cast ke array) |
| `last_message_at` | datetime | Timestamp pesan terakhir |
| `submitted_by_email` | string | Email submitter |

---

## Integrasi EcoSystem

EcoSystem adalah sistem sisi employee/admin (terpisah dari JARVIES). Integrasi terjadi di dua titik:

### 1. Saat Customer Submit Tiket

JARVIES POST ke EcoSystem endpoint:
```
POST {ECOSYSTEM_URL}/jarvies/staging-tickets
```
EcoSystem kemudian:
- Menyimpan staging ticket di sisinya
- Memanggil `linkStagingToEmail()` — fetch body + attachment dari M365 menggunakan `internet_message_id`
- Menampilkan staging ke admin untuk divalidasi

### 2. Saat Admin Approve (di EcoSystem)

- EcoSystem membuat record di tabel `ticket` (di database JARVIES, shared DB)
- Mengupdate `staging_tickets.status = 'approved'`, `staging_tickets.ticket_id = {new_ticket_id}`
- JARVIES menampilkan status ini ke customer via `GET /tickets/staging` atau `GET /api/tickets/staging`

---

## Error Handling & Fallback

| Skenario | Perilaku |
|---|---|
| Graph API throw exception | Log warning, lanjut ke STEP 4a dengan `channel='web'` |
| Graph API return null | Log warning, staging dibuat tanpa email threading |
| MySQL timeout setelah Graph | `DB::reconnect()` sebelum `StagingTicketService::createFromWeb()` |
| EcoSystem API gagal | Log warning, tidak rollback — staging tetap tersimpan di JARVIES DB |
| Customer tidak punya email | Return 422 sebelum proses apapun |
| Parent customer tidak pilih end customer | Return 422 validasi |

**Prinsip:** Pembuatan staging ticket di JARVIES DB (STEP 4a) adalah **operasi utama** yang harus berhasil. EcoSystem call (STEP 4b) adalah operasi sekunder — kegagalannya tidak memblokir response ke customer.
