<?php

namespace Tests\Feature;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\MidtransSnapService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\PelangganTestCase;

class MidtransSchedulerTest extends PelangganTestCase
{
    public function test_scheduled_command_reuses_verification_and_skips_payments_outside_its_scope(): void
    {
        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.server_key' => 'test-server',
            'services.midtrans.client_key' => 'test-client',
            'services.midtrans.sandbox_merchant_id' => 'test-merchant',
        ]);
        $data = $this->dataBelanja();
        $paid = $this->payment($data, 'paid');
        $pending = $this->payment($data, 'pending');
        $expired = $this->payment($data, 'expired', 'E-Wallet', 'txn-expired');
        $unavailable = $this->payment($data, 'unavailable');
        $held = $this->payment($data, 'held');
        $unknownDana = $this->payment($data, 'unknown-dana', 'E-Wallet');
        MidtransRekonsiliasi::create([
            'pembayaran_id' => $held->id,
            'midtrans_order_id' => $held->midtrans_order_id,
            'status_penanganan' => 'menunggu',
        ]);

        $snap = $this->partialMock(MidtransSnapService::class);
        $snap->shouldReceive('getTransactionStatus')->once()->with('paid')
            ->andReturn($this->response('paid', 'settlement', '200'));
        $snap->shouldReceive('getTransactionStatus')->twice()->with('pending')
            ->andReturn($this->response('pending', 'pending', '201'));
        $snap->shouldReceive('getTransactionStatus')->once()->with('txn-expired')
            ->andReturn($this->response('expired', 'expire', '407', 'dana'));
        $snap->shouldReceive('getTransactionStatus')->twice()->with('unavailable')
            ->andThrow(new RuntimeException('Not found', 404));

        $event = collect(app(Schedule::class)->events())
            ->sole(fn ($event) => str_contains($event->command, 'midtrans:sync-pending'));
        self::assertSame('*/5 * * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);

        Log::spy();
        $lock = Cache::lock('midtrans:sandbox:sync:running', 600);
        self::assertTrue($lock->get());
        $this->artisan('midtrans:sync-pending')->expectsOutput('Sinkronisasi Midtrans masih berjalan.')->assertSuccessful();
        $lock->release();

        $this->artisan('midtrans:sync-pending')->assertSuccessful();
        $firstPaymentTime = $paid->refresh()->getRawOriginal('waktu_pembayaran');
        self::assertNotNull($firstPaymentTime);
        $this->artisan('midtrans:sync-pending')->assertSuccessful();

        self::assertSame('Berhasil', $paid->refresh()->status_pembayaran);
        self::assertSame($firstPaymentTime, $paid->getRawOriginal('waktu_pembayaran'));
        self::assertSame('pending', $pending->refresh()->midtrans_status);
        self::assertSame('Menunggu', $pending->status_pembayaran);
        self::assertSame('Gagal', $expired->refresh()->status_pembayaran);
        self::assertSame('expire', $expired->midtrans_status);
        foreach ([$unavailable, $held, $unknownDana] as $payment) {
            self::assertSame('Menunggu', $payment->refresh()->status_pembayaran);
            self::assertNull($payment->midtrans_status);
        }
        self::assertSame(10, $data['produk']->refresh()->stok);
        Log::shouldHaveReceived('info')->with('Midtrans Sandbox sync skipped', ['reason' => 'already_running'])->once();
        foreach ([[4, 2], [2, 0]] as [$checked, $synced]) {
            Log::shouldHaveReceived('info')->with('Midtrans Sandbox sync completed', [
                'checked' => $checked,
                'synced' => $synced,
                'pending' => 1,
                'skipped' => 2,
                'deferred' => 0,
                'failed' => 1,
                'needs_reconciliation' => 0,
            ])->once();
        }
    }

    private function payment(array $data, string $reference, string $method = 'Transfer Bank', ?string $transactionId = null): Pembayaran
    {
        $order = Pesanan::create([
            'kode_pesanan' => $reference,
            'user_id' => $data['user']->id,
            'alamat_id' => $data['alamat'],
            'subtotal' => 12500,
            'total_pembayaran' => 12500,
        ]);

        return $order->pembayaran()->create([
            'gateway' => 'midtrans',
            'metode_pembayaran' => $method,
            'jumlah_pembayaran' => 12500,
            'status_pembayaran' => 'Menunggu',
            'midtrans_order_id' => $reference,
            'midtrans_transaction_id' => $transactionId,
            'snap_token' => 'fixture-token',
        ]);
    }

    private function response(string $reference, string $status, string $code, string $type = 'bank_transfer'): object
    {
        return (object) [
            'order_id' => $reference,
            'transaction_id' => 'txn-'.$reference,
            'merchant_id' => 'test-merchant',
            'transaction_status' => $status,
            'status_code' => $code,
            'payment_type' => $type,
            'gross_amount' => '12500.00',
            'currency' => 'IDR',
        ];
    }
}
