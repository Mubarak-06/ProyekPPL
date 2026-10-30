<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();

            $table->string('kode_pesanan', 30)->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('alamat_id')
                ->constrained('alamat')
                ->restrictOnDelete();

            $table->decimal('subtotal', 15, 2);
            $table->decimal('ongkos_kirim', 15, 2)->default(0);
            $table->decimal('total_pembayaran', 15, 2);

            $table->enum('status_pesanan', [
                'Menunggu',
                'Diproses',
                'Dikirim',
                'Selesai',
                'Ditolak'
            ])->default('Menunggu');

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};