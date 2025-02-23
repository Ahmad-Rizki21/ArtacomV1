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
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id(); // Tipe data untuk id sudah benar, menggunakan bigint unsigned
            $table->string('no_ktp');
            $table->string('nama');
            $table->string('alamat');
            $table->string('blok');
            $table->string('unit');
            $table->string('no_telp');
            $table->string('email');
            $table->timestamps();
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
