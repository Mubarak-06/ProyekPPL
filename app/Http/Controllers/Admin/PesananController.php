<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\AlurPengiriman;
use App\Services\PemeriksaanPembayaranPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function show($id, PemeriksaanPembayaranPengiriman $pemeriksaan)
    {
        $pesanan = Pesanan::with([
            'user',
            'alamat',
            'detailPesanan.produk',
            'pembayaran.rekonsiliasiAktif',
            'pengiriman',
        ])->findOrFail($id);

        $hambatanProses = $pesanan->status_pesanan === 'Menunggu'
            ? $pemeriksaan->hambatanPembayaranMidtrans($pesanan, 'Diproses', kunci: false)
            : null;

        return view('admin.pesanan.show', compact('pesanan', 'hambatanProses'));
    }

    public function updateStatus(Request $request, $id, AlurPengiriman $alur)
    {
        $request->validate(['status_pesanan' => 'required|in:Diproses']);

        return $alur->proses($request->user(), (int) $id);
    }

    public function tolak(Request $request, $id, PemeriksaanPembayaranPengiriman $pemeriksaan)
    {
        return DB::transaction(function () use ($id, $request, $pemeriksaan) {
            $pesanan = Pesanan::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!in_array($pesanan->status_pesanan, ['Menunggu', 'Diproses'], true)) {
                return back()->with(
                    'error',
                    'Pesanan dengan status ini tidak dapat ditolak.'
                );
            }

            if ($pesan = $pemeriksaan->hambatanPembayaranMidtrans($pesanan, 'Ditolak')) {
                return back()->with('error', $pesan);
            }

            if (!$pesanan->batalkanDanKembalikanStok($request->user())) {
                return back()->with('error', 'Pesanan belum dapat dibatalkan. Periksa detail barang dan catatan pengiriman.');
            }

            return back()->with(
                'success',
                'Pesanan berhasil ditolak.'
            );
        }, 3);
    }
}
