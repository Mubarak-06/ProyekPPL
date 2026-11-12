<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected static function booted(): void
    {
        static::updated(function (Pesanan $pesanan) {
            if ($pesanan->wasChanged('status_pesanan') && $pesanan->status_pesanan === 'Ditolak'
                && in_array($pesanan->getOriginal('status_pesanan'), ['Menunggu', 'Diproses'], true)) {
                // Penolakan admin mengembalikan stok yang dipesan saat checkout.
                foreach ($pesanan->detailPesanan()->orderBy('produk_id')->get() as $detail) {
                    Produk::whereKey($detail->produk_id)->increment('stok', $detail->jumlah);
                }
            }
        });
    }

    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan',
        'user_id',
        'alamat_id',
        'subtotal',
        'ongkos_kirim',
        'total_pembayaran',
        'status_pesanan',
        'catatan',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'ongkos_kirim' => 'decimal:2',
        'total_pembayaran' => 'decimal:2',
    ];

    // Relasi ke pelanggan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke alamat pelanggan
    public function alamat()
    {
        return $this->belongsTo(Alamat::class, 'alamat_id');
    }

    // Relasi ke detail pesanan
    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    // Relasi ke pembayaran
    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'pesanan_id');
    }

    // Relasi ke pengiriman
    public function pengiriman()
    {
        return $this->hasOne(Pengiriman::class, 'pesanan_id');
    }
}
