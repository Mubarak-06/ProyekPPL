<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $keranjang = $request->session()->get('keranjang', []);
        $keranjang = array_intersect_key($keranjang, array_flip($request->session()->get('keranjang_pilihan', array_keys($keranjang))));
        if (!$keranjang) {
            return redirect()->route('pelanggan.keranjang')->with('error', 'Keranjang masih kosong.');
        }
        $produk = Produk::with(['gambarProduk', 'material'])->whereIn('id', array_keys($keranjang))->get();
        try {
            $this->validasiProduk($produk, $keranjang);
        } catch (ValidationException $e) {
            return redirect()->route('pelanggan.keranjang')->withErrors($e->errors());
        }
        $subtotal = $this->subtotal($produk, $keranjang);
        $alamat = Alamat::with('kelurahan.ongkir')->where('user_id', $request->user()->id)->get();
        $wilayahAlamat = DB::table('kelurahan')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
            ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
            ->whereIn('kelurahan.id', $alamat->pluck('kelurahan_id'))
            ->select('kelurahan.id', 'nama_kecamatan', 'nama_kota', 'nama_provinsi')
            ->get()->keyBy('id');
        $metode = DB::table('metode_pembayaran')->where('status', 'aktif')->orderBy('id')->get();
        foreach ($metode as $item) {
            $item->tersedia = $item->jenis_metode === 'qris'
                ? ($item->gambar_qris && Storage::disk('public')->exists($item->gambar_qris))
                : !empty($item->nomor_rekening);
        }
        $alamatDipilih = $alamat->firstWhere('id', $request->input('alamat_id')) ?? $alamat->first();
        $ongkir = $alamatDipilih?->kelurahan?->ongkir;
        $token = $request->session()->get('checkout_token');
        if (!$token) {
            $token = Str::random(40);
            $request->session()->put('checkout_token', $token);
        }

        return view('pelanggan.checkout', compact('keranjang', 'produk', 'subtotal', 'alamat', 'wilayahAlamat', 'metode', 'alamatDipilih', 'ongkir', 'token'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'alamat_id' => 'required|integer',
            'metode_pembayaran_id' => 'required|integer',
            'catatan' => 'nullable|string|max:2000',
            'checkout_token' => 'required|string|size:40',
        ]);
        if (!hash_equals((string) $request->session()->get('checkout_token'), $data['checkout_token'])) {
            throw ValidationException::withMessages(['checkout' => 'Form checkout sudah digunakan. Silakan buka kembali checkout.']);
        }
        $keranjang = $request->session()->get('keranjang', []);
        $keranjang = array_intersect_key($keranjang, array_flip($request->session()->get('keranjang_pilihan', array_keys($keranjang))));

        $pesanan = DB::transaction(function () use ($request, $data, $keranjang) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            // Token yang sama tidak boleh membuat dua pesanan saat tombol diklik berulang.
            $sudahAda = DB::table('pembayaran')->join('pesanan', 'pembayaran.pesanan_id', '=', 'pesanan.id')
                ->where('referensi_pembayaran', $data['checkout_token'])
                ->where('pesanan.user_id', $request->user()->id)->value('pesanan.id');
            if ($sudahAda) {
                return Pesanan::findOrFail($sudahAda);
            }

            $alamat = Alamat::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($data['alamat_id']);
            $ongkir = DB::table('ongkir')->where('kelurahan_id', $alamat->kelurahan_id)->lockForUpdate()->first();
            $metode = DB::table('metode_pembayaran')->where('status', 'aktif')->where('id', $data['metode_pembayaran_id'])->lockForUpdate()->first();
            if (!$ongkir || !$metode) {
                throw ValidationException::withMessages(['checkout' => 'Ongkir atau metode pembayaran tidak tersedia. Silakan pilih kembali.']);
            }
            $metodeSiap = $metode->jenis_metode === 'qris'
                ? ($metode->gambar_qris && Storage::disk('public')->exists($metode->gambar_qris))
                : !empty($metode->nomor_rekening);
            if (!$metodeSiap) {
                throw ValidationException::withMessages(['checkout' => 'Informasi metode pembayaran belum lengkap. Silakan pilih metode lain.']);
            }

            $produk = Produk::whereIn('id', array_keys($keranjang))->orderBy('id')->lockForUpdate()->get();
            $this->validasiProduk($produk, $keranjang);
            $subtotal = $this->subtotal($produk, $keranjang);
            $total = ($subtotal + (int) round($ongkir->biaya_ongkir * 100)) / 100;
            $pesanan = Pesanan::create([
                'kode_pesanan' => 'KA-'.now()->format('Ymd').'-'.strtoupper(Str::random(12)),
                'user_id' => $request->user()->id,
                'alamat_id' => $alamat->id,
                'subtotal' => $subtotal / 100,
                'ongkos_kirim' => $ongkir->biaya_ongkir,
                'total_pembayaran' => $total,
                'status_pesanan' => 'Menunggu',
                'catatan' => $data['catatan'] ?? null,
            ]);
            foreach ($produk as $item) {
                $jumlah = (int) $keranjang[$item->id];
                $pesanan->detailPesanan()->create([
                    'produk_id' => $item->id,
                    'nama_produk' => $item->nama_produk,
                    'harga_satuan' => $item->harga,
                    'jumlah' => $jumlah,
                    'subtotal' => (int) round($item->harga * 100) * $jumlah / 100,
                ]);
                $item->decrement('stok', $jumlah);
            }
            DB::table('pembayaran')->insert([
                'pesanan_id' => $pesanan->id,
                'metode_pembayaran_id' => $metode->id,
                'metode_pembayaran' => ['transfer_bank' => 'Transfer Bank', 'ewallet' => 'E-Wallet', 'qris' => 'QRIS'][$metode->jenis_metode],
                'jumlah_pembayaran' => $total,
                'status_pembayaran' => 'Menunggu',
                'referensi_pembayaran' => $data['checkout_token'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wilayah = DB::table('kelurahan')
                ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
                ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
                ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
                ->where('kelurahan.id', $alamat->kelurahan_id)->first();
            $alamatLengkap = $alamat->alamat_lengkap.', '.$wilayah->nama_kelurahan.', '.$wilayah->nama_kecamatan.', '.$wilayah->nama_kota.', '.$wilayah->nama_provinsi.' '.$alamat->kode_pos;
            $pesanan->pengiriman()->create([
                'nama_penerima' => $alamat->nama_penerima,
                'nomor_telepon' => $alamat->nomor_telepon,
                'alamat_pengiriman' => $alamatLengkap,
                'ongkos_kirim' => $ongkir->biaya_ongkir,
                'status_pengiriman' => 'Menunggu',
            ]);

            return $pesanan;
        }, 3);

        $sisa = array_diff_key($request->session()->get('keranjang', []), $keranjang);
        if ($sisa) {
            $request->session()->put('keranjang', $sisa);
        } else {
            $request->session()->forget('keranjang');
        }
        $request->session()->forget(['keranjang_pilihan', 'checkout_token']);

        return redirect()->route('pelanggan.pesanan.show', $pesanan->id)->with('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');
    }

    private function validasiProduk($produk, array $keranjang): void
    {
        if (!$keranjang || $produk->count() !== count($keranjang)) {
            throw ValidationException::withMessages(['keranjang' => 'Keranjang kosong atau terdapat produk yang sudah tidak tersedia. Periksa kembali keranjang.']);
        }
        foreach ($produk as $item) {
            $jumlah = filter_var($keranjang[$item->id], FILTER_VALIDATE_INT);
            if ($jumlah === false || $jumlah < 1 || $jumlah > $item->stok) {
                throw ValidationException::withMessages(['keranjang' => 'Stok '.$item->nama_produk.' tidak mencukupi. Periksa kembali keranjang.']);
            }
        }
    }

    private function subtotal($produk, array $keranjang): int
    {
        return $produk->sum(fn ($item) => (int) round($item->harga * 100) * $keranjang[$item->id]);
    }
}
