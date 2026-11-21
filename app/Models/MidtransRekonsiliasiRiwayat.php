<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class MidtransRekonsiliasiRiwayat extends Model
{
    public $timestamps = false;

    protected $table = 'midtrans_rekonsiliasi_riwayat';

    protected static function booted(): void
    {
        static::updating(static fn () => throw new LogicException('Riwayat rekonsiliasi hanya dapat ditambahkan.'));
        static::deleting(static fn () => throw new LogicException('Riwayat rekonsiliasi hanya dapat ditambahkan.'));
    }
}
