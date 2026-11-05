<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ongkir extends Model
{
    protected $table = 'ongkir';

    protected $fillable = [
        'kelurahan_id',
        'jarak_km',
        'biaya_ongkir',
    ];

    protected $casts = [
        'jarak_km' => 'decimal:2',
        'biaya_ongkir' => 'decimal:2',
    ];

    public function kelurahan()
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id');
    }
}