<?php

namespace App\Console\Commands;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pembayaran;
use App\Services\MidtransWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SinkronisasiMidtrans extends Command
{
    private const BATCH_SIZE = 5;

    private const CURSOR = 'midtrans:sandbox:sync:cursor';

    protected $signature = 'midtrans:sync-pending';

    protected $description = 'Periksa status pembayaran Midtrans Sandbox yang masih menunggu';

    public function handle(MidtransWebhookService $midtrans): int
    {
        if (config('services.midtrans.is_production') !== false
            || blank(config('services.midtrans.server_key'))
            || blank(config('services.midtrans.client_key'))
            || blank(config('services.midtrans.sandbox_merchant_id'))) {
            Log::info('Midtrans Sandbox sync skipped', ['reason' => 'sandbox_configuration']);
            $this->warn('Sinkronisasi memerlukan mode Sandbox, kedua key, dan Merchant ID Sandbox.');

            return self::SUCCESS;
        }

        $lock = Cache::lock('midtrans:sandbox:sync:running', 600);
        if (! $lock->get()) {
            Log::info('Midtrans Sandbox sync skipped', ['reason' => 'already_running']);
            $this->info('Sinkronisasi Midtrans masih berjalan.');

            return self::SUCCESS;
        }

        try {
            $heldPayments = MidtransRekonsiliasi::query()
                ->where('status_penahanan', '<>', 'dilepas')
                ->select('pembayaran_id');
            $waiting = Pembayaran::query()
                ->where('gateway', 'midtrans')
                ->where('status_pembayaran', 'Menunggu');
            $eligible = (clone $waiting)
                ->whereNull('waktu_pembayaran')
                ->whereNotNull('midtrans_order_id')
                ->whereNotIn('id', $heldPayments)
                ->where(function ($query) {
                    $query->whereNotNull('midtrans_transaction_id')
                        ->orWhere(function ($query) {
                            $query->whereIn('metode_pembayaran', ['Transfer Bank', 'QRIS'])
                                ->where(function ($query) {
                                    $query->whereNotNull('snap_token')->orWhere('midtrans_status', 'pending');
                                });
                        });
                });
            $eligibleCount = (clone $eligible)->count();
            $payments = $eligible
                ->orderByRaw('CASE WHEN id > ? THEN 0 ELSE 1 END', [(int) Cache::get(self::CURSOR, 0)])
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->get();

            $summary = [
                'checked' => 0,
                'synced' => 0,
                'pending' => 0,
                'skipped' => max(0, $waiting->count() - $eligibleCount),
                'deferred' => max(0, $eligibleCount - $payments->count()),
                'failed' => 0,
                'needs_reconciliation' => 0,
            ];
            foreach ($payments as $payment) {
                $result = $midtrans->refreshStatus($payment);
                $bucket = match ($result['status']) {
                    'applied' => 'synced',
                    'acknowledged' => ($result['reason'] ?? null) === 'pending' ? 'pending' : 'skipped',
                    'needs_reconciliation' => 'needs_reconciliation',
                    'rejected', 'transaction_id_required' => 'skipped',
                    default => 'failed',
                };
                $summary['checked']++;
                $summary[$bucket]++;
                Cache::put(self::CURSOR, $payment->getKey(), now()->addDay());
            }

            Log::info('Midtrans Sandbox sync completed', $summary);
            $this->info("Midtrans Sandbox: {$summary['checked']} diperiksa, {$summary['synced']} diperbarui, {$summary['failed']} belum dapat diverifikasi.");
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
