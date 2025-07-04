<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


// Endpoint untuk membuat invoice
Route::post('/invoice', [PaymentController::class, 'createInvoice'])
    ->name('invoice.create');

// Endpoint untuk memeriksa status invoice berdasarkan nomor invoice
Route::get('/invoice/status/{invoiceNumber}', [PaymentController::class, 'checkStatus'])
    ->name('invoice.check-status');

// Endpoint untuk menerima webhook dari Xendit dan memperbarui status invoice
// Tidak perlu middleware auth karena akan dipanggil oleh Xendit
Route::post('/xendit/webhook', [PaymentController::class, 'handleWebhook'])
    ->name('xendit.webhook')
    ->withoutMiddleware(['auth:api', 'auth:sanctum', 'auth']); // Pastikan tidak ada auth middleware

// Endpoint alternatif untuk callback Xendit (jika Anda menggunakan handleXenditCallback)
Route::post('/xendit/callback', [PaymentController::class, 'handleXenditCallback'])
    ->name('xendit.callback')
    ->withoutMiddleware(['auth:api', 'auth:sanctum', 'auth']); // Pastikan tidak ada auth middleware

// Endpoint untuk update status invoice manual (untuk debugging)
Route::post('/invoice/update-status', [PaymentController::class, 'updateStatus'])
    ->name('invoice.update-status');

Route::get('/invoice-notifications', [PaymentController::class, 'checkInvoiceNotifications'])->name('invoice.notifications');

// Optional: Hapus jika tidak diperlukan
// Route::get('/invoice/{invoiceNumber}/check-xendit-status', [XenditInvoiceStatusController::class, 'forceCheckStatus'])
//     ->name('invoice.force-xendit-check');
Route::middleware('auth:sanctum')->get('/check-notifications', function (Request $request) {
    $user = $request->user();
    $unread = $user->unreadNotifications()->count();
    return response()->json(['unread' => $unread]);
});