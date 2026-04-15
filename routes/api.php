<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TicketController;

/*
|--------------------------------------------------------------------------
| Mobile API Routes (Flutter – Customer Side)
|--------------------------------------------------------------------------
|
| Semua route di sini otomatis diberi prefix /api oleh Laravel.
| Autentikasi: Bearer token (base64 stateless).
| Hanya customer (role_id=3) yang bisa mengakses endpoint bertanda 🔒.
|
| Format token: base64("{customer_code}|{timestamp}|customer")
|
*/

// ═══════════════════════════════════════════════════════════════
// PUBLIC — tidak butuh token
// ═══════════════════════════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('login',   [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']); // Perbarui access token (tidak butuh Bearer)
});

// ═══════════════════════════════════════════════════════════════
// PROTECTED 🔒 — wajib Bearer token customer
// ═══════════════════════════════════════════════════════════════
Route::middleware('api.auth')->group(function () {

    // ── Auth ──────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // ── Dashboard / Home ──────────────────────────────────────
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ── Tiket ─────────────────────────────────────────────────
    Route::prefix('tickets')->group(function () {
        Route::get('/',                  [TicketController::class, 'index']);           // List tiket saya
        Route::post('/',                 [TicketController::class, 'store']);           // Buat tiket baru (→ staging, tanpa email)
        Route::post('submit-with-email', [TicketController::class, 'storeWithEmail']); // Buat tiket + kirim email (full flow)
        Route::get('staging',            [TicketController::class, 'staging']);         // List staging tiket

        Route::get('{id}',              [TicketController::class, 'show']);        // Detail tiket
        Route::get('{id}/messages',     [TicketController::class, 'messages']);    // List pesan
        Route::post('{id}/messages',    [TicketController::class, 'sendMessage']); // Kirim pesan
    });
});
