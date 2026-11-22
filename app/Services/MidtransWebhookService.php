<?php

namespace App\Services;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class MidtransWebhookService
{
    private const PAYMENT_TYPES = [
        'Transfer Bank' => ['bank_transfer', 'echannel'],
        'QRIS' => ['qris'],
        'E-Wallet' => ['dana'],
    ];

    private const UNSUCCESSFUL = ['deny', 'cancel', 'expire', 'failure'];

    private const REVERSALS = ['refund', 'partial_refund', 'chargeback', 'partial_chargeback'];

    public function __construct(private readonly MidtransSnapService $snap) {}

    /** @return array{status: string, reason?: string} */
    public function process(array $payload): array
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Verifikasi notifikasi harus dimulai di luar transaksi database.');
        }

        $notice = $this->transactionData($payload);
        $signature = $payload['signature_key'] ?? null;
        if ($notice === null || ! is_string($signature) || preg_match('/\A[0-9a-f]{128}\z/i', $signature) !== 1) {
            return ['status' => 'rejected', 'reason' => 'invalid_payload'];
        }

        $key = config('services.midtrans.signature_secret_key') ?? config('services.midtrans.server_key');
        if (config('services.midtrans.is_production') !== false || ! is_string($key) || trim($key) === '') {
            return ['status' => 'unavailable', 'reason' => 'invalid_configuration'];
        }

        $expected = hash('sha512', $notice['order_id'].$notice['status_code'].$payload['gross_amount'].$key);
        if (! hash_equals($expected, strtolower($signature))) {
            return ['status' => 'rejected', 'reason' => 'invalid_signature'];
        }

        $payment = Pembayaran::where('midtrans_order_id', $notice['order_id'])->first();
        if ($payment === null || ! $this->matchesOrder($payment, $notice['order_id'])) {
            return ['status' => 'rejected', 'reason' => 'unknown_payment'];
        }

        try {
            $response = $this->snap->getTransactionStatus($notice['transaction_id']);
            $verified = $this->verifiedData((array) $response);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() !== 404) {
                return ['status' => 'unavailable', 'reason' => 'gateway_unavailable'];
            }
            $verified = null;
        } catch (InvalidArgumentException) {
            $verified = null;
        }

        return $this->applyVerification($payment, $notice, $verified);
    }

    public function refreshStatus(Pembayaran $payment, ?string $transactionId = null): array
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Verifikasi status harus dimulai di luar transaksi database.');
        }
        $orderId = $payment->midtrans_order_id;
        if (! is_string($orderId) || ! $this->matchesOrder($payment, $orderId)) {
            return ['status' => 'rejected'];
        }
        $identifier = $payment->midtrans_transaction_id ?? $transactionId;
        if ($identifier === null && $payment->metode_pembayaran === 'E-Wallet') {
            return ['status' => 'transaction_id_required'];
        }
        try {
            $verified = $this->verifiedData((array) $this->snap->getTransactionStatus($identifier ?? $orderId));
        } catch (InvalidArgumentException|RuntimeException) {
            return ['status' => 'unavailable'];
        }
        if ($verified === null || $verified['order_id'] !== $orderId
            || ($identifier !== null && $verified['transaction_id'] !== $identifier)) {
            return ['status' => 'unavailable'];
        }

        return $this->applyVerification($payment, $verified, $verified);
    }

    private function verifiedData(array $response): ?array
    {
        $merchant = config('services.midtrans.sandbox_merchant_id');
        if (filled($merchant) && ($response['merchant_id'] ?? null) !== $merchant) {
            return null;
        }

        // DANA Get Status can return uppercase statuses. Normalize only the authenticated response.
        if (($response['payment_type'] ?? null) === 'dana' && is_string($response['transaction_status'] ?? null)) {
            $response['transaction_status'] = strtolower($response['transaction_status']);
        }
        $verified = $this->transactionData($response);
        if ($verified !== null && $verified['transaction_status'] === 'settlement' && $verified['payment_type'] === 'bank_transfer') {
            $verified['va_amount_matches'] = $this->matchesOptionalVaPaymentAmount($response, $verified['gross_amount']);
        }

        return $verified;
    }

    private function applyVerification(Pembayaran $payment, array $notice, ?array $verified): array
    {
        return DB::transaction(function () use ($payment, $notice, $verified) {
            $order = Pesanan::whereKey($payment->pesanan_id)->lockForUpdate()->first();
            $payments = $order?->pembayaran()->orderBy('id')->limit(2)->lockForUpdate()->get();
            $current = $payments?->firstWhere('id', $payment->getKey());
            if ($current === null || ! $this->matchesOrder($current, $notice['order_id'])) {
                return ['status' => 'rejected', 'reason' => 'unknown_payment'];
            }

            if ($payments->count() !== 1) {
                return $this->reconcile($current, 'ambiguous_local_payment');
            }
            if ($verified === null) {
                return $this->reconcile($current, 'verification_unavailable', observedId: $notice['transaction_id']);
            }

            if ($verified['order_id'] !== $notice['order_id']
                || $verified['transaction_id'] !== $notice['transaction_id']
                || $verified['payment_type'] !== $notice['payment_type']) {
                return $this->reconcile($current, 'identity_conflict');
            }

            $amount = $verified['gross_amount'];
            if ($amount !== $notice['gross_amount']
                || $amount !== $this->rupiah($current->getRawOriginal('jumlah_pembayaran'))
                || $amount !== $this->rupiah($order->getRawOriginal('total_pembayaran'))
                || ($verified['va_amount_matches'] ?? true) === false) {
                return $this->reconcile($current, 'amount_conflict', $verified);
            }

            return $this->applyVerifiedStatus($order, $current, $verified);
        });
    }

    private function applyVerifiedStatus(Pesanan $order, Pembayaran $payment, array $verified): array
    {
        $status = $verified['transaction_status'];
        $type = $verified['payment_type'];
        $fraud = $verified['fraud_status'];
        $id = $verified['transaction_id'];
        $code = $verified['status_code'];

        $success = $status === 'settlement' || $status === 'capture';
        if ($success && (($status === 'capture' && ($type !== 'credit_card' || $fraud !== 'accept'))
            || ($fraud !== null && $fraud !== 'accept'))) {
            return $this->reconcile($payment, 'fraud_unconfirmed', $verified);
        }
        if (! in_array($type, self::PAYMENT_TYPES[$payment->metode_pembayaran] ?? [], true)) {
            return $this->reconcile($payment, 'payment_type_conflict', $verified);
        }
        if (! in_array($code, ['200', '201', '202', '407'], true)) {
            return $this->reconcile($payment, 'status_unconfirmed', $verified);
        }
        if (in_array($status, self::REVERSALS, true)) {
            return $this->reconcile($payment, 'funds_reversed', $verified, priority: true);
        }
        if ($status === 'deny' && $payment->status_pembayaran === 'Berhasil' && $payment->midtrans_transaction_id === $id) {
            return $this->reconcile($payment, 'possible_payment_reversal', $verified, priority: true);
        }
        $terminalFailure = $status === 'expire'
            || (in_array($status, ['cancel', 'deny'], true) && $payment->midtrans_transaction_id === $id);
        $failureReason = $status === 'expire' ? 'payment_expired' : 'payment_failed';
        if ($terminalFailure && $payment->status_pembayaran === 'Gagal'
            && $payment->midtrans_status === $status && $payment->midtrans_transaction_id === $id) {
            return ['status' => 'acknowledged', 'reason' => $failureReason];
        }
        if ($terminalFailure && $payment->status_pembayaran === 'Menunggu' && $payment->waktu_pembayaran === null
            && ($payment->midtrans_transaction_id === null || $payment->midtrans_transaction_id === $id)) {
            if (Pembayaran::where('midtrans_transaction_id', $id)->whereKeyNot($payment->getKey())->exists()) {
                return $this->reconcile($payment, 'transaction_conflict', $verified);
            }

            $payment->update([
                'status_pembayaran' => 'Gagal',
                'midtrans_status' => $status,
                'midtrans_payment_type' => $type,
                'midtrans_transaction_id' => $id,
            ]);

            return ['status' => 'applied', 'reason' => $failureReason];
        }
        if (in_array($status, self::UNSUCCESSFUL, true)) {
            return $this->reconcile($payment, 'attempt_unsuccessful', $verified);
        }
        if ($status === 'pending' && in_array($code, ['200', '201'], true) && $payment->status_pembayaran === 'Berhasil') {
            return ['status' => 'acknowledged', 'reason' => 'pending'];
        }
        if ($payment->midtrans_transaction_id !== null && $payment->midtrans_transaction_id !== $id) {
            return $this->reconcile($payment, 'transaction_conflict', $verified, priority: $success);
        }
        if ($status === 'pending' && in_array($code, ['200', '201'], true)) {
            if ($payment->status_pembayaran === 'Menunggu') {
                $changes = [];
                if ($payment->midtrans_status === null) {
                    $changes = ['midtrans_status' => 'pending', 'midtrans_payment_type' => $type];
                }
                if ($type === 'dana' && $payment->midtrans_transaction_id === null) {
                    if (Pembayaran::where('midtrans_transaction_id', $id)->whereKeyNot($payment->getKey())->exists()) {
                        return $this->reconcile($payment, 'transaction_conflict', $verified);
                    }
                    $changes['midtrans_transaction_id'] = $id;
                }
                if ($changes !== []) {
                    $payment->update($changes);
                }
            }

            return ['status' => 'acknowledged', 'reason' => 'pending'];
        }
        if (! $success || $code !== '200') {
            return $this->reconcile($payment, 'status_unconfirmed', $verified);
        }
        if ($order->status_pesanan === 'Ditolak') {
            return $this->reconcile($payment, 'paid_after_rejection', $verified, priority: true);
        }
        if ($payment->status_pembayaran === 'Berhasil' && $payment->midtrans_transaction_id === $id) {
            return ['status' => 'acknowledged', 'reason' => 'already_paid'];
        }
        if ($payment->status_pembayaran !== 'Menunggu' || $payment->waktu_pembayaran !== null
            || MidtransRekonsiliasi::where('pembayaran_id', $payment->getKey())
                ->where('midtrans_transaction_id', $id)
                ->whereIn('catatan_manual', ['funds_reversed', 'possible_payment_reversal', 'attempt_unsuccessful'])->exists()) {
            return $this->reconcile($payment, 'success_conflicts_with_history', $verified, priority: true);
        }
        if (Pembayaran::where('midtrans_transaction_id', $id)->whereKeyNot($payment->getKey())->exists()) {
            return $this->reconcile($payment, 'transaction_conflict', $verified, priority: true);
        }

        $payment->update([
            'status_pembayaran' => 'Berhasil',
            'waktu_pembayaran' => now(),
            'midtrans_status' => $status,
            'midtrans_payment_type' => $type,
            'midtrans_transaction_id' => $id,
        ]);

        return ['status' => 'applied', 'reason' => 'payment_verified'];
    }

    public function inspectStoredTransaction(Pesanan $order, Pembayaran $payment, string $transactionId, array $response): string
    {
        $verified = $this->verifiedData($response);
        if ($verified === null) {
            return 'tidak_pasti';
        }
        if ($verified['transaction_id'] !== $transactionId
            || ! $this->matchesOrder($payment, $verified['order_id'])
            || ! in_array($verified['payment_type'], self::PAYMENT_TYPES[$payment->metode_pembayaran] ?? [], true)) {
            return 'konflik';
        }
        $amount = $verified['gross_amount'];
        if ($amount !== $this->rupiah($payment->getRawOriginal('jumlah_pembayaran'))
            || $amount !== $this->rupiah($order->getRawOriginal('total_pembayaran'))
            || ($verified['payment_type'] === 'bank_transfer'
                && $verified['transaction_status'] === 'settlement'
                && ! $this->matchesOptionalVaPaymentAmount($response, $amount))) {
            return 'konflik';
        }
        if (! in_array($verified['status_code'], ['200', '201', '202', '407'], true)) {
            return 'tidak_pasti';
        }

        return in_array($verified['transaction_status'], ['pending', 'settlement', ...self::REVERSALS, ...self::UNSUCCESSFUL], true)
            ? $verified['transaction_status']
            : 'tidak_pasti';
    }

    private function transactionData(array $data): ?array
    {
        $fields = [
            'order_id' => '/\A[A-Za-z0-9_~.\-]{1,50}\z/',
            'transaction_id' => '/\A[A-Za-z0-9_~.\-]{1,50}\z/',
            'status_code' => '/\A[1-5][0-9]{2}\z/',
            'transaction_status' => '/\A[a-z_]{1,50}\z/',
            'payment_type' => '/\A[a-z0-9_]{1,50}\z/',
        ];
        foreach ($fields as $field => $pattern) {
            if (! is_string($data[$field] ?? null) || preg_match($pattern, $data[$field]) !== 1) {
                return null;
            }
        }
        $amount = $this->rupiah($data['gross_amount'] ?? null);
        $fraud = $data['fraud_status'] ?? null;
        if ($amount === null || in_array($data['transaction_id'], ['.', '..'], true)
            || (isset($data['currency']) && $data['currency'] !== 'IDR')
            || ($fraud !== null && (! is_string($fraud) || preg_match('/\A[a-z_]{1,32}\z/', $fraud) !== 1))) {
            return null;
        }

        return array_intersect_key($data, $fields) + ['gross_amount' => $amount, 'fraud_status' => $fraud];
    }

    private function matchesOptionalVaPaymentAmount(array $response, string $amount): bool
    {
        if (! array_key_exists('payment_amounts', $response) || $response['payment_amounts'] === []) {
            return true;
        }

        // Riwayat opsional dari Get Status hanya dipakai sebagai pemeriksaan tambahan.
        $payments = $response['payment_amounts'];
        if (! is_array($payments) || ! array_is_list($payments) || count($payments) !== 1) {
            return false;
        }

        return $this->rupiah(((array) $payments[0])['amount'] ?? null) === $amount;
    }

    private function rupiah(mixed $value): ?string
    {
        if ((! is_int($value) && ! is_string($value))
            || preg_match('/\A([1-9][0-9]{0,12})(?:\.0{1,2})?\z/', (string) $value, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function matchesOrder(Pembayaran $payment, string $orderId): bool
    {
        return $payment->gateway === 'midtrans' && $payment->midtrans_order_id === $orderId;
    }

    private function reconcile(Pembayaran $payment, string $reason, array $verified = [], bool $priority = false, ?string $observedId = null): array
    {
        $status = $verified['transaction_status'] ?? null;
        if (! in_array($status, ['pending', 'capture', 'settlement', ...self::REVERSALS, ...self::UNSUCCESSFUL], true)) {
            $status = null;
        }
        $type = $verified['payment_type'] ?? null;
        if (! in_array($type, [...array_merge(...array_values(self::PAYMENT_TYPES)), 'credit_card'], true)) {
            $type = null;
        }
        $identity = [
            'pembayaran_id' => $payment->getKey(),
            'midtrans_order_id' => $payment->midtrans_order_id,
            'midtrans_transaction_id' => $verified['transaction_id'] ?? $observedId,
            'status_transaksi' => $status,
            'catatan_manual' => $reason,
        ];
        $case = MidtransRekonsiliasi::where($identity)->orderBy('id')->lockForUpdate()->first()
            ?? MidtransRekonsiliasi::create($identity + [
                'status_penanganan' => $priority ? 'prioritas_tinggi' : 'menunggu',
                'jenis_pembayaran' => $type,
                'terakhir_diperiksa_at' => now(),
                'periksa_ulang_at' => now(),
            ]);
        (new MidtransReconciliationAudit)->recordSystemCase($case, 'webhook', $verified !== []);

        return ['status' => 'needs_reconciliation', 'reason' => $reason];
    }
}
