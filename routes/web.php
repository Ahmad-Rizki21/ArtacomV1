<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\XenditInvoiceStatusController;

// Halaman utama
Route::get('/', function () {
    return view('welcome');
});

// Menampilkan invoice berdasarkan nomor invoice untuk halaman admin atau frontend
Route::get('invoices/{invoiceNumber}', [InvoiceController::class, 'show'])->name('invoices.show');

// Menampilkan form untuk membuat invoice atau halaman terkait
Route::post('/invoice/create', [InvoiceController::class, 'createInvoice'])
    ->name('invoice.create');

// Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceId}', [XenditInvoiceStatusController::class, 'checkStatus'])
//     ->name('invoice.status');

// // Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceNumber}', [XenditInvoiceStatusController::class, 'checkStatus']);
