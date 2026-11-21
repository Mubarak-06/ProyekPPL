<?php

namespace App\Services;

use App\Models\MidtransRekonsiliasi;
use App\Models\MidtransRekonsiliasiRiwayat;
use App\Models\Pesanan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MidtransReconciliationReview
{
    public function __construct(private MidtransSnapService $snap, private MidtransWebhookService $verification) {}

    public function review(string $id, int $adminId): string
    {
        $case = MidtransRekonsiliasi::with('pembayaran:id,pesanan_id')->findOrFail($id);
        $orderId = $case->pembayaran?->pesanan_id;
        abort_if($orderId === null, 409);
        $claim = DB::transaction(function () use ($case, $orderId) {
            [, $payment, $current] = $this->lockedCase($orderId, $case->getKey());
            $identifier = $current->midtrans_transaction_id;
            if ($identifier === null || $current->midtrans_order_id !== $payment->midtrans_order_id) {
                return null;
            }
            if ($current->periksa_ulang_at?->isFuture()) {
                return null;
            }
            $until = now()->addMinute()->startOfSecond();
            $current->update(['periksa_ulang_at' => $until]);

            return [$identifier, $current->getRawOriginal('periksa_ulang_at'), $payment->getKey(), $payment->midtrans_order_id];
        }, 3);
        if ($claim === null) {
            return 'Pemeriksaan belum tersedia atau masih dalam jeda. Periksa identitas kasus dan jadwal.';
        }

        [$identifier, $deadline, $paymentId, $gatewayOrder] = $claim;
        try {
            $response = (array) $this->snap->getTransactionStatus($identifier);
            $failure = null;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $response = null;
            $failure = match ((int) $exception->getCode()) {
                28 => 'timeout',
                404 => 'tidak_ditemukan',
                default => 'tidak_pasti',
            };
        }

        return DB::transaction(function () use ($orderId, $case, $identifier, $deadline, $paymentId, $gatewayOrder, $adminId, $response, $failure) {
            [$order, $payment, $current] = $this->lockedCase($orderId, $case->getKey());
            if ($current->getRawOriginal('periksa_ulang_at') !== $deadline
                || $payment->getKey() !== $paymentId
                || $payment->midtrans_order_id !== $gatewayOrder
                || $current->midtrans_order_id !== $gatewayOrder
                || $current->midtrans_transaction_id !== $identifier) {
                return 'Kasus berubah selama pemeriksaan. Periksa kembali setelah jeda.';
            }

            $result = $response === null
                ? $failure
                : $this->verification->inspectStoredTransaction($order, $payment, $identifier, $response);
            $close = $current->status_penanganan === 'menunggu'
                && $current->percobaanGagalTerpisah($payment)
                && in_array($result, ['deny', 'cancel', 'expire', 'failure'], true);

            $changes = ['terakhir_diperiksa_at' => now()];
            if ($close) {
                $changes['status_penanganan'] = 'selesai';
            }
            if (! $close && $response !== null && $result !== $current->status_transaksi) {
                // Temuan baru membatalkan pengecualian untuk percobaan gagal lama.
                $changes['status_penahanan'] = 'ditahan';
                if ($current->catatan_manual === 'attempt_unsuccessful') {
                    $changes['status_penanganan'] = 'prioritas_tinggi';
                }
            }
            $current->forceFill($changes)->save();
            $time = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s.u');
            MidtransRekonsiliasiRiwayat::query()->insert([
                'rekonsiliasi_id' => $current->getKey(),
                'jenis_tindakan' => $close ? 'tutup_kasus' : 'pemeriksaan',
                'waktu_tindakan' => $time,
                'jenis_aktor' => 'admin',
                'admin_id' => $adminId,
                'hasil_pemeriksaan' => $result,
                'sumber_bukti' => $response === null ? 'tanpa_bukti' : 'verifikasi_gateway',
                'keputusan_operasional' => $close ? 'tanpa_perubahan' : 'tetap_ditahan',
                'alasan_keputusan' => $close ? 'percobaan_gagal_berbeda' : 'perlu_pemeriksaan_manual',
                'kunci_idempotensi' => hash('sha256', 'review:'.$deadline),
                'created_at' => $time,
            ]);

            return $close
                ? 'Kasus percobaan pembayaran yang berbeda telah ditutup.'
                : 'Hasil pemeriksaan dicatat. Kasus memerlukan pemeriksaan manual.';
        }, 3);
    }

    private function lockedCase(int $orderId, int $caseId): array
    {
        $order = Pesanan::whereKey($orderId)->lockForUpdate()->firstOrFail();
        $payments = $order->pembayaran()->orderBy('id')->limit(2)->lockForUpdate()->get();
        abort_unless($payments->count() === 1 && $payments->first()->gateway === 'midtrans', 409);
        $payment = $payments->first();
        $case = MidtransRekonsiliasi::where('pembayaran_id', $payment->getKey())->lockForUpdate()->findOrFail($caseId);

        return [$order, $payment, $case];
    }
}
