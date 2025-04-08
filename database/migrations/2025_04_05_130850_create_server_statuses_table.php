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
        Schema::create('server_statuses', function (Blueprint $table) {
            $table->id();
            $table->float('cpu_usage')->nullable();
            $table->float('memory_usage')->nullable();
            $table->float('load_1m')->nullable();
            $table->float('load_5m')->nullable();
            $table->float('load_15m')->nullable();
            $table->string('uptime')->nullable();
            $table->integer('process_count')->nullable();
            $table->integer('running_processes')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('snapshot_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_statuses');
    }
};