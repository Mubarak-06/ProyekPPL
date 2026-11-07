<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LaporanPenjualanExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        // 1. Periode dan filter kategori
        $tanggalAwal = $request->input(
            'tanggal_awal',
            now()->startOfMonth()->toDateString()
        );

        $tanggalAkhir = $request->input(
            'tanggal_akhir',
            now()->toDateString()
        );

        $request->merge([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
        ]);

        $request->validate([
            'tanggal_awal' => ['required', 'date'],
            'tanggal_akhir' => [
                'required',
                'date',
                'after_or_equal:tanggal_awal'
            ],
            'kategori_id' => [
                'nullable',
                'integer',
                'exists:kategori,id'
            ],
        ]);

        $kategoriId = $request->input('kategori_id');

        $kategori = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->get();

        // 2. Subquery tanggal pembayaran berhasil pertama
        // Setiap pesanan hanya muncul satu kali.
        $pembayaranPertama = DB::table('pembayaran')
            ->select('pesanan_id')
            ->selectRaw('MIN(waktu_pembayaran) as tanggal_bayar')
            ->where('status_pembayaran', 'Berhasil')
            ->whereNotNull('waktu_pembayaran')
            ->groupBy('pesanan_id');

        // 3. Query pesanan berdasarkan tanggal pembuatan
        $queryPesanan = DB::table('pesanan')
            ->whereDate('pesanan.created_at', '>=', $tanggalAwal)
            ->whereDate('pesanan.created_at', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->whereExists(function ($subquery) use ($kategoriId) {
                    $subquery->selectRaw('1')
                        ->from('detail_pesanan')
                        ->join(
                            'produk',
                            'detail_pesanan.produk_id',
                            '=',
                            'produk.id'
                        )
                        ->whereColumn(
                            'detail_pesanan.pesanan_id',
                            'pesanan.id'
                        )
                        ->where('produk.kategori_id', $kategoriId);
                });
            });

        $jumlahPesanan = (clone $queryPesanan)->count();

        $pesananSelesai = (clone $queryPesanan)
            ->where('pesanan.status_pesanan', 'Selesai')
            ->count();

        // 4. Query produk yang berhasil dibayar
        $queryPenjualan = DB::table('detail_pesanan')
            ->join(
                'pesanan',
                'detail_pesanan.pesanan_id',
                '=',
                'pesanan.id'
            )
            ->join(
                'produk',
                'detail_pesanan.produk_id',
                '=',
                'produk.id'
            )
            ->join(
                'kategori',
                'produk.kategori_id',
                '=',
                'kategori.id'
            )
            ->joinSub(
                $pembayaranPertama,
                'bayar',
                function ($join) {
                    $join->on(
                        'detail_pesanan.pesanan_id',
                        '=',
                        'bayar.pesanan_id'
                    );
                }
            )
            ->whereDate('bayar.tanggal_bayar', '>=', $tanggalAwal)
            ->whereDate('bayar.tanggal_bayar', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->where('produk.kategori_id', $kategoriId);
            });

        // 5. Kartu Total Penjualan dan Produk Terjual
        $totalPenjualan = (clone $queryPenjualan)
            ->sum('detail_pesanan.subtotal');

        $produkTerjual = (clone $queryPenjualan)
            ->sum('detail_pesanan.jumlah');

        // 6. Produk Terlaris
        $produkTerlaris = (clone $queryPenjualan)
            ->select(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as jumlah_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupBy(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->orderByDesc('jumlah_terjual')
            ->limit(5)
            ->get();

        // 7. Penjualan per Kategori
        $penjualanKategori = (clone $queryPenjualan)
            ->select(
                'kategori.id',
                'kategori.nama_kategori'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupBy(
                'kategori.id',
                'kategori.nama_kategori'
            )
            ->orderByDesc('total_penjualan')
            ->get();

        // 8. Grafik Penjualan
        $grafikPenjualan = (clone $queryPenjualan)
            ->selectRaw('DATE(bayar.tanggal_bayar) as tanggal')
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total'
            )
            ->groupByRaw('DATE(bayar.tanggal_bayar)')
            ->orderBy('tanggal')
            ->get();

        // 9. Jumlah pesanan per bulan
        $ringkasanPesanan = (clone $queryPesanan)
            ->selectRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m') as bulan"
            )
            ->selectRaw('COUNT(*) as jumlah_pesanan')
            ->groupByRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 10. Produk terjual dan penjualan per bulan
        $ringkasanPenjualan = (clone $queryPenjualan)
            ->selectRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m') as bulan"
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as produk_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupByRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 11. Menggabungkan ringkasan bulanan
        $ringkasanBulanan = collect();

        $bulanMulai = Carbon::parse($tanggalAwal)->startOfMonth();
        $bulanSelesai = Carbon::parse($tanggalAkhir)->startOfMonth();

        while ($bulanMulai->lte($bulanSelesai)) {

            $key = $bulanMulai->format('Y-m');

            $ringkasanBulanan->push((object) [
                'bulan' => $bulanMulai->locale('id')->translatedFormat('F Y'),

                'jumlah_pesanan' =>
                    $ringkasanPesanan[$key]->jumlah_pesanan ?? 0,

                'produk_terjual' =>
                    $ringkasanPenjualan[$key]->produk_terjual ?? 0,

                'total_penjualan' =>
                    $ringkasanPenjualan[$key]->total_penjualan ?? 0,
            ]);

            $bulanMulai->addMonth();
        }

        // 12. Mengirim data ke halaman Laporan
        return view('admin.laporan.index', compact(
            'tanggalAwal',
            'tanggalAkhir',
            'kategori',
            'jumlahPesanan',
            'pesananSelesai',
            'totalPenjualan',
            'produkTerjual',
            'produkTerlaris',
            'penjualanKategori',
            'grafikPenjualan',
            'ringkasanBulanan'
        ));
    }

    public function exportPdf(Request $request)
    {
        // 1. Validasi filter laporan
        $data = $request->validate([
            'tanggal_awal' => ['required', 'date'],
            'tanggal_akhir' => [
                'required',
                'date',
                'after_or_equal:tanggal_awal'
            ],
            'kategori_id' => [
                'nullable',
                'integer',
                'exists:kategori,id'
            ],
        ]);

        $tanggalAwal = $data['tanggal_awal'];
        $tanggalAkhir = $data['tanggal_akhir'];
        $kategoriId = $data['kategori_id'] ?? null;

        // 2. Mengambil kategori yang dipilih
        $namaKategori = 'Semua Kategori';

        if ($kategoriId) {
            $namaKategori = DB::table('kategori')
                ->where('id', $kategoriId)
                ->value('nama_kategori');
        }

        // 3. Mengambil semua kategori produk
        $semuaKategori = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->get();

        // 4. Mengambil pembayaran berhasil pertama
        // agar setiap pesanan tidak dihitung berulang
        $pembayaranPertama = DB::table('pembayaran')
            ->select('pesanan_id')
            ->selectRaw('MIN(waktu_pembayaran) as tanggal_bayar')
            ->where('status_pembayaran', 'Berhasil')
            ->whereNotNull('waktu_pembayaran')
            ->groupBy('pesanan_id');

        // 5. Query pesanan berdasarkan tanggal dibuat
        $queryPesanan = DB::table('pesanan')
            ->whereDate('pesanan.created_at', '>=', $tanggalAwal)
            ->whereDate('pesanan.created_at', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->whereExists(function ($subquery) use ($kategoriId) {
                    $subquery->selectRaw('1')
                        ->from('detail_pesanan')
                        ->join(
                            'produk',
                            'detail_pesanan.produk_id',
                            '=',
                            'produk.id'
                        )
                        ->whereColumn(
                            'detail_pesanan.pesanan_id',
                            'pesanan.id'
                        )
                        ->where('produk.kategori_id', $kategoriId);
                });
            });

        $jumlahPesanan = (clone $queryPesanan)->count();

        $pesananSelesai = (clone $queryPesanan)
            ->where('pesanan.status_pesanan', 'Selesai')
            ->count();

        // 6. Query produk dari pesanan yang berhasil dibayar
        $queryPenjualan = DB::table('detail_pesanan')
            ->join(
                'pesanan',
                'detail_pesanan.pesanan_id',
                '=',
                'pesanan.id'
            )
            ->join(
                'produk',
                'detail_pesanan.produk_id',
                '=',
                'produk.id'
            )
            ->join(
                'kategori',
                'produk.kategori_id',
                '=',
                'kategori.id'
            )
            ->joinSub(
                $pembayaranPertama,
                'bayar',
                function ($join) {
                    $join->on(
                        'detail_pesanan.pesanan_id',
                        '=',
                        'bayar.pesanan_id'
                    );
                }
            )
            ->whereDate('bayar.tanggal_bayar', '>=', $tanggalAwal)
            ->whereDate('bayar.tanggal_bayar', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->where('produk.kategori_id', $kategoriId);
            });

        // 7. Ringkasan penjualan
        $totalPenjualan = (clone $queryPenjualan)
            ->sum('detail_pesanan.subtotal');

        $produkTerjual = (clone $queryPenjualan)
            ->sum('detail_pesanan.jumlah');

        // 8. Penjualan per kategori
        // Tetap menampilkan kategori dengan penjualan Rp0
        $penjualanKategori = $semuaKategori
            ->when($kategoriId, function ($kategori) use ($kategoriId) {
                return $kategori->where('id', $kategoriId);
            })
            ->map(function ($kategori) use ($queryPenjualan) {

                $hasil = (clone $queryPenjualan)
                    ->where('kategori.id', $kategori->id)
                    ->selectRaw(
                        'SUM(detail_pesanan.jumlah) as jumlah_terjual'
                    )
                    ->selectRaw(
                        'SUM(detail_pesanan.subtotal) as total_penjualan'
                    )
                    ->first();

                return (object) [
                    'nama_kategori' => $kategori->nama_kategori,
                    'jumlah_terjual' => $hasil->jumlah_terjual ?? 0,
                    'total_penjualan' => $hasil->total_penjualan ?? 0,
                ];
            })
            ->values();

        // 9. Produk terlaris
        $produkTerlaris = (clone $queryPenjualan)
            ->select(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as jumlah_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupBy(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->orderByDesc('jumlah_terjual')
            ->limit(5)
            ->get();

        // 10. Ringkasan jumlah pesanan per bulan
        $ringkasanPesanan = (clone $queryPesanan)
            ->selectRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m') as bulan"
            )
            ->selectRaw('COUNT(*) as jumlah_pesanan')
            ->groupByRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 11. Ringkasan penjualan per bulan
        $ringkasanPenjualan = (clone $queryPenjualan)
            ->selectRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m') as bulan"
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as produk_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupByRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 12. Menggabungkan ringkasan bulanan
        $ringkasanBulanan = collect();

        $bulanMulai = Carbon::parse($tanggalAwal)->startOfMonth();
        $bulanSelesai = Carbon::parse($tanggalAkhir)->startOfMonth();

        while ($bulanMulai->lte($bulanSelesai)) {

            $key = $bulanMulai->format('Y-m');

            $ringkasanBulanan->push((object) [
                'bulan' => $bulanMulai
                    ->locale('id')
                    ->translatedFormat('F Y'),

                'jumlah_pesanan' =>
                    $ringkasanPesanan[$key]->jumlah_pesanan ?? 0,

                'produk_terjual' =>
                    $ringkasanPenjualan[$key]->produk_terjual ?? 0,

                'total_penjualan' =>
                    $ringkasanPenjualan[$key]->total_penjualan ?? 0,
            ]);

            $bulanMulai->addMonth();
        }

        // 13. Membuat file PDF
        $pdf = Pdf::loadView('admin.laporan.pdf', compact(
            'tanggalAwal',
            'tanggalAkhir',
            'namaKategori',
            'jumlahPesanan',
            'pesananSelesai',
            'totalPenjualan',
            'produkTerjual',
            'penjualanKategori',
            'produkTerlaris',
            'ringkasanBulanan'
        ))->setPaper('a4', 'portrait');

        return $pdf->download(
            'Laporan_Penjualan_' .
            $tanggalAwal .
            '_sampai_' .
            $tanggalAkhir .
            '.pdf'
        );
    }

    public function exportExcel(Request $request)
    {
        // 1. Validasi filter
        $data = $request->validate([
            'tanggal_awal' => ['required', 'date'],
            'tanggal_akhir' => [
                'required',
                'date',
                'after_or_equal:tanggal_awal'
            ],
            'kategori_id' => [
                'nullable',
                'integer',
                'exists:kategori,id'
            ],
        ]);

        $tanggalAwal = $data['tanggal_awal'];
        $tanggalAkhir = $data['tanggal_akhir'];
        $kategoriId = $data['kategori_id'] ?? null;

        // 2. Nama kategori
        $namaKategori = 'Semua Kategori';

        if ($kategoriId) {
            $namaKategori = DB::table('kategori')
                ->where('id', $kategoriId)
                ->value('nama_kategori');
        }

        $semuaKategori = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->get();

        // 3. Pembayaran berhasil pertama untuk setiap pesanan
        $pembayaranPertama = DB::table('pembayaran')
            ->select('pesanan_id')
            ->selectRaw('MIN(waktu_pembayaran) as tanggal_bayar')
            ->where('status_pembayaran', 'Berhasil')
            ->whereNotNull('waktu_pembayaran')
            ->groupBy('pesanan_id');

        // 4. Jumlah pesanan berdasarkan tanggal dibuat
        $queryPesanan = DB::table('pesanan')
            ->whereDate('pesanan.created_at', '>=', $tanggalAwal)
            ->whereDate('pesanan.created_at', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->whereExists(function ($subquery) use ($kategoriId) {
                    $subquery->selectRaw('1')
                        ->from('detail_pesanan')
                        ->join(
                            'produk',
                            'detail_pesanan.produk_id',
                            '=',
                            'produk.id'
                        )
                        ->whereColumn(
                            'detail_pesanan.pesanan_id',
                            'pesanan.id'
                        )
                        ->where('produk.kategori_id', $kategoriId);
                });
            });

        $jumlahPesanan = (clone $queryPesanan)->count();

        $pesananSelesai = (clone $queryPesanan)
            ->where('pesanan.status_pesanan', 'Selesai')
            ->count();

        // 5. Penjualan berdasarkan pembayaran berhasil
        $queryPenjualan = DB::table('detail_pesanan')
            ->join(
                'pesanan',
                'detail_pesanan.pesanan_id',
                '=',
                'pesanan.id'
            )
            ->join(
                'produk',
                'detail_pesanan.produk_id',
                '=',
                'produk.id'
            )
            ->join(
                'kategori',
                'produk.kategori_id',
                '=',
                'kategori.id'
            )
            ->joinSub(
                $pembayaranPertama,
                'bayar',
                function ($join) {
                    $join->on(
                        'detail_pesanan.pesanan_id',
                        '=',
                        'bayar.pesanan_id'
                    );
                }
            )
            ->whereDate('bayar.tanggal_bayar', '>=', $tanggalAwal)
            ->whereDate('bayar.tanggal_bayar', '<=', $tanggalAkhir)
            ->when($kategoriId, function ($query) use ($kategoriId) {
                $query->where('produk.kategori_id', $kategoriId);
            });

        $totalPenjualan = (clone $queryPenjualan)
            ->sum('detail_pesanan.subtotal');

        $produkTerjual = (clone $queryPenjualan)
            ->sum('detail_pesanan.jumlah');

        // 6. Penjualan per kategori
        $penjualanKategori = $semuaKategori
            ->when($kategoriId, function ($kategori) use ($kategoriId) {
                return $kategori->where('id', $kategoriId);
            })
            ->map(function ($kategori) use ($queryPenjualan) {
                $hasil = (clone $queryPenjualan)
                    ->where('kategori.id', $kategori->id)
                    ->selectRaw(
                        'SUM(detail_pesanan.jumlah) as jumlah_terjual'
                    )
                    ->selectRaw(
                        'SUM(detail_pesanan.subtotal) as total_penjualan'
                    )
                    ->first();

                return (object) [
                    'nama_kategori' => $kategori->nama_kategori,
                    'jumlah_terjual' => $hasil->jumlah_terjual ?? 0,
                    'total_penjualan' => $hasil->total_penjualan ?? 0,
                ];
            })
            ->values();

        // 7. Produk terlaris
        $produkTerlaris = (clone $queryPenjualan)
            ->select(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as jumlah_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupBy(
                'detail_pesanan.produk_id',
                'detail_pesanan.nama_produk',
                'kategori.nama_kategori'
            )
            ->orderByDesc('jumlah_terjual')
            ->limit(5)
            ->get();

        // 8. Jumlah pesanan per bulan
        $ringkasanPesanan = (clone $queryPesanan)
            ->selectRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m') as bulan"
            )
            ->selectRaw('COUNT(*) as jumlah_pesanan')
            ->groupByRaw(
                "DATE_FORMAT(pesanan.created_at, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 9. Penjualan per bulan
        $ringkasanPenjualan = (clone $queryPenjualan)
            ->selectRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m') as bulan"
            )
            ->selectRaw(
                'SUM(detail_pesanan.jumlah) as produk_terjual'
            )
            ->selectRaw(
                'SUM(detail_pesanan.subtotal) as total_penjualan'
            )
            ->groupByRaw(
                "DATE_FORMAT(bayar.tanggal_bayar, '%Y-%m')"
            )
            ->get()
            ->keyBy('bulan');

        // 10. Menggabungkan data bulanan
        $ringkasanBulanan = collect();

        $bulanMulai = Carbon::parse($tanggalAwal)->startOfMonth();
        $bulanSelesai = Carbon::parse($tanggalAkhir)->startOfMonth();

        while ($bulanMulai->lte($bulanSelesai)) {
            $key = $bulanMulai->format('Y-m');

            $ringkasanBulanan->push((object) [
                'bulan' => $bulanMulai
                    ->locale('id')
                    ->translatedFormat('F Y'),
                'jumlah_pesanan' =>
                    $ringkasanPesanan[$key]->jumlah_pesanan ?? 0,
                'produk_terjual' =>
                    $ringkasanPenjualan[$key]->produk_terjual ?? 0,
                'total_penjualan' =>
                    $ringkasanPenjualan[$key]->total_penjualan ?? 0,
            ]);

            $bulanMulai->addMonth();
        }

        // 11. Mengirim data ke empat sheet Excel
        $laporan = [
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'namaKategori' => $namaKategori,
            'jumlahPesanan' => $jumlahPesanan,
            'pesananSelesai' => $pesananSelesai,
            'totalPenjualan' => $totalPenjualan,
            'produkTerjual' => $produkTerjual,
            'penjualanKategori' => $penjualanKategori,
            'produkTerlaris' => $produkTerlaris,
            'ringkasanBulanan' => $ringkasanBulanan,
        ];

        // 12. Mengunduh file Excel
        return Excel::download(
            new LaporanPenjualanExport($laporan),
            'Laporan_Penjualan_' .
            $tanggalAwal .
            '_sampai_' .
            $tanggalAkhir .
            '.xlsx'
        );
    }
}