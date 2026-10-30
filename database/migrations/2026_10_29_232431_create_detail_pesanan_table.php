<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel pesanan
            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->cascadeOnDelete();

            // Relasi ke tabel produk
            $table->foreignId('produk_id')
                ->constrained('produk')
                ->restrictOnDelete();

            // Informasi produk saat pembelian
            $table->string('nama_produk', 255);
            $table->decimal('harga_satuan', 15, 2);
            $table->unsignedInteger('jumlah');
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
    }
};