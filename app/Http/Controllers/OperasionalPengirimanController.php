<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Services\AlurPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperasionalPengirimanController extends Controller
{
    public function dashboard(Request $request)
    {
        $tugas = $this->tugas($request);
        $ringkasan = [
            'Siap Dikirim' => (clone $tugas)->where('tahap_kurir', Pengiriman::SIAP)->count(),
            'Dalam Perjalanan' => (clone $tugas)->where('tahap_kurir', Pengiriman::PERJALANAN)->whereDoesntHave('kendala', fn ($q) => $q->whereNull('diselesaikan_at'))->count(),
            'Kendala Aktif' => (clone $tugas)->whereHas('kendala', fn ($q) => $q->whereNull('diselesaikan_at'))->count(),
            'Telah Diserahkan' => (clone $tugas)->whereIn('tahap_kurir', [Pengiriman::SAMPAI, Pengiriman::DITERIMA])->count(),
        ];

        return view('kurir.dashboard', compact('ringkasan'));
    }

    public function daftar(Request $request)
    {
        $riwayat = $request->routeIs('kurir.riwayat');
        $daftar = $this->tugas($request)
            ->with(['pesanan.user', 'pesanan.detailPesanan.produk', 'kendala'])
            ->whereIn('tahap_kurir', $riwayat
                ? [Pengiriman::SAMPAI, Pengiriman::DITERIMA]
                : [Pengiriman::SIAP, Pengiriman::PERJALANAN])
            ->orderByDesc('id')->paginate(15);

        return view('kurir.daftar', compact('daftar', 'riwayat'));
    }

    public function detail(Request $request, int $id)
    {
        $pengiriman = $this->tugas($request)
            ->with(['pesanan.user', 'pesanan.detailPesanan.produk', 'pesanan.pembayaran', 'riwayat', 'kendala'])
            ->findOrFail($id);

        return view('kurir.detail', compact('pengiriman'));
    }

    public function ubah(Request $request, int $id, AlurPengiriman $alur)
    {
        abort_unless(AlurPengiriman::tersedia(), 503);
        $aktor = $request->user();
        $target = Pengiriman::query()
            ->when($aktor->isKurir(), fn ($q) => $q->where('kurir_id', $aktor->id))
            ->when(! $aktor->is_admin && ! $aktor->isKurir(),
                fn ($q) => $q->whereHas('pesanan', fn ($pesanan) => $pesanan->where('user_id', $aktor->id)))
            ->findOrFail($id);
        $aksi = $request->route('aksi');
        $tanggal = 'date_format:Y-m-d|after_or_equal:'.now(config('toko.timezone'))->toDateString();
        $data = $request->validate(match ($aksi) {
            'siapkan' => [
                'estimasi_tanggal' => 'required|'.$tanggal,
                'estimasi_jam' => 'nullable|date_format:H:i',
                'catatan_driver' => 'nullable|string|max:1000',
            ],
            'sampai' => [
                'penerima_aktual' => 'required|string|max:100',
                'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120',
                    'dimensions:max_width=8000,max_height=8000'],
                'catatan_penyerahan' => 'nullable|string|max:1000',
            ],
            'kendala' => ['catatan' => 'required|string|max:1000'],
            'tindak-lanjut' => [
                'kendala_id' => 'required|integer|min:1',
                'penyelesaian' => 'required|string|max:1000',
                'estimasi_tanggal' => 'nullable|required_if:jadwalkan_ulang,1|required_with:estimasi_jam|'.$tanggal,
                'estimasi_jam' => 'nullable|date_format:H:i',
                'jadwalkan_ulang' => 'sometimes|accepted',
            ],
            default => [],
        });

        $foto = null;
        if ($aksi === 'sampai') {
            $foto = $data['foto']->store('bukti-pengiriman', 'local');
            if (! $foto) {
                throw ValidationException::withMessages(['foto' => 'Foto belum berhasil disimpan.']);
            }
            unset($data['foto']);
            $data['foto_penyerahan'] = $foto;
        }
        try {
            $alur->jalankan($aktor, $target, $aksi, $data);
        } catch (Throwable $exception) {
            if ($foto) {
                Storage::disk('local')->delete($foto);
            }
            throw $exception;
        }

        return back()->with('success', 'Informasi pengiriman berhasil diperbarui.');
    }

    public function bukti(Request $request, int $id)
    {
        abort_unless(AlurPengiriman::tersedia(), 404);
        $aktor = $request->user();
        $pengiriman = Pengiriman::with('pesanan')->findOrFail($id);
        abort_unless($aktor->is_admin || ($aktor->isKurir()
            ? (int) $pengiriman->kurir_id === $aktor->id
            : (int) $pengiriman->pesanan->user_id === $aktor->id), 404);
        $disk = Storage::disk('local');
        $path = $pengiriman->foto_penyerahan;
        abort_unless($path && preg_match('~\Abukti-pengiriman/[a-zA-Z0-9]+\.(?:jpe?g|png|webp)\z~i', $path) && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function tugas(Request $request)
    {
        abort_unless(AlurPengiriman::tersedia(), 503);

        return Pengiriman::where('kurir_id', $request->user()->id);
    }
}
