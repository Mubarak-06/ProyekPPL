<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPengiriman extends Model
{
    protected $table = 'riwayat_pengiriman';
    public $timestamps = false;
    protected $fillable = ['pelaku_id', 'status_lama', 'status_baru', 'catatan', 'terjadi_at'];
    protected $casts = ['terjadi_at' => 'datetime'];

    public function pelaku()
    {
        return $this->belongsTo(User::class, 'pelaku_id');
    }
}
