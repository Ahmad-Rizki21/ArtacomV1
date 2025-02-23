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
    Schema::create('data_teknis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('pelanggan_id'); // ✅ FOREIGN KEY ke `pelanggan`
        $table->string('id_vlan');
        $table->string('id_pelanggan')->unique();
        $table->string('password_pppoe');
        $table->string('ip_pelanggan');
        $table->string('profile_pppoe');
        $table->string('olt');
        $table->integer('pon');
        $table->integer('otb');
        $table->integer('odc');
        $table->integer('odp');
        $table->integer('onu_power');
        $table->timestamps();

        // ✅ Tambahkan Foreign Key ke `pelanggan`
        $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_teknis');
    }
};
