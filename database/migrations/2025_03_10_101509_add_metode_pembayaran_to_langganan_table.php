<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetodePembayaranToLanggananTable extends Migration
{
    public function up()
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['otomatis', 'manual'])->default('otomatis'); // Tambah kolom metode_pembayaran
        });
    }

    public function down()
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->dropColumn('metode_pembayaran'); // Menghapus kolom jika rollback
        });
    }
}
