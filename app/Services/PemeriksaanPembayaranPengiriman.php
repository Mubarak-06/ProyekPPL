<?php

namespace App\Services;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pesanan;

class PemeriksaanPembayaranPengiriman
{
    public function hambatanPembayaranMidtrans(Pesanan $pesanan, string $tujuan, bool $kunci = true): ?string
    {
        $pembayaranPesanan = $pesanan->pembayaran()->orderBy('id')
            ->when($kunci, fn ($query) => $query->lockForUpdate())->get();
        if ($pembayaranPesanan->count() > 1) {
            return 'Pesanan memiliki beberapa catatan pembayaran. Periksa catatan sebelum mengubah status.';
        }
        if (
            $tujuan === 'Ditolak' && $pembayaranPesanan->contains(
                fn($item) => $item->status_pembayaran === 'Berhasil' || $item->waktu_pembayaran !== null
            )
        ) {
            return 'Penolakan ditahan karena pembayaran telah tercatat. Selesaikan penanganan dana terlebih dahulu.';
        }
        $pembayaran = $pembayaranPesanan->firstWhere('gateway', 'midtrans');

        if ($pembayaran === null) {
            if ($tujuan === 'Ditolak') {
                return null;
            }

            return $pembayaranPesanan->isEmpty()
                ? 'Status pesanan tidak dapat dilanjutkan karena catatan pembayaran tidak ditemukan.'
                : 'Status pesanan tidak dapat dilanjutkan karena pembayaran historis ini tidak memiliki verifikasi Midtrans. Hubungi pengelola untuk meninjau pesanan.';
        }

        if ($tujuan !== 'Ditolak') {
            if (
                $pembayaran->status_pembayaran !== 'Berhasil' || blank($pembayaran->midtrans_transaction_id)
                || !in_array($pembayaran->midtrans_status, ['settlement', 'capture'], true)
            ) {
                return 'Pesanan hanya dapat diproses, dikirim, atau diselesaikan setelah pembayaran Midtrans terverifikasi berhasil.';
            }

            $kasusPembayaran = MidtransRekonsiliasi::where('pembayaran_id', $pembayaran->getKey())
                ->orderBy('id')
                ->when($kunci, fn ($query) => $query->lockForUpdate())
                ->get();

            foreach ($kasusPembayaran as $kasus) {
                if ($kasus->menahanPemenuhan($pembayaran)) {
                    return 'Tindakan ditahan karena masih ada hambatan pemenuhan pembayaran Midtrans.';
                }
            }

            return null;
        }

        if (
            $pembayaran->status_pembayaran !== 'Menunggu'
            || $pembayaran->snap_requested_at !== null
            || $pembayaran->getRawOriginal('snap_token') !== null
            || $pembayaran->getRawOriginal('snap_redirect_url') !== null
            || $pembayaran->snap_token_expires_at !== null
            || $pembayaran->midtrans_transaction_id !== null
            || $pembayaran->midtrans_status !== null
            || $pembayaran->midtrans_payment_type !== null
            || MidtransRekonsiliasi::where('pembayaran_id', $pembayaran->getKey())->exists()
        ) {
            return 'Penolakan ditahan. Pastikan sesi dan pembayaran Midtrans telah diperiksa melalui rekonsiliasi.';
        }

        return null;
    }
}
