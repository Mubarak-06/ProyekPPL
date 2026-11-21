<?php

namespace App\Services;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class MidtransPaymentCoordinator
{
    public function __construct(private readonly MidtransSnapService $snap) {}

    /**
     * Gunakan ID pengguna terautentikasi. Token dan URL pada hasil ready hanya untuk pemilik pesanan.
     *
     * @return array{status: 'rejected'|'needs_reconciliation'}|array{status: 'ready', token: string, redirect_url: string}
     */
    public function requestToken(int|string $userId, int|string $orderId): array
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Permintaan Snap harus dimulai di luar transaksi database.');
        }

        $claim = DB::transaction(function () use ($userId, $orderId) {
            $order = Pesanan::whereKey($orderId)->where('user_id', $userId)->lockForUpdate()->first();
            $payment = $order ? $this->lockPayment($order) : null;

            if (! $payment || ! $this->isPayable($order, $payment)) {
                return ['status' => 'rejected'];
            }

            if (! is_string($payment->midtrans_order_id) || $payment->midtrans_order_id === '') {
                return ['status' => 'rejected'];
            }

            $hasLocalSession = $payment->getRawOriginal('snap_token') !== null
                || $payment->getRawOriginal('snap_redirect_url') !== null
                || $payment->snap_token_expires_at !== null;
            $gatewayActivity = $payment->midtrans_transaction_id !== null || $payment->midtrans_status !== null;
            if (! $gatewayActivity && $payment->snap_token_expires_at?->isFuture()) {
                try {
                    $this->snap->buildTransactionParameters($order, $payment);
                    $token = $payment->snap_token;
                    $url = $payment->snap_redirect_url;
                    if (is_string($token) && $token !== '' && is_string($url) && $url !== '') {
                        return ['status' => 'ready', 'token' => $token, 'redirect_url' => $url];
                    }
                } catch (DecryptException) {
                    return $this->flagReconciliation($payment, $payment->midtrans_order_id, 'Sesi Snap tersimpan tidak dapat dibaca.');
                } catch (InvalidArgumentException|RuntimeException) {
                    return $this->flagReconciliation($payment, $payment->midtrans_order_id, 'Parameter sesi Snap perlu diperiksa.');
                }
            }

            // Beri request pertama waktu untuk menyimpan hasil tanpa membuka kasus audit.
            if (! $gatewayActivity && ! $hasLocalSession
                && $payment->snap_requested_at?->greaterThan(CarbonImmutable::now()->subSeconds(30))) {
                return ['status' => 'needs_reconciliation'];
            }

            if ($gatewayActivity || $hasLocalSession || $payment->snap_requested_at !== null) {
                return $this->flagReconciliation($payment, $payment->midtrans_order_id, 'Klaim atau sesi Snap memerlukan pemeriksaan.', reuseUnknown: true);
            }

            try {
                $this->snap->buildTransactionParameters($order, $payment);
            } catch (InvalidArgumentException|RuntimeException) {
                return ['status' => 'rejected'];
            }

            $requestedAt = CarbonImmutable::now()->startOfSecond();
            $snapshot = clone $payment;
            $payment->snap_requested_at = $requestedAt;
            $payment->save();

            return [
                'status' => 'claimed',
                'order' => $order,
                'payment' => $snapshot,
                'requested_at' => $requestedAt,
            ];
        });

        if ($claim['status'] !== 'claimed') {
            return $claim;
        }

        try {
            $result = $this->snap->requestSnapToken($claim['order'], $claim['payment'], requestedAt: $claim['requested_at']);
        } catch (InvalidArgumentException|RuntimeException) {
            $result = ['status' => 'unknown'];
        }

        return DB::transaction(function () use ($claim, $result, $orderId, $userId) {
            $order = Pesanan::whereKey($orderId)->lockForUpdate()->first();
            $original = $claim['payment'];
            $gatewayId = $original->midtrans_order_id;
            $payment = $order ? $this->lockPayment($order) : null;

            if (! $payment || $payment->getKey() !== $original->getKey()) {
                // Catat referensi gateway awal selama pembayaran asal masih terkait.
                $originalPayment = $order ? $order->pembayaran()->whereKey($original->getKey())->lockForUpdate()->first() : null;

                return $originalPayment
                    ? $this->flagReconciliation($originalPayment, $gatewayId, 'Relasi pembayaran berubah setelah klaim Snap.')
                    : ['status' => 'needs_reconciliation'];
            }

            if ((string) $order->user_id !== (string) $userId || ! $this->isPayable($order, $payment)
                || $payment->midtrans_order_id !== $gatewayId
                || ! $payment->snap_requested_at?->equalTo($claim['requested_at'])
                || $payment->midtrans_transaction_id !== null
                || $payment->midtrans_status !== null
                || $payment->getRawOriginal('snap_token') !== null
                || $payment->getRawOriginal('jumlah_pembayaran') !== $original->getRawOriginal('jumlah_pembayaran')
                || $payment->metode_pembayaran !== $original->metode_pembayaran
                || $order->getRawOriginal('total_pembayaran') !== $claim['order']->getRawOriginal('total_pembayaran')) {
                return $this->flagReconciliation($payment, $gatewayId, 'Data pembayaran berubah setelah klaim Snap.');
            }

            if ($result['status'] !== 'ready' || $result['snap_token_expires_at'] <= CarbonImmutable::now()) {
                return $this->flagReconciliation($payment, $gatewayId, 'Hasil penerbitan sesi Snap belum dapat dipastikan.');
            }

            $payment->fill([
                'snap_token' => $result['token'],
                'snap_redirect_url' => $result['redirect_url'],
                'snap_token_expires_at' => $result['snap_token_expires_at'],
            ])->save();

            return ['status' => 'ready', 'token' => $result['token'], 'redirect_url' => $result['redirect_url']];
        });
    }

    private function lockPayment(Pesanan $order): ?Pembayaran
    {
        $payments = $order->pembayaran()->orderBy('id')->limit(2)->lockForUpdate()->get();

        return $payments->count() === 1 ? $payments->first() : null;
    }

    private function isPayable(Pesanan $order, Pembayaran $payment): bool
    {
        return $order->status_pesanan === 'Menunggu'
            && $payment->gateway === 'midtrans'
            && $payment->status_pembayaran === 'Menunggu'
            && $payment->waktu_pembayaran === null;
    }

    private function flagReconciliation(Pembayaran $payment, string $gatewayId, string $reason, bool $reuseUnknown = false): array
    {
        // Kunci pembayaran menjaga satu kasus menunggu per pembayaran dan ID gateway.
        $identity = [
            'pembayaran_id' => $payment->getKey(),
            'midtrans_order_id' => $gatewayId,
        ];
        $reasons = [$reason];
        if ($reuseUnknown) {
            $reasons[] = 'Hasil penerbitan sesi Snap belum dapat dipastikan.';
        }
        $case = MidtransRekonsiliasi::where($identity)->whereIn('catatan_manual', $reasons)->orderBy('id')->lockForUpdate()->first()
            ?? MidtransRekonsiliasi::create($identity + [
                'status_penanganan' => 'menunggu',
                'catatan_manual' => $reason,
                'periksa_ulang_at' => CarbonImmutable::now(),
            ]);
        (new MidtransReconciliationAudit)->recordSystemCase($case, 'coordinator', false);

        return ['status' => 'needs_reconciliation'];
    }
}
