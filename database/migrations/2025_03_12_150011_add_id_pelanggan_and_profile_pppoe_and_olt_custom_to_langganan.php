<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdPelangganAndProfilePppoeAndOltCustomToLangganan extends Migration
{
    /**
     * Menjalankan migration untuk menambah kolom ke tabel langganan.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('langganan', function (Blueprint $table) {
            // Menambahkan kolom id_pelanggan, profile_pppoe, dan olt_custom
            $table->string('id_pelanggan')->nullable()->after('pelanggan_id'); // id_pelanggan sebagai referensi ke data_teknis
            $table->string('profile_pppoe')->nullable()->after('id_pelanggan');
            $table->string('olt')->nullable()->after('profile_pppoe');

            // Jika id_pelanggan mengacu pada kolom id di tabel data_teknis
            // Pastikan id_pelanggan adalah referensi yang benar, jika data_teknis terhubung dengan pelanggan
            $table->foreign('id_pelanggan')->references('id_pelanggan')->on('data_teknis')->onDelete('cascade');
        });
    }

    /**
     * Membalikkan perubahan yang dilakukan oleh migration ini.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('langganan', function (Blueprint $table) {
            // Menghapus kolom jika migration dibatalkan
            $table->dropForeign(['id_pelanggan']); // Menghapus foreign key
            $table->dropColumn(['id_pelanggan', 'profile_pppoe', 'olt']);
        });
    }
}
