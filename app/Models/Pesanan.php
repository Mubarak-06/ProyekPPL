<?php

namespace App\Models;

use App\Services\AlurPengiriman;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    // Pemanggil mengunci pesanan dan pembayaran dalam satu transaksi.
    public function batalkanDanKembalikanStok(?User $pelaku = null): bool
    {
        if (! in_array($this->status_pesanan, ['Menunggu', 'Diproses'], true)) {
            return false;
        }
        $pengiriman = $this->pengiriman()->lockForUpdate()->get();
        $detail = $this->detailPesanan()->orderBy('produk_id')->get();
        if ($pengiriman->count() !== 1 || $pengiriman->first()->status_pengiriman !== $this->status_pesanan
            || $detail->isEmpty() || $detail->contains(fn ($item) => $item->jumlah < 1)) {
            return false;
        }
        $produk = Produk::whereIn('id', $detail->pluck('produk_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($detail->contains(fn ($item) => ! $produk->has($item->produk_id))) {
            return false;
        }

        foreach ($detail as $item) {
            $produk[$item->produk_id]->increment('stok', $item->jumlah);
        }
        $this->update(['status_pesanan' => 'Ditolak']);
        if (AlurPengiriman::tersedia()) {
            app(AlurPengiriman::class)->ubah($pengiriman->first(), $pelaku, 'Dibatalkan', ['status_pengiriman' => 'Ditolak']);
        } else {
            $this->pengiriman()->update(['status_pengiriman' => 'Ditolak']);
        }

        return true;
    }

    public function alasanPembatalan(): ?string
    {
        if ($this->status_pesanan !== 'Ditolak') {
            return null;
        }

        return match ($this->pembayaran?->midtrans_status) {
            'expire' => 'Pembayaran kedaluwarsa. Pesanan dibatalkan.',
            'cancel' => 'Midtrans membatalkan pembayaran. Pesanan dibatalkan.',
            'deny' => 'Midtrans menolak pembayaran. Pesanan dibatalkan.',
            default => 'Pesanan telah dibatalkan.',
        };
    }

    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan',
        'user_id',
        'alamat_id',
        'subtotal',
        'ongkos_kirim',
        'total_pembayaran',
        'status_pesanan',
        'catatan',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'ongkos_kirim' => 'decimal:2',
        'total_pembayaran' => 'decimal:2',
    ];

    // Relasi ke pelanggan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke alamat pelanggan
    public function alamat()
    {
        return $this->belongsTo(Alamat::class, 'alamat_id');
    }

    // Relasi ke detail pesanan
    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    // Relasi ke pembayaran
    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'pesanan_id');
    }

    // Relasi ke pengiriman
    public function pengiriman()
    {
        return $this->hasOne(Pengiriman::class, 'pesanan_id');
    }
}
