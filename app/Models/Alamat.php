<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alamat extends Model
{
    protected $table = 'alamat';

    protected $fillable = [
        'user_id',
        'nama_penerima',
        'nomor_telepon',
        'alamat_lengkap',
        'kelurahan_id',
        'kode_pos',
    ];

    // Relasi ke pengguna
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke pesanan
    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'alamat_id');
    }
}