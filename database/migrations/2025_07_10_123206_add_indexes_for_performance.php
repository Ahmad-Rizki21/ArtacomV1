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
        // Indeks untuk mempercepat query pembuatan invoice
        Schema::table('langganan', function (Blueprint $table) {
            $table->index(['user_status', 'tgl_jatuh_tempo']);
        });

        // Indeks untuk mempercepat pengecekan invoice yang sudah ada
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['pelanggan_id', 'tgl_invoice']);
        });
    }

    public function down(): void
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->dropIndex(['user_status', 'tgl_jatuh_tempo']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['pelanggan_id', 'tgl_invoice']);
        });
    }
};
