<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            $table->json('system_resources')->nullable()->after('active_connections');
            $table->json('additional_info')->nullable()->after('system_resources');
        });
    }

    public function down()
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropColumn(['system_resources', 'additional_info']);
        });
    }
};