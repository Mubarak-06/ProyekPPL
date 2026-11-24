<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Services\MidtransPaymentCoordinator;
use App\Services\MidtransSnapService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const BATAS_NOMINAL = 9_999_999_999_999;

    public function index(Request $request, MidtransSnapService $snap)
    {
        $langsung = $request->query('langsung');
        $draft = $request->session()->get('checkout_langsung');
        if ($langsung !== null && $langsung !== ($draft['token'] ?? null)) {
            return redirect()->route('pelanggan.produk')->with('error', 'Pilihan Beli Sekarang sudah tidak tersedia.');
        }
        $keranjang = $langsung !== null ? [$draft['produk_id'] => $draft['jumlah']] : $this->pilihanKeranjang($request);
        if (! $keranjang) {
            return redirect()->route('pelanggan.keranjang')->with('error', 'Keranjang masih kosong.');
        }
        $produk = Produk::with('material')->whereIn('id', array_keys($keranjang))->orderBy('id')->get();
        $alamat = Alamat::with('kelurahan.ongkir')->where('user_id', $request->user()->id)->get();
        $wilayahAlamat = $this->wilayahPengiriman()
            ->whereIn('kelurahan.id', $alamat->pluck('kelurahan_id'))
            ->get()->keyBy('id');
        $metode = $snap->checkoutMethods();
        $alamatDipilih = $alamat->firstWhere('id', $request->input('alamat_id')) ?? $alamat->first();
        $ongkir = $alamatDipilih?->kelurahan?->ongkir;
        $wilayah = $wilayahAlamat->get($alamatDipilih?->kelurahan_id);
        try {
            $ringkasan = $this->ringkasan($produk, $keranjang, $alamatDipilih, $wilayah, $ongkir?->biaya_ongkir);
        } catch (ValidationException $e) {
            return redirect()->route($langsung !== null ? 'pelanggan.produk' : 'pelanggan.keranjang')
                ->withErrors($e->errors());
        }

        $subtotal = $ringkasan['subtotal'];
        $total = $ringkasan['total'];
        $key = $this->kunciRingkasan($langsung);
        $tersimpan = $request->session()->get($key);
        $token = null;
        if ($total !== null) {
            $sidik = $this->sidikRingkasan($request, $langsung, $ringkasan);
            if (($tersimpan['sidik'] ?? null) !== $sidik || $tersimpan['berlaku_sampai'] <= now()->timestamp) {
                $tersimpan = ['token' => Str::random(40), 'sidik' => $sidik, 'berlaku_sampai' => now()->addMinutes(30)->timestamp];
                $request->session()->put($key, $tersimpan);
            }
            $token = $tersimpan['token'];
        } else {
            $request->session()->forget($key);
        }

        return view('pelanggan.checkout', compact('keranjang', 'produk', 'subtotal', 'total', 'alamat', 'wilayahAlamat', 'metode', 'alamatDipilih', 'ongkir', 'token', 'langsung'));
    }

    public function store(Request $request, MidtransSnapService $snap, MidtransPaymentCoordinator $coordinator)
    {
        $data = $request->validate([
            'alamat_id' => 'required|integer',
            'metode_pembayaran' => 'required|string|max:32',
            'catatan' => 'nullable|string|max:2000',
            'checkout_token' => 'required|string|size:40',
            'langsung' => 'nullable|string|size:40',
        ]);
        $draft = $request->session()->get('checkout_langsung');
        $langsung = $data['langsung'] ?? null;
        $konteksValid = $langsung === null || $langsung === ($draft['token'] ?? null);
        $key = $this->kunciRingkasan($langsung);
        $tersimpan = $request->session()->get($key);
        $tokenSesuai = $konteksValid && hash_equals($tersimpan['token'] ?? '', $data['checkout_token']);
        $keranjang = $langsung === null ? $this->pilihanKeranjang($request)
            : ($konteksValid ? [$draft['produk_id'] => $draft['jumlah']] : []);
        $tujuan = $konteksValid
            ? route('pelanggan.checkout', array_filter(['langsung' => $langsung, 'alamat_id' => $data['alamat_id']]))
            : route('pelanggan.produk');

        try {
            [$pesanan, $baru] = $this->konfirmasi($request, $snap, $data, $keranjang, $langsung, $tokenSesuai ? $tersimpan : null);
        } catch (ValidationException $e) {
            if ($tokenSesuai) {
                $request->session()->forget($key);
            }

            throw $e->redirectTo($tujuan);
        }

        if ($baru) {
            $request->session()->forget($key);
            if ($langsung !== null) {
                $request->session()->forget('checkout_langsung');
            } else {
                $sisa = array_diff_key($request->session()->get('keranjang', []), $keranjang);
                if ($sisa) {
                    $request->session()->put('keranjang', $sisa);
                } else {
                    $request->session()->forget('keranjang');
                }
                $request->session()->forget('keranjang_pilihan');
            }
        }

        $metodeTersimpan = $pesanan->pembayaran?->metode_pembayaran;
        if ($metodeTersimpan !== $data['metode_pembayaran']) {
            return redirect()->route('pelanggan.pembayaran', $pesanan->id)
                ->with('error', 'Pesanan ini sudah dibuat dengan metode '.$metodeTersimpan.'. Pilihan baru belum diterapkan.');
        }

        if (isset($snap->checkoutMethods()[$metodeTersimpan])) {
            if ($metodeTersimpan === 'E-Wallet' && $snap->sandboxClientKey() !== null) {
                return redirect()->route('pelanggan.pembayaran', $pesanan->id)->with('buka_snap_dana', true);
            }
            $result = $coordinator->requestToken($request->user()->id, $pesanan->id);
            if ($result['status'] === 'ready'
                && $snap->isSandboxRedirectUrl($result['redirect_url'])) {
                return redirect()->away($result['redirect_url'], 303, [
                    'Cache-Control' => 'private, no-store',
                    'Referrer-Policy' => 'no-referrer',
                ]);
            }
        }

        return redirect()->route('pelanggan.pembayaran', $pesanan->id);
    }

    private function konfirmasi(Request $request, MidtransSnapService $snap, array $data, array $keranjang, ?string $langsung, ?array $tersimpan): array
    {
        return DB::transaction(function () use ($request, $data, $keranjang, $langsung, $tersimpan, $snap) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            // Token yang sama tidak boleh membuat dua pesanan saat tombol diklik berulang.
            $sudahAda = DB::table('pembayaran')->join('pesanan', 'pembayaran.pesanan_id', '=', 'pesanan.id')
                ->where('referensi_pembayaran', $data['checkout_token'])
                ->where('gateway', 'midtrans')
                ->where('pesanan.user_id', $request->user()->id)->value('pesanan.id');
            if ($sudahAda) {
                return [Pesanan::findOrFail($sudahAda), false];
            }
            if (! $tersimpan || $tersimpan['berlaku_sampai'] <= now()->timestamp) {
                throw ValidationException::withMessages(['checkout' => 'Ringkasan checkout sudah kedaluwarsa atau tidak berlaku. Tinjau ringkasan terbaru dan konfirmasi ulang.']);
            }

            if (! isset($snap->checkoutMethods()[$data['metode_pembayaran']])) {
                throw ValidationException::withMessages(['checkout' => 'Kanal Midtrans Sandbox ini belum tersedia.']);
            }
            $alamat = Alamat::where('user_id', $request->user()->id)->lockForUpdate()->find($data['alamat_id']);
            if (! $alamat) {
                throw ValidationException::withMessages(['checkout' => 'Alamat pengiriman tidak tersedia. Pilih alamat dan tinjau ringkasan terbaru.']);
            }
            $ongkir = DB::table('ongkir')->where('kelurahan_id', $alamat->kelurahan_id)->lockForUpdate()->value('biaya_ongkir');
            $wilayah = $this->wilayahPengiriman()->where('kelurahan.id', $alamat->kelurahan_id)->lockForUpdate()->first();
            $produk = Produk::with(['material' => fn ($query) => $query->lockForUpdate()])
                ->whereIn('id', array_keys($keranjang))->orderBy('id')->lockForUpdate()->get();
            $ringkasan = $this->ringkasan($produk, $keranjang, $alamat, $wilayah, $ongkir);
            if ($ringkasan['total'] === null) {
                throw ValidationException::withMessages(['checkout' => 'Ongkos kirim tidak tersedia. Silakan pilih alamat lain.']);
            }
            if ($tersimpan['sidik'] !== $this->sidikRingkasan($request, $langsung, $ringkasan)) {
                throw ValidationException::withMessages(['checkout' => 'Produk, harga, alamat, atau ongkos kirim berubah. Tinjau ringkasan terbaru dan konfirmasi ulang.']);
            }
            $total = $ringkasan['total'];
            $pengiriman = $ringkasan['pengiriman'];
            $pesanan = Pesanan::create([
                'kode_pesanan' => 'KA-'.now()->format('Ymd').'-'.strtoupper(Str::random(12)),
                'user_id' => $request->user()->id,
                'alamat_id' => $alamat->id,
                'subtotal' => $ringkasan['subtotal'],
                'ongkos_kirim' => $pengiriman['ongkos_kirim'],
                'total_pembayaran' => $total,
                'status_pesanan' => 'Menunggu',
                'catatan' => $data['catatan'] ?? null,
            ]);
            foreach ($produk as $item) {
                $detail = $ringkasan['items'][$item->id];
                $pesanan->detailPesanan()->create(Arr::except($detail, ['ukuran', 'material_id', 'material', 'satuan_id']));
                $item->decrement('stok', $detail['jumlah']);
            }
            DB::table('pembayaran')->insert([
                'pesanan_id' => $pesanan->id,
                'gateway' => 'midtrans',
                'midtrans_order_id' => $pesanan->kode_pesanan,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'jumlah_pembayaran' => $total,
                'status_pembayaran' => 'Menunggu',
                'referensi_pembayaran' => $data['checkout_token'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $pesanan->pengiriman()->create(Arr::except($pengiriman, ['kelurahan_id']) + [
                'status_pengiriman' => 'Menunggu',
            ]);

            return [$pesanan, true];
        }, 3);
    }

    private function pilihanKeranjang(Request $request): array
    {
        $keranjang = $request->session()->get('keranjang', []);

        return array_intersect_key($keranjang, array_flip($request->session()->get('keranjang_pilihan', array_keys($keranjang))));
    }

    private function kunciRingkasan(?string $langsung): string
    {
        return $langsung === null ? 'checkout_ringkasan.keranjang' : 'checkout_ringkasan.langsung';
    }

    private function sidikRingkasan(Request $request, ?string $langsung, array $ringkasan): string
    {
        return hash('sha256', serialize([$request->user()->id, $langsung, $ringkasan]));
    }

    private function wilayahPengiriman()
    {
        return DB::table('kelurahan')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
            ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
            ->select('kelurahan.id', 'nama_kelurahan', 'nama_kecamatan', 'nama_kota', 'nama_provinsi');
    }

    private function ringkasan($produk, array $keranjang, ?Alamat $alamat, ?object $wilayah, mixed $ongkir): array
    {
        if (! $keranjang || $produk->count() !== count($keranjang)) {
            throw ValidationException::withMessages(['keranjang' => 'Keranjang kosong atau terdapat produk yang sudah tidak tersedia. Periksa kembali keranjang.']);
        }
        $items = [];
        $subtotal = 0;
        foreach ($produk as $item) {
            $jumlah = filter_var($keranjang[$item->id], FILTER_VALIDATE_INT);
            if ($jumlah === false || $jumlah < 1 || $jumlah > $item->stok) {
                throw ValidationException::withMessages(['keranjang' => 'Stok '.$item->nama_produk.' tidak mencukupi. Periksa kembali keranjang.']);
            }
            $harga = $this->rupiahBulat($item->harga, 'Harga '.$item->nama_produk);
            if ($harga > intdiv(self::BATAS_NOMINAL - $subtotal, $jumlah)) {
                throw ValidationException::withMessages(['checkout' => 'Subtotal melebihi batas nominal pesanan.']);
            }
            $subtotalItem = $harga * $jumlah;
            $items[$item->id] = [
                'produk_id' => $item->id,
                'nama_produk' => $item->nama_produk,
                'ukuran' => $item->ukuran,
                'material_id' => $item->material_id,
                'material' => $item->material?->nama_material,
                'satuan_id' => $item->satuan_id,
                'harga_satuan' => $harga,
                'jumlah' => $jumlah,
                'subtotal' => $subtotalItem,
            ];
            $subtotal += $subtotalItem;
        }

        $pengiriman = null;
        $total = null;
        if ($alamat && $wilayah && $ongkir !== null) {
            $pengiriman = [
                'kelurahan_id' => $alamat->kelurahan_id,
                'nama_penerima' => $alamat->nama_penerima,
                'nomor_telepon' => $alamat->nomor_telepon,
                'alamat_pengiriman' => $alamat->alamat_lengkap.', '.$wilayah->nama_kelurahan.', '.$wilayah->nama_kecamatan.', '.$wilayah->nama_kota.', '.$wilayah->nama_provinsi.' '.$alamat->kode_pos,
                'ongkos_kirim' => $this->rupiahBulat($ongkir, 'Ongkos kirim'),
            ];
            $total = $subtotal + $pengiriman['ongkos_kirim'];
            if ($total <= 0 || $total > self::BATAS_NOMINAL) {
                throw ValidationException::withMessages(['checkout' => 'Total pembayaran harus positif dan berada dalam batas nominal pesanan.']);
            }
        }

        return ['alamat_id' => $alamat?->id, 'items' => $items, 'subtotal' => $subtotal, 'pengiriman' => $pengiriman, 'total' => $total];
    }

    private function rupiahBulat(mixed $nilai, string $label): int
    {
        if (! preg_match('/\A([0-9]{1,13})(?:\.0+)?\z/', (string) $nilai, $bagian)) {
            throw ValidationException::withMessages(['checkout' => $label.' harus berupa rupiah bulat. Hubungi toko untuk memeriksa nominal ini.']);
        }

        return (int) $bagian[1];
    }
}
