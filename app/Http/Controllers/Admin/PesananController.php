<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'pengiriman'
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
            'pembayaran',
            'pengiriman'
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
        });
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

            $pesanan->update([
                'status_pesanan' => 'Ditolak',
            ]);

            DB::table('pengiriman')
                ->where('pesanan_id', $pesanan->id)
                ->update([
                    'status_pengiriman' => 'Ditolak',
                ]);

            return back()->with(
                'success',
                'Pesanan berhasil ditolak.'
            );
        });
    }
}