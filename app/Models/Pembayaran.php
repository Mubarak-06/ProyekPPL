<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'jumlah_pembayaran' => 'decimal:2',
        'waktu_pembayaran' => 'datetime',
    ];

    // Relasi ke pesanan
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}