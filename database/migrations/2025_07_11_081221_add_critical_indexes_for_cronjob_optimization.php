<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CRITICAL INDEXES untuk mengatasi slow queries
 */
class AddCriticalIndexesForCronjobOptimization extends Migration
{
    public function up()
    {
        // INDEX 1: Compound index untuk query overdue subscriptions
        Schema::table('langganan', function (Blueprint $table) {
            $table->index(['tgl_jatuh_tempo', 'user_status'], 'idx_langganan_due_status');
            $table->index(['pelanggan_id', 'user_status'], 'idx_langganan_customer_status');
            $table->index(['user_status', 'tgl_jatuh_tempo'], 'idx_langganan_status_due');
        });

        // INDEX 2: Indexes untuk invoice queries
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['pelanggan_id', 'tgl_jatuh_tempo', 'status_invoice'], 'idx_invoice_customer_due_status');
            $table->index(['status_invoice', 'xendit_id'], 'idx_invoice_status_xendit');
            $table->index(['tgl_jatuh_tempo', 'status_invoice'], 'idx_invoice_due_status');
        });

        // INDEX 3: Indexes untuk data teknis
        Schema::table('data_teknis', function (Blueprint $table) {
            $table->index('pelanggan_id', 'idx_data_teknis_customer');
            $table->index('id_pelanggan', 'idx_data_teknis_id_customer');
        });

        // INDEX 4: Indexes untuk pelanggan
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->index('id', 'idx_pelanggan_id');
        });
    }

    public function down()
    {
        Schema::table('langganans', function (Blueprint $table) {
            $table->dropIndex('idx_langganan_due_status');
            $table->dropIndex('idx_langganan_customer_status');
            $table->dropIndex('idx_langganan_status_due');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_customer_due_status');
            $table->dropIndex('idx_invoice_status_xendit');
            $table->dropIndex('idx_invoice_due_status');
        });

        Schema::table('data_teknis', function (Blueprint $table) {
            $table->dropIndex('idx_data_teknis_customer');
            $table->dropIndex('idx_data_teknis_id_customer');
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropIndex('idx_pelanggan_id');
        });
    }
}