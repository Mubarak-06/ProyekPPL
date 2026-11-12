<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'material';

    protected $fillable = [
        'nama_material',
    ];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'material_id');
    }
}
