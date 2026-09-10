<?php

use App\Http\Controllers\CapitalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WhatsappWebhookController;
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
    Route::get('/whatsapp', [WhatsappWebhookController::class, 'index'])->name('whatsapp.index');
    Route::patch('/whatsapp', [WhatsappWebhookController::class, 'updateLink'])->name('whatsapp.update');
    Route::post('/whatsapp/test-connection', [WhatsappWebhookController::class, 'testConnection'])->name('whatsapp.test-connection');
    Route::get('/whatsapp/history', [WhatsappWebhookController::class, 'history'])->name('whatsapp.history');
    Route::post('/whatsapp/send', [WhatsappWebhookController::class, 'sendFromWeb'])->name('whatsapp.send');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// WhatsApp webhook (no auth required)
Route::post('/api/webhooks/whatsapp', [WhatsappWebhookController::class, 'handleWebhook'])->withoutMiddleware(['auth', 'verified']);

require __DIR__.'/auth.php';
