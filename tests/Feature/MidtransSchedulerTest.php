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
            'services.midtrans.sandbox_channels' => ['bca_va', 'dana'],
            'services.midtrans.sandbox_verified_channels' => ['bca_va', 'dana'],
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
        $snap->shouldReceive('getTransactionStatus')->twice()->with('txn-expired')
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
        self::assertSame('Ditolak', $expired->pesanan->status_pesanan);
        self::assertSame('Ditolak', $expired->pesanan->pengiriman->status_pengiriman);
        foreach ([$unavailable, $held, $unknownDana] as $payment) {
            self::assertSame('Menunggu', $payment->refresh()->status_pembayaran);
            self::assertNull($payment->midtrans_status);
        }
        self::assertSame(5, $data['produk']->refresh()->stok);
        $notice = (array) $this->response('expired', 'expire', '407', 'dana');
        $notice['signature_key'] = hash('sha512', 'expired40712500.00test-server');
        $this->postJson('/api/webhooks/midtrans', $notice)->assertOk();
        self::assertSame(5, $data['produk']->refresh()->stok);

        $order = $expired->pesanan;
        $this->actingAs($data['user'])->get('/pesanan/'.$order->id)->assertOk()
            ->assertSee('Dibatalkan')->assertSee('Pembayaran kedaluwarsa')->assertDontSee('Bayar Sekarang');
        $this->get('/pesanan/'.$order->id.'/pembayaran')->assertOk()
            ->assertSee('Pembayaran kedaluwarsa')->assertDontSee('Buka pembayaran Snap');
        $this->postJson('/pesanan/'.$order->id.'/pembayaran/snap')->assertExactJson(['status' => 'rejected']);
        $this->actingAs($this->akunAdmin())->patch('/admin/pesanan/'.$order->id.'/status', ['status_pesanan' => 'Dikirim'])
            ->assertSessionHasErrors('status_pesanan');
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

    public function test_expired_dana_session_without_identity_is_flagged_without_releasing_stock(): void
    {
        config(['services.midtrans.sandbox_merchant_id' => 'test-merchant']);
        $data = $this->dataBelanja();
        $payment = $this->payment($data, 'expired-page', 'E-Wallet');
        $payment->update(['snap_token_expires_at' => now()->subMinute()]);
        $this->partialMock(MidtransSnapService::class)->shouldNotReceive('getTransactionStatus');

        $this->artisan('midtrans:sync-pending')->assertSuccessful();
        $this->artisan('midtrans:sync-pending')->assertSuccessful();
        $this->assertDatabaseHas('midtrans_rekonsiliasi', [
            'pembayaran_id' => $payment->id, 'catatan_manual' => 'expired_snap_missing_identity', 'status_penahanan' => 'ditahan',
        ]);
        $this->assertDatabaseCount('midtrans_rekonsiliasi', 1);
        self::assertSame('Menunggu', $payment->refresh()->status_pembayaran);
        self::assertSame('Menunggu', $payment->pesanan->status_pesanan);
        self::assertSame(9, $data['produk']->refresh()->stok);
    }

    private function payment(array $data, string $reference, string $method = 'Transfer Bank', ?string $transactionId = null): Pembayaran
    {
        $order = Pesanan::create([
            'kode_pesanan' => $reference,
            'user_id' => $data['user']->id,
            'alamat_id' => $data['alamat'],
            'subtotal' => 12500,
            'total_pembayaran' => 12500,
            'status_pesanan' => 'Menunggu',
        ]);
        $order->detailPesanan()->create([
            'produk_id' => $data['produk']->id, 'nama_produk' => $data['produk']->nama_produk,
            'harga_satuan' => 12500, 'jumlah' => 1, 'subtotal' => 12500,
        ]);
        $order->pengiriman()->create([
            'nama_penerima' => 'Penerima', 'nomor_telepon' => '081234567890',
            'alamat_pengiriman' => 'Alamat pengujian', 'status_pengiriman' => 'Menunggu',
        ]);
        $data['produk']->decrement('stok');

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
