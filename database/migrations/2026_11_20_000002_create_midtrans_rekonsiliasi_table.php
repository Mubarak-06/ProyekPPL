<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_rekonsiliasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->restrictOnDelete();
            $table->string('midtrans_order_id', 100);
            $table->string('midtrans_transaction_id', 100)->nullable();
            $table->string('status_transaksi', 50)->nullable();
            $table->string('jenis_pembayaran', 50)->nullable();
            $table->dateTime('terakhir_diperiksa_at')->nullable();
            $table->dateTime('periksa_ulang_at')->nullable();
            $table->string('status_penanganan', 32)->default('menunggu');
            $table->text('catatan_manual')->nullable();
            $table->dateTime('pembatalan_dikonfirmasi_at')->nullable();
            $table->string('sumber_konfirmasi_pembatalan', 32)->nullable();
            $table->string('referensi_konfirmasi_pembatalan', 100)->nullable();
            $table->timestamps();

            $table->index('midtrans_order_id', 'midtrans_rekon_order_idx');
            $table->index('midtrans_transaction_id', 'midtrans_rekon_transaksi_idx');
            $table->index(['status_penanganan', 'periksa_ulang_at'], 'midtrans_rekon_jadwal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_rekonsiliasi');
    }
};
