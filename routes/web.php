<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceExportController;
use App\Http\Controllers\PelangganImportController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\LanggananController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataTeknisController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PelangganExportController;
use App\Http\Controllers\XenditInvoiceStatusController;
use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\PaymentController;

// Halaman utama
Route::get('/', function () {
    return redirect('/admin/login');
});

// Menampilkan invoice berdasarkan nomor invoice untuk halaman admin atau frontend
Route::get('invoices/{invoiceNumber}', [PaymentController::class, 'show'])->name('invoices.show');

// Menampilkan form untuk membuat invoice atau halaman terkait
// Route::post('/invoice/create', [PaymentController::class, 'createInvoice'])
//     ->name('invoice.create');



// Di routes/web.php
Route::get('/invoice/view/{invoice}', [InvoiceViewController::class, 'show'])->name('invoice.view');
Route::get('/invoice/print/{invoice}', [InvoiceViewController::class, 'print'])->name('invoice.print');
// Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceId}', [XenditInvoiceStatusController::class, 'checkStatus'])
//     ->name('invoice.status');

// // Pengecekan status invoice (untuk admin atau halaman tertentu)
// Route::get('/invoice/status/{invoiceNumber}', [XenditInvoiceStatusController::class, 'checkStatus']);


//Excel
Route::get('invoices/export', [InvoiceExportController::class, 'export'])->name('invoices.export');
// di routes/web.php
Route::post('pelanggan/import', [PelangganImportController::class, 'import'])->name('pelanggan.import');
// di routes/web.php
Route::get('pelanggan/template/download', [TemplateController::class, 'downloadPelangganTemplate'])->name('pelanggan.template.download');


// di routes/web.php
Route::get('pelanggan/export', [PelangganExportController::class, 'showExportForm'])->name('pelanggan.export.form');
Route::post('pelanggan/export', [PelangganExportController::class, 'export'])->name('pelanggan.export');

// Rute untuk ekspor template Data Teknis
Route::get('data-teknis/template/download', [DataTeknisController::class, 'downloadTemplate'])
    ->name('data-teknis.template.download');

// Rute untuk ekspor Data Teknis
Route::get('data-teknis/export', [DataTeknisController::class, 'showExportForm'])
    ->name('data-teknis.export.form');
Route::post('data-teknis/export', [DataTeknisController::class, 'export'])
    ->name('data-teknis.export');

// Rute untuk impor Data Teknis
Route::get('data-teknis/import', [DataTeknisController::class, 'showImportForm'])
    ->name('data-teknis.import.form');
Route::post('data-teknis/import', [DataTeknisController::class, 'import'])
    ->name('data-teknis.import');


    // Rute untuk download template Langganan
Route::get('langganan/template/download', [LanggananController::class, 'downloadTemplate'])
->name('langganan.template.download');

// Rute untuk export Langganan
Route::get('langganan/export', [LanggananController::class, 'showExportForm'])
->name('langganan.export.form');
Route::post('langganan/export', [LanggananController::class, 'export'])
->name('langganan.export');

// Rute untuk import Langganan
Route::get('langganan/import', [LanggananController::class, 'showImportForm'])
->name('langganan.import.form');
Route::post('langganan/import', [LanggananController::class, 'import'])
->name('langganan.import');


Route::get('/test-notification', function() {
    $invoice = \App\Models\Invoice::first();
    $user = \App\Models\User::first();
    
    if ($invoice && $user) {
        $user->notify(new \App\Notifications\XenditPaymentNotification($invoice));
        return "Notifikasi berhasil dikirim ke user ID: " . $user->id;
    }
    
    return "Tidak ada invoice atau user ditemukan";
})->middleware(['auth']);