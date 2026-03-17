# Panduan Perubahan: CC Email & Priority Baru — EcoSystem Adjustments

> **Tanggal:** 2026-03-10
> **Versi JARVIES:** update CC + Very High priority + Web-to-Email Relay flow

---

## Ringkasan Perubahan di JARVIES

1. **Fitur CC pada Create Ticket** — customer dapat menambahkan CC saat membuat tiket baru
2. **Priority baru: Very High** — urutan sekarang: `Very High | High | Medium | Low`
3. **Submit ticket non-OAuth → API EcoSystem** — JARVIES kini memanggil `POST /api/staging-tickets` ke EcoSystem (bukan langsung tulis ke DB lokal), agar relay email berjalan otomatis

---

## 1. Fitur CC Email

### Cara Kerja di JARVIES

Customer mengisi field CC (tag input multi-email) saat membuat tiket baru.

**Jalur OAuth (ada email terhubung):**
- CC disertakan di header email yang dikirim:
  - Gmail → header `Cc: email1@x.com, email2@y.com`
  - Microsoft Graph → field `ccRecipients`
- Penerima CC menerima email langsung dari akun customer
- CC bisa membalas email (dan masuk ke thread helpdesk seperti biasa)

**Jalur non-OAuth (tanpa email terhubung):**
- JARVIES memanggil `POST /api/staging-tickets` ke EcoSystem dengan field `cc_emails` (JSON string)
- EcoSystem menyimpan ke `staging_tickets.cc_emails` dan meneruskan CC saat approve → kirim email approval

---

### Perubahan Database

#### Tabel `staging_tickets` (JARVIES migration)

```sql
ALTER TABLE staging_tickets
    ADD COLUMN cc_emails TEXT NULL AFTER body;
```

> **File migration JARVIES:** `2026_03_09_000001_add_cc_emails_to_staging_tickets.php`

**Nilai `cc_emails`:**
- `NULL` — tidak ada CC
- `["a@example.com","b@example.com"]` — JSON array email CC

---

### Yang Harus Dilakukan EcoSystem

#### A. Saat approve staging ticket

Baca `staging_tickets.cc_emails` dan simpan ke `ticket_message.cc_emails` pada pesan pertama:

```php
// Contoh di EcoSystem StagingTicketController@approve()
$ccEmails = $staging->cc_emails; // string JSON atau null

TicketMessage::create([
    'ticket_id'      => $newTicket->ticket_id,
    'sender_type'    => 'customer',
    'sender_id'      => $staging->customer_id,
    'sender_email'   => $staging->submitted_by_email,
    'sender_name'    => $staging->sender_name,
    'message'        => $staging->body,
    'cc_emails'      => $ccEmails, // <-- simpan di sini
    'channel'        => 'web',
    'is_internal_note' => false,
]);
```

#### B. Saat kirim email balasan (sendTicketReply)

Jika ada `cc_emails` di tiket/pesan, sertakan dalam email balasan:

```php
// Di EmailController@sendTicketReply() atau equivalent

// Ambil CC dari pesan pertama atau dari request
$ccEmails = json_decode($firstMessage->cc_emails ?? '[]', true);

// Untuk Microsoft Graph (createReply atau sendMail):
if (!empty($ccEmails)) {
    $message['ccRecipients'] = array_map(
        fn($email) => ['emailAddress' => ['address' => $email]],
        $ccEmails
    );
}
```

#### C. Proses inbox email masuk (processInbox)

Saat email masuk dengan CC, Graph API mengembalikan:

```json
{
  "ccRecipients": [
    {"emailAddress": {"name": "...", "address": "cc@example.com"}}
  ]
}
```

Simpan ke `ticket_message.cc_emails`:

```php
$ccEmails = collect($emailData['ccRecipients'] ?? [])
    ->map(fn($r) => ['name' => $r['emailAddress']['name'] ?? null, 'address' => $r['emailAddress']['address']])
    ->toArray();

// Simpan sebagai JSON string di ticket_message.cc_emails
'cc_emails' => !empty($ccEmails) ? json_encode($ccEmails) : null,
```

> `ticket_message.cc_emails` sudah ada di DB dan sudah di-return oleh JARVIES `getMessages()` API.
> JARVIES show.blade.php sudah merender CC dari field ini (mendukung string maupun JSON array).

---

## 2. Priority: Tambah "Very High"

### Nilai Priority Baru (urutan turun)

| Nilai | Keterangan |
|---|---|
| `Very High` | Sangat mendesak |
| `High` | Mendesak |
| `Medium` | Normal (default) |
| `Low` | Tidak mendesak |

### Yang Harus Dilakukan EcoSystem

#### A. Validasi di semua endpoint yang menerima `ticket_priority`

Ubah dari:
```php
'ticket_priority' => 'in:Low,Medium,High'
```
Menjadi:
```php
'ticket_priority' => 'in:Very High,High,Medium,Low'
```

File-file EcoSystem yang kemungkinan perlu diupdate:
- `StagingTicketController.php` — approve/create ticket dari staging
- `TicketController.php` — store/update ticket
- `ApiTicketController.php` — external API create ticket
- Setiap form/modal di EcoSystem yang menampilkan pilihan priority

#### B. Tampilan badge priority di EcoSystem

Tambahkan styling untuk `Very High`:

```php
// Contoh helper badge
$priorityColors = [
    'Very High' => 'bg-red-100 text-red-700 border-red-300',
    'High'      => 'bg-orange-100 text-orange-700 border-orange-300',
    'Medium'    => 'bg-blue-100 text-blue-700 border-blue-300',
    'Low'       => 'bg-green-100 text-green-700 border-green-300',
];
```

#### C. Database

Kolom `ticket_priority` di tabel `ticket` dan `staging_tickets` biasanya `varchar` — tidak perlu migrasi, `Very High` cukup disimpan sebagai string.

Jika ada `ENUM` di DB, tambahkan nilai:
```sql
ALTER TABLE ticket
    MODIFY COLUMN ticket_priority ENUM('Very High','High','Medium','Low') DEFAULT 'Medium';

ALTER TABLE staging_tickets
    MODIFY COLUMN ticket_priority ENUM('Very High','High','Medium','Low') DEFAULT 'Medium';
```

---

## Ringkasan File EcoSystem yang Perlu Diubah

| File / Area | Perubahan |
|---|---|
| **`StagingTicketController@store()`** | Endpoint `POST /api/staging-tickets` — terima field dari JARVIES (lihat checklist) |
| Validasi `ticket_priority` di semua endpoint | Tambah `Very High` ke daftar `in:` |
| Badge/label priority di view EcoSystem | Tambah style untuk `Very High` |
| `StagingTicketController@approve()` | Salin `staging.cc_emails` ke `ticket_message.cc_emails` + sertakan CC saat kirim email approval |
| `EmailController@sendTicketReply()` | Sertakan `ccRecipients` dari `cc_emails` jika ada |
| `EmailController@processInbox()` | Simpan `ccRecipients` dari Graph ke `ticket_message.cc_emails` |
| DB `ticket` (jika ENUM) | Tambah `Very High` |
| DB `staging_tickets` (jika ENUM) | Tambah `Very High` |

---

## 3. Submit Ticket Non-OAuth → API EcoSystem

### Endpoint yang Dipanggil JARVIES

```http
POST {ECOSYSTEM_API_URL}/staging-tickets
Content-Type: application/json
X-Api-Key: {EXTERNAL_TICKET_API_KEY}
```

### Payload dari JARVIES

```json
{
  "description":        "Judul / subject tiket",
  "body":               "<p>Detail masalah...</p>",
  "ticket_priority":    "Medium",
  "sender_name":        "Nama Customer",
  "submitted_by_email": "customer@perusahaan.com",
  "cc_emails":          "[\"cc1@perusahaan.com\",\"cc2@vendor.com\"]",
  "customer_id":        42
}
```

**Catatan penting:**
- `submitted_by_email` — email login customer, dipakai untuk kirim notifikasi approval
- `cc_emails` — JSON string (bukan array), bisa NULL jika tidak ada CC
- `body` — HTML (sudah di-convert JARVIES dari plain text ke `<p>...</p>`)
- Jika API gagal, JARVIES fallback ke penyimpanan lokal staging (tanpa relay email)

### Yang Harus Diimplementasikan EcoSystem di `StagingTicketController@store()`

```php
// Validasi
$validated = $request->validate([
    'description'        => 'required|string',
    'body'               => 'nullable|string',
    'ticket_priority'    => 'nullable|in:Very High,High,Medium,Low',
    'sender_name'        => 'nullable|string',
    'submitted_by_email' => 'nullable|email',
    'cc_emails'          => 'nullable|string',  // JSON array string
    'customer_id'        => 'nullable|integer|exists:customer,customer_id',
]);

// Simpan ke staging_tickets
StagingTicket::create([
    'customer_id'        => $validated['customer_id'],
    'description'        => $validated['description'],
    'body'               => $validated['body'],
    'ticket_priority'    => $validated['ticket_priority'] ?? 'Medium',
    'status'             => 'unvalidated',
    'channel'            => 'web',
    'submitted_by_email' => $validated['submitted_by_email'],
    'sender_name'        => $validated['sender_name'],
    'cc_emails'          => $validated['cc_emails'],
]);
```

---

## Catatan Kompatibilitas

- Data tiket lama dengan `ticket_priority = 'High'` tetap valid — tidak ada breaking change
- `cc_emails` di `ticket_message` sudah ada di DB dan sudah di-return oleh JARVIES API — tidak ada perubahan schema dari JARVIES untuk tabel ini
- JARVIES `getMessages()` sudah include `cc_emails` dalam response
- JARVIES `show.blade.php` sudah render CC untuk semua pesan (dari processInbox maupun dari reply)
