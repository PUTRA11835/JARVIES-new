# JARVIES API Documentation

Dokumentasi lengkap seluruh API JARVIES untuk integrasi mobile app dan testing Postman.

**Base URL (Production):** `https://jarvies.ecosystemtest.org`
**Base URL (Local):** `http://127.0.0.1:8001`
**Format response:** JSON
**Content-Type default:** `application/json`

---

## Daftar Isi

1. [Autentikasi](#1-autentikasi)
2. [Dashboard](#2-dashboard)
3. [Tiket](#3-tiket)
4. [Setup Postman — Lengkap](#4-setup-postman--lengkap)
5. [HTTP Status Code](#5-http-status-code)
6. [Catatan Penting](#6-catatan-penting)

---

## 1. Autentikasi

### Format Token

JARVIES menggunakan **Bearer token stateless** (bukan JWT/Sanctum).

```
access_token  = base64("{customer_code}|{unix_timestamp}|customer")
refresh_token = hex string 64 karakter (disimpan di tabel api_refresh_tokens, expire 90 hari)
```

- Access token **berlaku 7 hari** sejak diterbitkan
- Setelah expire, gunakan `POST /api/auth/refresh` untuk mendapatkan token baru
- Kirim di setiap request protected:

```
Authorization: Bearer {access_token}
```

---

### POST /api/auth/login

Login customer. Mendapatkan access_token dan refresh_token.

**URL:** `https://jarvies.ecosystemtest.org/api/auth/login`
**Method:** `POST`
**Auth:** Tidak diperlukan

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body (raw JSON):**
```json
{
    "email": "customer@domain.com",
    "password": "password123"
}
```

> `email` bisa diisi dengan **email**, **username**, atau **nomor HP** yang terdaftar.

**Response 200 — Berhasil:**
```json
{
    "success": true,
    "message": "Login berhasil.",
    "data": {
        "access_token": "Q1VTVC0wMDF8MTcwMDAwMDAwMHxjdXN0b21lcg==",
        "refresh_token": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2",
        "token_type": "Bearer",
        "user": {
            "id": 37,
            "type": "customer",
            "customer_code": "CUST-001",
            "company_name": "PT Contoh Perusahaan",
            "email": "customer@domain.com",
            "category": "Enterprise",
            "group": "A"
        }
    }
}
```

**Response 401 — Password salah / akun tidak ditemukan:**
```json
{
    "success": false,
    "message": "Email atau password salah."
}
```

**Response 403 — Akun baru, belum set password:**
```json
{
    "success": false,
    "require_password_change": true,
    "message": "Silakan cek email Anda untuk mengatur password terlebih dahulu.",
    "email": "cu***@domain.com"
}
```

**Response 403 — Bukan customer (employee/admin):**
```json
{
    "success": false,
    "message": "Akun ini tidak memiliki akses ke aplikasi mobile."
}
```

**Response 422 — Validasi gagal:**
```json
{
    "success": false,
    "message": "Validasi gagal.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

---

### POST /api/auth/refresh

Tukar refresh token dengan access token baru (**rotating** — token lama langsung hangus).

**URL:** `https://jarvies.ecosystemtest.org/api/auth/refresh`
**Method:** `POST`
**Auth:** Tidak diperlukan

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body (raw JSON):**
```json
{
    "refresh_token": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2"
}
```

**Response 200 — Berhasil:**
```json
{
    "success": true,
    "data": {
        "access_token": "bmV3dG9rZW58MTcwMDAwMDAwMHxjdXN0b21lcg==",
        "refresh_token": "newrefreshtoken64charshere...",
        "expires_in": 604800,
        "token_type": "Bearer"
    }
}
```

**Response 401 — Token tidak valid atau sudah expire:**
```json
{
    "success": false,
    "message": "Refresh token tidak valid atau sudah kedaluwarsa. Silakan login ulang.",
    "code": "REFRESH_TOKEN_INVALID"
}
```

---

### GET /api/auth/me 🔒

Profil customer yang sedang login berdasarkan token.

**URL:** `https://jarvies.ecosystemtest.org/api/auth/me`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "id": 37,
        "type": "customer",
        "customer_code": "CUST-001",
        "company_name": "PT Contoh Perusahaan",
        "email": "customer@domain.com",
        "category": "Enterprise",
        "group": "A",
        "role": {
            "id": 3,
            "name": "Customer"
        }
    }
}
```

**Response 401 — Token tidak ditemukan / tidak valid:**
```json
{
    "success": false,
    "message": "Token tidak ditemukan. Silakan login terlebih dahulu."
}
```

**Response 401 — Token expired:**
```json
{
    "success": false,
    "message": "Sesi telah berakhir. Silakan refresh token.",
    "code": "TOKEN_EXPIRED"
}
```

---

### POST /api/auth/logout 🔒

Logout dan hapus refresh token dari database.

**URL:** `https://jarvies.ecosystemtest.org/api/auth/logout`
**Method:** `POST`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
Accept: application/json
```

**Request Body (raw JSON, opsional):**
```json
{
    "refresh_token": "a1b2c3d4e5f6..."
}
```

> Jika `refresh_token` **tidak dikirim** → semua refresh token customer ini di **semua device** akan dihapus (logout semua device).
> Jika `refresh_token` **dikirim** → hanya refresh token tersebut yang dihapus (logout device ini saja).

**Response 200:**
```json
{
    "success": true,
    "message": "Logout berhasil."
}
```

---

## 2. Dashboard

### GET /api/dashboard 🔒

Ringkasan statistik tiket + 5 tiket terbaru untuk halaman Home.

**URL:** `https://jarvies.ecosystemtest.org/api/dashboard`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "total": 15,
            "open": 3,
            "in_progress": 5,
            "hold": 1,
            "closed": 5,
            "cancel": 1
        },
        "unread_messages": 2,
        "recent_tickets": [
            {
                "ticket_id": 101,
                "ticket_number": "TCK-2024-001",
                "description": "Masalah tcode VL03 tidak bisa dibuka",
                "status": "open",
                "status_label": "Open",
                "ticket_priority": "High",
                "priority_color": "#ef4444",
                "employee_name": "Budi",
                "created_at": "2024-01-15T08:00:00.000000Z"
            }
        ]
    }
}
```

**Nilai status yang mungkin:**

| status | status_label | priority_color |
|---|---|---|
| `open` | Open | `#ef4444` (merah) |
| `in_progress` | In Progress | `#f59e0b` (kuning) |
| `hold` | Hold | `#6b7280` (abu) |
| `closed` | Closed | `#10b981` (hijau) |
| `cancel` | Cancel | `#6b7280` (abu) |

---

## 3. Tiket

### GET /api/tickets 🔒

List semua tiket milik customer yang sedang login.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Query Params (opsional):**

| Param | Nilai | Keterangan |
|---|---|---|
| `status` | `open` `in_progress` `hold` `closed` `cancel` | Filter berdasarkan status |

**Contoh dengan filter:** `GET /api/tickets?status=open`

**Response 200:**
```json
{
    "success": true,
    "data": [
        {
            "ticket_id": 101,
            "ticket_number": "TCK-2024-001",
            "description": "Masalah tcode VL03 tidak bisa dibuka",
            "status": "open",
            "status_label": "Open",
            "ticket_priority": "High",
            "priority_color": "#ef4444",
            "start_date": null,
            "end_date": null,
            "man_days": null,
            "employee": {
                "employee_id": 5,
                "employee_name": "Budi"
            },
            "members": [],
            "created_at": "2024-01-15T08:00:00.000000Z",
            "updated_at": "2024-01-15T10:00:00.000000Z"
        }
    ]
}
```

> `employee` bisa `null` jika tiket belum ditugaskan ke employee.
> `members` bisa array kosong `[]`.

---

### GET /api/tickets/{id} 🔒

Detail satu tiket. Customer hanya bisa mengakses tiket miliknya sendiri.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets/101`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Response 200:**
```json
{
    "success": true,
    "data": {
        "ticket_id": 101,
        "ticket_number": "TCK-2024-001",
        "description": "Masalah tcode VL03 tidak bisa dibuka",
        "status": "open",
        "status_label": "Open",
        "jarvies_status": "in process",
        "ticket_priority": "High",
        "priority_color": "#ef4444",
        "channel": "email",
        "wait_close": null,
        "start_date": null,
        "end_date": null,
        "man_days": null,
        "employee": {
            "employee_id": 5,
            "employee_name": "Budi"
        },
        "members": [],
        "created_at": "2024-01-15T08:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
    }
}
```

**Response 404:**
```json
{
    "success": false,
    "message": "Tiket tidak ditemukan."
}
```

---

### POST /api/tickets 🔒

Buat tiket baru — **masuk ke staging saja** (tanpa kirim email, tanpa panggil EcoSystem).

> Gunakan endpoint ini untuk **mobile app Flutter** dimana email tidak diperlukan.
> Untuk testing yang identik dengan web UI (ada email), gunakan `POST /api/tickets/submit-with-email`.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets`
**Method:** `POST`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
Accept: application/json
```

**Request Body (raw JSON):**
```json
{
    "description": "Masalah pada tcode VL03 tidak bisa dibuka",
    "ticket_priority": "High",
    "body": "Detail masalah lebih lengkap di sini. Sudah dicoba restart tapi masih error."
}
```

| Field | Tipe | Wajib | Nilai yang diterima |
|---|---|---|---|
| `description` | string | **Ya** | Judul/subject tiket, max 5000 karakter |
| `ticket_priority` | string | Tidak | `Low` `Medium` `High` |
| `body` | string | Tidak | Isi pesan pertama |

**Response 201 — Berhasil:**
```json
{
    "success": true,
    "message": "Tiket berhasil dikirim dan sedang menunggu validasi admin.",
    "data": {
        "id": 42,
        "staging_ref": "STG-42",
        "description": "Masalah pada tcode VL03 tidak bisa dibuka",
        "ticket_priority": "High",
        "status": "unvalidated",
        "status_label": "Menunggu Validasi",
        "created_at": "2024-01-15T08:00:00.000000Z"
    }
}
```

**Response 422 — Validasi gagal:**
```json
{
    "success": false,
    "message": "Validasi gagal.",
    "errors": {
        "description": ["The description field is required."]
    }
}
```

---

### POST /api/tickets/submit-with-email 🔒

Buat tiket baru dengan **alur lengkap** — identik dengan membuat tiket dari web UI JARVIES:

1. Kirim email ke customer via Microsoft Graph API
2. Ambil `internet_message_id` dari email yang terkirim
3. POST ke EcoSystem `/jarvies/staging-tickets` dengan `channel: email` + `internet_message_id`

> **Gunakan endpoint ini untuk testing Postman** jika ingin memastikan email terkirim dan EcoSystem menerima `channel=email` yang benar.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets/submit-with-email`
**Method:** `POST`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

> **PENTING:** Jangan set `Content-Type` manual — biarkan Postman set otomatis saat menggunakan `form-data`.

**Request Body (form-data di Postman):**

| Key | Type | Wajib | Contoh Value |
|---|---|---|---|
| `description` | Text | **Ya** | `Masalah tcode VL03 tidak bisa dibuka` |
| `ticket_priority` | Text | Tidak | `High` |
| `body` | Text | Tidak | `Detail masalah di sini...` |
| `body_html` | Text | Tidak | `<p>Detail masalah <b>di sini</b>...</p>` |
| `cc_emails[]` | Text | Tidak | `cc@domain.com` (bisa multiple) |
| `attachments[]` | File | Tidak | Upload file (max 20MB per file) |

**Nilai `ticket_priority` yang diterima:** `Low` `Medium` `High` `Very High`

**Response 201 — Berhasil (email terkirim + staging dibuat di EcoSystem):**
```json
{
    "success": true,
    "staging": true,
    "email_sent": true,
    "message": "Tiket berhasil dikirim dan sedang menunggu validasi admin."
}
```

**Response 422 — Email tidak ada di akun:**
```json
{
    "success": false,
    "message": "Akun Anda tidak memiliki email. Hubungi administrator."
}
```

**Response 422 — Validasi gagal:**
```json
{
    "success": false,
    "message": "Validasi gagal.",
    "errors": {
        "description": ["The description field is required."]
    }
}
```

**Response 500 — Gagal kirim email:**
```json
{
    "success": false,
    "message": "Gagal mengirim email: Connection timeout"
}
```

---

### GET /api/tickets/staging 🔒

List staging tiket milik customer — tiket yang masuk staging (menunggu validasi / sudah divalidasi admin).

**URL:** `https://jarvies.ecosystemtest.org/api/tickets/staging`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Response 200:**
```json
{
    "success": true,
    "data": [
        {
            "id": 42,
            "staging_ref": "STG-42",
            "description": "Masalah pada tcode VL03",
            "ticket_priority": "High",
            "status": "unvalidated",
            "status_label": "Menunggu Validasi",
            "rejection_reason": null,
            "ticket_id": null,
            "ticket_number": null,
            "created_at": "2024-01-15T08:00:00.000000Z",
            "validated_at": null
        },
        {
            "id": 38,
            "staging_ref": "STG-38",
            "description": "Permasalahan laporan SAP",
            "ticket_priority": "Medium",
            "status": "validated",
            "status_label": "Disetujui",
            "rejection_reason": null,
            "ticket_id": 101,
            "ticket_number": "TCK-2024-001",
            "created_at": "2024-01-10T08:00:00.000000Z",
            "validated_at": "2024-01-10T09:30:00.000000Z"
        },
        {
            "id": 35,
            "staging_ref": "STG-35",
            "description": "Tiket duplikat",
            "ticket_priority": "Low",
            "status": "rejected",
            "status_label": "Ditolak",
            "rejection_reason": "Tiket duplikat dengan TCK-2024-001.",
            "ticket_id": null,
            "ticket_number": null,
            "created_at": "2024-01-08T08:00:00.000000Z",
            "validated_at": "2024-01-08T10:00:00.000000Z"
        }
    ]
}
```

**Nilai `status` staging:**

| status | status_label | Keterangan |
|---|---|---|
| `unvalidated` | Menunggu Validasi | Belum diproses admin |
| `validated` | Disetujui | Sudah disetujui, `ticket_id` & `ticket_number` terisi |
| `rejected` | Ditolak | Ditolak admin, lihat `rejection_reason` |

---

### GET /api/tickets/{id}/messages 🔒

List semua pesan percakapan dalam tiket. Internal note tidak ditampilkan ke customer.
Otomatis menandai pesan dari agent/employee sebagai **sudah dibaca**.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets/101/messages`
**Method:** `GET`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

**Response 200:**
```json
{
    "success": true,
    "data": [
        {
            "id": 201,
            "sender_type": "employee",
            "sender_name": "Budi (Support)",
            "message": "Halo, kami sedang meninjau masalah Anda. Mohon tunggu.",
            "attachments": [],
            "created_at": "2024-01-15T09:00:00.000000Z"
        },
        {
            "id": 202,
            "sender_type": "customer",
            "sender_name": "PT Contoh Perusahaan",
            "message": "Terima kasih, mohon segera ditangani.",
            "attachments": [
                {
                    "id": 10,
                    "file_name": "screenshot-error.png",
                    "type": "image",
                    "url": "https://jarvies.ecosystemtest.org/storage/..."
                },
                {
                    "id": 11,
                    "file_name": "log-error.pdf",
                    "type": "pdf",
                    "url": "https://jarvies.ecosystemtest.org/storage/..."
                }
            ],
            "created_at": "2024-01-15T09:30:00.000000Z"
        }
    ]
}
```

**Nilai `sender_type`:**
- `employee` — pesan dari agent/admin EcoSystem
- `customer` — pesan dari customer

**Nilai `type` pada attachment:**
- `image` `pdf` `document` `spreadsheet` `archive` `file`

**Response 404:**
```json
{
    "success": false,
    "message": "Tiket tidak ditemukan."
}
```

---

### POST /api/tickets/{id}/messages 🔒

Kirim pesan/reply ke dalam tiket.

**URL:** `https://jarvies.ecosystemtest.org/api/tickets/101/messages`
**Method:** `POST`
**Auth:** Bearer Token

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
Accept: application/json
```

**Request Body (raw JSON):**
```json
{
    "message": "Masalah masih terjadi setelah restart. Berikut screenshot error terlampir."
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `message` | string | **Ya** | Isi pesan, max 5000 karakter |

**Response 201 — Berhasil:**
```json
{
    "success": true,
    "message": "Pesan berhasil dikirim.",
    "data": {
        "id": 203,
        "sender_type": "customer",
        "sender_name": "PT Contoh Perusahaan",
        "message": "Masalah masih terjadi setelah restart. Berikut screenshot error terlampir.",
        "created_at": "2024-01-15T10:00:00.000000Z"
    }
}
```

**Response 422 — Pesan kosong:**
```json
{
    "success": false,
    "message": "Pesan tidak boleh kosong.",
    "errors": {
        "message": ["The message field is required."]
    }
}
```

**Response 404:**
```json
{
    "success": false,
    "message": "Tiket tidak ditemukan."
}
```

---

## 4. Setup Postman — Lengkap

### A. Buat Environment

Di Postman, klik **Environments → Add** dan buat environment baru bernama **"JARVIES Production"** dengan variabel:

| Variable | Initial Value | Current Value |
|---|---|---|
| `base_url` | `https://jarvies.ecosystemtest.org` | *(sama)* |
| `access_token` | *(kosong)* | *(diisi otomatis setelah login)* |
| `refresh_token` | *(kosong)* | *(diisi otomatis setelah login)* |
| `ticket_id` | *(kosong)* | *(diisi manual setelah dapat tiket)* |

Aktifkan environment ini di pojok kanan atas Postman.

---

### B. Buat Collection

Buat collection baru bernama **"JARVIES API"**. Di tab **Authorization** collection:
- Type: `Bearer Token`
- Token: `{{access_token}}`

Semua request dalam collection akan otomatis menggunakan token ini (kecuali login & refresh).

---

### C. Auto-set Token setelah Login

Di request **POST /api/auth/login**, buka tab **Tests** dan tambahkan script:

```javascript
if (pm.response.code === 200) {
    const data = pm.response.json().data;
    pm.environment.set("access_token",  data.access_token);
    pm.environment.set("refresh_token", data.refresh_token);
    console.log("✅ Token set:", data.access_token);
    console.log("👤 User:", data.user.company_name, "|", data.user.customer_code);
}
```

---

### D. Auto-set Token setelah Refresh

Di request **POST /api/auth/refresh**, buka tab **Tests** dan tambahkan script:

```javascript
if (pm.response.code === 200) {
    const data = pm.response.json().data;
    pm.environment.set("access_token",  data.access_token);
    pm.environment.set("refresh_token", data.refresh_token);
    console.log("🔄 Token refreshed:", data.access_token);
}
```

---

### E. Daftar Lengkap Request — Urutan Testing

Buat request berikut secara berurutan di dalam collection:

#### 1. POST Login
- **URL:** `{{base_url}}/api/auth/login`
- **Method:** POST
- **Auth:** No Auth (override collection)
- **Body (raw JSON):**
```json
{
    "email": "customer@domain.com",
    "password": "password123"
}
```
- **Tests:** Script auto-set token (lihat bagian C)

#### 2. GET Me (Verifikasi Token)
- **URL:** `{{base_url}}/api/auth/me`
- **Method:** GET
- **Auth:** Inherit from collection (Bearer `{{access_token}}`)

#### 3. GET Dashboard
- **URL:** `{{base_url}}/api/dashboard`
- **Method:** GET
- **Auth:** Inherit from collection

#### 4. GET Semua Tiket
- **URL:** `{{base_url}}/api/tickets`
- **Method:** GET
- **Auth:** Inherit from collection

#### 5. GET Tiket Filter Status
- **URL:** `{{base_url}}/api/tickets?status=open`
- **Method:** GET
- **Auth:** Inherit from collection
- **Query Params:** Key=`status`, Value=`open`

#### 6a. POST Buat Tiket (Tanpa Email)
- **URL:** `{{base_url}}/api/tickets`
- **Method:** POST
- **Auth:** Inherit from collection
- **Body (raw JSON):**
```json
{
    "description": "Masalah tcode VL03 tidak bisa dibuka",
    "ticket_priority": "High",
    "body": "Saat membuka tcode VL03, muncul error dump. Sudah dicoba restart SAP tapi masih sama."
}
```

#### 6b. POST Buat Tiket + Kirim Email (Full Flow)
- **URL:** `{{base_url}}/api/tickets/submit-with-email`
- **Method:** POST
- **Auth:** Inherit from collection
- **Body: form-data** (pilih tab `form-data`, bukan `raw`)

| Key | Type | Value |
|---|---|---|
| `description` | Text | `Testing create ticket via Postman dengan email` |
| `ticket_priority` | Text | `High` |
| `body` | Text | `Ini adalah body pesan testing dari Postman.` |

> Tambahkan baris `attachments[]` dengan Type=File untuk test upload file.

#### 7. GET Staging Tiket
- **URL:** `{{base_url}}/api/tickets/staging`
- **Method:** GET
- **Auth:** Inherit from collection

#### 8. GET Detail Tiket
- **URL:** `{{base_url}}/api/tickets/{{ticket_id}}`
- **Method:** GET
- **Auth:** Inherit from collection
- *Isi `{{ticket_id}}` di environment dengan angka ticket_id dari response langkah 4*

#### 9. GET Pesan Tiket
- **URL:** `{{base_url}}/api/tickets/{{ticket_id}}/messages`
- **Method:** GET
- **Auth:** Inherit from collection

#### 10. POST Kirim Pesan
- **URL:** `{{base_url}}/api/tickets/{{ticket_id}}/messages`
- **Method:** POST
- **Auth:** Inherit from collection
- **Body (raw JSON):**
```json
{
    "message": "Testing kirim pesan dari Postman. Apakah pesan ini masuk?"
}
```

#### 11. POST Refresh Token
- **URL:** `{{base_url}}/api/auth/refresh`
- **Method:** POST
- **Auth:** No Auth (override collection)
- **Body (raw JSON):**
```json
{
    "refresh_token": "{{refresh_token}}"
}
```
- **Tests:** Script auto-set token (lihat bagian D)

#### 12. POST Logout
- **URL:** `{{base_url}}/api/auth/logout`
- **Method:** POST
- **Auth:** Inherit from collection
- **Body (raw JSON, opsional — kosong untuk logout semua device):**
```json
{
    "refresh_token": "{{refresh_token}}"
}
```

---

### F. Headers yang Wajib Dikirim

Untuk semua endpoint 🔒:
```
Authorization: Bearer {{access_token}}
Accept: application/json
```

Untuk endpoint dengan body JSON:
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
Accept: application/json
```

Untuk `POST /api/tickets/submit-with-email` (form-data):
```
Authorization: Bearer {{access_token}}
Accept: application/json
```
> Jangan set `Content-Type` — Postman otomatis set `multipart/form-data` saat pilih body form-data.

---

## 5. HTTP Status Code

| Code | Artinya |
|---|---|
| `200` | Berhasil |
| `201` | Berhasil membuat data baru |
| `401` | Token tidak valid / tidak ada / expired |
| `403` | Tidak punya akses (bukan customer / belum set password) |
| `404` | Data tidak ditemukan |
| `422` | Validasi gagal |
| `500` | Error server |

---

## 6. Catatan Penting

1. **Token expire 7 hari** — access token JARVIES berlaku 7 hari. Setelah expire, gunakan `POST /api/auth/refresh` dengan refresh token.
2. **Refresh token expire 90 hari** — jika refresh token juga expire, customer harus login ulang.
3. **Rotating refresh token** — setiap kali `/api/auth/refresh` dipanggil, refresh token lama hangus dan diganti baru. Simpan refresh token terbaru.
4. **Hanya customer** — semua endpoint `/api/*` hanya bisa diakses role customer (role_id = 3).
5. **Database shared** — JARVIES dan EcoSystem berbagi database `ecosystem`. Tiket yang dibuat/diupdate di salah satu sistem langsung terlihat di sistem lain.
6. **Staging flow** — semua tiket customer selalu masuk `staging_tickets` dulu. EcoSystem yang bertugas memvalidasi dan membuat tiket nyata di tabel `tickets`.
7. **Internal note** — pesan dengan `is_internal_note = true` tidak pernah tampil di API customer.
8. **Channel email vs mobile** — `POST /api/tickets` menghasilkan channel `web`. `POST /api/tickets/submit-with-email` menghasilkan channel `email` (dengan pengiriman email via Graph API).
9. **EcoSystem URL** — `https://ecosystemtest.org` adalah sistem admin/employee side yang terpisah. JARVIES (`https://jarvies.ecosystemtest.org`) memanggil EcoSystem API saat membuat staging tiket.
