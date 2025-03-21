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
        Schema::table('langganan', function (Blueprint $table) {
            $table->string('last_processed_invoice')->nullable()->after('tgl_invoice_terakhir')
                ->comment('Nomor invoice terakhir yang diproses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->dropColumn('last_processed_invoice');
        });
    }
};