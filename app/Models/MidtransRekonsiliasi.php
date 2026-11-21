<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtransRekonsiliasi extends Model
{
    protected $table = 'midtrans_rekonsiliasi';

    // Catatan dan referensi konfirmasi hanya untuk metadata yang sudah disanitasi.
    protected $fillable = [
        'pembayaran_id',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'status_transaksi',
        'jenis_pembayaran',
        'terakhir_diperiksa_at',
        'periksa_ulang_at',
        'status_penanganan',
        'catatan_manual',
        'pembatalan_dikonfirmasi_at',
        'sumber_konfirmasi_pembatalan',
        'referensi_konfirmasi_pembatalan',
    ];

    protected $hidden = [
        'catatan_manual',
        'referensi_konfirmasi_pembatalan',
    ];

    protected $casts = [
        'terakhir_diperiksa_at' => 'datetime',
        'periksa_ulang_at' => 'datetime',
        'pembatalan_dikonfirmasi_at' => 'datetime',
    ];

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function menahanPemenuhan(Pembayaran $payment): bool
    {
        return $this->status_penahanan !== 'dilepas'
            && ! $this->percobaanGagalTerpisah($payment);
    }

    public function percobaanGagalTerpisah(Pembayaran $payment): bool
    {
        return in_array($this->status_penanganan, ['menunggu', 'selesai'], true)
            && $this->catatan_manual === 'attempt_unsuccessful'
            && in_array($this->status_transaksi, ['deny', 'cancel', 'expire', 'failure'], true)
            && $payment->status_pembayaran === 'Berhasil'
            && in_array($payment->midtrans_status, ['settlement', 'capture'], true)
            && filled($this->midtrans_transaction_id)
            && filled($payment->midtrans_transaction_id)
            && $this->midtrans_transaction_id !== $payment->midtrans_transaction_id;
    }
}
