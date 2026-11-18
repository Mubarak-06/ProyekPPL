<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kode_barang',
        'nama_produk',
        'kategori_id',
        'material_id',
        'satuan_id',
        'ukuran',
        'berat',
        'harga',
        'stok',
        'deskripsi',
        'gambar',
    ];

    protected $casts = [
        'berat' => 'decimal:2',
        'harga' => 'decimal:2',
        'stok' => 'integer',
        'gambar' => 'array',
    ];

    // Relasi ke detail pesanan
    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'produk_id');
    }

    public function material()
    {
        return $this->belongsTo(\App\Models\Material::class, 'material_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
