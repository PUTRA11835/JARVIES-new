# JARVIES — Dokumentasi Perubahan yang Perlu Diimplementasi Ulang

Dokumen ini dibuat agar perubahan dapat diimplementasi ulang setelah rekan melakukan commit/push.
Semua perubahan berkaitan dengan **Mobile API (Flutter)** dan **fix bug pengiriman email**.

---

## Daftar File yang Diubah

1. `app/Http/Middleware/ApiAuth.php`
2. `routes/api.php`
3. `app/Http/Controllers/Api/TicketController.php`
4. `app/Http/Controllers/TicketController.php`

---

## 1. `app/Http/Middleware/ApiAuth.php`

**Masalah:** Field `email` di `api_user` diambil dari `customer.email` (email perusahaan),
bukan dari `auth_users.email` (email login). Akibatnya email tiket terkirim ke alamat yang salah.

**Fix:** Join tabel `auth_users` dan ambil `login_email` sebagai prioritas.

### Cari bagian ini (query DB):

```php
$customer = DB::table('customer as c')
    ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
    ->where('c.customer_code', $customerCode)
    ->where('c.is_active', true)
    ->select(
        'c.customer_id',
        'c.customer_code',
        'c.email',
        'cb.title',
        'cb.name_1',
        'cb.name_2',
        'cb.customer_category',
        'cb.customer_group'
    )
    ->first();
```

### Ganti dengan:

```php
$customer = DB::table('customer as c')
    ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
    ->leftJoin('auth_users as au', 'au.customer_id', '=', 'c.customer_id')
    ->where('c.customer_code', $customerCode)
    ->where('c.is_active', true)
    ->select(
        'c.customer_id',
        'c.customer_code',
        'c.email as company_email',
        'cb.title',
        'cb.name_1',
        'cb.name_2',
        'cb.customer_category',
        'cb.customer_group',
        'au.email as login_email'
    )
    ->first();
```

### Cari bagian inject `api_user`:

```php
$companyName = trim($customer->title . ' ' . $customer->name_1 . ' ' . ($customer->name_2 ?? ''));

// Inject data user ke request agar bisa diakses controller
$request->attributes->set('api_user', [
    'id'            => $customer->customer_id,
    'type'          => 'customer',
    'customer_code' => $customer->customer_code,
    'company_name'  => $companyName,
    'email'         => $customer->email,
    'category'      => $customer->customer_category,
    'group'         => $customer->customer_group,
    'role'          => ['id' => 3, 'name' => 'Customer'],
]);
```

### Ganti dengan:

```php
$companyName = trim($customer->title . ' ' . $customer->name_1 . ' ' . ($customer->name_2 ?? ''));

// Gunakan login_email (auth_users) untuk pengiriman email — sama dengan web UI
// Fallback ke company_email jika auth_users tidak ada
$email = $customer->login_email ?? $customer->company_email;

// Inject data user ke request agar bisa diakses controller
$request->attributes->set('api_user', [
    'id'            => $customer->customer_id,
    'type'          => 'customer',
    'customer_code' => $customer->customer_code,
    'company_name'  => $companyName,
    'email'         => $email,
    'category'      => $customer->customer_category,
    'group'         => $customer->customer_group,
    'role'          => ['id' => 3, 'name' => 'Customer'],
]);
```

---

## 2. `routes/api.php`

**Tambahan:** Route baru `POST /api/tickets/submit-with-email` untuk full flow (email + staging EcoSystem).

### Cari bagian ini:

```php
Route::prefix('tickets')->group(function () {
    Route::get('/',       [TicketController::class, 'index']);   // List tiket saya
    Route::post('/',      [TicketController::class, 'store']);   // Buat tiket baru (→ staging)
    Route::get('staging', [TicketController::class, 'staging']); // List staging tiket

    Route::get('{id}',              [TicketController::class, 'show']);        // Detail tiket
    Route::get('{id}/messages',     [TicketController::class, 'messages']);    // List pesan
    Route::post('{id}/messages',    [TicketController::class, 'sendMessage']); // Kirim pesan
});
```

### Ganti dengan:

```php
Route::prefix('tickets')->group(function () {
    Route::get('/',                  [TicketController::class, 'index']);          // List tiket saya
    Route::post('/',                 [TicketController::class, 'store']);          // Buat tiket baru (→ staging, tanpa email)
    Route::post('submit-with-email', [TicketController::class, 'storeWithEmail']); // Buat tiket + kirim email (full flow)
    Route::get('staging',            [TicketController::class, 'staging']);        // List staging tiket

    Route::get('{id}',              [TicketController::class, 'show']);        // Detail tiket
    Route::get('{id}/messages',     [TicketController::class, 'messages']);    // List pesan
    Route::post('{id}/messages',    [TicketController::class, 'sendMessage']); // Kirim pesan
});
```

---

## 3. `app/Http/Controllers/Api/TicketController.php`

### 3a. Tambah import di bagian atas file

**Cari:**
```php
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\StagingTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
```

**Ganti dengan:**
```php
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\GraphRelayService;
use App\Services\StagingTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
```

---

### 3b. Tambah method `storeWithEmail`

Sisipkan method berikut **sebelum** komentar `// ─── STAGING ───` (setelah method `store()`).

```php
/**
 * POST /api/tickets/submit-with-email
 * Buat tiket baru dengan alur lengkap:
 *   1. Kirim email ke customer via Microsoft Graph API
 *   2. POST ke EcoSystem /jarvies/staging-tickets dengan channel=email
 *
 * Endpoint ini untuk keperluan testing Postman — identik dengan alur web UI.
 *
 * Body (multipart/form-data atau JSON):
 *   description      (required)
 *   ticket_priority  (nullable: Low|Medium|High)
 *   body             (nullable, isi pesan plain text)
 *   body_html        (nullable, isi pesan HTML dari Quill)
 *   cc_emails[]      (nullable, array email CC)
 *   attachments[]    (nullable, file upload)
 */
public function storeWithEmail(Request $request)
{
    $user = $request->attributes->get('api_user');

    $validator = Validator::make($request->all(), [
        'description'     => 'required|string|max:5000',
        'ticket_priority' => 'nullable|in:Very High,High,Medium,Low',
        'body'            => 'nullable|string',
        'body_html'       => 'nullable|string',
        'cc_emails'       => 'nullable|array|max:10',
        'cc_emails.*'     => 'email',
        'attachments'     => 'nullable|array',
        'attachments.*'   => 'file|max:20480',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $validated   = $validator->validated();
    $senderName  = $user['company_name'] ?? $user['name'] ?? null;
    $senderEmail = $user['email'] ?? null;

    if (!$senderEmail) {
        return response()->json([
            'success' => false,
            'message' => 'Akun Anda tidak memiliki email. Hubungi administrator.',
        ], 422);
    }

    $ccEmails = array_values(array_filter($validated['cc_emails'] ?? []));

    // Body HTML; fallback ke plain text
    $bodyHtml = $validated['body_html'] ?? $validated['body'] ?? '';
    if ($bodyHtml && !str_starts_with(ltrim($bodyHtml), '<')) {
        $bodyHtml = '<p>' . nl2br(htmlspecialchars($bodyHtml)) . '</p>';
    }

    // ── STEP 1: Ekstrak inline images dari body HTML (data URI → cid:) ──
    $emailInlineImages = [];
    $emailBodyHtml     = $bodyHtml;

    if ($emailBodyHtml) {
        preg_match_all(
            '/<img[^>]+src="(data:(image\/[a-zA-Z+\-]+);base64,([A-Za-z0-9+\/=\s]+))"[^>]*>/i',
            $emailBodyHtml,
            $imgMatches,
            PREG_SET_ORDER
        );
        foreach ($imgMatches as $i => $m) {
            $mime    = strtolower($m[2]);
            $content = base64_decode(preg_replace('/\s+/', '', $m[3]));
            $ext     = ltrim(strrchr($mime, '/'), '/');
            $ext     = str_replace(['+xml', '+'], ['', '-'], $ext) ?: 'png';
            $cid     = 'img-' . ($i + 1) . '@jarvies';
            $imgName = 'image-' . ($i + 1) . '.' . $ext;

            $emailInlineImages[] = [
                'name'    => $imgName,
                'content' => $content,
                'mime'    => $mime,
                'cid'     => $cid,
            ];
            $emailBodyHtml = str_replace($m[1], 'cid:' . $cid, $emailBodyHtml);
        }
    }

    // ── STEP 2: Baca file attachments dari request ───────────────────────
    $emailFileAttachments = [];
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $emailFileAttachments[] = [
                'name'    => $file->getClientOriginalName(),
                'content' => file_get_contents($file->getRealPath()),
                'mime'    => $file->getMimeType() ?: 'application/octet-stream',
            ];
        }
    }

    // ── STEP 3: Kirim email via GraphRelayService ────────────────────────
    $emailSubject = '[Menunggu Validasi] ' . $validated['description'];

    $emailBody = '<p style="color:#555;margin-bottom:12px"><em>[Tiket baru dari '
        . htmlspecialchars($senderName ?? 'Customer')
        . ' via Jarvies]</em></p>'
        . '<div style="margin-bottom:16px"><strong>Description:</strong>'
        . '<div style="margin-top:8px;padding:12px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:4px">'
        . ($emailBodyHtml ?: '<p><em>(Tidak ada pesan)</em></p>')
        . '</div></div>';

    try {
        $graphService = new GraphRelayService();
        $emailResult  = $graphService->sendStandaloneEmail(
            $senderEmail,
            $emailSubject,
            $emailBody,
            $ccEmails,
            $emailInlineImages,
            $emailFileAttachments
        );
    } catch (\Exception $e) {
        Log::error('Mobile API: TicketController@storeWithEmail: Graph exception', [
            'error' => $e->getMessage(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim email: ' . $e->getMessage(),
        ], 500);
    }

    if (!$emailResult) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim email. Silakan coba lagi.',
        ], 500);
    }

    $internetMsgId = $emailResult['internet_message_id'];

    // ── STEP 4: POST ke EcoSystem /jarvies/staging-tickets ──────────────
    $multipart = [
        ['name' => 'description',         'contents' => $validated['description']],
        ['name' => 'customer_id',         'contents' => (string) $user['id']],
        ['name' => 'submitted_by_email',  'contents' => $senderEmail],
        ['name' => 'body',                'contents' => $emailBodyHtml ?: ''],
        ['name' => 'internet_message_id', 'contents' => $internetMsgId],
        ['name' => 'sender_name',         'contents' => $senderName ?? ''],
        ['name' => 'ticket_priority',     'contents' => $validated['ticket_priority'] ?? 'Medium'],
        ['name' => 'channel',             'contents' => 'email'],
    ];

    if (!empty($ccEmails)) {
        $multipart[] = [
            'name'     => 'cc_emails',
            'contents' => json_encode(array_map(
                fn($email) => ['name' => $email, 'address' => $email],
                $ccEmails
            )),
        ];
    }

    foreach ($emailFileAttachments as $file) {
        $multipart[] = [
            'name'     => 'attachments[]',
            'contents' => $file['content'],
            'filename' => $file['name'],
            'headers'  => ['Content-Type' => $file['mime']],
        ];
    }

    $ecoStatus = null;
    $ecoBody   = null;
    $ecoError  = null;

    try {
        $ecoResponse = Http::withHeaders(['X-Api-Key' => config('ecosystem.api_key')])
            ->asMultipart()
            ->timeout(20)
            ->post(config('ecosystem.url') . '/jarvies/staging-tickets', $multipart);

        $ecoStatus = $ecoResponse->status();
        $ecoBody   = $ecoResponse->body();

        if (!$ecoResponse->successful()) {
            Log::warning('Mobile API: storeWithEmail: EcoSystem API failed (non-critical)', [
                'status' => $ecoStatus,
                'body'   => $ecoBody,
            ]);
        }
    } catch (\Exception $apiEx) {
        $ecoError = $apiEx->getMessage();
        Log::warning('Mobile API: storeWithEmail: EcoSystem API exception (non-critical)', [
            'error' => $ecoError,
        ]);
    }

    return response()->json([
        'success'    => true,
        'staging'    => true,
        'email_sent' => true,
        'message'    => 'Tiket berhasil dikirim dan sedang menunggu validasi admin.',
        'debug_eco'  => [
            'url'    => config('ecosystem.url') . '/jarvies/staging-tickets',
            'status' => $ecoStatus,
            'body'   => $ecoBody,
            'error'  => $ecoError,
        ],
    ], 201);
}
```

---

### 3c. Fix method `sendMessage` — 2 perubahan

**Perubahan 1 — Ganti `channel: mobile` → `channel: web`**

Cari di dalam method `sendMessage()`:
```php
'channel'                => 'mobile',
```
Ganti dengan:
```php
'channel'                => 'web',
```

**Perubahan 2 — Hapus update `last_message_at` yang tidak ada di schema**

Cari:
```php
$ticket->update([
    'last_message_at'        => now(),
    'last_customer_reply_at' => now(),
]);
```
Ganti dengan:
```php
// last_message_at / last_customer_reply_at tidak ada di schema ticket — skip update
```

**Perubahan 3 — Tambah `debug` di catch block (opsional, untuk debugging)**

Cari:
```php
return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan.'], 500);
```
Ganti dengan:
```php
return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan.', 'debug' => $e->getMessage()], 500);
```

---

## 4. `app/Http/Controllers/TicketController.php` (Web Controller)

**Masalah:** Tiket yang dibuat dari web UI tidak menyertakan `channel: email` saat POST ke EcoSystem API.

### Cari bagian multipart di method `store()` (sekitar baris 492–505):

```php
$multipart = [
    // Wajib
    ['name' => 'description',         'contents' => $validated['description']],
    ['name' => 'customer_id',         'contents' => (string) $user['id']],
    // Sangat direkomendasikan
    ['name' => 'submitted_by_email',  'contents' => $senderEmail],
    ['name' => 'body',                'contents' => $emailBodyHtml ?: ''],
    ['name' => 'internet_message_id', 'contents' => $internetMsgId],
    // Opsional
    ['name' => 'sender_name',         'contents' => $senderName ?? ''],
    ['name' => 'ticket_priority',     'contents' => $validated['ticket_priority'] ?? 'Medium'],
];
```

### Tambahkan baris `channel` setelah `ticket_priority`:

```php
$multipart = [
    // Wajib
    ['name' => 'description',         'contents' => $validated['description']],
    ['name' => 'customer_id',         'contents' => (string) $user['id']],
    // Sangat direkomendasikan
    ['name' => 'submitted_by_email',  'contents' => $senderEmail],
    ['name' => 'body',                'contents' => $emailBodyHtml ?: ''],
    ['name' => 'internet_message_id', 'contents' => $internetMsgId],
    // Opsional
    ['name' => 'sender_name',         'contents' => $senderName ?? ''],
    ['name' => 'ticket_priority',     'contents' => $validated['ticket_priority'] ?? 'Medium'],
    // Channel email — karena tiket masuk via email yang dikirim Graph API
    ['name' => 'channel',             'contents' => 'email'],
];
```

---

## 5. Konfigurasi `.env` Production (di server)

Pastikan di production server JARVIES, `.env` menggunakan **https**:

```
ECOSYSTEM_URL=https://ecosystemtest.org/api
ECOSYSTEM_API_URL=https://ecosystemtest.org/api
ECOSYSTEM_API_KEY=jarvies_e00e2bbf84559de81545ae7a6f4f2a8a217b5201dbaf6e3202a7afe3bd54f880
```

> **Penting:** `http://` menyebabkan 401 Unauthorized karena header `X-Api-Key` hilang saat redirect ke https.

Setelah update `.env`, jalankan:
```bash
php artisan config:clear
```

---

## Urutan Implementasi Ulang

1. Update `app/Http/Middleware/ApiAuth.php`
2. Update `routes/api.php`
3. Update `app/Http/Controllers/Api/TicketController.php` (import + method baru + fix sendMessage)
4. Update `app/Http/Controllers/TicketController.php` (tambah channel email)
5. Update `.env` production: ganti `http://` → `https://` pada `ECOSYSTEM_URL`
6. Jalankan `php artisan config:clear` di server
7. Deploy ke production
8. Test via Postman: Login → Me → Submit With Email → cek email masuk & staging muncul
