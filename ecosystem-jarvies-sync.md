# EcoSystem ← JARVIES: Panduan Implementasi Sinkronisasi

> **Dibuat:** 2026-04-07  
> **Konteks:** JARVIES (customer portal) dan EcoSystem berbagi **database yang sama** (`ecosystem`).  
> Dokumen ini menjelaskan **apa yang sudah berjalan di JARVIES** dan **apa yang harus diimplementasikan di EcoSystem** agar alur end-to-end bekerja dengan benar.

---

## Daftar Isi

1. [Gambaran Arsitektur](#1-gambaran-arsitektur)
2. [Alur Lengkap Submit Ticket](#2-alur-lengkap-submit-ticket)
3. [Status Ticket: Penjelasan Lengkap](#3-status-ticket-penjelasan-lengkap)
4. [Yang Sudah Diimplementasikan di JARVIES](#4-yang-sudah-diimplementasikan-di-jarvies)
5. [Yang Wajib Diimplementasikan di EcoSystem](#5-yang-wajib-diimplementasikan-di-ecosystem)
6. [Kontrak API: JARVIES → EcoSystem](#6-kontrak-api-jarvies--ecosystem)
7. [Referensi Struktur Database](#7-referensi-struktur-database)
8. [Checklist Implementasi](#8-checklist-implementasi)

---

## 1. Gambaran Arsitektur

```
┌─────────────────────────────┐        ┌─────────────────────────────┐
│          JARVIES            │        │         EcoSystem           │
│     (Customer Portal)       │        │     (Employee / Admin)      │
├─────────────────────────────┤        ├─────────────────────────────┤
│ Customer buat ticket        │──API──▶│ Terima → simpan ke          │
│                             │        │ staging_tickets             │
│ Customer lihat ticket list: │        │                             │
│  - Status "Initial"         │◀──DB───│ Admin review staging        │
│    = belum divalidasi        │        │ Admin approve staging       │
│  - Status lain              │        │  → buat ticket              │
│    = sudah jadi ticket      │        │  → buat ticket_message      │
└─────────────────────────────┘        └─────────────────────────────┘
              │                                      │
              └──────────── Database Bersama ────────┘
                           (ecosystem DB)
```

**Penting:** JARVIES dan EcoSystem membaca dari database yang sama. Tidak ada sync API
untuk membaca data — hanya untuk menulis (JARVIES kirim staging ke EcoSystem via API).

---

## 2. Alur Lengkap Submit Ticket

### 2.1 Diagram Alur

```
Customer isi form ticket di JARVIES
              │
              ▼
    Apakah customer punya OAuth Email terhubung?
              │
    ┌─────────┴──────────┐
    │ YA                 │ TIDAK
    ▼                    ▼
Kirim email ke       POST ke EcoSystem API
MS_SENDER_EMAIL      /api/staging-tickets
(via Graph API)          │
    │                    │
    ▼                    ▼
processInbox()      EcoSystem simpan ke
buat staging        staging_tickets
(EcoSystem)         status: 'unvalidated'
    │                    │
    └────────┬───────────┘
             ▼
    staging_tickets.status = 'unvalidated'
             │
             │  ← Admin EcoSystem melihat daftar staging
             │  ← Admin klik "Approve"
             ▼
    ┌─────────────────────────────────┐
    │ EcoSystem: Proses Approve       │
    │                                 │
    │ 1. Buat record di tabel ticket  │
    │ 2. Buat ticket_message pertama  │  ← ⚠️ HARUS DIIMPLEMENTASIKAN
    │    dari staging.body            │
    │ 3. Salin email_thread_id ke     │  ← ⚠️ HARUS DIIMPLEMENTASIKAN
    │    ticket                       │
    │ 4. Salin cc_emails ke ticket    │  ← ⚠️ HARUS DIIMPLEMENTASIKAN
    │ 5. Update staging.status        │
    │    = 'approved'                 │
    │ 6. Isi staging.ticket_id        │
    │    = ticket.ticket_id yang baru │
    └─────────────────────────────────┘
             │
             ▼
    JARVIES customer buka halaman tickets:
    - Staging hilang dari list (status bukan 'unvalidated' lagi)
    - Ticket baru muncul dengan status sesuai (misal: 'in process')
```

### 2.2 Kondisi Fallback JARVIES

Jika **EcoSystem API tidak bisa diakses** (timeout / error), JARVIES otomatis menyimpan
staging langsung ke database lokal (tabel `staging_tickets`) sebagai fallback. Hasilnya
sama — data masuk ke database yang sama, hanya melewati API.

---

## 3. Status Ticket: Penjelasan Lengkap

> ⚠️ **INI BAGIAN PALING PENTING** — Pahami perbedaan "Initial" vs status lainnya.

### 3.1 Dua Sumber Data di Halaman Ticket JARVIES

Halaman daftar ticket customer di JARVIES menampilkan **dua jenis data** sekaligus:

| Sumber Data | Tabel | Kondisi | Status yang Ditampilkan |
|---|---|---|---|
| Staging (belum divalidasi) | `staging_tickets` | `status = 'unvalidated'` | **"Initial"** (abu-abu) |
| Ticket nyata | `tickets` | milik customer ini | sesuai `tickets.jarvies_status` |

### 3.2 Status "Initial" — Dari Staging, Bukan dari Tabel Ticket

**"Initial" BUKAN field di tabel `tickets`.** Status ini adalah label UI yang muncul saat
tiket customer masih ada di `staging_tickets` dengan `status = 'unvalidated'`.

Begitu EcoSystem approve staging → buat ticket, staging dihapus dari list (karena
`status` berubah dari `unvalidated` menjadi `approved`), dan ticket baru muncul
di list dengan status sesuai field `tickets.jarvies_status`.

```
staging_tickets.status = 'unvalidated'  →  tampil di JARVIES sebagai "Initial"
staging_tickets.status = 'approved'     →  hilang dari list "Initial"
                                            (ticket baru muncul dengan status aslinya)
```

### 3.3 Field `jarvies_status` di Tabel `tickets`

Ini adalah status yang ditampilkan di JARVIES untuk ticket yang sudah divalidasi:

| Nilai `jarvies_status` | Label di JARVIES | Warna Badge |
|---|---|---|
| `in process` | In Process | Biru |
| `author action` | Author Action | Amber/Kuning |
| `proposed solution` | Proposed Solution | Ungu |
| `sent in to SAP` | Sent in to SAP | Indigo |
| `closed` | Closed | Hijau |

> Field `jarvies_status` dikelola sepenuhnya oleh EcoSystem. JARVIES hanya membacanya.

### 3.4 Diagram Status Timeline dari Perspektif Customer

```
[Customer Submit Ticket]
        │
        ▼
  ┌──────────┐
  │ Initial  │  ← Muncul di JARVIES, data dari staging_tickets
  │ (abu2)   │     status = 'unvalidated'
  └────┬─────┘
       │  Admin EcoSystem approve
       ▼
  ┌──────────────┐
  │  In Process  │  ← Ticket sudah ada, jarvies_status = 'in process'
  │  (biru)      │
  └──────────────┘
       │ (status berubah sesuai progress EcoSystem)
       ▼
  ┌──────────────────┐   ┌──────────────┐   ┌──────────────┐
  │  Author Action   │   │  Proposed    │   │ Sent to SAP  │
  │  (amber)         │   │  Solution    │   │ (indigo)     │
  └──────────────────┘   └──────────────┘   └──────────────┘
       │
       ▼
  ┌──────────┐
  │  Closed  │
  │  (hijau) │
  └──────────┘
```

---

## 4. Yang Sudah Diimplementasikan di JARVIES

> Bagian ini untuk referensi — tidak perlu diubah di JARVIES.

### 4.1 `TicketController@store` — Saat Customer Submit Ticket

**Path: customer (role = 3)**

```
Customer submit form
    │
    ├─ Punya OAuth email? → Kirim email via Graph API ke MS_SENDER_EMAIL
    │   (processInbox EcoSystem akan buat staging dari email masuk)
    │
    └─ Tidak punya OAuth → POST /api/staging-tickets ke EcoSystem
        dengan payload JSON berikut:
        {
            description, body (HTML), ticket_priority,
            sender_name, submitted_by_email, cc_emails (JSON string),
            customer_id, contact_id, name, no_hp, module, client
        }
        Header: X-Api-Key: {ECOSYSTEM_API_KEY}
```

Response dari EcoSystem yang diharapkan:
- `HTTP 201` → tampilkan pesan sukses ke customer, staging akan muncul di list
- `HTTP 4xx/5xx` → fallback: simpan langsung ke `staging_tickets` via DB

### 4.2 `TicketController@getTickets` — Saat Customer Buka Halaman Ticket

```php
// Untuk customer (role 3), JARVIES melakukan dua query:

// Query 1: Ticket yang sudah divalidasi
$tickets = Ticket::where('customer_id', $sessionUser['id'])->get();

// Query 2: Staging yang belum divalidasi
$stagingTickets = StagingTicket::where('customer_id', $sessionUser['id'])
    ->where('status', 'unvalidated')  // ← hanya yang belum divalidasi
    ->orderByDesc('created_at')
    ->get();

// Staging dimap ke format sama dengan ticket, tapi dengan:
//   is_staging = true
//   jarvies_status = 'initial'
//   ticket_id = null

// Keduanya digabung, staging ditaruh di ATAS list
$result = $stagingData->concat($ticketsData);
```

**Implikasinya untuk EcoSystem:** Begitu staging di-approve dan `status` diubah ke
`'approved'`, staging otomatis hilang dari list JARVIES (query filter hanya
`status = 'unvalidated'`). Ticket baru langsung muncul di list.

### 4.3 Tampilan di `index.blade.php`

Untuk staging ticket (is_staging = true):   
- Badge status: **"Initial"** dengan warna abu-abu (`bg-gray-100 text-gray-500`)
- Sub-label: *"Awaiting validation"* (italic, abu-abu)
- Avatar: abu-abu (bukan merah)
- Klik card → redirect ke `/tickets/pending` (halaman info staging)
- Card sedikit transparan (`opacity-80`)

Untuk ticket biasa (is_staging = false):
- Badge status: sesuai `jarvies_status` dengan warna masing-masing
- Klik card → redirect ke `/tickets/{ticket_id}`

---

## 5. Yang Wajib Diimplementasikan di EcoSystem

### 5.1 Endpoint: Terima Staging dari JARVIES

**`POST /api/staging-tickets`**

EcoSystem harus memiliki endpoint ini yang:
1. Memvalidasi `X-Api-Key` dari header
2. Menerima payload JSON dari JARVIES
3. Menyimpan ke tabel `staging_tickets` dengan `status = 'unvalidated'`

```php
// StagingTicketController@store (EcoSystem)

public function store(Request $request)
{
    // Validasi API key
    if ($request->header('X-Api-Key') !== config('services.jarvies.api_key')) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $validated = $request->validate([
        'description'        => 'required|string',
        'body'               => 'nullable|string',       // HTML dari Quill
        'ticket_priority'    => 'nullable|in:Very High,High,Medium,Low',
        'sender_name'        => 'nullable|string|max:255',
        'submitted_by_email' => 'nullable|email',
        'cc_emails'          => 'nullable|string',       // JSON string: '["a@b.com"]'
        'customer_id'        => 'required|integer',
        'contact_id'         => 'nullable|integer',
        'name'               => 'nullable|string|max:255',
        'no_hp'              => 'nullable|string|max:255',
        'module'             => 'nullable|string|max:255',
        'client'             => 'nullable|string|max:255',
    ]);

    $staging = StagingTicket::create([
        ...$validated,
        'status'  => 'unvalidated',
        'channel' => 'web',
    ]);

    return response()->json([
        'success' => true,
        'id'      => $staging->id,
        'message' => 'Staging ticket created successfully',
    ], 201);
}
```

**Tambahkan ke `.env` EcoSystem:**
```env
JARVIES_API_KEY=<samakan dengan ECOSYSTEM_API_KEY di JARVIES .env>
```

**Tambahkan ke `config/services.php` EcoSystem:**
```php
'jarvies' => [
    'api_key' => env('JARVIES_API_KEY'),
],
```

---

### 5.2 Proses Approve Staging → Buat Ticket + Pesan Pertama

> ⚠️ **Ini yang paling kritis.** Tanpa ini, chat history ticket kosong meski customer
> sudah mengisi form detail lengkap.

Temukan method di EcoSystem yang menangani approve staging (kemungkinan di
`StagingTicketController@approve` atau service terkait), lalu tambahkan logika berikut:

```php
// EcoSystem — Method approve staging ticket

public function approve(Request $request, $stagingId)
{
    $staging = StagingTicket::findOrFail($stagingId);

    // ── 1. Buat ticket ────────────────────────────────────────────
    $ticket = Ticket::create([
        'customer_id'      => $staging->customer_id,
        'description'      => $staging->description,
        'ticket_priority'  => $staging->ticket_priority ?? 'Medium',
        'jarvies_status'   => 'in process',     // status awal ticket
        'status'           => 'open',
        'channel'          => $staging->channel ?? 'web',
        // ... field lain sesuai implementasi EcoSystem
    ]);

    // ── 2. Buat ticket_message PERTAMA dari staging.body ─────────
    //    JANGAN LEWATI ini — ini adalah pesan awal customer
    if (!empty($staging->body)) {
        TicketMessage::create([
            'ticket_id'           => $ticket->ticket_id,
            'sender_type'         => 'customer',
            'sender_id'           => $staging->customer_id,
            'sender_name'         => $staging->sender_name ?? 'Customer',
            'sender_email'        => $staging->submitted_by_email ?? null,
            'message'             => strip_tags($staging->body), // plain text
            'message_html'        => $staging->body,             // HTML asli dari Quill
            'channel'             => $staging->channel ?? 'web',
            'is_internal_note'    => false,
            'is_read_by_customer' => true,
            'is_read_by_agent'    => false,
        ]);

        // Update last_message_at agar urutan di list benar
        $ticket->update(['last_message_at' => $staging->created_at]);
    }

    // ── 3. Salin email_thread_id ──────────────────────────────────
    //    Agar reply email customer berikutnya masuk ke thread yang benar
    if (!empty($staging->email_thread_id)) {
        $ticket->update(['email_thread_id' => $staging->email_thread_id]);
    }

    // ── 4. Salin cc_emails ────────────────────────────────────────
    //    Agar CC customer ikut menerima email balasan dari helpdesk
    if (!empty($staging->cc_emails)) {
        $ticket->update(['cc_emails' => $staging->cc_emails]);
        // cc_emails adalah JSON string: '["a@b.com","c@d.com"]'
        // Parse saat kirim email: json_decode($ticket->cc_emails, true)
    }

    // ── 5. Update staging: tandai approved ───────────────────────
    $staging->update([
        'status'       => 'approved',
        'ticket_id'    => $ticket->ticket_id,
        'validated_by' => auth()->id(),     // atau ID admin yang approve
        'validated_at' => now(),
    ]);

    // ── 6. (Opsional) Kirim notifikasi ke customer ────────────────
    // ...

    return response()->json(['success' => true, 'ticket_id' => $ticket->ticket_id]);
}
```

### 5.3 Cek Kolom `cc_emails` di Tabel `ticket`

Pastikan kolom `cc_emails` sudah ada di tabel `ticket`. Jika belum, buat migration:

```php
// Migration di EcoSystem

Schema::table('ticket', function (Blueprint $table) {
    $table->text('cc_emails')->nullable()->after('email_thread_id');
    // cc_emails disimpan sebagai JSON string: '["a@b.com","c@d.com"]'
});
```

Cara menggunakannya saat kirim email balasan:

```php
$ccEmails = json_decode($ticket->cc_emails ?? '[]', true);
// $ccEmails = ['a@b.com', 'c@d.com']
// Teruskan ke fungsi kirim email sebagai parameter CC
```

### 5.4 Cek Kolom `body` di Tabel `staging_tickets`

Pastikan kolom `body` sudah ada di tabel `staging_tickets`. Jika belum:

```php
Schema::table('staging_tickets', function (Blueprint $table) {
    $table->text('body')->nullable()->after('description');
});
```

> Di JARVIES, kolom ini sudah ditambahkan via migration. Pastikan EcoSystem
> juga sudah menjalankan migration yang sama.

---

## 6. Kontrak API: JARVIES → EcoSystem

### Request dari JARVIES ke EcoSystem

```
POST {ECOSYSTEM_API_URL}/staging-tickets
Headers:
  X-Api-Key:    {ECOSYSTEM_API_KEY}     ← dari .env JARVIES
  Content-Type: application/json
  Accept:       application/json

Body (JSON):
{
  "description":        "Error saat membuka modul SAP FI",
  "body":               "<p>Detail masalah dalam <strong>HTML</strong>...</p>",
  "ticket_priority":    "High",
  "sender_name":        "Budi Santoso",
  "submitted_by_email": "budi@contoso.com",
  "cc_emails":          "[\"manajer@contoso.com\",\"it@contoso.com\"]",
  "customer_id":        37,
  "contact_id":         12,
  "name":               "Budi Santoso",
  "no_hp":              "08123456789",
  "module":             "SAP FI",
  "client":             "PT Indo Raya"
}
```

**Catatan field `body`:**
- Format: HTML dari Quill editor
- Bisa berisi tag: `<p>`, `<strong>`, `<em>`, `<ol>`, `<ul>`, `<li>`, `<img>`
- Inline image dalam body sudah di-replace ke `cid:xxx` (jika via OAuth email)
- Jika via web form tanpa OAuth: HTML biasa, tidak ada `cid:`

**Catatan field `cc_emails`:**
- Adalah JSON string (bukan array), contoh: `"[\"a@b.com\",\"b@c.com\"]"`
- Parse di EcoSystem: `json_decode($cc_emails, true)`
- Bisa `null` jika customer tidak mengisi CC

### Response yang Diharapkan

**Sukses (201):**
```json
{
  "success": true,
  "id": 42
}
```

**Error validasi (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "description": ["The description field is required."] }
}
```

**Error auth (401):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

> Jika EcoSystem mengembalikan **4xx/5xx**, JARVIES akan **fallback ke local staging**
> (simpan langsung ke database). Data tidak hilang, tapi tidak melewati API EcoSystem.

---

## 7. Referensi Struktur Database

### Tabel `staging_tickets` — Field yang Digunakan

```sql
id                   INT (PK, auto increment)
customer_id          INT                 -- FK ke customer.customer_id
description          VARCHAR             -- Subject/judul ticket
body                 TEXT NULL           -- HTML dari Quill editor  ← PENTING
cc_emails            TEXT NULL           -- JSON: '["a@b.com"]'
ticket_priority      VARCHAR             -- Very High/High/Medium/Low
status               VARCHAR             -- 'unvalidated' | 'approved' | 'rejected'
email_thread_id      VARCHAR NULL        -- Thread ID email OAuth
submitted_by_email   VARCHAR NULL        -- Email pengirim
sender_name          VARCHAR NULL        -- Nama pengirim
channel              VARCHAR             -- 'web' | 'email'
contact_id           INT NULL
name                 VARCHAR NULL        -- Nama dari form
no_hp                VARCHAR NULL        -- No HP dari form
module               VARCHAR NULL        -- Modul terkait
client               VARCHAR NULL        -- Nama klien
ticket_id            INT NULL            -- Diisi setelah approved
validated_by         INT NULL            -- ID admin yang approve
validated_at         DATETIME NULL
created_at           TIMESTAMP
updated_at           TIMESTAMP
```

### Tabel `ticket_message` — Field yang Diisi saat Approve Staging

```sql
ticket_id            INT       -- FK ke ticket.ticket_id
sender_type          VARCHAR   -- 'customer'
sender_id            INT       -- customer_id
sender_name          VARCHAR   -- staging.sender_name
sender_email         VARCHAR   -- staging.submitted_by_email
message              TEXT      -- strip_tags(staging.body)   ← plain text
message_html         TEXT      -- staging.body               ← HTML asli
channel              VARCHAR   -- staging.channel ('web'|'email')
is_internal_note     BOOLEAN   -- false  (bukan catatan internal)
is_read_by_customer  BOOLEAN   -- true   (customer yang nulis, sudah "baca")
is_read_by_agent     BOOLEAN   -- false  (agent belum baca)
created_at           TIMESTAMP
```

### Tabel `ticket` — Field Tambahan yang Perlu Dicek

```sql
email_thread_id      VARCHAR NULL   -- Thread ID email (untuk reply threading)
cc_emails            TEXT NULL      -- JSON: '["a@b.com"]'  ← PASTIKAN ADA
jarvies_status       VARCHAR        -- Status yang ditampilkan di JARVIES
```

---

## 8. Checklist Implementasi

### Prioritas Tinggi (Harus Selesai Sebelum Go-Live)

```
[ ] A. Pastikan kolom staging_tickets.body ada (TEXT NULL)
        → Jalankan migration jika belum ada

[ ] B. Endpoint POST /api/staging-tickets bisa menerima dan menyimpan payload
        dari JARVIES ke staging_tickets dengan status = 'unvalidated'

[ ] C. Validasi X-Api-Key pada endpoint tersebut (tolak request tanpa key valid)

[ ] D. Saat approve staging → buat ticket_message PERTAMA dari staging.body
        (jangan biarkan history chat kosong)

[ ] E. Saat approve staging → ubah staging.status = 'approved'
        dan isi staging.ticket_id = ticket.ticket_id yang baru dibuat
        (agar staging hilang dari list "Initial" di JARVIES)

[ ] F. Saat approve staging → salin staging.email_thread_id ke ticket.email_thread_id
        (agar reply email masuk ke thread yang benar)

[ ] G. Saat approve staging → salin staging.cc_emails ke ticket.cc_emails
        (agar CC customer ikut menerima balasan)

[ ] H. Pastikan kolom ticket.cc_emails ada (TEXT NULL)
        → Buat migration jika belum ada
```

### Prioritas Sedang

```
[ ] I. Saat kirim email balasan dari EcoSystem, parse ticket.cc_emails
        dan sertakan sebagai CC penerima
        → json_decode($ticket->cc_emails ?? '[]', true)
```

### Prioritas Rendah (Opsional)

```
[ ] J. Endpoint /api/staging-tickets bisa menerima multipart/form-data
        agar file attachment dari customer (non-OAuth) bisa dikirim bersama staging
```

---

## Catatan Penting

### Kenapa staging harus di-approve dulu?

Alur ini dirancang agar helpdesk bisa **memverifikasi** bahwa ticket yang masuk
memang valid sebelum ditangani. Customer bisa submit dari form web, tapi admin
EcoSystem yang memutuskan apakah ini menjadi ticket resmi.

### Kenapa "Initial" hilang setelah approve?

JARVIES hanya menampilkan staging dengan `status = 'unvalidated'`. Begitu EcoSystem
mengubah `staging.status` menjadi `'approved'`, query JARVIES tidak akan menemukan
staging tersebut lagi — otomatis hilang dari list. Tidak perlu ada API notifikasi
khusus dari EcoSystem ke JARVIES.

### Jika staging di-reject?

Ubah `staging.status = 'rejected'`. Staging akan hilang dari list "Initial" di
JARVIES. Sebaiknya tambahkan notifikasi ke customer (email atau in-app) bahwa
tiket tidak dapat diproses, beserta alasannya (`staging.rejection_reason`).
