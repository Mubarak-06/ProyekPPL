<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'metode_pembayaran',
        'jumlah_pembayaran',
        'bukti_pembayaran',
        'status_pembayaran',
        'waktu_pembayaran',
        'referensi_pembayaran',
        'gateway',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'snap_token',
        'snap_redirect_url',
        'snap_token_expires_at',
        'snap_requested_at',
        'midtrans_status',
        'midtrans_payment_type',
    ];

    protected $hidden = [
        'snap_token',
        'snap_redirect_url',
    ];

    protected $casts = [
        'jumlah_pembayaran' => 'decimal:2',
        'waktu_pembayaran' => 'datetime',
        'snap_token' => 'encrypted',
        'snap_redirect_url' => 'encrypted',
        'snap_token_expires_at' => 'datetime',
        'snap_requested_at' => 'datetime',
    ];

    // Relasi ke pesanan
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function rekonsiliasiAktif(): HasMany
    {
        return $this->hasMany(MidtransRekonsiliasi::class, 'pembayaran_id')
            ->where('status_penahanan', '<>', 'dilepas');
    }
}
