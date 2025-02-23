<?php


use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::post('/api/invoices/{id}/send-to-xendit', [InvoiceController::class, 'sendToXendit']);



