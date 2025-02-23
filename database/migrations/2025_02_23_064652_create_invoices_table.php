<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // Primary Key Auto Increment
            $table->string('invoice_number')->unique(); // Nomor Invoice Unik

            // 🔹 Foreign Key ke tabel `pelanggan`
            $table->unsignedBigInteger('pelanggan_id');
            $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->onDelete('cascade');

            // 🔹 Foreign Key ke tabel `data_teknis` (Menggunakan ID Pelanggan)
            $table->string('id_pelanggan', 255);
            $table->foreign('id_pelanggan')->references('id_pelanggan')->on('data_teknis')->onDelete('cascade');

            // 🔹 Data Langganan
            $table->string('brand'); // Brand dari Langganan (Jelantik / Jakinet)
            $table->decimal('total_harga', 15, 2)->default(0); // Total Harga Invoice

            // 🔹 Informasi Pelanggan
            $table->string('no_telp'); // Nomor Telepon Pelanggan
            $table->string('email'); // Email Pelanggan

            // 🔹 Tanggal Invoice dan Jatuh Tempo
            $table->date('tgl_invoice')->default(now()); // Tanggal Invoice Dibuat
            $table->date('tgl_jatuh_tempo'); // Tanggal Jatuh Tempo

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

