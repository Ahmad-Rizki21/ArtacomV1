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
            $table->date('tgl_jatuh_tempo')->nullable()->after('total_harga_layanan_x_pajak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('langganan', function (Blueprint $table) {
            $table->dropColumn('tgl_jatuh_tempo');
        });
    }
};
