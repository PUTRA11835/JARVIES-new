# Dokumentasi: Change Password / Password Setup Flow

> Diimplementasikan di: **EcoSystem** (employee side)
> Akan diadaptasi untuk: **Jarvies** (customer side)
> Shared database & tabel `auth_users`

---

## Konsep Utama

Ada **dua skenario** pengiriman email reset/setup password, keduanya pakai mekanisme yang sama:

| Skenario | Type | Trigger |
|---|---|---|
| Akun baru belum set password | `setup` | User coba login → `is_already_cp = false` |
| Lupa password (existing user) | `reset` | User klik "Forgot Password" → isi email |

---

## Kolom Kunci di `auth_users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `is_already_cp` | `boolean` | `false` = akun baru, wajib set password dulu sebelum bisa login |
| `cp_token` | `string\|null` | Token random 64 char, dikirim di URL email link |
| `cp_token_expires_at` | `timestamp\|null` | Kadaluarsa 24 jam setelah token dibuat |
| `email` | `string\|null` | Tujuan pengiriman email (nullable di `auth_users`) |

---

## Flow Diagram

```
[Akun Baru]                          [Forgot Password]
     │                                      │
User coba login                   User isi form /forgot-password
     │                                      │
is_already_cp = false?            Cari auth_users by email
     │                                      │
Ya → generateAndSendToken()       generateAndSendToken(type='reset')
     │                                      │
     └──────────────┬───────────────────────┘
                    │
          Token random 64 char dibuat
          Simpan ke auth_users.cp_token
          Expiry: now() + 24 jam
                    │
          Kirim email via MS Graph API
          Link: /change-password?token={token}
                    │
          User klik link → showChangePassword()
          Validasi: token ada & belum expired
                    │
          User isi form → submitChangePassword()
          Hash::make(password) → simpan
          is_already_cp = true
          cp_token = null (hapus token)
                    │
          Redirect ke /login dengan flash success
```

---

## File yang Terlibat (EcoSystem)

### Controller
**`app/Http/Controllers/PasswordSetupController.php`**

| Method | Route | Fungsi |
|---|---|---|
| `showCheckEmail(Request)` | GET `/verify-email?email=&type=` | Halaman "cek email Anda" setelah kirim link |
| `showChangePassword(Request)` | GET `/change-password?token=` | Form set/reset password dari link email |
| `submitChangePassword(Request)` | POST `/change-password` | Proses simpan password baru |
| `showForgotPassword()` | GET `/forgot-password` | Form input email forgot password |
| `submitForgotPassword(Request)` | POST `/forgot-password` | Proses kirim email reset |
| `generateAndSendToken(authUser, type)` *(static)* | — | Helper: generate token + kirim email via Graph |
| `getGraphToken()` *(static private)* | — | OAuth2 client_credentials ke Microsoft |
| `maskEmail(email)` *(private)* | — | `ab***@domain.com` untuk tampilan |

### Routes (public — tidak perlu auth)
```php
// routes/web.php (baris 44-55)
Route::get('/verify-email',    [...'showCheckEmail'])->name('password-setup.check-email');
Route::get('/change-password', [...'showChangePassword'])->name('password-setup.change');
Route::post('/change-password',[...'submitChangePassword'])->name('password-setup.submit');
Route::get('/forgot-password', [...'showForgotPassword'])->name('password-setup.forgot');
Route::post('/forgot-password',[...'submitForgotPassword'])->name('password-setup.forgot.submit');
```

### Views
| File | Fungsi |
|---|---|
| `resources/views/auth/forgot-password.blade.php` | Form input email lupa password |
| `resources/views/auth/check-email.blade.php` | Halaman konfirmasi "cek email" (type=setup/reset) |
| `resources/views/auth/change-password.blade.php` | Form set/reset password + strength checker JS |

### Trigger dari AuthController
```php
// app/Http/Controllers/AuthController.php (sekitar baris 182-196)
if (!$authUser->is_already_cp) {
    PasswordSetupController::generateAndSendToken($authUser); // type='setup' (default)
    // Redirect ke /verify-email dengan masked email
}
```

---

## Detail `generateAndSendToken()` — Yang Paling Penting

```php
public static function generateAndSendToken(object $authUser, string $type = 'setup'): void
{
    // 1. Buat token & simpan ke DB
    $token   = Str::random(64);
    $expires = now()->addHours(24);
    DB::table('auth_users')->where('id', $authUser->id)->update([
        'cp_token'            => $token,
        'cp_token_expires_at' => $expires,
    ]);

    // 2. Build URL link
    $link = url('/change-password?token=' . $token);
    // ⚠️ JARVIES: link harus mengarah ke domain Jarvies, bukan EcoSystem!
    // Contoh: url('https://jarvies.domain.com/change-password?token=' . $token)
    // ATAU: gunakan env variable JARVIES_URL

    // 3. Kirim email via Microsoft Graph API
    $sender     = env('MS_SENDER_EMAIL');
    $graphToken = self::getGraphToken();
    Http::withToken($graphToken)->post(
        env('GRAPH_BASE_URL') . "/users/{$sender}/sendMail",
        [
            'message' => [
                'subject'      => $subject,
                'body'         => ['contentType' => 'HTML', 'content' => $body],
                'toRecipients' => [['emailAddress' => ['address' => $authUser->email]]],
            ],
            'saveToSentItems' => true,
        ]
    );
}
```

**Email body (HTML):**
- **type=setup**: Subject `"EcoSystem Account Activation — Set Your Password"`, tombol "Set My Password"
- **type=reset**: Subject `"Reset Your EcoSystem Password"`, tombol "Reset My Password"

---

## Logika Validasi Token di `showChangePassword` dan `submitChangePassword`

```php
$authUser = DB::table('auth_users')
    ->where('cp_token', $token)
    ->where('cp_token_expires_at', '>', now())  // belum expired
    ->first();

if (!$authUser) {
    // Token tidak valid atau sudah expired
    return redirect()->route('login')->with('error', 'This link has expired.');
}
```

Setelah password berhasil disimpan:
```php
DB::table('auth_users')->where('id', $authUser->id)->update([
    'password'            => Hash::make($request->password),
    'is_already_cp'       => true,   // unlock akun
    'cp_token'            => null,   // invalidate token (one-time use)
    'cp_token_expires_at' => null,
]);
```

---

## Env Variables yang Dibutuhkan

```env
MS_TENANT_ID=...
MS_CLIENT_ID=...
MS_CLIENT_SECRET=...
MS_SENDER_EMAIL=helpdesk@pt-eclectic.com
GRAPH_BASE_URL=https://graph.microsoft.com/v1.0
```

---

## Panduan Adaptasi untuk JARVIES

### Yang perlu dibuat di Jarvies:

1. **Routes** (public, tidak perlu auth):
   ```
   GET  /change-password   → tampilkan form set password (dari link email)
   POST /change-password   → proses simpan password baru
   GET  /forgot-password   → tampilkan form input email
   POST /forgot-password   → proses kirim email reset
   GET  /verify-email      → halaman "cek email Anda"
   ```

2. **Controller** — Bisa copy `PasswordSetupController.php`, lalu:
   - Ubah `$link = url('/change-password?token=' . $token)` → gunakan URL domain Jarvies
   - Ubah nama app: `config('app.name')` atau hardcode `'Jarvies'`
   - Ubah redirect setelah sukses: ke `/login` versi Jarvies
   - Ubah route names sesuai Jarvies

3. **Views** — Buat ulang 3 view (bisa adaptasi dari EcoSystem):
   - `forgot-password.blade.php` — form email, branding Jarvies
   - `check-email.blade.php` — konfirmasi kirim email, branding Jarvies
   - `change-password.blade.php` — form set password + strength checker

4. **Env variables** — Sama persis dengan EcoSystem (shared MS365 credentials)

5. **AuthController Jarvies** — Tambahkan pengecekan `is_already_cp`:
   ```php
   if (!$authUser->is_already_cp) {
       PasswordSetupController::generateAndSendToken($authUser, 'setup');
       // Redirect ke /verify-email
   }
   ```

### Poin kritis — URL link di email:

Karena EcoSystem dan Jarvies domain berbeda, **link di email harus mengarah ke Jarvies**, bukan EcoSystem. Gunakan env variable:
```env
# Di EcoSystem .env (untuk email yang dikirim ke customer/Jarvies user)
JARVIES_URL=https://jarvies.yourdomain.com
```

Lalu di `generateAndSendToken()`:
```php
$link = env('JARVIES_URL', url('')) . '/change-password?token=' . $token;
```

Atau: buat method/class terpisah di Jarvies yang override `$link`.

### Catatan keamanan:
- Token valid 24 jam — one-time use (langsung dihapus setelah dipakai)
- `is_already_cp = false` memblokir login sampai password di-set
- Email customer bisa `null` di `auth_users` → selalu cek sebelum kirim
- Jangan expose apakah email terdaftar atau tidak (gunakan respons generik di forgot password)

---

## Referensi File Jarvies (saat diimplementasikan)

*Isi kolom ini setelah implementasi selesai di Jarvies.*

| File Jarvies | Keterangan |
|---|---|
| *(belum dibuat)* | Controller: PasswordSetupController.php |
| *(belum dibuat)* | View: forgot-password |
| *(belum dibuat)* | View: check-email |
| *(belum dibuat)* | View: change-password |
