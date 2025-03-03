<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddXenditExternalIdToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Tambahkan kolom xendit_external_id
            $table->string('xendit_external_id')
                  ->nullable()
                  ->unique()
                  ->comment('External ID dari Xendit untuk invoice ini');
            
            // Tambahan: jika Anda ingin memastikan kolom xendit_id ada
            if (!Schema::hasColumn('invoices', 'xendit_id')) {
                $table->string('xendit_id')
                      ->nullable()
                      ->comment('ID transaksi dari Xendit');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Hapus kolom jika di-rollback
            $table->dropColumn([
                'xendit_external_id', 
                // Uncomment baris di bawah jika Anda ingin menghapus xendit_id saat rollback
                 'xendit_id'
            ]);
        });
    }
}