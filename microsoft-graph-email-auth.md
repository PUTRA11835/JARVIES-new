# Microsoft Graph API — Panduan Autentikasi & Akses Email

> Dokumentasi teknis untuk developer yang membangun aplikasi berbasis AI atau otomatisasi
> yang perlu membaca, mengelola, dan menyimpan email dari Microsoft Outlook / Office 365.

---

## Daftar Isi

1. [Konsep Dasar Microsoft Graph API](#1-konsep-dasar)
2. [Jenis Autentikasi & Permission](#2-jenis-autentikasi)
3. [Mendaftarkan Aplikasi di Azure Portal](#3-registrasi-azure)
4. [Konfigurasi Client ID, Secret & Redirect URI](#4-konfigurasi)
5. [Authorization Code Flow (step-by-step)](#5-auth-flow)
6. [Mendapatkan Access Token](#6-access-token)
7. [Mengakses Email via Graph API](#7-akses-email)
8. [Menyimpan Email ke File Lokal](#8-simpan-lokal)
9. [Best Practices Keamanan](#9-best-practices)

---

## 1. Konsep Dasar

**Microsoft Graph API** adalah REST API terpusat Microsoft yang menyediakan akses ke
data dan layanan Microsoft 365: email (Outlook/Exchange), kalender, file (OneDrive),
Teams, SharePoint, dan lainnya — semuanya melalui satu endpoint:

```
https://graph.microsoft.com/v1.0/
```

### Mengapa Graph API (bukan IMAP/SMTP)?

| Aspek | IMAP/SMTP tradisional | Microsoft Graph API |
|---|---|---|
| Protokol | IMAP/POP3/SMTP | REST over HTTPS |
| Auth | Username + Password | OAuth 2.0 (tanpa password user) |
| Dukungan MFA | Tidak | Ya |
| Akses metadata | Terbatas | Lengkap (thread, attachment, flag, dll) |
| Modern auth | Tidak (deprecated) | Ya (standar industri) |
| Kemampuan AI | Sulit diintegrasikan | JSON response, mudah diproses LLM |

> Microsoft menonaktifkan Basic Auth (IMAP+password) untuk Microsoft 365 sejak Oktober 2022.
> Graph API adalah **satu-satunya cara modern** untuk akses email programatik.

---

## 2. Jenis Autentikasi & Permission

### 2.1 OAuth 2.0

Graph API menggunakan **OAuth 2.0** sebagai protokol autentikasi.
Aplikasi tidak pernah menyentuh password user — hanya token yang diterbitkan oleh
Microsoft Identity Platform (Azure AD / Entra ID).

### 2.2 Delegated vs Application Permissions

| | **Delegated Permission** | **Application Permission** |
|---|---|---|
| Konteks | Atas nama user yang login | Atas nama aplikasi itu sendiri |
| User harus login? | **Ya** | **Tidak** (background/daemon) |
| Consent | User atau admin | Admin wajib grant |
| Contoh scope | `Mail.Read`, `Mail.Send` | `Mail.Read` (tanpa `.Delegated`) |
| Use case | Web app, mobile app | Cron job, server-side automation |
| Token type | Access token + Refresh token | Access token only (client credentials) |

#### Kapan pakai mana?

```
User buka browser → login Microsoft → aplikasi akses email mereka
→ Gunakan DELEGATED permission (Authorization Code Flow)

Server berjalan tanpa user → baca semua mailbox tenant
→ Gunakan APPLICATION permission (Client Credentials Flow)
```

### 2.3 Flow yang Tersedia

| Flow | Kapan Digunakan |
|---|---|
| **Authorization Code** | Web app dengan user login (paling umum) |
| **Client Credentials** | Server-to-server, daemon, background service |
| **Device Code** | CLI / perangkat tanpa browser |
| **On-Behalf-Of** | API memanggil API lain atas nama user |

---

## 3. Mendaftarkan Aplikasi di Azure Portal

### Langkah-langkah

**1. Buka Azure Portal**
- Navigasi ke: https://portal.azure.com
- Masuk menggunakan akun Microsoft 365 / Azure

**2. Buka App Registrations**
```
Azure Active Directory (Entra ID)
  → App registrations
  → + New registration
```

**3. Isi form registrasi**

| Field | Nilai |
|---|---|
| Name | `MyEmailApp` (nama bebas) |
| Supported account types | Lihat tabel di bawah |
| Redirect URI | `http://localhost:3000/callback` (untuk dev) |

**Pilihan Supported Account Types:**

| Pilihan | Artinya |
|---|---|
| Single tenant | Hanya user dari organisasi ini |
| Multitenant | User dari organisasi Azure AD manapun |
| Multitenant + Personal | Termasuk akun Outlook.com personal |
| Personal only | Hanya akun Outlook.com personal |

**4. Klik Register** → catat **Application (client) ID** dan **Directory (tenant) ID**

**5. Tambah API Permissions**
```
App → API permissions
  → + Add a permission
  → Microsoft Graph
  → Delegated permissions
  → Cari dan centang:
      ✅ Mail.Read
      ✅ Mail.ReadWrite (jika perlu write/delete)
      ✅ Mail.Send (jika perlu kirim)
      ✅ offline_access (untuk refresh token)
      ✅ User.Read (untuk info profil user)
  → Add permissions
  → Grant admin consent (jika tenant Anda)
```

**6. Buat Client Secret**
```
App → Certificates & secrets
  → + New client secret
  → Description: "prod-secret"
  → Expires: 24 months (atau sesuai kebijakan)
  → Add
  → ⚠️ COPY VALUE sekarang — tidak bisa dilihat lagi setelah halaman di-refresh
```

---

## 4. Konfigurasi: Client ID, Secret & Redirect URI

### Environment Variables (`.env`)

```env
# Azure App Registration
AZURE_CLIENT_ID=9040a78f-1a07-4629-a0f6-09bc560704ef
AZURE_CLIENT_SECRET=Td08Q~uOQc-LMUZ-z-qPBodYPBis8O1mjBYr3auy
AZURE_TENANT_ID=common
# "common"         → semua Azure AD tenant + personal
# "organizations"  → hanya Azure AD (tanpa personal)
# "consumers"      → hanya personal (Outlook.com)
# "{tenant-id}"    → spesifik satu tenant organisasi

# Redirect URI — harus sama persis dengan yang didaftarkan di Azure
AZURE_REDIRECT_URI=http://localhost:3000/auth/callback

# Scope yang diminta
AZURE_SCOPES=openid profile email offline_access Mail.Read Mail.Send
```

### Penting: Redirect URI

- Harus **identik** (karakter per karakter) antara `.env` dan Azure Portal
- Beda trailing slash `/` → error
- `http://` hanya boleh untuk `localhost` — production wajib `https://`
- Bisa daftarkan **multiple** redirect URI di Azure Portal untuk dev/staging/prod

---

## 5. Authorization Code Flow (Step-by-Step)

```
┌──────────┐         ┌───────────────┐         ┌──────────────────────┐
│  Browser │         │  Aplikasi     │         │  Microsoft Identity  │
│  (User)  │         │  (Server)     │         │  Platform (Azure AD) │
└────┬─────┘         └──────┬────────┘         └──────────┬───────────┘
     │                      │                             │
     │  Klik "Login"        │                             │
     │─────────────────────>│                             │
     │                      │                             │
     │                      │  Redirect ke auth URL       │
     │<─────────────────────│────────────────────────────>│
     │                      │                             │
     │  User login + consent│                             │
     │─────────────────────────────────────────────────-->│
     │                      │                             │
     │  Redirect ke callback dengan ?code=AUTH_CODE        │
     │<────────────────────────────────────────────────────│
     │                      │                             │
     │  POST AUTH_CODE       │                             │
     │─────────────────────>│                             │
     │                      │  POST /token (code)         │
     │                      │────────────────────────────>│
     │                      │                             │
     │                      │  { access_token,            │
     │                      │    refresh_token,           │
     │                      │    expires_in }             │
     │                      │<────────────────────────────│
     │                      │                             │
     │  Simpan token ke DB  │                             │
     │                      │                             │
```

### Step 1 — Build Authorization URL

```python
import urllib.parse

params = {
    "client_id":     CLIENT_ID,
    "response_type": "code",
    "redirect_uri":  REDIRECT_URI,
    "response_mode": "query",
    "scope":         "openid profile email offline_access Mail.Read Mail.Send",
    "state":         "random_csrf_string",  # CSRF protection
    "prompt":        "select_account",      # paksa pilih akun
}

auth_url = (
    f"https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/authorize?"
    + urllib.parse.urlencode(params)
)

# Redirect user ke auth_url
print(auth_url)
```

```javascript
// Node.js
const params = new URLSearchParams({
  client_id:     process.env.AZURE_CLIENT_ID,
  response_type: 'code',
  redirect_uri:  process.env.AZURE_REDIRECT_URI,
  response_mode: 'query',
  scope:         'openid profile email offline_access Mail.Read Mail.Send',
  state:         crypto.randomBytes(16).toString('hex'),
  prompt:        'select_account',
});

const authUrl = `https://login.microsoftonline.com/${process.env.AZURE_TENANT_ID}/oauth2/v2.0/authorize?${params}`;
res.redirect(authUrl);
```

---

## 6. Mendapatkan Access Token

### Step 2 — Tukar Authorization Code dengan Token

Setelah user login, Microsoft redirect ke callback URL dengan query param `?code=...`.

```python
# Python — callback handler
import requests

def handle_callback(auth_code: str) -> dict:
    token_url = f"https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/token"

    data = {
        "grant_type":    "authorization_code",
        "client_id":     CLIENT_ID,
        "client_secret": CLIENT_SECRET,
        "code":          auth_code,
        "redirect_uri":  REDIRECT_URI,
        "scope":         "openid profile email offline_access Mail.Read Mail.Send",
    }

    response = requests.post(token_url, data=data)
    response.raise_for_status()

    tokens = response.json()
    # {
    #   "access_token":  "eyJ0eXAiOiJKV1QiLCJhbGci...",   ← pakai untuk API
    #   "refresh_token": "M.C3_BAY...",                    ← simpan ke DB
    #   "expires_in":    3600,                             ← detik (biasanya 1 jam)
    #   "token_type":    "Bearer",
    #   "scope":         "Mail.Read Mail.Send offline_access openid profile email"
    # }
    return tokens
```

```javascript
// Node.js
const axios = require('axios');

async function handleCallback(authCode) {
  const response = await axios.post(
    `https://login.microsoftonline.com/${process.env.AZURE_TENANT_ID}/oauth2/v2.0/token`,
    new URLSearchParams({
      grant_type:    'authorization_code',
      client_id:     process.env.AZURE_CLIENT_ID,
      client_secret: process.env.AZURE_CLIENT_SECRET,
      code:          authCode,
      redirect_uri:  process.env.AZURE_REDIRECT_URI,
      scope:         'openid profile email offline_access Mail.Read Mail.Send',
    }),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
  );
  return response.data;
}
```

### Step 3 — Refresh Token (saat access token expired)

Access token berlaku **1 jam**. Refresh token berlaku hingga **90 hari** (atau sampai dicabut).

```python
def refresh_access_token(refresh_token: str) -> dict:
    response = requests.post(
        f"https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/token",
        data={
            "grant_type":    "refresh_token",
            "client_id":     CLIENT_ID,
            "client_secret": CLIENT_SECRET,
            "refresh_token": refresh_token,
            "scope":         "openid profile email offline_access Mail.Read Mail.Send",
        }
    )
    response.raise_for_status()
    return response.json()
    # Response berisi access_token baru dan refresh_token baru
    # ⚠️ Simpan refresh_token yang baru — yang lama tidak valid lagi
```

### Client Credentials Flow (tanpa user)

```python
def get_app_token() -> str:
    """Untuk daemon/server — tidak memerlukan user login."""
    response = requests.post(
        f"https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/token",
        data={
            "grant_type":    "client_credentials",
            "client_id":     CLIENT_ID,
            "client_secret": CLIENT_SECRET,
            "scope":         "https://graph.microsoft.com/.default",
        }
    )
    return response.json()["access_token"]
    # Tidak ada refresh_token — minta token baru setiap kali expired
```

---

## 7. Mengakses Email via Graph API

### Base URL & Header

```
Base URL : https://graph.microsoft.com/v1.0
Header   : Authorization: Bearer {access_token}
           Content-Type: application/json
```

### 7.1 Baca Daftar Email (Inbox)

```python
def get_inbox_messages(access_token: str, top: int = 20) -> list:
    headers = {"Authorization": f"Bearer {access_token}"}

    params = {
        "$top":     top,
        "$orderby": "receivedDateTime desc",
        "$select":  "id,subject,from,toRecipients,ccRecipients,receivedDateTime,bodyPreview,hasAttachments,isRead",
        "$filter":  "isRead eq false",  # hanya yang belum dibaca (opsional)
    }

    response = requests.get(
        "https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages",
        headers=headers,
        params=params
    )
    response.raise_for_status()

    data = response.json()
    return data.get("value", [])
    # "@odata.nextLink" tersedia jika ada halaman berikutnya (pagination)
```

### 7.2 Baca Detail Email + Body

```python
def get_message_detail(access_token: str, message_id: str) -> dict:
    headers = {
        "Authorization": f"Bearer {access_token}",
        "Prefer":        'outlook.body-content-type="html"',  # minta body dalam HTML
    }

    response = requests.get(
        f"https://graph.microsoft.com/v1.0/me/messages/{message_id}",
        headers=headers,
        params={
            "$select": "id,subject,from,toRecipients,ccRecipients,receivedDateTime,body,hasAttachments,conversationId,internetMessageId"
        }
    )
    response.raise_for_status()
    return response.json()
```

**Contoh Response:**
```json
{
  "id": "AAMkAGI2...",
  "subject": "Laporan Bulanan",
  "from": {
    "emailAddress": { "name": "Budi", "address": "budi@contoso.com" }
  },
  "receivedDateTime": "2026-04-01T08:30:00Z",
  "body": {
    "contentType": "html",
    "content": "<html><body>Isi email...</body></html>"
  },
  "hasAttachments": true,
  "conversationId": "AAQkAGI2...",
  "internetMessageId": "<CAFvH...@mail.gmail.com>"
}
```

### 7.3 Baca Attachment

```python
def get_attachments(access_token: str, message_id: str) -> list:
    headers = {"Authorization": f"Bearer {access_token}"}

    response = requests.get(
        f"https://graph.microsoft.com/v1.0/me/messages/{message_id}/attachments",
        headers=headers
    )
    response.raise_for_status()
    return response.json().get("value", [])

def download_attachment(attachment: dict) -> bytes:
    """Decode base64 content dari attachment."""
    import base64
    return base64.b64decode(attachment["contentBytes"])
```

### 7.4 Kirim Email

```python
def send_email(access_token: str, to: str, subject: str, body_html: str, cc: list = []) -> bool:
    headers = {
        "Authorization": f"Bearer {access_token}",
        "Content-Type":  "application/json",
    }

    payload = {
        "message": {
            "subject": subject,
            "body": {
                "contentType": "HTML",
                "content":     body_html,
            },
            "toRecipients": [{"emailAddress": {"address": to}}],
            "ccRecipients": [{"emailAddress": {"address": e}} for e in cc],
        },
        "saveToSentItems": True,
    }

    response = requests.post(
        "https://graph.microsoft.com/v1.0/me/sendMail",
        headers=headers,
        json=payload
    )
    return response.status_code == 202
```

### 7.5 Tandai Email Sudah Dibaca

```python
def mark_as_read(access_token: str, message_id: str):
    requests.patch(
        f"https://graph.microsoft.com/v1.0/me/messages/{message_id}",
        headers={
            "Authorization": f"Bearer {access_token}",
            "Content-Type":  "application/json"
        },
        json={"isRead": True}
    )
```

### 7.6 Pagination (banyak email)

```python
def get_all_messages(access_token: str) -> list:
    headers = {"Authorization": f"Bearer {access_token}"}
    url     = "https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?$top=50"
    all_messages = []

    while url:
        response = requests.get(url, headers=headers)
        response.raise_for_status()
        data = response.json()
        all_messages.extend(data.get("value", []))
        url = data.get("@odata.nextLink")  # None jika sudah habis

    return all_messages
```

---

## 8. Menyimpan Email ke File Lokal

### 8.1 Simpan sebagai JSON

```python
import json, os
from datetime import datetime

def save_email_as_json(message: dict, output_dir: str = "./emails"):
    os.makedirs(output_dir, exist_ok=True)

    # Nama file dari tanggal + ID
    date_str   = message.get("receivedDateTime", "")[:10]  # "2026-04-01"
    message_id = message.get("id", "unknown")[:16]
    filename   = f"{date_str}_{message_id}.json"
    filepath   = os.path.join(output_dir, filename)

    with open(filepath, "w", encoding="utf-8") as f:
        json.dump(message, f, ensure_ascii=False, indent=2)

    print(f"Saved: {filepath}")
    return filepath
```

### 8.2 Simpan sebagai .eml (RFC 2822)

```python
import email, base64
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders

def save_email_as_eml(message: dict, attachments: list, output_dir: str = "./emails"):
    os.makedirs(output_dir, exist_ok=True)

    msg = MIMEMultipart("mixed")
    msg["Subject"] = message.get("subject", "(no subject)")
    msg["From"]    = message["from"]["emailAddress"]["address"]
    msg["To"]      = ", ".join(
        r["emailAddress"]["address"] for r in message.get("toRecipients", [])
    )
    msg["Date"]       = message.get("receivedDateTime", "")
    msg["Message-ID"] = message.get("internetMessageId", "")

    # Body
    body_content = message.get("body", {}).get("content", "")
    body_type    = message.get("body", {}).get("contentType", "text").lower()
    mime_subtype = "html" if body_type == "html" else "plain"
    msg.attach(MIMEText(body_content, mime_subtype, "utf-8"))

    # Attachments
    for att in attachments:
        if att.get("@odata.type") != "#microsoft.graph.fileAttachment":
            continue
        part = MIMEBase("application", "octet-stream")
        part.set_payload(base64.b64decode(att["contentBytes"]))
        encoders.encode_base64(part)
        part.add_header("Content-Disposition", f'attachment; filename="{att["name"]}"')
        msg.attach(part)

    # Simpan
    date_str = message.get("receivedDateTime", "")[:10]
    msg_id   = message.get("id", "unknown")[:16]
    filepath = os.path.join(output_dir, f"{date_str}_{msg_id}.eml")

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(msg.as_string())

    return filepath
```

### 8.3 Pipeline Lengkap: Fetch → Simpan

```python
def sync_inbox_to_disk(access_token: str, output_dir: str = "./emails"):
    """Ambil semua email inbox dan simpan ke disk."""
    messages = get_inbox_messages(access_token, top=50)

    for msg_summary in messages:
        msg_id = msg_summary["id"]

        # Ambil detail lengkap termasuk body
        detail = get_message_detail(access_token, msg_id)

        # Simpan metadata + body sebagai JSON
        save_email_as_json(detail, output_dir)

        # Jika ada attachment, download dan simpan
        if detail.get("hasAttachments"):
            attachments = get_attachments(access_token, msg_id)
            att_dir     = os.path.join(output_dir, msg_id[:16], "attachments")
            os.makedirs(att_dir, exist_ok=True)

            for att in attachments:
                if "contentBytes" in att:
                    content  = base64.b64decode(att["contentBytes"])
                    att_path = os.path.join(att_dir, att["name"])
                    with open(att_path, "wb") as f:
                        f.write(content)
                    print(f"  Attachment: {att_path}")

        # Tandai sudah dibaca
        mark_as_read(access_token, msg_id)

    print(f"Synced {len(messages)} messages to {output_dir}")
```

### 8.4 Struktur Folder Output

```
emails/
├── 2026-04-01_AAMkAGI2abc1.json          ← metadata + body
├── 2026-04-01_AAMkAGI2abc1.eml           ← format email standar
├── 2026-04-01_AAMkAGI2abc2.json
└── AAMkAGI2abc1/
    └── attachments/
        ├── laporan_maret.xlsx
        └── foto_bukti.png
```

---

## 9. Best Practices Keamanan

### 9.1 Penyimpanan Token

```
❌ JANGAN simpan token di:
   - Source code / hardcode
   - Git repository
   - Log file
   - localStorage browser (XSS risk)

✅ SIMPAN token di:
   - Environment variables (.env) — tidak di-commit ke git
   - Database terenkripsi (kolom ENCRYPTED atau AES)
   - Secret manager: Azure Key Vault, AWS Secrets Manager, HashiCorp Vault
   - Server-side session (bukan cookie)
```

### 9.2 Scope Minimalis (Principle of Least Privilege)

```python
# ❌ Terlalu luas
scope = "https://graph.microsoft.com/.default"

# ✅ Minta hanya yang diperlukan
scope = "Mail.Read offline_access"              # hanya baca
scope = "Mail.Read Mail.Send offline_access"    # baca + kirim
```

### 9.3 Refresh Token Management

```python
import time

class TokenManager:
    def __init__(self, db):
        self.db = db

    def get_valid_token(self, user_id: str) -> str:
        token_data = self.db.get_token(user_id)

        # Cek apakah access_token masih valid (dengan buffer 5 menit)
        expires_at = token_data["expires_at"]
        if time.time() < expires_at - 300:
            return token_data["access_token"]

        # Expired → refresh
        new_tokens = refresh_access_token(token_data["refresh_token"])

        # ⚠️ Simpan KEDUA token baru (access + refresh)
        self.db.save_token(user_id, {
            "access_token":  new_tokens["access_token"],
            "refresh_token": new_tokens["refresh_token"],  # refresh token juga baru!
            "expires_at":    time.time() + new_tokens["expires_in"],
        })

        return new_tokens["access_token"]
```

### 9.4 State Parameter (CSRF Protection)

```python
import secrets

def start_auth_flow(session):
    state = secrets.token_hex(16)
    session["oauth_state"] = state  # simpan di server-side session
    # Sertakan state di auth URL

def handle_callback(request, session):
    if request.args.get("state") != session.get("oauth_state"):
        raise ValueError("CSRF detected — state mismatch")
    # Lanjut proses token
```

### 9.5 Enkripsi Token di Database

```python
from cryptography.fernet import Fernet

FERNET_KEY = os.environ["TOKEN_ENCRYPTION_KEY"]  # 32-byte base64 key
cipher     = Fernet(FERNET_KEY)

def encrypt_token(token: str) -> str:
    return cipher.encrypt(token.encode()).decode()

def decrypt_token(encrypted: str) -> str:
    return cipher.decrypt(encrypted.encode()).decode()

# Simpan: encrypt_token(access_token) → ke DB
# Baca:   decrypt_token(db_value) → access_token asli
```

### 9.6 Rotasi Secret & Monitoring

```
✅ Praktik yang disarankan:
   - Rotasi Client Secret setiap 6-12 bulan (set reminder sebelum expired)
   - Monitor Azure AD sign-in logs untuk aktivitas mencurigakan
   - Gunakan Conditional Access Policy untuk membatasi akses berdasarkan IP/device
   - Aktifkan alerting jika ada token request dari lokasi tidak biasa
   - Hapus token dari DB saat user disconnect / logout
   - Audit log setiap akses email yang dilakukan atas nama user
```

### 9.7 Rate Limiting & Retry

Graph API memiliki throttling. Handle `429 Too Many Requests`:

```python
import time

def api_request_with_retry(url: str, headers: dict, max_retries: int = 3) -> dict:
    for attempt in range(max_retries):
        response = requests.get(url, headers=headers)

        if response.status_code == 429:
            retry_after = int(response.headers.get("Retry-After", 10))
            print(f"Rate limited. Waiting {retry_after}s...")
            time.sleep(retry_after)
            continue

        if response.status_code == 401:
            # Token expired — refresh dan retry
            raise TokenExpiredError()

        response.raise_for_status()
        return response.json()

    raise Exception("Max retries exceeded")
```

---

## Ringkasan Flow Lengkap

```
[User]
  │
  ├─ 1. Klik "Connect Email"
  │
  ▼
[Aplikasi] → Build auth URL dengan scope → Redirect ke Microsoft
  │
  ▼
[Microsoft Login Page]
  │
  ├─ User login + grant permission
  │
  ▼
[Callback URL] ← ?code=AUTH_CODE&state=...
  │
  ├─ Validasi state (CSRF check)
  ├─ POST /token dengan AUTH_CODE
  ├─ Terima { access_token, refresh_token, expires_in }
  ├─ Enkripsi & simpan ke DB
  │
  ▼
[Gunakan access_token]
  │
  ├─ GET /me/mailFolders/inbox/messages → list email
  ├─ GET /me/messages/{id}             → detail email + body
  ├─ GET /me/messages/{id}/attachments → attachment
  ├─ POST /me/sendMail                 → kirim email
  │
  ▼
[Saat token expired]
  │
  ├─ POST /token dengan refresh_token
  ├─ Simpan access_token + refresh_token baru
  └─ Lanjut akses API
```

---

## Referensi

| Sumber | URL |
|---|---|
| Microsoft Graph Docs | https://learn.microsoft.com/graph/overview |
| Graph API Explorer | https://developer.microsoft.com/graph/graph-explorer |
| Azure Portal | https://portal.azure.com |
| Token endpoint reference | https://learn.microsoft.com/entra/identity-platform/v2-oauth2-auth-code-flow |
| Mail API reference | https://learn.microsoft.com/graph/api/resources/mail-api-overview |
| Permissions reference | https://learn.microsoft.com/graph/permissions-reference |
