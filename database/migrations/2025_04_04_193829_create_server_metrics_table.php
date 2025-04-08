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
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_server_id')->constrained()->cascadeOnDelete();
            $table->string('cpu_load')->nullable();
            $table->string('memory_usage')->nullable();
            $table->string('disk_usage')->nullable();
            $table->string('uptime')->nullable();
            $table->json('interfaces_traffic')->nullable();
            $table->json('active_connections')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
