<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelurahan extends Model
{
    protected $table = 'kelurahan';

    protected $fillable = [
        'kecamatan_id',
        'nama_kelurahan',
    ];

    public function ongkir()
    {
        return $this->hasOne(Ongkir::class, 'kelurahan_id');
    }
}