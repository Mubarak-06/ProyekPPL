<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            // Checkout lama masih menyimpan pembayaran tanpa menentukan gateway.
            $table->string('gateway', 20)->default('manual');
            $table->string('midtrans_order_id', 100)->nullable()->unique();
            $table->string('midtrans_transaction_id', 100)->nullable()->unique();
            $table->text('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->timestamp('snap_token_expires_at')->nullable();
            $table->timestamp('snap_requested_at')->nullable();
            $table->string('midtrans_status', 50)->nullable();
            $table->string('midtrans_payment_type', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
            $table->dropUnique(['midtrans_transaction_id']);
            $table->dropColumn([
                'gateway',
                'midtrans_order_id',
                'midtrans_transaction_id',
                'snap_token',
                'snap_redirect_url',
                'snap_token_expires_at',
                'snap_requested_at',
                'midtrans_status',
                'midtrans_payment_type',
            ]);
        });
    }
};
