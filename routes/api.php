<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\XenditWebhookController;
use App\Http\Controllers\XenditInvoiceStatusController;

// Endpoint untuk membuat invoice
Route::post('/invoice', [InvoiceController::class, 'createInvoice'])
    ->name('invoice.create');

// Endpoint untuk memeriksa status invoice berdasarkan nomor invoice
// Route::get('/invoice/status/{invoiceNumber}', [XenditInvoiceStatusController::class, 'checkStatus'])
//     ->name('invoice.check-status');

// Endpoint untuk menerima webhook dari Xendit dan memperbarui status invoice
Route::post('/xendit/webhook', [XenditWebhookController::class, 'handleWebhook'])
    ->name('xendit.webhook');

// Endpoint untuk update status invoice manual (untuk debugging)
Route::post('/invoice/update-status', [InvoiceController::class, 'updateStatus'])
    ->name('invoice.update-status');

// Optional: Endpoint untuk memaksa pengecekan status invoice dari Xendit
// Route::get('/invoice/{invoiceNumber}/check-xendit-status', [XenditInvoiceStatusController::class, 'forceCheckStatus'])
//     ->name('invoice.force-xendit-check');