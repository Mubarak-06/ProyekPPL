<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
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