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
        'grosir_aktif',
    ];

    protected $casts = [
        'berat' => 'decimal:2',
        'harga' => 'decimal:2',
        'stok' => 'integer',
        'grosir_aktif' => 'boolean',
        'gambar' => 'array',
    ];

    public function hargaGrosir()
    {
        return $this->hasMany(HargaGrosir::class)->orderBy('min_qty');
    }

    public function tingkatanGrosir(): array
    {
        if (! $this->grosir_aktif) {
            return [];
        }

        $tingkatan = [];
        $hargaSebelumnya = $this->harga;
        $minimalSebelumnya = 1;
        foreach ($this->hargaGrosir as $tier) {
            if ($tier->min_qty <= $minimalSebelumnya || $tier->harga_satuan < 1 || $tier->harga_satuan >= $hargaSebelumnya) {
                return [];
            }
            $minimalSebelumnya = (int) $tier->min_qty;
            $hargaSebelumnya = (int) $tier->harga_satuan;
            $tingkatan[$minimalSebelumnya] = $hargaSebelumnya;
        }

        return $tingkatan;
    }

    public function hargaUntukJumlah(int $jumlah): array
    {
        $harga = $this->harga;
        $berikutnya = null;
        foreach ($this->tingkatanGrosir() as $minimal => $hargaGrosir) {
            if ($jumlah < $minimal) {
                $berikutnya = $minimal;
                break;
            }

            $harga = $hargaGrosir;
        }

        return [
            'harga_satuan' => $harga,
            'subtotal' => $harga * $jumlah,
            'hemat' => ($this->harga - $harga) * $jumlah,
            'minimal_berikutnya' => $berikutnya,
        ];
    }

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
