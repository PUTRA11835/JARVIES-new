<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OAuthEmailController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PasswordSetupController;

/*
|--------------------------------------------------------------------------
| Jarvies Portal Routes
|--------------------------------------------------------------------------
|
| Clean, organized routing structure for Jarvies frontend portal
| All routes communicate with Ecosystem API backend
|
*/

// ==================== GUEST ROUTES ====================
Route::middleware('jarvies.guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ==================== PASSWORD SETUP & RESET ROUTES ====================
Route::get('/password/check-email', [PasswordSetupController::class, 'showCheckEmail'])->name('password-setup.check-email');
Route::get('/password/change', [PasswordSetupController::class, 'showChangePassword'])->name('password-setup.change');
Route::post('/password/change', [PasswordSetupController::class, 'submitChangePassword'])->name('password-setup.submit');
Route::get('/password/forgot', [PasswordSetupController::class, 'showForgotPassword'])->name('password-setup.forgot');
Route::post('/password/forgot', [PasswordSetupController::class, 'submitForgotPassword'])->name('password-setup.forgot.submit');

// ==================== AUTHENTICATED ROUTES ====================
Route::middleware('jarvies.auth')->group(function () {
    
    // ==================== AUTHENTICATION ====================
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // ==================== DASHBOARD ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // ==================== TICKETS ====================
    Route::prefix('tickets')->name('tickets.')->group(function () {
        // List & Views
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::get('/{id}', [TicketController::class, 'show'])->name('show')->whereNumber('id');
        
        // CRUD Actions
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::put('/{id}', [TicketController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{id}', [TicketController::class, 'destroy'])->name('destroy')->whereNumber('id');
        
        // AJAX API Endpoints
        Route::get('/ajax/fetch', [TicketController::class, 'getTickets'])->name('ajax.fetch');
        Route::get('/staging', [TicketController::class, 'getStagingTickets'])->name('staging');
        Route::get('/{id}/messages', [TicketController::class, 'getMessages'])->name('messages')->whereNumber('id');
        Route::post('/{id}/comment', [TicketController::class, 'addComment'])->name('comment')->whereNumber('id');
    });
    
    // ==================== CUSTOMER PORTAL ====================
    Route::prefix('my')->name('my.')->middleware('role:customer')->group(function () {
        Route::get('/tickets', [TicketController::class, 'myTickets'])->name('tickets');
        Route::get('/tickets/{id}', [TicketController::class, 'showMyTicket'])->name('tickets.show')->whereNumber('id');
    });

    // ==================== OAUTH EMAIL LINKING ====================
    Route::prefix('oauth/email')->name('oauth.email.')->group(function () {
        Route::get('/status', [OAuthEmailController::class, 'status'])->name('status');
        Route::get('/redirect/{provider}', [OAuthEmailController::class, 'redirect'])->name('redirect');
        Route::get('/callback/{provider}', [OAuthEmailController::class, 'callback'])->name('callback');
        Route::delete('/disconnect', [OAuthEmailController::class, 'disconnect'])->name('disconnect');
    });
});

// ==================== FALLBACK ====================
Route::fallback(function () {
    abort(404);
});