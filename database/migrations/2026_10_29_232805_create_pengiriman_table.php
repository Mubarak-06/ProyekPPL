<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel pesanan
            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->restrictOnDelete();

            // Informasi penerima
            $table->string('nama_penerima', 100);
            $table->string('nomor_telepon', 20);
            $table->text('alamat_pengiriman');

            // Biaya pengiriman dalam kota
            $table->decimal('ongkos_kirim', 15, 2)->default(0);

            // Status pengiriman
            $table->enum('status_pengiriman', [
                'Menunggu',
                'Diproses',
                'Dikirim',
                'Selesai',
                'Ditolak'
            ])->default('Menunggu');

            // Catatan untuk driver perusahaan
            $table->text('catatan_driver')->nullable();

            // Waktu pengiriman
            $table->timestamp('waktu_dikirim')->nullable();
            $table->timestamp('waktu_selesai')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
    }
};