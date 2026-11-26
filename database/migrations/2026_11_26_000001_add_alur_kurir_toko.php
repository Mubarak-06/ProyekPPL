<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('slot_kurir')->nullable()->unique();
        });
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->foreignId('kurir_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('tahap_kurir', 30)->nullable();
            $table->index(['kurir_id', 'tahap_kurir']);
            $table->date('estimasi_tanggal')->nullable();
            $table->time('estimasi_jam')->nullable();
            $table->string('penerima_aktual', 100)->nullable();
            $table->string('foto_penyerahan')->nullable();
            $table->text('catatan_penyerahan')->nullable();
        });
        Schema::create('riwayat_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengiriman')->restrictOnDelete();
            $table->foreignId('pelaku_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_lama', 30);
            $table->string('status_baru', 30);
            $table->text('catatan')->nullable();
            $table->timestamp('terjadi_at');
        });
        Schema::create('kendala_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengiriman')->restrictOnDelete();
            $table->foreignId('pelapor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('peran_pelapor', 15);
            $table->text('catatan');
            $table->timestamp('dilaporkan_at');
            $table->foreignId('penyelesai_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('penyelesaian')->nullable();
            $table->timestamp('diselesaikan_at')->nullable();
            $table->index(['pengiriman_id', 'diselesaikan_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendala_pengiriman');
        Schema::dropIfExists('riwayat_pengiriman');
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropIndex(['kurir_id', 'tahap_kurir']);
            $table->dropForeign(['kurir_id']);
        });
        Schema::table('pengiriman', fn (Blueprint $table) => $table->dropColumn([
            'kurir_id', 'tahap_kurir', 'estimasi_tanggal', 'estimasi_jam',
            'penerima_aktual', 'foto_penyerahan', 'catatan_penyerahan',
        ]));
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slot_kurir']);
            $table->dropColumn('slot_kurir');
        });
    }
};
