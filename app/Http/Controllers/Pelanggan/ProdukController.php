<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function beranda()
    {
        $kategori = Kategori::orderBy('id')->get();
        $produk = Produk::with('gambarProduk')->withSum([
            'detailPesanan as jumlah_terjual' => function ($query) {
                $query->whereHas('pesanan', fn ($pesanan) => $pesanan->where('status_pesanan', 'Selesai'));
            },
        ], 'jumlah')->orderByDesc('jumlah_terjual')->orderByDesc('id')->limit(8)->get();
        $adaPenjualan = $produk->contains(fn ($item) => $item->jumlah_terjual > 0);

        if ($adaPenjualan) {
            $produk = $produk->where('jumlah_terjual', '>', 0);
        }

        return view('pelanggan.beranda', compact('kategori', 'produk', 'adaPenjualan'));
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150',
            'kategori' => 'nullable|integer|exists:kategori,id',
            'material' => 'nullable|integer|exists:material,id',
            'ukuran' => 'nullable|string|max:100',
        ]);
        $kategori = Kategori::orderBy('id')->get();
        $kategoriDipilih = $kategori->firstWhere('id', $data['kategori'] ?? null);
        $material = \App\Models\Material::orderBy('nama_material')->get();
        $ukuran = Produk::when($data['kategori'] ?? null, fn ($query, $id) => $query->where('kategori_id', $id))
            ->whereNotNull('ukuran')->where('ukuran', '!=', '')->distinct()->orderBy('ukuran')->pluck('ukuran');
        $produk = Produk::with('gambarProduk')
            ->when($data['q'] ?? null, function ($query, $pencarian) {
                $query->where(function ($query) use ($pencarian) {
                    $query->where('nama_produk', 'like', '%'.$pencarian.'%')
                        ->orWhere('kode_barang', 'like', '%'.$pencarian.'%');
                });
            })
            ->when($data['kategori'] ?? null, fn ($query, $id) => $query->where('kategori_id', $id))
            ->when($data['material'] ?? null, fn ($query, $id) => $query->where('material_id', $id))
            ->when($data['ukuran'] ?? null, fn ($query, $nilai) => $query->where('ukuran', $nilai))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return view('pelanggan.produk.index', compact('kategori', 'produk', 'kategoriDipilih', 'material', 'ukuran'));
    }

    public function show(Produk $produk)
    {
        $produk->load('kategori', 'material', 'satuan', 'gambarProduk');

        return view('pelanggan.produk.show', compact('produk'));
    }
}
