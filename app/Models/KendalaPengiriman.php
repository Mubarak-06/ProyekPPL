<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KendalaPengiriman extends Model
{
    protected $table = 'kendala_pengiriman';
    public $timestamps = false;
    protected $fillable = [
        'pelapor_id', 'peran_pelapor', 'catatan', 'dilaporkan_at',
        'penyelesai_id', 'penyelesaian', 'diselesaikan_at',
    ];
    protected $casts = ['dilaporkan_at' => 'datetime', 'diselesaikan_at' => 'datetime'];
}
