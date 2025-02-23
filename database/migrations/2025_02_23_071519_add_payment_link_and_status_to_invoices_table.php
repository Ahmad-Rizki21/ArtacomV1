<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_link')->nullable()->after('total_harga'); // 🔹 Simpan link dari Xendit
            $table->enum('status_invoice', ['Belum Dibayar', 'Lunas', 'Kadaluarsa'])
                ->default('Belum Dibayar')
                  ->after('payment_link'); // 🔹 Status Pembayaran
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_link', 'status_invoice']);
        });
    }
};
