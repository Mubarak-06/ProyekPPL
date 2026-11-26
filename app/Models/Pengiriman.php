<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    public const DISIAPKAN = 'Disiapkan';
    public const SIAP = 'Siap Dikirim';
    public const PERJALANAN = 'Dalam Perjalanan';
    public const SAMPAI = 'Barang Sampai';
    public const DITERIMA = 'Diterima';

    protected $table = 'pengiriman';

    protected $fillable = [
        'pesanan_id',
        'nama_penerima',
        'nomor_telepon',
        'alamat_pengiriman',
        'ongkos_kirim',
        'status_pengiriman',
        'catatan_driver',
        'waktu_dikirim',
        'waktu_selesai',
    ];

    protected $hidden = ['foto_penyerahan'];

    public function tahap(): string
    {
        return $this->tahap_kurir ?? match ($this->status_pengiriman) {
            'Menunggu' => 'Belum Dikirim',
            'Diproses' => self::DISIAPKAN,
            'Ditolak' => 'Dibatalkan',
            default => $this->status_pengiriman,
        };
    }

    public function dikirimHistoris(): bool
    {
        return $this->status_pengiriman === 'Dikirim'
            && $this->tahap_kurir === null
            && $this->kurir_id === null
            && $this->foto_penyerahan === null;
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatPengiriman::class)->orderBy('id');
    }

    public function kendala()
    {
        return $this->hasMany(KendalaPengiriman::class)->orderByDesc('id');
    }

    protected $casts = [
        'ongkos_kirim' => 'decimal:2',
        'waktu_dikirim' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    // Relasi ke pesanan
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}
