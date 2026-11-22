<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use App\Models\MidtransRekonsiliasi;
use App\Models\Pesanan;
use App\Services\MidtransPaymentCoordinator;
use App\Services\MidtransSnapService;
use App\Services\MidtransWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AkunController extends Controller
{
    public function profil(Request $request)
    {
        $user = $request->user();

        return view('pelanggan.akun.profil', compact('user'));
    }

    public function updateProfil(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'nomor_telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\s-]{8,20}$/'],
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        unset($data['foto_profil']);
        $user = $request->user();
        $fotoLama = $user->foto_profil;
        $fotoBaru = null;
        if ($request->hasFile('foto_profil')) {
            $fotoBaru = $request->file('foto_profil')->store('profil', 'public');
            if (! $fotoBaru) {
                throw ValidationException::withMessages(['foto_profil' => 'Foto gagal disimpan.']);
            }
            $user->foto_profil = $fotoBaru;
        }
        if (array_key_exists('nomor_telepon', $data)) {
            $user->nomor_telepon = $data['nomor_telepon'];
        }
        unset($data['nomor_telepon']);
        if ($data['email'] !== $request->user()->email) {
            $request->user()->email_verified_at = null;
        }
        try {
            $user->fill($data)->save();
        } catch (\Throwable $e) {
            if ($fotoBaru) {
                Storage::disk('public')->delete($fotoBaru);
            }
            throw $e;
        }
        if ($fotoBaru && $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        return redirect()->route('pelanggan.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function editProfil(Request $request)
    {
        return view('pelanggan.akun.edit-profil', ['user' => $request->user()]);
    }

    public function alamat(Request $request)
    {
        $alamat = Alamat::with('kelurahan.ongkir')->where('user_id', $request->user()->id)->latest()->get();

        return view('pelanggan.akun.alamat', compact('alamat'));
    }

    public function formAlamat(Request $request, $id = null)
    {
        $alamat = $id ? Alamat::where('user_id', $request->user()->id)->findOrFail($id) : new Alamat;
        $kelurahan = DB::table('kelurahan')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
            ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
            ->select('kelurahan.id', 'kelurahan.kecamatan_id', 'kecamatan.kota_id', 'kota.provinsi_id', 'nama_kelurahan', 'nama_kecamatan', 'nama_kota', 'nama_provinsi')
            ->orderBy('nama_kelurahan')->get();

        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi')->get();
        $wilayah = $kelurahan->firstWhere('id', old('kelurahan_id', $alamat->kelurahan_id));

        return view('pelanggan.akun.form-alamat', compact('alamat', 'kelurahan', 'provinsi', 'wilayah'));
    }

    public function simpanAlamat(Request $request, $id = null)
    {
        $alamat = $id ? Alamat::where('user_id', $request->user()->id)->findOrFail($id) : new Alamat;
        $data = $request->validate([
            'nama_penerima' => 'required|string|max:100',
            'nomor_telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\s-]{8,20}$/'],
            'alamat_lengkap' => 'required|string|max:2000',
            'kelurahan_id' => 'required|integer|exists:kelurahan,id',
            'kode_pos' => ['required', 'regex:/^[0-9]{5}$/'],
        ]);
        $alamat->fill($data);
        $alamat->user_id = $request->user()->id;
        $alamat->save();

        return redirect()->route('pelanggan.alamat')->with('success', 'Alamat berhasil disimpan.');
    }

    public function pesanan(Request $request, MidtransPaymentCoordinator $coordinator)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'per_page' => ['nullable', Rule::in([10, 25, 50])]]);
        $pesanan = Pesanan::with('pembayaran.rekonsiliasiAktif', 'pengiriman', 'detailPesanan')->where('user_id', $request->user()->id)
            ->when($data['q'] ?? null, fn ($query, $q) => $query->where('kode_pesanan', 'like', '%'.$q.'%'))
            ->latest()->paginate($data['per_page'] ?? 10)->withQueryString();

        $pesananBisaDibayar = $pesanan->getCollection()->filter($coordinator->canOfferPayment(...))->modelKeys();

        return view('pelanggan.akun.pesanan', compact('pesanan', 'pesananBisaDibayar'));
    }

    public function hapusAlamat(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $alamat = Alamat::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
            if ($alamat->pesanan()->exists()) {
                return back()->with('error', 'Alamat sudah digunakan pada pesanan dan tidak dapat dihapus.');
            }
            $alamat->delete();

            return back()->with('success', 'Alamat berhasil dihapus.');
        });
    }

    public function wilayah(Request $request)
    {
        $data = $request->validate(['jenis' => ['required', Rule::in(['kota', 'kecamatan', 'kelurahan'])], 'induk' => 'required|integer|min:1']);
        $induk = ['kota' => 'provinsi_id', 'kecamatan' => 'kota_id', 'kelurahan' => 'kecamatan_id'];

        return response()->json(DB::table($data['jenis'])->where($induk[$data['jenis']], $data['induk'])
            ->orderBy('nama_'.$data['jenis'])->get(['id', 'nama_'.$data['jenis'].' as nama']));
    }

    public function pembayaran(Request $request, $id, MidtransSnapService $snap, MidtransPaymentCoordinator $coordinator, MidtransWebhookService $midtrans)
    {
        $pesanan = Pesanan::with('pembayaran.rekonsiliasiAktif', 'pengiriman')->where('user_id', $request->user()->id)->findOrFail($id);
        if ($pesanan->pembayaran?->gateway === 'midtrans') {
            $payment = $pesanan->pembayaran;
            $returnId = $request->query('transaction_id');
            $returnId = is_string($returnId) && preg_match('/\A[A-Za-z0-9_~.\-]{1,50}\z/', $returnId) === 1
                && ! in_array($returnId, ['.', '..'], true) ? $returnId : null;
            $perluPemeriksaan = $payment->rekonsiliasiAktif
                ->contains(fn (MidtransRekonsiliasi $case) => $case->menahanPemenuhan($payment));
            $statusKembali = null;
            $orderReference = $request->query('order_id');
            $isSnapReturn = ($request->query('snap_return') === '1' && $orderReference === null)
                || (filled($payment->midtrans_order_id) && $orderReference === $payment->midtrans_order_id);
            if ($isSnapReturn && ! $perluPemeriksaan && $pesanan->status_pesanan !== 'Ditolak'
                && $payment->status_pembayaran === 'Menunggu' && config('services.midtrans.is_production') === false) {
                $result = RateLimiter::attempt('midtrans:finish:'.$payment->id, 3, fn () => $midtrans->refreshStatus(
                    $payment,
                    $payment->metode_pembayaran === 'E-Wallet' ? $returnId : null,
                ));
                if ($result !== false) {
                    $statusKembali = $this->pesanStatusSnap($result);
                    $payment->refresh();
                    $perluPemeriksaan = $payment->rekonsiliasiAktif
                        ->contains(fn (MidtransRekonsiliasi $case) => $case->menahanPemenuhan($payment));
                }
            }
            if (! $perluPemeriksaan && $pesanan->status_pesanan !== 'Ditolak'
                && $pesanan->pembayaran->status_pembayaran === 'Berhasil'
                && in_array($pesanan->pembayaran->midtrans_status, ['settlement', 'capture'], true)
                && filled($pesanan->pembayaran->midtrans_transaction_id)) {
                return redirect()->route('pelanggan.pesanan')->header('Cache-Control', 'private, no-store');
            }
            $clientKey = $snap->sandboxClientKey();
            $snapAvailable = $coordinator->canOfferPayment($pesanan);
            return response()->view('pelanggan.pembayaran-snap', compact('pesanan', 'perluPemeriksaan', 'clientKey', 'snapAvailable', 'returnId', 'statusKembali'))
                ->header('Cache-Control', 'private, no-store');
        }
        $metode = $pesanan->pembayaran ? DB::table('metode_pembayaran')->find($pesanan->pembayaran->metode_pembayaran_id) : null;

        return view('pelanggan.pembayaran', compact('pesanan', 'metode'));
    }

    public function tokenSnap(Request $request, int $id, MidtransPaymentCoordinator $coordinator, MidtransSnapService $snap)
    {
        $order = Pesanan::with('pembayaran:id,pesanan_id,gateway,metode_pembayaran')
            ->where('user_id', $request->user()->id)->findOrFail($id);
        if ($order->pembayaran?->gateway !== 'midtrans'
            || ! isset($snap->checkoutMethods()[$order->pembayaran->metode_pembayaran])) {
            if (! $request->expectsJson()) {
                return redirect()->route('pelanggan.pembayaran', $order->id, 303);
            }

            return response()->json(['status' => 'rejected'])->header('Cache-Control', 'private, no-store');
        }
        $result = $coordinator->requestToken($request->user()->id, $order->id);
        $redirectUrl = $result['status'] === 'ready' && $snap->isSandboxRedirectUrl($result['redirect_url'])
            ? $result['redirect_url']
            : null;
        if (! $request->expectsJson()) {
            return $redirectUrl !== null
                ? redirect()->away($redirectUrl, 303, ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer'])
                : redirect()->route('pelanggan.pembayaran', $order->id, 303)
                    ->with('status_midtrans', 'Sesi Snap belum dapat dibuka. Periksa status pembayaran di bawah.');
        }

        $body = ['status' => $result['status']];
        if ($result['status'] === 'ready') {
            if ($redirectUrl !== null) {
                $body['redirect_url'] = $redirectUrl;
            }
            if ($snap->sandboxClientKey() !== null) {
                $body['token'] = $result['token'];
            }
            if (count($body) === 1) {
                $body['status'] = 'rejected';
            }
        }

        return response()->json($body)->header('Cache-Control', 'private, no-store');
    }

    public function periksaStatusSnap(Request $request, int $id, MidtransWebhookService $midtrans)
    {
        $pesanan = Pesanan::with('pembayaran')->where('user_id', $request->user()->id)->findOrFail($id);
        abort_unless($pesanan->pembayaran?->gateway === 'midtrans' && config('services.midtrans.is_production') === false, 404);
        $data = $request->validate(['transaction_id' => ['nullable', 'string', 'regex:/\A[A-Za-z0-9_~.\-]{1,50}\z/']]);
        $transactionId = $pesanan->pembayaran->metode_pembayaran === 'E-Wallet' ? ($data['transaction_id'] ?? null) : null;
        $result = $midtrans->refreshStatus($pesanan->pembayaran, $transactionId);
        $reason = $result['reason'] ?? null;
        $verified = in_array($reason, ['payment_verified', 'already_paid'], true);
        $message = $this->pesanStatusSnap($result);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $result['status'],
                'reason' => $reason,
                'message' => $message,
                ...($verified ? ['redirect_url' => route('pelanggan.pesanan', absolute: false)] : []),
            ])->header('Cache-Control', 'private, no-store');
        }

        return $verified
            ? redirect()->route('pelanggan.pesanan')
            : redirect()->route('pelanggan.pembayaran', $id)->with('status_midtrans', $message);
    }

    private function pesanStatusSnap(array $result): string
    {
        return match ($result['reason'] ?? null) {
            'payment_expired' => 'Pembayaran telah kedaluwarsa di Midtrans.',
            'payment_failed' => 'Pembayaran gagal menurut hasil verifikasi Midtrans.',
            default => match ($result['status']) {
                'applied' => 'Pembayaran berhasil diverifikasi oleh Midtrans.',
                'acknowledged' => ($result['reason'] ?? null) === 'already_paid'
                    ? 'Pembayaran sudah terverifikasi.' : 'Midtrans masih menunggu pembayaran.',
                'needs_reconciliation' => 'Pembayaran perlu diperiksa lebih lanjut.',
                'transaction_id_required' => 'Masukkan ID transaksi DANA dari detail transaksi Midtrans Sandbox.',
                default => 'Status belum dapat diverifikasi. Coba lagi setelah pembayaran disimulasikan.',
            },
        };

    }

    public function detailPesanan(Request $request, $id, MidtransPaymentCoordinator $coordinator)
    {
        $pesanan = Pesanan::with('detailPesanan', 'pembayaran.rekonsiliasiAktif', 'pengiriman')
            ->where('user_id', $request->user()->id)->findOrFail($id);
        $metode = $pesanan->pembayaran
            ? DB::table('metode_pembayaran')->find($pesanan->pembayaran->metode_pembayaran_id)
            : null;

        $bisaDibayar = $coordinator->canOfferPayment($pesanan);

        return view('pelanggan.akun.detail-pesanan', compact('pesanan', 'metode', 'bisaDibayar'));
    }

    public function unggahBukti(Request $request, $id)
    {
        $request->validate(['bukti' => 'required|image|mimes:jpg,jpeg,png|max:2048']);
        $path = null;

        try {
            DB::transaction(function () use ($request, $id, &$path) {
                $pesanan = Pesanan::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
                $pembayaran = $pesanan->pembayaran()->lockForUpdate()->firstOrFail();
                abort_unless($pembayaran->gateway !== 'midtrans'
                    && in_array($pesanan->status_pesanan, ['Menunggu', 'Diproses'], true)
                    && $pembayaran->status_pembayaran === 'Menunggu'
                    && ! $pembayaran->bukti_pembayaran, 422, 'Bukti pembayaran tidak dapat diunggah untuk pesanan ini.');
                $path = $request->file('bukti')->store('bukti-pembayaran', 'public');
                if (! $path) {
                    throw ValidationException::withMessages(['bukti' => 'Bukti pembayaran gagal disimpan. Silakan coba kembali.']);
                }
                $pembayaran->update(['bukti_pembayaran' => $path]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu pemeriksaan admin.');
    }
}
