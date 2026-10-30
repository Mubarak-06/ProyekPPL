<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel pesanan
            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->restrictOnDelete();

            // Metode pembayaran pelanggan
            $table->enum('metode_pembayaran', [
                'Transfer Bank',
                'E-Wallet',
                'QRIS'
            ]);

            // Jumlah pembayaran
            $table->decimal('jumlah_pembayaran', 15, 2);

            // Bukti pembayaran jika diperlukan
            $table->string('bukti_pembayaran', 255)->nullable();

            // Status pembayaran
            $table->enum('status_pembayaran', [
                'Menunggu',
                'Berhasil',
                'Gagal'
            ])->default('Menunggu');

            // Waktu pembayaran berhasil
            $table->timestamp('waktu_pembayaran')->nullable();

            // Referensi dari layanan pembayaran
            $table->string('referensi_pembayaran', 100)
                ->nullable()
                ->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};