<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MidtransRekonsiliasi;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PesananController extends Controller
{
    public function index()
    {
        $pesanan = Pesanan::with([
            'user',
            'alamat',
            'detailPesanan.produk',
            'pembayaran',
            'pengiriman',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        $jumlahStatus = [
            'Semua' => $pesanan->count(),
            'Menunggu' => $pesanan->where('status_pesanan', 'Menunggu')->count(),
            'Diproses' => $pesanan->where('status_pesanan', 'Diproses')->count(),
            'Dikirim' => $pesanan->where('status_pesanan', 'Dikirim')->count(),
            'Selesai' => $pesanan->where('status_pesanan', 'Selesai')->count(),
            'Ditolak' => $pesanan->where('status_pesanan', 'Ditolak')->count(),
        ];

        return view('admin.pesanan.index', compact(
            'pesanan',
            'jumlahStatus'
        ));
    }

    public function show($id)
    {
        $pesanan = Pesanan::with([
            'user',
            'alamat',
            'detailPesanan.produk',
            'pembayaran.rekonsiliasiAktif',
            'pengiriman',
        ])->findOrFail($id);

        return view('admin.pesanan.show', compact('pesanan'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_pesanan' => [
                'required',
                Rule::in(['Diproses', 'Dikirim', 'Selesai']),
            ],
        ]);

        $transisi = [
            'Menunggu' => ['Diproses'],
            'Diproses' => ['Dikirim'],
            'Dikirim' => ['Selesai'],
            'Selesai' => [],
            'Ditolak' => [],
        ];

        return DB::transaction(function () use ($request, $id, $transisi) {
            $pesanan = Pesanan::whereKey($id)->lockForUpdate()->firstOrFail();

            $statusLama = $pesanan->status_pesanan;
            $statusBaru = $request->input('status_pesanan');

            if (!in_array($statusBaru, $transisi[$statusLama] ?? [], true)) {
                return back()->with(
                    'error',
                    'Perubahan status pesanan tidak diizinkan.'
                );
            }

            if ($pesan = $this->hambatanPembayaranMidtrans($pesanan, $statusBaru)) {
                return back()->with('error', $pesan);
            }
            $pengiriman = $pesanan->pengiriman()->lockForUpdate()->get();
            if ($pengiriman->count() !== 1 || $pengiriman->first()->status_pengiriman !== $statusLama) {
                return back()->with('error', 'Catatan pengiriman tidak tersedia atau tidak sesuai dengan status pesanan.');
            }

            if ($statusBaru === 'Selesai' && !$request->boolean('konfirmasi_penerimaan')) {
                return back()->with(
                    'error',
                    'Pesanan hanya dapat diselesaikan setelah penerimaan barang dikonfirmasi.'
                );
            }

            $pesanan->update([
                'status_pesanan' => $statusBaru,
            ]);

            $perubahanPengiriman = [
                'status_pengiriman' => $statusBaru,
            ];

            if ($statusBaru === 'Dikirim') {
                $perubahanPengiriman['waktu_dikirim'] = now();
            }

            if ($statusBaru === 'Selesai') {
                $perubahanPengiriman['waktu_selesai'] = now();
            }

            DB::table('pengiriman')
                ->where('pesanan_id', $pesanan->id)
                ->update($perubahanPengiriman);

            return back()->with(
                'success',
                'Status pesanan berhasil diperbarui.'
            );
        }, 3);
    }

    public function tolak($id)
    {
        return DB::transaction(function () use ($id) {
            $pesanan = Pesanan::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!in_array($pesanan->status_pesanan, ['Menunggu', 'Diproses'], true)) {
                return back()->with(
                    'error',
                    'Pesanan dengan status ini tidak dapat ditolak.'
                );
            }

            if ($pesan = $this->hambatanPembayaranMidtrans($pesanan, 'Ditolak')) {
                return back()->with('error', $pesan);
            }

            if (!$pesanan->batalkanDanKembalikanStok()) {
                return back()->with('error', 'Pesanan belum dapat dibatalkan. Periksa detail barang dan catatan pengiriman.');
            }

            return back()->with(
                'success',
                'Pesanan berhasil ditolak.'
            );
        }, 3);
    }

    private function hambatanPembayaranMidtrans(Pesanan $pesanan, string $tujuan): ?string
    {
        $pembayaranPesanan = $pesanan->pembayaran()->orderBy('id')->lockForUpdate()->get();
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
                ->lockForUpdate()
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
