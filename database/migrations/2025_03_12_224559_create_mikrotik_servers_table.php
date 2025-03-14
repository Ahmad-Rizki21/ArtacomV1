<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mikrotik_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('host_ip');
            $table->string('username');
            $table->text('password');
            $table->integer('port')->default(8728);
            
            $table->string('ros_version')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->string('last_connection_status')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mikrotik_servers');
    }
};