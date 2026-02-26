# EcoSystem Project Memory

> **Proyek:** EcoSystem — PT Eclectic Consulting Yogyakarta
> **Stack:** Laravel 11 + PHP + Blade + Tailwind CSS + MySQL
> **Path:** `d:\Magang\PT Eclectic Consulting Yogyakarta\Project\Master\EcoSystem-main`

---

## KONTEKS SISTEM (PENTING)

EcoSystem adalah **Employee/Admin Side**.
Ada project terpisah yang menjadi **Customer Side** dan mengonsumsi API yang sama.

- **EcoSystem** → diakses karyawan & admin untuk mengelola ticket, delivery, project
- **Customer Project** → diakses customer untuk submit & memantau ticket mereka
- Keduanya berbagi database + tabel `auth_users` yang sama

Lihat detail lengkap di: [architecture.md](architecture.md)

---

## AUTENTIKASI (CUSTOM — BUKAN LARAVEL AUTH)

- Tabel sentral: `auth_users` (bukan `users`)
- Login via: email, username, atau phone
- Token disimpan di Laravel session (`session('auth_token')`, `session('user')`)
- Middleware: `CheckAuthToken` (bukan `auth`)
- **JANGAN** gunakan `Auth::user()` atau `auth()->user()` — selalu gunakan `session('user')`

### is_already_cp Flow
- `is_already_cp = false` → akun baru, wajib verifikasi email + set password sebelum bisa login
- `is_already_cp = true` → bisa login normal
- Kolom terkait: `cp_token`, `cp_token_expires_at` di `auth_users`
- Controller: `PasswordSetupController.php`

---

## POLA PENTING

- Views: `@extends('dashboard')`, `@section('content')`, `@push('scripts')`
- AJAX JSON response (bukan redirect) untuk form di dalam dashboard
- Route profile: `route('profile.edit')` → `/profile`
- Kirim email: **Microsoft Graph API** (bukan SMTP/Mailables) → `EmailController::getAccessToken()`

---

## REFERENSI CEPAT

| Kebutuhan | File |
|---|---|
| Auth login | `app/Http/Controllers/AuthController.php` |
| Password setup/reset | `app/Http/Controllers/PasswordSetupController.php` |
| Profil user | `resources/views/profile/edit.blade.php` |
| Ticket (employee) | `app/Http/Controllers/TicketController.php` |
| Email inbox processor | `app/Http/Controllers/EmailController.php` |
| Delivery support | `app/Http/Controllers/Delivery/DeliverySupportController.php` |
| Routes utama | `routes/web.php`, `routes/api.php`, `routes/delivery-support.php` |
