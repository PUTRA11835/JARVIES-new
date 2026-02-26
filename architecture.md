# Arsitektur Sistem EcoSystem

## Gambaran Besar: Employee Side vs Customer Side

```
┌─────────────────────────────────────┐     ┌────────────────────────────────────────┐
│          ECOSYSTEM (Project ini)     │     │        CUSTOMER PROJECT (terpisah)     │
│         ── Employee / Admin Side ──  │     │          ── Customer Side ──            │
│                                     │     │                                        │
│  • Kelola ticket (buka, tangani,    │     │  • Submit ticket baru                  │
│    assign ke delivery, tutup)       │     │  • Lihat status ticket mereka          │
│  • Delivery Support management      │◄───►│  • Balas pesan dari agent              │
│  • Project planning (Gantt, etc)    │     │  • Download dokumen (BAST, dll)        │
│  • Manajemen Employee & Customer    │     │  • Lihat history ticket                │
│  • Email inbox processing           │     │                                        │
│  • Dashboard & reporting            │     │  Login: auth_users (customer_id FK)    │
│                                     │     │  Role: 3 (Customer)                    │
│  Login: auth_users (employee_id FK) │     │                                        │
│  Role: 1 (Admin), 2 (Employee)      │     │                                        │
└──────────────────┬──────────────────┘     └──────────────────┬─────────────────────┘
                   │                                           │
                   └───────────────┬───────────────────────────┘
                                   │ Shared Database + API
                              ┌────▼────┐
                              │  MySQL   │
                              │   DB     │
                              └─────────┘
```

---

## Database Tables Utama

### Authentication
```
auth_users
├── id (PK)
├── employee_id (FK → employee) — null jika customer
├── customer_id (FK → customer) — null jika employee
├── username   — ECI untuk employee, customer_code untuk customer
├── email      — nullable
├── phone      — nullable
├── password   — bcrypt hash
├── is_active
├── is_already_cp  — false = akun baru belum setup password
├── cp_token, cp_token_expires_at  — untuk link setup/reset password
├── last_login_at
└── created_at, updated_at
```

**Cara buat akun baru:**
- Employee → `EmployeeController@store` insert ke `auth_users` dengan `is_already_cp = false`
- Customer → `CustomerController@store` insert ke `auth_users` dengan `is_already_cp = false`
- Keduanya akan dapat email verifikasi untuk set password pertama kali
- Setelah set password, `is_already_cp = true`

### Ticket System

```
ticket
├── ticket_id (PK)
├── ticket_number (unique, format: TKT-YYYYMMDD-XXXX)
├── customer_id (FK → customer)  — siapa yang punya tiket
├── employee_id (FK → employee)  — PIC / agent
├── description
├── status: open | in_progress | hold | cancel | closed | reply
├── ticket_priority: Low | Medium | High
├── jarvies_status: in process | author action | proposed solution | ...
├── channel: email | web
├── email_thread_id   — MS conversationId untuk threading email
├── man_days, wait_close
├── last_customer_reply_at, last_agent_reply_at, last_message_at
└── deleted_at (soft delete)

ticket_message
├── ticket_id (FK)
├── sender_type: customer | employee | system
├── sender_id, sender_email, sender_name
├── message (text HTML)
├── is_internal_note  — catatan internal, tidak terlihat customer
├── channel: email | web
├── email_message_id   — internetMessageId dari email
└── email_in_reply_to  — In-Reply-To header

ticket_member
├── ticket_id (FK)
├── employee_id (FK)
└── role
```

**Ticket Status Flow:**
```
open → in_progress → hold ──→ reply → wait_to_close → closed
             └───────────→ cancel
```

**Ticket dibuat dari:**
1. Employee input manual via web
2. Customer submit via Customer Project
3. Email masuk ke mailbox MS365 → auto-created via `EmailController@processInbox`

### Delivery Support

Ticket yang butuh penanganan lebih lanjut bisa di-assign ke Delivery Support.

```
delivery_support
├── id (PK)
├── client_id (FK → customer)
├── name
├── type: AMS | MO | ATS | Project | Internal
├── start_date, end_date
├── delivery_owner_id, support_manager_id, created_by_id (FK → employee)
├── calculated_progress (0-100)
└── total_mandays

delivery_support_activities
├── delivery_support_id (FK)
├── delivery_support_phase_id (FK)
├── ticket_id (FK → ticket)  ← link ke ticket!
├── name, description
├── start_date, end_date, actual_start_date, actual_end_date
├── progress_percentage, weight
└── status: not_started | in_progress | completed
```

**Relasi Ticket ↔ Delivery Support:**
- 1 ticket bisa di-assign ke 1 activity dalam delivery support
- Link: `delivery_support_activities.ticket_id`
- Cara assign: `POST /tickets/{id}/assign-to-support` atau `POST /tickets/{id}/create-delivery-support`

---

## Email Integration (Microsoft Graph API)

```
MS365 Mailbox
     │
     ▼ (pull setiap X menit via scheduler)
ProcessEmailInbox (artisan command)
     │
     ▼
EmailController@processInbox
     │
     ├── Email baru? → Buat Ticket baru (channel: email)
     │                  Set email_thread_id = conversationId
     │
     └── Email reply? → Tambah TicketMessage ke ticket yang ada
                        Match via: email_thread_id atau email_message_id
```

**Konfigurasi .env yang dibutuhkan:**
```
MS_TENANT_ID=...
MS_CLIENT_ID=...
MS_CLIENT_SECRET=...
MS_SENDER_EMAIL=...
GRAPH_BASE_URL=https://graph.microsoft.com/v1.0
```

**Menjalankan manual:**
```bash
php artisan email:process-inbox
```

---

## Struktur Route

| Route File | Konten |
|---|---|
| `routes/web.php` | Web views (dashboard, profile, ticket view, planning) |
| `routes/api.php` | API JSON (ticket CRUD, messages, delivery support data) |
| `routes/delivery-support.php` | Web + API routes delivery support |
| `routes/delivery.php` | Web + API routes delivery project |

**Middleware auth:** `CheckAuthToken` (custom, cek `session('auth_token')`)

---

## Password Setup / Forgot Password Flow

```
Employee/Customer baru dibuat
        │
        ▼
auth_users insert (is_already_cp = false)
        │
        ▼ (saat login pertama kali)
AuthController deteksi is_already_cp = false
        │
        ▼
generateAndSendToken(authUser, 'setup')
Email dikirim via Graph API berisi link: /change-password?token=xxx
        │
        ▼
User klik link → PasswordSetupController@showChangePassword
        │
        ▼
User isi password baru → submitChangePassword
        │
        ▼
auth_users.password = Hash::make(password)
auth_users.is_already_cp = true
auth_users.cp_token = null
        │
        ▼
User bisa login normal

── Forgot Password ──
Login page → "Forgot Password?" → /forgot-password
User isi email → submitForgotPassword
generateAndSendToken(authUser, 'reset')
Sama seperti flow di atas
```

---

## Struktur Controller Utama

```
app/Http/Controllers/
├── AuthController.php          — login, logout, me()
├── PasswordSetupController.php — setup/reset password via email token
├── ProfileController.php       — halaman profil user (employee/customer)
├── EmployeeController.php      — CRUD employee + insert auth_users
├── CustomerController.php      — CRUD customer + insert auth_users
├── TicketController.php        — business logic ticket (assign, status, messages)
├── TicketViewController.php    — web view ticket (employee side)
├── EmailController.php         — Microsoft Graph API email processing
├── DashboardController.php     — dashboard data & export
├── DeliveryProjectController.php — project delivery CRUD
├── Delivery/
│   ├── DeliverySupportController.php
│   ├── DeliverySupportPhaseController.php
│   ├── DeliverySupportActivityController.php
│   ├── DeliverySupportPlanningController.php
│   └── DeliverySupportDataController.php
└── ... (ProfileController, CalendarController, dll)
```

---

## Catatan Penting untuk Customer Project

Jika mengembangkan Customer Side, perhatikan:

1. **Auth** — gunakan `auth_users` yang sama, bedakan dari `customer_id IS NOT NULL`
2. **Ticket** — customer hanya bisa lihat ticket miliknya (`ticket.customer_id = customer.customer_id`)
3. **Messages** — `is_internal_note = true` TIDAK boleh terlihat di customer side
4. **Email reply** — bisa via `TicketController@customerResponse` atau endpoint API
5. **Delivery Support** — customer bisa lihat progress tapi tidak bisa edit
6. **is_already_cp** — wajib diimplementasikan juga di customer project untuk first-login flow

---

## Perubahan Database Terbaru (Feb 2026)

| Migration | Perubahan |
|---|---|
| `2026_02_20_100000` | Tambah `is_already_cp`, `cp_token`, `cp_token_expires_at` ke `auth_users` |
| `2026_02_20_000000` | `auth_users.email` jadi nullable |
| `2026_02_19_100000` | Hapus password dari `employee`/`customer`, pindah ke `auth_users` |
| `2026_02_18_100000` | Pindah kolom `type` dari `ticket` ke `delivery_support` |
| `2026_02_17_100000` | Tambah `ticket_id` ke `delivery_support_activities` |
