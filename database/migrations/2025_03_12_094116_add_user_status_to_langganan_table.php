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
            $table->string('user_status')->default('Aktif');  // Menambahkan kolom user_status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->dropColumn('user_status'); // Menghapus kolom user_status jika rollback
        });
    }
};
