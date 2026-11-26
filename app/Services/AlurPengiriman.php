<?php

namespace App\Services;

use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlurPengiriman
{
    public function __construct(private PemeriksaanPembayaranPengiriman $pemeriksaan) {}

    public static function tersedia(): bool
    {
        return DB::connection()->getSchemaBuilder()->hasTable('kendala_pengiriman');
    }

    public function proses(User $admin, int $id)
    {
        abort_unless($admin->is_admin, 403);

        return DB::transaction(function () use ($admin, $id) {
            $pesanan = Pesanan::lockForUpdate()->findOrFail($id);
            if ($pesanan->status_pesanan !== 'Menunggu') {
                return back()->with('error', 'Perubahan status pesanan tidak diizinkan.');
            }
            if ($pesan = $this->pemeriksaan->hambatanPembayaranMidtrans($pesanan, 'Diproses')) {
                return back()->with('error', $pesan);
            }
            $data = $pesanan->pengiriman()->lockForUpdate()->get();
            if ($data->count() !== 1 || $data->first()->status_pengiriman !== 'Menunggu') {
                return back()->with('error', 'Catatan pengiriman tidak tersedia atau tidak sesuai.');
            }
            $pengiriman = $data->first();
            $pesanan->update(['status_pesanan' => 'Diproses']);
            if (self::tersedia()) {
                $this->ubah($pengiriman, $admin, Pengiriman::DISIAPKAN, ['status_pengiriman' => 'Diproses']);
            } else {
                $pesanan->pengiriman()->update(['status_pengiriman' => 'Diproses']);
            }

            return back()->with('success', 'Pesanan mulai disiapkan.');
        }, 3);
    }

    public function jalankan(User $pelaku, Pengiriman $target, string $aksi, array $data = []): void
    {
        abort_unless(self::tersedia(), 503);
        DB::transaction(function () use ($pelaku, $target, $aksi, $data) {
            // Urutan penguncian mengikuti webhook Midtrans: pesanan, pembayaran, rekonsiliasi, pengiriman.
            $pesanan = Pesanan::lockForUpdate()->findOrFail($target->pesanan_id);
            $kurir = $pelaku->isKurir();
            abort_unless(match ($aksi) {
                'siapkan', 'tindak-lanjut' => $pelaku->is_admin,
                'mulai', 'sampai' => $kurir && (int) $target->kurir_id === $pelaku->id,
                'terima', 'kendala' => $kurir
                    ? $aksi === 'kendala' && (int) $target->kurir_id === $pelaku->id
                    : ! $pelaku->is_admin && (int) $pesanan->user_id === $pelaku->id,
                default => false,
            }, 403);

            if (in_array($aksi, ['siapkan', 'mulai', 'sampai', 'terima'], true)
                && ($pesan = $this->pemeriksaan->hambatanPembayaranMidtrans($pesanan, $aksi))) {
                $this->tolak($pesan);
            }

            $catatan = $pesanan->pengiriman()->lockForUpdate()->get();
            $pengiriman = $catatan->first();
            if ($catatan->count() !== 1 || $pengiriman->id !== $target->id) {
                $this->tolak('Catatan pengiriman perlu diperiksa.');
            }
            if ($kurir) {
                abort_unless((int) $pengiriman->kurir_id === $pelaku->id, 403);
            }
            $aktif = $pengiriman->kendala()->whereNull('diselesaikan_at')->first();
            $tahap = $pengiriman->tahap();
            $diperbolehkan = match ($aksi) {
                'siapkan' => $tahap === Pengiriman::DISIAPKAN && $pesanan->status_pesanan === 'Diproses',
                'mulai' => $tahap === Pengiriman::SIAP && $pesanan->status_pesanan === 'Diproses',
                'sampai' => $tahap === Pengiriman::PERJALANAN && $pesanan->status_pesanan === 'Dikirim',
                'terima' => ($tahap === Pengiriman::SAMPAI || $pengiriman->dikirimHistoris())
                    && $pesanan->status_pesanan === 'Dikirim',
                'kendala' => $tahap === ($kurir ? Pengiriman::PERJALANAN : Pengiriman::SAMPAI)
                    && $pesanan->status_pesanan === 'Dikirim',
                'tindak-lanjut' => $aktif && (int) $data['kendala_id'] === $aktif->id
                    && in_array($tahap, [Pengiriman::PERJALANAN, Pengiriman::SAMPAI], true)
                    && (empty($data['jadwalkan_ulang']) || $tahap === Pengiriman::PERJALANAN),
            };
            if (! $diperbolehkan || ($aktif && $aksi !== 'tindak-lanjut')) {
                $this->tolak('Tindakan tidak tersedia pada status ini atau masih ada kendala aktif.');
            }

            switch ($aksi) {
                case 'siapkan':
                    $petugas = User::where('slot_kurir', 1)->where('is_admin', false)->lockForUpdate()->first();
                    if (! $petugas) {
                        $this->tolak('Akun kurir aktif belum tersedia.');
                    }
                    $this->ubah($pengiriman, $pelaku, Pengiriman::SIAP, [
                        'kurir_id' => $petugas->id,
                        'estimasi_tanggal' => $data['estimasi_tanggal'],
                        'estimasi_jam' => $data['estimasi_jam'] ?? null,
                        'catatan_driver' => $data['catatan_driver'] ?? null,
                    ]);
                    break;
                case 'mulai':
                    $pesanan->update(['status_pesanan' => 'Dikirim']);
                    $this->ubah($pengiriman, $pelaku, Pengiriman::PERJALANAN, [
                        'status_pengiriman' => 'Dikirim', 'waktu_dikirim' => now(),
                    ]);
                    break;
                case 'sampai':
                    $this->ubah($pengiriman, $pelaku, Pengiriman::SAMPAI, $data);
                    break;
                case 'terima':
                    $pesanan->update(['status_pesanan' => 'Selesai']);
                    $this->ubah($pengiriman, $pelaku, Pengiriman::DITERIMA, [
                        'status_pengiriman' => 'Selesai', 'waktu_selesai' => now(),
                    ], $pengiriman->dikirimHistoris()
                        ? 'Penerimaan pesanan lama dikonfirmasi langsung oleh pelanggan.'
                        : null);
                    break;
                case 'kendala':
                    $pengiriman->kendala()->create([
                        'pelapor_id' => $pelaku->id,
                        'peran_pelapor' => $kurir ? 'Kurir' : 'Pelanggan',
                        'catatan' => $data['catatan'],
                        'dilaporkan_at' => now(),
                    ]);
                    break;
                case 'tindak-lanjut':
                    $aktif->update([
                        'penyelesai_id' => $pelaku->id,
                        'penyelesaian' => $data['penyelesaian'],
                        'diselesaikan_at' => now(),
                    ]);
                    $estimasi = empty($data['estimasi_tanggal']) ? [] : [
                        'estimasi_tanggal' => $data['estimasi_tanggal'],
                        'estimasi_jam' => $data['estimasi_jam'] ?? null,
                    ];
                    if (! empty($data['jadwalkan_ulang'])) {
                        $pesanan->update(['status_pesanan' => 'Diproses']);
                        $this->ubah($pengiriman, $pelaku, Pengiriman::SIAP, $estimasi + [
                            'status_pengiriman' => 'Diproses', 'waktu_dikirim' => null,
                        ], 'Pengiriman dijadwalkan ulang.');
                    } else {
                        $pengiriman->forceFill($estimasi)->save();
                    }
                    break;
            }
        }, 3);
    }

    public function ubah(Pengiriman $pengiriman, ?User $pelaku, string $tahap, array $atribut, ?string $catatan = null): void
    {
        $lama = $pengiriman->tahap();
        $pengiriman->forceFill($atribut + ['tahap_kurir' => $tahap])->save();
        $pengiriman->riwayat()->create([
            'pelaku_id' => $pelaku?->id,
            'status_lama' => $lama,
            'status_baru' => $tahap,
            'catatan' => $catatan,
            'terjadi_at' => now(),
        ]);
    }

    private function tolak(string $pesan): never
    {
        throw ValidationException::withMessages(['pengiriman' => $pesan]);
    }
}
