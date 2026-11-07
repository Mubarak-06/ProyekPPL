<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('metode_pembayaran_id')
                ->nullable()
                ->constrained('metode_pembayaran')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metode_pembayaran_id');
        });
    }
};