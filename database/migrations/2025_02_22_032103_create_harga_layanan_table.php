<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('harga_layanan', function (Blueprint $table) {
        $table->string('id_brand')->primary();
        $table->string('brand');
        $table->decimal('pajak', 5, 2)->default(10);
        $table->decimal('harga_10mbps', 15, 2);
        $table->decimal('harga_20mbps', 15, 2);
        $table->decimal('harga_30mbps', 15, 2);
        $table->decimal('harga_50mbps', 15, 2);
        $table->timestamps();
    });
    
    
    
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harga_layanan');
    }
};
