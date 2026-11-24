<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MidtransRekonsiliasi;
use App\Models\MidtransRekonsiliasiRiwayat;
use App\Models\Pembayaran;
use App\Services\MidtransReconciliationReview;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MidtransRekonsiliasiController extends Controller
{
    // Ambang yang dipakai MidtransPaymentCoordinator sebelum klaim perlu diperiksa.
    private const SNAP_WAIT_SECONDS = 30;

    private const ALASAN = [
        'ambiguous_local_payment' => 'Ada lebih dari satu catatan pembayaran untuk pesanan.',
        'verification_unavailable' => 'Verifikasi transaksi di Midtrans belum tersedia.',
        'identity_conflict' => 'Identitas transaksi Midtrans berbeda dari data yang diharapkan.',
        'amount_conflict' => 'Nominal transaksi Midtrans berbeda dari nominal yang diharapkan.',
        'fraud_unconfirmed' => 'Hasil pemeriksaan risiko pembayaran belum terkonfirmasi.',
        'payment_type_conflict' => 'Jenis pembayaran Midtrans berbeda dari metode yang dipilih.',
        'status_unconfirmed' => 'Status transaksi Midtrans belum dapat dipastikan.',
        'funds_reversed' => 'Midtrans mencatat pengembalian dana atau chargeback.',
        'possible_payment_reversal' => 'Transaksi yang sebelumnya berhasil kemudian dilaporkan ditolak.',
        'attempt_unsuccessful' => 'Satu percobaan pembayaran berakhir tanpa keberhasilan.',
        'terminal_payment_held' => 'Pembayaran berakhir, tetapi terdapat rekonsiliasi aktif. Stok tetap ditahan.',
        'terminal_order_inconsistent' => 'Pembayaran berakhir, tetapi status pesanan atau data pengiriman dan barang perlu diperiksa.',
        'expired_snap_missing_identity' => 'Sesi Snap kedaluwarsa tanpa ID transaksi. Hasil pembayaran belum dapat dipastikan; stok tetap ditahan.',
        'transaction_conflict' => 'ID transaksi bertentangan dengan catatan pembayaran lainnya.',
        'paid_after_rejection' => 'Pembayaran terverifikasi setelah pesanan ditolak.',
        'success_conflicts_with_history' => 'Status berhasil bertentangan dengan riwayat pembayaran.',
        'Sesi Snap tersimpan tidak dapat dibaca.' => 'Sesi Snap tersimpan tidak dapat dibaca.',
        'Parameter sesi Snap perlu diperiksa.' => 'Parameter sesi Snap perlu diperiksa.',
        'Klaim atau sesi Snap memerlukan pemeriksaan.' => 'Klaim atau sesi Snap memerlukan pemeriksaan.',
        'Relasi pembayaran berubah setelah klaim Snap.' => 'Relasi pembayaran berubah setelah permintaan sesi Snap.',
        'Data pembayaran berubah setelah klaim Snap.' => 'Data pembayaran berubah setelah permintaan sesi Snap.',
        'Hasil penerbitan sesi Snap belum dapat dipastikan.' => 'Hasil penerbitan sesi Snap belum dapat dipastikan.',
    ];

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $prioritas = $request->query('prioritas');
        $jenis = $request->query('jenis');
        $kode = $request->query('kode');
        $kode = is_string($kode) ? mb_substr(trim($kode), 0, 30) : '';
        $jenis = is_string($jenis) && isset(self::ALASAN[$jenis]) ? $jenis : '';
        $status = in_array($status, ['terbuka', 'selesai'], true) ? $status : '';
        $prioritas = in_array($prioritas, ['tinggi', 'reguler'], true) ? $prioritas : '';

        $query = $this->kasus();
        if ($status === 'selesai') {
            $query->where('status_penanganan', 'selesai');
        } elseif ($status === 'terbuka') {
            $query->where('status_penanganan', '<>', 'selesai');
        }
        if ($prioritas !== '') {
            $query->where('status_penanganan', $prioritas === 'tinggi' ? 'prioritas_tinggi' : 'menunggu');
        }
        if ($jenis !== '') {
            $query->where('catatan_manual', $jenis);
        }
        if ($kode !== '') {
            $literal = strtr($kode, ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereHas('pembayaran.pesanan', function (Builder $pesanan) use ($literal) {
                $pesanan->whereRaw("kode_pesanan LIKE ? ESCAPE '!'", ['%'.$literal.'%']);
            });
        }

        $filters = array_filter(compact('status', 'prioritas', 'jenis', 'kode'), fn ($value) => $value !== '');
        $kasus = $query->orderByRaw("CASE status_penanganan WHEN 'prioritas_tinggi' THEN 0 WHEN 'selesai' THEN 2 ELSE 1 END")
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15)->appends($filters)->through($this->ringkasan(...));

        $kandidat = Pembayaran::select('id', 'pesanan_id', 'snap_requested_at', 'status_pembayaran')
            ->with('pesanan:id,kode_pesanan')
            ->where('gateway', 'midtrans')
            ->whereNotNull('snap_requested_at')
            ->where('snap_requested_at', '<=', CarbonImmutable::now()->subSeconds(self::SNAP_WAIT_SECONDS))
            ->whereNull('snap_token')
            ->whereNotExists(MidtransRekonsiliasi::selectRaw('1')->whereColumn('pembayaran_id', 'pembayaran.id'))
            ->orderBy('snap_requested_at')->orderBy('id')
            ->paginate(15, pageName: 'klaim')->appends($filters + ['page' => $kasus->currentPage()]);
        $kasus->appends(['klaim' => $kandidat->currentPage()]);

        return view('admin.midtrans-rekonsiliasi.index', compact('kasus', 'kandidat', 'filters') + ['alasan' => self::ALASAN]);
    }

    public function show(string $id): View
    {
        $kasus = $this->ringkasan($this->kasus()->findOrFail($id));
        $riwayat = MidtransRekonsiliasiRiwayat::where('rekonsiliasi_id', $id)
            ->orderByDesc('id')->paginate(15, ['id', 'jenis_tindakan', 'waktu_tindakan', 'jenis_aktor', 'admin_id', 'proses_sistem',
                'hasil_pemeriksaan', 'keputusan_operasional']);

        return view('admin.midtrans-rekonsiliasi.show', compact('kasus', 'riwayat'));
    }

    public function review(Request $request, string $id, MidtransReconciliationReview $review)
    {
        return redirect()->route('admin.midtrans-rekonsiliasi.show', $id)
            ->with('hasil_rekonsiliasi', $review->review($id, (int) $request->user()->getAuthIdentifier()));
    }

    private function kasus(): Builder
    {
        // Perbandingan HEX menjaga kecocokan persis seperti guard PHP pada kolasi MySQL.
        $kasusPenghambat = MidtransRekonsiliasi::selectRaw('1')
            ->whereColumn('pembayaran_id', 'pembayaran.id')
            ->whereRaw("HEX(status_penahanan) <> HEX('dilepas')")
            ->whereRaw("CASE WHEN HEX(status_penanganan) IN (HEX('menunggu'), HEX('selesai'))
                AND HEX(catatan_manual) = HEX('attempt_unsuccessful')
                AND HEX(status_transaksi) IN (HEX('deny'), HEX('cancel'), HEX('expire'), HEX('failure'))
                AND TRIM(midtrans_transaction_id) <> ''
                AND TRIM(pembayaran.midtrans_transaction_id) <> ''
                AND HEX(midtrans_transaction_id) <> HEX(pembayaran.midtrans_transaction_id)
                THEN 0 ELSE 1 END = 1")
            ->limit(1);

        return MidtransRekonsiliasi::select(
            'id', 'pembayaran_id', 'status_penanganan', 'catatan_manual', 'status_transaksi', 'midtrans_transaction_id',
            'jenis_pembayaran', 'created_at', 'terakhir_diperiksa_at', 'periksa_ulang_at',
        )->with([
            'pembayaran' => fn ($query) => $query->select(
                'id', 'pesanan_id', 'gateway', 'status_pembayaran', 'jumlah_pembayaran', 'metode_pembayaran',
                'midtrans_transaction_id', 'midtrans_status',
            )->addSelect([
                'ada_kasus_penghambat' => $kasusPenghambat,
                'jumlah_pembayaran_pesanan' => Pembayaran::from('pembayaran as pembayaran_pesanan')
                    ->selectRaw('COUNT(*)')->whereColumn('pembayaran_pesanan.pesanan_id', 'pembayaran.pesanan_id'),
            ]),
            'pembayaran.pesanan:id,kode_pesanan,status_pesanan',
        ]);
    }

    private function ringkasan(MidtransRekonsiliasi $kasus): array
    {
        $pembayaran = $kasus->pembayaran;
        $pesanan = $pembayaran?->pesanan;
        $hambatan = match (true) {
            $pesanan === null || $pembayaran->gateway !== 'midtrans' => 'Belum tersedia',
            (int) $pembayaran->jumlah_pembayaran_pesanan !== 1 => 'Tertahan · beberapa catatan pembayaran',
            $pembayaran->status_pembayaran !== 'Berhasil' || blank($pembayaran->midtrans_transaction_id)
                || ! in_array($pembayaran->midtrans_status, ['settlement', 'capture'], true) => 'Tertahan · pembayaran belum terverifikasi berhasil',
            (bool) $pembayaran->ada_kasus_penghambat => 'Tertahan · ada kasus yang perlu diperiksa',
            default => 'Tidak ada hambatan pembayaran Midtrans',
        };

        return [
            'id' => $kasus->id,
            'pembayaran_id' => $kasus->pembayaran_id,
            'bisa_diperiksa' => filled($kasus->midtrans_transaction_id),
            'pesanan' => $pesanan,
            'penanganan' => match ($kasus->status_penanganan) {
                'selesai' => 'Selesai',
                'menunggu' => 'Terbuka · Reguler',
                'prioritas_tinggi' => 'Terbuka · Prioritas tinggi',
                default => 'Terbuka · Status belum tersedia',
            },
            'alasan' => self::ALASAN[$kasus->catatan_manual] ?? 'Belum tersedia',
            'kanal' => $pembayaran?->gateway === 'midtrans' ? 'Midtrans' : 'Belum tersedia',
            'lokal' => $pembayaran?->status_pembayaran,
            'gateway' => in_array($kasus->status_transaksi, [
                'pending', 'capture', 'settlement', 'refund', 'partial_refund', 'chargeback',
                'partial_chargeback', 'deny', 'cancel', 'expire', 'failure',
            ], true) ? $kasus->status_transaksi : null,
            'jenis' => match ($kasus->jenis_pembayaran) {
                'bank_transfer' => 'Transfer bank',
                'echannel' => 'Mandiri Bill Payment',
                'qris' => 'QRIS',
                'dana' => 'DANA',
                'credit_card' => 'Kartu kredit',
                default => null,
            },
            'metode' => $pembayaran?->metode_pembayaran,
            'nominal' => $pembayaran?->jumlah_pembayaran,
            'dibuat' => $kasus->created_at,
            'diperiksa' => $kasus->terakhir_diperiksa_at,
            'jadwal' => $kasus->periksa_ulang_at,
            'hambatan' => $hambatan,
        ];
    }
}
