<?php

use App\Http\Controllers\CapitalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions/export', [TransactionController::class, 'exportCsv'])->name('transactions.export');
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'create']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update']);
    Route::resource('capitals', CapitalController::class)->only(['index', 'store', 'create']);
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams/join', [TeamController::class, 'join'])->name('teams.join');
    Route::post('/teams/{team}/switch', [TeamController::class, 'switchTeam'])->name('teams.switch');
    Route::get('/telegram', [TelegramWebhookController::class, 'index'])->name('telegram.index');
    Route::patch('/telegram', [TelegramWebhookController::class, 'updateLink'])->name('telegram.update');
    Route::post('/telegram/test-connection', [TelegramWebhookController::class, 'testConnection'])->name('telegram.test-connection');
    Route::get('/telegram/history', [TelegramWebhookController::class, 'history'])->name('telegram.history');
    Route::post('/telegram/send', [TelegramWebhookController::class, 'sendFromWeb'])->name('telegram.send');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Telegram webhook (no auth required)
Route::post('/api/webhooks/telegram', [TelegramWebhookController::class, 'handleWebhook'])->withoutMiddleware(['auth', 'verified']);

require __DIR__.'/auth.php';
