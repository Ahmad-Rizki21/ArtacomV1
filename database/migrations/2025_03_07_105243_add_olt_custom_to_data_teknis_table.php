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
        Schema::table('data_teknis', function (Blueprint $table) {
            $table->string('olt_custom')->nullable()->after('olt'); // Add olt_custom column after 'olt' column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_teknis', function (Blueprint $table) {
            $table->dropColumn('olt_custom'); // Drop olt_custom column if rollback
        });
    }
};
