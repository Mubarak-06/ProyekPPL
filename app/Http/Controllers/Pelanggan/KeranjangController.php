<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KeranjangController extends Controller
{
    public function index(Request $request)
    {
        $keranjang = $request->session()->get('keranjang', []);
        $produk = Produk::with('gambarProduk')->whereIn('id', array_keys($keranjang))->get();
        $subtotal = $produk->sum(fn ($item) => $item->harga * $keranjang[$item->id]);
        $produkHilang = count($keranjang) !== $produk->count();
        $pilihan = $request->session()->get('keranjang_pilihan', array_keys($keranjang));
        $subtotalPilihan = $produk->whereIn('id', $pilihan)->sum(fn ($item) => $item->harga * $keranjang[$item->id]);

        return view('pelanggan.keranjang', compact('keranjang', 'produk', 'subtotal', 'produkHilang', 'pilihan', 'subtotalPilihan'));
    }

    public function store(Request $request, Produk $produk)
    {
        $data = $request->validate(['jumlah' => 'required|integer|min:1|max:1000000']);
        $keranjang = $request->session()->get('keranjang', []);
        $jumlah = ($keranjang[$produk->id] ?? 0) + $data['jumlah'];
        $this->validasiStok($produk, $jumlah);
        $keranjang[$produk->id] = $jumlah;
        $request->session()->put('keranjang', $keranjang);
        if ($request->session()->has('keranjang_pilihan')) {
            $pilihan = $request->session()->get('keranjang_pilihan');
            $pilihan[] = $produk->id;
            $request->session()->put('keranjang_pilihan', array_values(array_unique($pilihan)));
        }

        if ($request->boolean('beli')) {
            $request->session()->put('keranjang_pilihan', [$produk->id]);
            return redirect()->route('pelanggan.checkout');
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.')->with('keranjang_ditambah', true);
    }

    public function pilih(Request $request)
    {
        $data = $request->validate(['pilihan' => 'nullable|array', 'pilihan.*' => 'integer|min:1']);
        $keranjang = $request->session()->get('keranjang', []);
        $pilihan = array_values(array_intersect(array_keys($keranjang), $data['pilihan'] ?? []));
        if ($request->input('aksi') === 'hapus') {
            foreach ($pilihan as $id) unset($keranjang[$id]);
            $request->session()->put('keranjang', $keranjang);
            $pilihan = [];
        }
        $request->session()->put('keranjang_pilihan', $pilihan);
        return back()->with('success', 'Pilihan keranjang diperbarui.');
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $request->validate(['jumlah' => 'required|integer|min:1|max:1000000']);
        $this->validasiStok($produk, $data['jumlah']);
        $keranjang = $request->session()->get('keranjang', []);
        abort_unless(isset($keranjang[$produk->id]), 404);
        $keranjang[$produk->id] = (int) $data['jumlah'];
        $request->session()->put('keranjang', $keranjang);

        return back()->with('success', 'Jumlah produk diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $keranjang = $request->session()->get('keranjang', []);
        unset($keranjang[$id]);
        $request->session()->put('keranjang', $keranjang);
        if ($request->session()->has('keranjang_pilihan')) {
            $request->session()->put('keranjang_pilihan', array_values(array_intersect(array_keys($keranjang), $request->session()->get('keranjang_pilihan'))));
        }

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    private function validasiStok(Produk $produk, int $jumlah): void
    {
        if ($jumlah > $produk->stok) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah melebihi stok '.$produk->nama_produk.'.']);
        }
    }
}
