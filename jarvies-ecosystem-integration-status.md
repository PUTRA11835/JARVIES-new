# Status Implementasi Integrasi JARVIES ↔ EcoSystem

> **Terakhir diperbarui:** 2026-04-09
> **Tujuan:** Dokumen ini mencatat semua yang sudah diimplementasikan di JARVIES
> dan bagaimana integrasi dengan EcoSystem berjalan — khususnya alur ticket dan email.

---

## 1. Ringkasan Status

| Fitur | JARVIES | EcoSystem | Status Keseluruhan |
|---|---|---|---|
| Submit tiket (web form → staging) | ✅ via EcoSystem API | ✅ approve staging | ✅ Selesai |
| Fallback staging lokal | ✅ jika API gagal | — | ✅ Selesai |
| Customer reply via JARVIES | ✅ GraphRelayService | ✅ lihat via shared DB | ✅ Selesai |
| Email relay ke customer | ✅ FROM helpdesk via M365 | ✅ FROM helpdesk via M365 | ✅ Selesai |
| Customer reply langsung dari email | — | ✅ processInbox | ✅ Selesai |
| Deduplication processInbox | — | ✅ Ada | ✅ Selesai |
| CC email pada semua relay | ✅ GraphRelayService | ✅ | ✅ Selesai |
| Setup password → auto-login | ✅ → /dashboard | — | ✅ Selesai |

---

## 2. Yang Sudah Diimplementasikan di JARVIES

### 2.1 Submit Tiket Baru (`POST /tickets`) — `store()`

**File:** `app/Http/Controllers/TicketController.php`

Semua customer selalu masuk ke staging dulu (tidak ada OAuth branching lagi).

**Alur:**
```
Customer submit form Jarvies
    ↓
POST /api/staging-tickets ke EcoSystem API (X-Api-Key)
    │
    ├─ Berhasil → return 201 staging=true
    │
    └─ Gagal (timeout / API down)
        → Fallback: StagingTicketService::createFromWeb() simpan ke staging_tickets lokal
        → return 201 staging=true
```

**Field yang dikirim ke EcoSystem API:**
```json
{
  "description":        "subject tiket",
  "body":               "<p>isi pesan HTML dari Quill</p>",
  "ticket_priority":    "Medium",
  "sender_name":        "nama individual dari auth_users.username",
  "submitted_by_email": "email@customer.com",
  "cc_emails":          "[\"cc1@mail.com\",\"cc2@mail.com\"]",
  "customer_id":        37,
  "contact_id":         12,
  "name":               "nama PIC",
  "no_hp":              "08123456789",
  "module":             "nama modul",
  "client":             "nama klien"
}
```

**Environment variables:**
```env
ECOSYSTEM_API_URL=http://localhost:8000/api
EXTERNAL_TICKET_API_KEY=<api_key>
```

---

### 2.2 Customer Reply Tiket (`POST /tickets/{id}/comment`) — `addComment()`

**File:** `app/Http/Controllers/TicketController.php`

**Alur (role == 3 / customer):**
```
Customer klik Send Reply di JARVIES
    ↓
GraphRelayService::sendRelayEmail()
    │
    ├─ Ada email_thread_id → createReply (threading otomatis via M365 conversationId)
    └─ Belum ada thread → createNewDraft (email baru)
    │
    ├─ PATCH toRecipients = customer email (fix bug Graph createReply)
    ├─ PATCH ccRecipients = cc_emails dari ticket (jika ada)
    ├─ Upload inline images (dari paste Quill)
    ├─ Upload file attachments
    ├─ Fetch internetMessageId sebelum send
    └─ Send draft → cari di Sent Items → return IDs
    │
TicketMessage::create() di shared DB
    (langsung ke DB karena JARVIES & EcoSystem berbagi DB yang sama)
    │
email_thread_id diperbarui di ticket jika belum ada
```

**Email dikirim:**
```
FROM : Raditya@eclecticonsulting.onmicrosoft.com (helpdesk)
TO   : email customer (dari session user)
CC   : cc_emails dari ticket (jika ada)
Via  : Microsoft Graph API (client_credentials — bukan delegated)
```

**Alur (role == 1 / admin):**
```
Admin reply → TicketMessage::create() langsung ke DB
(tidak kirim email — EcoSystem handle dari sisi mereka)
```

---

### 2.3 Setup Password & Auto-Login

**File:** `app/Http/Controllers/PasswordSetupController.php` → `submitChangePassword()`

```
Customer klik link setup password dari email
    ↓
Isi password baru → submit
    ↓
Jika customer (bukan employee):
    → Auto-login (buat session sama seperti login normal)
    → Session includes: id, type, customer_code, name, company_name, email, role
    → Redirect langsung ke /dashboard

Jika employee:
    → Redirect ke /login
```

> Tidak ada halaman onboarding connect-email lagi. Semua email dikirim
> dari helpdesk ke customer — tidak perlu customer menghubungkan akun email.

---

### 2.4 GraphRelayService (Email Relay Helpdesk → Customer)

**File:** `app/Services/GraphRelayService.php`

| Method | Keterangan |
|---|---|
| `sendRelayEmail($ticket, $toEmail, $senderName, $htmlBody, $inReplyTo, $inlineImages, $fileAttachments, $ccEmails)` | Entry point relay email |
| `createReplyDraft(...)` | createReply via Graph, PATCH toRecipients + ccRecipients |
| `createNewDraft(...)` | Buat draft baru dengan to + cc |
| `getAccessToken()` | Client credentials token dari M365 |

**Architecture email-first:**
1. Buat draft (createReply atau baru)
2. PATCH draft: toRecipients, ccRecipients, subject, body
3. Upload attachment ke draft
4. Fetch `internetMessageId` sebelum send
5. Send draft
6. Cari di Sent Items via PHP matching (retry 3x, sleep 1s)
7. Return IDs untuk disimpan ke DB

**Environment variables:**
```env
MS_TENANT_ID=<tenant_id>
MS_CLIENT_ID=<client_id>
MS_CLIENT_SECRET=<secret>
MS_SENDER_EMAIL=Raditya@eclecticonsulting.onmicrosoft.com
GRAPH_BASE_URL=https://graph.microsoft.com/v1.0
```

---

## 3. Yang Sudah Diimplementasikan di EcoSystem

### 3.1 ✅ Simpan `email_message_id` untuk Email Keluar

EcoSystem fetch `internetMessageId` dari Graph sebelum send, simpan ke `ticket_message.email_message_id`.
Diperlukan agar threading M365 bekerja saat customer reply via JARVIES.

### 3.2 ✅ Deduplication di `processInbox()`

Sebelum menyimpan email masuk, cek apakah `internetMessageId` sudah ada di `ticket_messages`.
Mencegah duplikat jika email masuk diproses lebih dari sekali.

### 3.3 ✅ Support `channel` di `customerReply()`

EcoSystem menerima dan menyimpan `channel` (email / web) pada pesan yang dibuat via API.

---

## 4. Alur Lengkap Saat Ini

```
[Ticket Baru via Web]
Customer submit form Jarvies
    → POST EcoSystem API /staging-tickets (atau fallback local)
    → Helpdesk approve di EcoSystem
    → Ticket dibuat + EcoSystem kirim email notifikasi ke customer
    → ticket_message.email_message_id = internetMessageId email notifikasi

[Reply dari Helpdesk via EcoSystem]
Helpdesk ketik reply di EcoSystem chat
    → EcoSystem kirim relay email TO customer (createReply / sendMail via Graph)
    → ticket_message.email_message_id = internetMessageId tersimpan
    → Email masuk ke customer dalam thread yang sama ✅

[Reply dari Customer via JARVIES Web]
Customer ketik reply → klik Send
    → JARVIES::addComment() → GraphRelayService::sendRelayEmail()
    → Email dikirim FROM helpdesk (Raditya) TO customer + CC
    → Via M365 createReply (threading dari email_thread_id) atau pesan baru
    → TicketMessage::create() langsung ke shared DB
    → EcoSystem employee lihat pesan baru di chat mereka ✅

[Reply Customer Langsung dari Email]
Customer balas email notifikasi dari email client
    → Email masuk ke M365 inbox helpdesk
    → EcoSystem processInbox() memproses
    → Cek deduplication via email_message_id
    → Simpan sebagai TicketMessage
    → JARVIES tampilkan via shared DB ✅
```

---

## 5. Pola Penting

- **Shared DB**: JARVIES dan EcoSystem pakai DB `ecosystem` yang sama → `TicketMessage::create()` langsung visible di EcoSystem
- **Email ONE-WAY**: Semua email ke customer dikirim FROM helpdesk (Raditya) — customer tidak perlu connect email OAuth
- **CC**: `ticket.cc_emails` (JSON array) diikutkan ke semua relay email (JARVIES & EcoSystem)
- **Threading**: Via `ticket.email_thread_id` (M365 conversationId) → `createReply` di Graph
- **Password reset**: Pakai `cp_token` + Graph client_credentials (bukan OAuth Socialite)

---

## 6. Environment Variables JARVIES (Referensi Lengkap)

```env
# EcoSystem API
ECOSYSTEM_API_URL=http://localhost:8000/api
EXTERNAL_TICKET_API_KEY=<key>

# Microsoft Graph (helpdesk outgoing email — client credentials)
MS_TENANT_ID=<tenant_id>
MS_CLIENT_ID=<client_id>
MS_CLIENT_SECRET=<secret>
MS_SENDER_EMAIL=Raditya@eclecticonsulting.onmicrosoft.com
GRAPH_BASE_URL=https://graph.microsoft.com/v1.0
```

---

## 7. Referensi File JARVIES

| Fungsi | File |
|---|---|
| Submit tiket (store) | `app/Http/Controllers/TicketController.php` → `store()` |
| Customer reply (addComment) | `app/Http/Controllers/TicketController.php` → `addComment()` |
| Relay email via M365 | `app/Services/GraphRelayService.php` |
| Password setup + auto-login | `app/Http/Controllers/PasswordSetupController.php` |
| Staging lokal (fallback) | `app/Services/StagingTicketService.php` |
| Model staging | `app/Models/StagingTicket.php` |
| Model ticket | `app/Models/Ticket.php` |
| Model ticket message | `app/Models/TicketMessage.php` |
| Ticket list + modal | `resources/views/tickets/index.blade.php` |
| Ticket create form | `resources/views/tickets/create.blade.php` |
| Routes utama | `routes/web.php` |
