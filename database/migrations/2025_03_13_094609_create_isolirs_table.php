<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('isolir', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('langganan_id');
            $table->unsignedBigInteger('pelanggan_id');
            $table->string('brand');
            
            // Optional user who performed isolation
            $table->unsignedBigInteger('user_id')->nullable();

            // Isolation details
            $table->text('alasan_isolir')->nullable();
            $table->timestamp('tanggal_isolir')->nullable();
            $table->timestamp('tanggal_aktif_kembali')->nullable();
            
            // Status isolir
            $table->enum('status_isolir', [
                'pending', 
                'aktif', 
                'selesai'
            ])->default('pending');
            
            // Catatan tambahan
            $table->text('catatan')->nullable();

            // Foreign key constraints
            $table->foreign('langganan_id')
                  ->references('id')
                  ->on('langganan')
                  ->onDelete('cascade');
            
            $table->foreign('pelanggan_id')
                  ->references('id')
                  ->on('pelanggan')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('isolir');
    }
};