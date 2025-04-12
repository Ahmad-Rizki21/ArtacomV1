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
        Schema::table('pelanggan', function (Blueprint $table) {
            // Menambahkan kolom id_brand untuk brand layanan
            $table->string('id_brand')->nullable()->after('email');
            
            // Menambahkan kolom layanan untuk paket layanan
            $table->string('layanan')->nullable()->after('id_brand');
            
            // Menambahkan kolom brand_default untuk menyimpan brand default
            $table->string('brand_default')->nullable()->after('layanan');
            
            // Menambahkan kolom alamat_custom untuk alamat yang tidak ada di daftar
            $table->string('alamat_custom')->nullable()->after('alamat');

            // Menambahkan kolom Tanggal Instalasi
            $table->date('tgl_instalasi')->nullable()->after('alamat_custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            // Menghapus kolom jika migration di-rollback
            $table->dropColumn('id_brand');
            $table->dropColumn('layanan');
            $table->dropColumn('brand_default');
            $table->dropColumn('alamat_custom');
            $table->dropColumn('tgl_instalasi');
        });
    }
};