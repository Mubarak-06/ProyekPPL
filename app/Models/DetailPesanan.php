<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    protected $table = 'detail_pesanan';

    protected $fillable = [
        'pesanan_id',
        'produk_id',
        'nama_produk',
        'harga_satuan',
        'jumlah',
        'subtotal',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'jumlah' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    // Relasi ke pesanan
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    // Relasi ke produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}