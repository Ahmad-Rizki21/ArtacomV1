<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\XenditInvoiceStatusController;
use App\Http\Controllers\InvoiceViewController;

// Halaman utama
Route::get('/', function () {
    return redirect('/admin/login');
});

// Menampilkan invoice berdasarkan nomor invoice untuk halaman admin atau frontend
Route::get('invoices/{invoiceNumber}', [InvoiceController::class, 'show'])->name('invoices.show');

// Menampilkan form untuk membuat invoice atau halaman terkait
Route::post('/invoice/create', [InvoiceController::class, 'createInvoice'])
    ->name('invoice.create');



// Di routes/web.php
Route::get('/invoice/view/{invoice}', [InvoiceViewController::class, 'show'])->name('invoice.view');
Route::get('/invoice/print/{invoice}', [InvoiceViewController::class, 'print'])->name('invoice.print');
// Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceId}', [XenditInvoiceStatusController::class, 'checkStatus'])
//     ->name('invoice.status');

// // Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceNumber}', [XenditInvoiceStatusController::class, 'checkStatus']);
