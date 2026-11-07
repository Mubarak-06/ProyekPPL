<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->id();

            $table->enum('jenis_metode', [
                'transfer_bank',
                'ewallet',
                'qris'
            ]);

            $table->string('nama_penyedia', 100);
            $table->string('nomor_rekening', 50)->nullable();
            $table->string('nama_pemilik', 150)->nullable();
            $table->string('logo')->nullable();
            $table->string('gambar_qris')->nullable();

            $table->enum('status', [
                'aktif',
                'nonaktif'
            ])->default('aktif');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metode_pembayaran');
    }
};