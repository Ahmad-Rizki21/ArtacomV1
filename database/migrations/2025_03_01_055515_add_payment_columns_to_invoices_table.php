<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentColumnsToInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Tambahkan kolom paid_amount jika belum ada
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->nullable();
            }

            // Tambahkan kolom paid_at jika belum ada
            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Hapus kolom jika di-rollback
            $table->dropColumnIfExists('paid_amount');
            $table->dropColumnIfExists('paid_at');
        });
    }
}