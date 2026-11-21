<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schema = DB::connection($this->getConnection())->getSchemaBuilder();
        $schema->table('midtrans_rekonsiliasi', function (Blueprint $table) {
            $table->string('status_penahanan', 16)->default('ditahan');
            $table->index(['pembayaran_id', 'id'], 'midtrans_rekon_payment_case_idx');
        });
        $schema->create('midtrans_rekonsiliasi_riwayat', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('rekonsiliasi_id')->constrained('midtrans_rekonsiliasi')->restrictOnDelete();
            $table->string('jenis_tindakan', 32);
            $table->dateTime('waktu_tindakan', 6);
            $table->string('jenis_aktor', 16);
            $table->foreignId('admin_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('proses_sistem', 32)->nullable();
            $table->string('hasil_pemeriksaan', 40);
            $table->string('sumber_bukti', 32);
            $table->string('referensi_bukti', 100)->nullable();
            $table->string('keputusan_operasional', 32);
            $table->string('alasan_keputusan', 64);
            $table->char('kunci_idempotensi', 64);
            $table->dateTime('created_at', 6);
            $table->index(['rekonsiliasi_id', 'waktu_tindakan', 'id'], 'midtrans_riwayat_case_time_idx');
            $table->index('admin_id', 'midtrans_riwayat_admin_idx');
            $table->unique(['rekonsiliasi_id', 'kunci_idempotensi'], 'midtrans_riwayat_idempotency_uq');
        });
    }

    public function down(): void
    {
        $schema = DB::connection($this->getConnection())->getSchemaBuilder();
        $schema->dropIfExists('midtrans_rekonsiliasi_riwayat');
        $schema->table('midtrans_rekonsiliasi', function (Blueprint $table) {
            $table->dropIndex('midtrans_rekon_payment_case_idx');
            $table->dropColumn('status_penahanan');
        });
    }
};
