<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_link')) {
                $table->text('payment_link')->nullable(); // Link Pembayaran dari Xendit
            }

            if (!Schema::hasColumn('invoices', 'status_invoice')) {
                $table->string('status_invoice', 50)->default('Belum Dibayar'); // Status Pembayaran Invoice
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_link')) {
                $table->dropColumn('payment_link');
            }

            if (Schema::hasColumn('invoices', 'status_invoice')) {
                $table->dropColumn('status_invoice');
            }
        });
    }
};