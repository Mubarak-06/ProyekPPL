<?php

namespace Tests\Unit;

use App\Services\MidtransSnapService;
use App\Services\MidtransWebhookService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MidtransWebhookServiceTest extends TestCase
{
    private const KEY = 'fixture-only-signing-key';

    private Manager $database;

    protected function setUp(): void
    {
        parent::setUp();
        $app = new Container;
        Container::setInstance($app);
        $app->instance('config', new Repository([
            'services' => ['midtrans' => ['server_key' => self::KEY, 'is_production' => false]],
        ]));
        $this->database = new Manager($app);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->bootEloquent();
        $app->instance('db', $this->database->getDatabaseManager());
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication($app);

        foreach ([
            'CREATE TABLE pesanan (id INTEGER PRIMARY KEY, status_pesanan TEXT, total_pembayaran NUMERIC, updated_at TEXT)',
            'CREATE TABLE pembayaran (id INTEGER PRIMARY KEY, pesanan_id INTEGER, gateway TEXT, metode_pembayaran TEXT, jumlah_pembayaran NUMERIC, status_pembayaran TEXT, waktu_pembayaran TEXT, midtrans_order_id TEXT UNIQUE, midtrans_transaction_id TEXT UNIQUE, midtrans_status TEXT, midtrans_payment_type TEXT, updated_at TEXT)',
            'CREATE TABLE midtrans_rekonsiliasi (id INTEGER PRIMARY KEY, pembayaran_id INTEGER, midtrans_order_id TEXT, midtrans_transaction_id TEXT, status_transaksi TEXT, jenis_pembayaran TEXT, status_penanganan TEXT, catatan_manual TEXT, terakhir_diperiksa_at TEXT, periksa_ulang_at TEXT, pembatalan_dikonfirmasi_at TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE produk (id INTEGER PRIMARY KEY, stok INTEGER, updated_at TEXT)',
            'CREATE TABLE detail_pesanan (pesanan_id INTEGER, produk_id INTEGER, jumlah INTEGER)',
            'CREATE TABLE pengiriman (pesanan_id INTEGER, status_pengiriman TEXT, updated_at TEXT)',
            "INSERT INTO pesanan (id,status_pesanan,total_pembayaran) VALUES (1,'Menunggu',15000),(2,'Menunggu',15000)",
            "INSERT INTO pembayaran (id,pesanan_id,gateway,metode_pembayaran,jumlah_pembayaran,status_pembayaran,midtrans_order_id) VALUES (1,1,'midtrans','QRIS',15000,'Menunggu','KA-1'),(2,2,'manual','QRIS',15000,'Menunggu','KA-MANUAL')",
            'INSERT INTO produk (id,stok) VALUES (1,8)',
            'INSERT INTO detail_pesanan VALUES (1,1,2)',
            "INSERT INTO pengiriman (pesanan_id,status_pengiriman) VALUES (1,'Menunggu')",
        ] as $sql) {
            DB::statement($sql);
        }
        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        (require __DIR__.'/../../database/migrations/2026_11_20_000003_add_midtrans_fulfillment_audit.php')->up();
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        Model::unsetConnectionResolver();
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_invalid_or_unrelated_notifications_do_not_reach_gateway(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->never())->method('getTransactionStatus');
        $service = new MidtransWebhookService($snap);
        $historical = DB::table('pembayaran')->find(2);
        $payload = $this->payload();
        self::assertSame('invalid_signature', $service->process(['signature_key' => str_repeat('a', 128)] + $payload)['reason']);
        self::assertSame('invalid_payload', $service->process(['signature_key' => ''] + $payload)['reason']);
        unset($payload['transaction_id']);
        self::assertSame('invalid_payload', $service->process($payload)['reason']);
        self::assertSame('unknown_payment', $service->process($this->payload(['order_id' => 'NOT-FOUND']))['reason']);
        self::assertSame('unknown_payment', $service->process($this->payload(['order_id' => 'KA-MANUAL']))['reason']);
        self::assertEquals($historical, DB::table('pembayaran')->find(2));
        self::assertSame(0, DB::table('midtrans_rekonsiliasi')->count());
    }

    public static function nonSuccessStatuses(): array
    {
        return [
            'pending' => ['pending', '201', 'qris', null, 'pending', 0],
            'deny' => ['deny', '202', 'qris', 'deny', 'attempt_unsuccessful', 1],
            'cancel' => ['cancel', '200', 'qris', null, 'attempt_unsuccessful', 1],
            'failure' => ['failure', '407', 'qris', null, 'attempt_unsuccessful', 1],
            'refund' => ['refund', '200', 'qris', null, 'funds_reversed', 1],
            'partial refund' => ['partial_refund', '200', 'qris', null, 'funds_reversed', 1],
            'safe card capture outside supported channels' => ['capture', '200', 'credit_card', 'accept', 'payment_type_conflict', 1],
            'unsafe card capture' => ['capture', '200', 'credit_card', 'challenge', 'fraud_unconfirmed', 1],
            'undocumented status' => ['unknown', '200', 'qris', null, 'status_unconfirmed', 1],
            'unsuccessful status code' => ['settlement', '202', 'qris', null, 'status_unconfirmed', 1],
        ];
    }

    #[DataProvider('nonSuccessStatuses')]
    public function test_verified_status_without_eligible_settlement_keeps_order_and_stock(
        string $status, string $code, string $type, ?string $fraud, string $reason, int $cases,
    ): void {
        $payload = $this->payload(['transaction_status' => $status, 'status_code' => $code, 'payment_type' => $type, 'fraud_status' => $fraud]);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(2))->method('getTransactionStatus')->with('txn-1')->willReturn((object) $payload);
        $service = new MidtransWebhookService($snap);
        self::assertSame($reason, $service->process($payload)['reason']);
        self::assertSame($reason, $service->process($payload)['reason']);
        self::assertSame($cases, DB::table('midtrans_rekonsiliasi')->count());
        self::assertNull(DB::table('midtrans_rekonsiliasi')->value('pembatalan_dikonfirmasi_at'));
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame('Menunggu', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_amount_and_identity_conflicts_are_recorded_without_payment_update(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->method('getTransactionStatus')->willReturnOnConsecutiveCalls(
            (object) $this->payload(['gross_amount' => '14999.00']),
            (object) $this->payload(['transaction_id' => 'other-transaction']),
        );
        $service = new MidtransWebhookService($snap);
        self::assertSame('amount_conflict', $service->process($this->payload())['reason']);
        self::assertSame('identity_conflict', $service->process($this->payload())['reason']);
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame(2, DB::table('midtrans_rekonsiliasi')->count());
    }

    public function test_overlapping_webhooks_apply_payment_once_and_late_pending_cannot_downgrade_it(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $service = new MidtransWebhookService($snap);
        $calls = 0;
        $before = DB::table('pembayaran')->find(2);
        $snap->expects($this->exactly(3))->method('getTransactionStatus')->willReturnCallback(function () use ($service, &$calls) {
            self::assertSame(0, DB::transactionLevel());
            $calls++;
            if ($calls === 1) {
                self::assertSame('applied', $service->process($this->payload())['status']);
            }

            return (object) ($calls === 3
                ? $this->payload(['transaction_status' => 'pending', 'status_code' => '201'])
                : $this->payload());
        });
        self::assertSame('already_paid', $service->process($this->payload())['reason']);
        $paid = DB::table('pembayaran')->find(1);
        self::assertSame('Berhasil', $paid->status_pembayaran);
        self::assertSame('txn-1', $paid->midtrans_transaction_id);
        self::assertNotNull($paid->waktu_pembayaran);
        self::assertSame('pending', $service->process($this->payload(['transaction_status' => 'pending', 'status_code' => '201']))['reason']);
        self::assertEquals($paid, DB::table('pembayaran')->find(1));
        self::assertEquals($before, DB::table('pembayaran')->find(2));
        self::assertSame('Menunggu', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_refund_applied_during_verification_blocks_stale_success_snapshot(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $service = new MidtransWebhookService($snap);
        $active = false;
        $snap->expects($this->exactly(2))->method('getTransactionStatus')->willReturnCallback(function () use ($service, &$active) {
            $refund = $this->payload(['transaction_status' => 'refund']);
            if ($active) {
                return (object) $refund;
            }
            $active = true;
            self::assertSame('funds_reversed', $service->process($refund)['reason']);

            return (object) $this->payload();
        });
        self::assertSame('success_conflicts_with_history', $service->process($this->payload())['reason']);
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame(2, DB::table('midtrans_rekonsiliasi')->count());
    }

    public function test_server_status_overrides_notification_and_refund_preserves_success(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $pending = $this->payload(['transaction_status' => 'pending', 'status_code' => '201']);
        $refund = $this->payload(['transaction_status' => 'partial_refund']);
        $snap->method('getTransactionStatus')->willReturnOnConsecutiveCalls(
            (object) $pending, (object) $this->payload(), (object) $refund,
        );
        $service = new MidtransWebhookService($snap);

        self::assertSame('pending', $service->process($this->payload())['reason']);
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame('applied', $service->process($pending)['status']);
        $paid = DB::table('pembayaran')->find(1);
        self::assertSame('funds_reversed', $service->process($refund)['reason']);
        self::assertEquals($paid, DB::table('pembayaran')->find(1));
        self::assertSame('prioritas_tinggi', DB::table('midtrans_rekonsiliasi')->value('status_penanganan'));
    }

    public function test_rejected_order_with_verified_payment_is_prioritized_without_stock_changes(): void
    {
        DB::table('pesanan')->where('id', 1)->update(['status_pesanan' => 'Ditolak']);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->method('getTransactionStatus')->willReturn((object) $this->payload());
        $service = new MidtransWebhookService($snap);
        self::assertSame('paid_after_rejection', $service->process($this->payload())['reason']);
        self::assertSame('prioritas_tinggi', DB::table('midtrans_rekonsiliasi')->value('status_penanganan'));
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame('Ditolak', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_gateway_timeout_can_be_retried_without_leaving_a_hold(): void
    {
        $payload = $this->payload();
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(2))->method('getTransactionStatus')->with('txn-1')
            ->willReturnCallback(function () use ($payload) {
                static $attempt = 0;
                if (++$attempt === 1) {
                    throw new RuntimeException('Timeout', 28);
                }

                return (object) $payload;
            });
        $service = new MidtransWebhookService($snap);

        self::assertSame(['status' => 'unavailable', 'reason' => 'gateway_unavailable'], $service->process($payload));
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame('applied', $service->process($payload)['status']);
        self::assertSame('Berhasil', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame(0, DB::table('midtrans_rekonsiliasi')->count());
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_404_stays_uncertain_and_raw_error_is_not_stored(): void
    {
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->method('getTransactionStatus')->willThrowException(new RuntimeException('sensitive fake response', 404));
        $service = new MidtransWebhookService($snap);
        self::assertSame('verification_unavailable', $service->process($this->payload())['reason']);
        self::assertSame('verification_unavailable', DB::table('midtrans_rekonsiliasi')->value('catatan_manual'));
        self::assertNull(DB::table('midtrans_rekonsiliasi')->value('pembatalan_dikonfirmasi_at'));
    }

    public static function settlementAfterDeny(): array
    {
        return [
            'same attempt' => ['txn-1', 'success_conflicts_with_history', 'Menunggu'],
            'another attempt' => ['txn-2', 'payment_verified', 'Berhasil'],
        ];
    }

    #[DataProvider('settlementAfterDeny')]
    public function test_settlement_after_pending_and_deny(string $settledId, string $reason, string $localStatus): void
    {
        $pending = $this->payload(['transaction_status' => 'pending', 'status_code' => '201']);
        $deny = $this->payload(['transaction_status' => 'deny', 'status_code' => '202']);
        $settlement = $this->payload(['transaction_id' => $settledId]);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(3))->method('getTransactionStatus')
            ->willReturnOnConsecutiveCalls((object) $pending, (object) $deny, (object) $settlement);
        $service = new MidtransWebhookService($snap);
        $historical = DB::table('pembayaran')->find(2);

        self::assertSame('pending', $service->process($pending)['reason']);
        self::assertSame('attempt_unsuccessful', $service->process($deny)['reason']);
        self::assertSame($reason, $service->process($settlement)['reason']);
        $payment = DB::table('pembayaran')->find(1);
        self::assertSame($localStatus, $payment->status_pembayaran);
        self::assertSame($localStatus === 'Berhasil' ? $settledId : null, $payment->midtrans_transaction_id);
        self::assertSame('Menunggu', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
        self::assertEquals($historical, DB::table('pembayaran')->find(2));
    }

    public static function eventsAfterSettlement(): array
    {
        return [
            'same-ID deny' => ['deny', '202', 'txn-1', 'possible_payment_reversal', 'prioritas_tinggi'],
            'other-ID deny' => ['deny', '202', 'txn-2', 'attempt_unsuccessful', 'menunggu'],
            'refund' => ['refund', '200', 'txn-1', 'funds_reversed', 'prioritas_tinggi'],
            'partial refund' => ['partial_refund', '200', 'txn-1', 'funds_reversed', 'prioritas_tinggi'],
            'other-ID pending' => ['pending', '201', 'txn-2', 'pending', null],
            'other-ID settlement' => ['settlement', '200', 'txn-2', 'transaction_conflict', 'prioritas_tinggi'],
        ];
    }

    #[DataProvider('eventsAfterSettlement')]
    public function test_events_after_settlement_preserve_payment_and_deduplicate_cases(
        string $status, string $code, string $transactionId, string $reason, ?string $priority,
    ): void {
        $settlement = $this->payload();
        $event = $this->payload(['transaction_status' => $status, 'status_code' => $code, 'transaction_id' => $transactionId]);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(3))->method('getTransactionStatus')
            ->willReturnOnConsecutiveCalls((object) $settlement, (object) $event, (object) $event);
        $service = new MidtransWebhookService($snap);

        self::assertSame('applied', $service->process($settlement)['status']);
        $paid = DB::table('pembayaran')->find(1);
        self::assertSame($reason, $service->process($event)['reason']);
        self::assertSame($reason, $service->process($event)['reason']);
        self::assertSame($priority === null ? 0 : 1, DB::table('midtrans_rekonsiliasi')->count());
        self::assertSame($priority === null ? 0 : 1, DB::table('midtrans_rekonsiliasi_riwayat')->count());
        self::assertSame($priority, DB::table('midtrans_rekonsiliasi')->value('status_penanganan'));
        self::assertNull(DB::table('midtrans_rekonsiliasi')->value('pembatalan_dikonfirmasi_at'));
        self::assertEquals($paid, DB::table('pembayaran')->find(1));
        self::assertSame('Menunggu', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_mandiri_settlement_uses_its_own_payment_type(): void
    {
        DB::table('pembayaran')->where('id', 1)->update(['metode_pembayaran' => 'Transfer Bank']);
        $notice = $this->payload(['payment_type' => 'echannel']);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->once())->method('getTransactionStatus')->with($notice['transaction_id'])
            ->willReturn((object) ($notice + ['bill_key' => '001689', 'biller_code' => '70012']));

        self::assertSame('applied', (new MidtransWebhookService($snap))->process($notice)['status']);
        self::assertSame('echannel', DB::table('pembayaran')->where('id', 1)->value('midtrans_payment_type'));
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public static function vaAmounts(): array
    {
        return [
            'gross below order' => ['14999.00', [], [], 'amount_conflict'],
            'gross above order' => ['15001.00', [], [], 'amount_conflict'],
            'missing history' => ['15000.00', [], [], 'payment_verified'],
            'empty history' => ['15000.00', ['payment_amounts' => []], [], 'payment_verified'],
            'exact server received amount' => ['15000.00', ['payment_amounts' => [(object) ['amount' => '15000.00']]], [], 'payment_verified'],
            'server history below gross' => ['15000.00', ['payment_amounts' => [['amount' => '14999.00']]], [], 'amount_conflict'],
            'server history above gross' => ['15000.00', ['payment_amounts' => [['amount' => '15001.00']]], [], 'amount_conflict'],
            'server history overrides conflicting notification' => [
                '15000.00', ['payment_amounts' => [['amount' => '14999.00']]], ['payment_amounts' => [['amount' => '15000.00']]], 'amount_conflict',
            ],
            'unsigned notification history is ignored' => [
                '15000.00', [], ['payment_amounts' => [['amount' => '14999.00']]], 'payment_verified',
            ],
            'multiple entries are not combined' => [
                '15000.00', ['payment_amounts' => [['amount' => '5000.00'], ['amount' => '10000.00']]], [], 'amount_conflict',
            ],
        ];
    }

    #[DataProvider('vaAmounts')]
    public function test_va_requires_exact_verified_amount(string $grossAmount, array $server, array $notice, string $reason): void
    {
        DB::table('pembayaran')->where('id', 1)->update(['metode_pembayaran' => 'Transfer Bank']);
        $fields = ['payment_type' => 'bank_transfer', 'gross_amount' => $grossAmount];
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->once())->method('getTransactionStatus')->willReturn((object) $this->payload($server + $fields));
        $service = new MidtransWebhookService($snap);

        self::assertSame($reason, $service->process($this->payload($notice + $fields))['reason']);
        self::assertSame($reason === 'payment_verified' ? 'Berhasil' : 'Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public static function danaTerminalStatuses(): array
    {
        return [
            'expired' => ['expire', '407', 'payment_expired'],
            'canceled' => ['cancel', '200', 'payment_failed'],
            'denied' => ['deny', '202', 'payment_failed'],
        ];
    }

    #[DataProvider('danaTerminalStatuses')]
    public function test_dana_refresh_validates_merchant_and_applies_terminal_status_once(string $status, string $code, string $reason): void
    {
        config(['services.midtrans.sandbox_merchant_id' => 'merchant-sandbox']);
        DB::table('pembayaran')->where('id', 1)->update(['metode_pembayaran' => 'E-Wallet']);
        $fields = ['payment_type' => 'dana', 'merchant_id' => 'merchant-sandbox'];
        $pending = $this->payload(['transaction_status' => 'pending', 'status_code' => '201'] + $fields);
        $expired = $this->payload(['transaction_status' => $status, 'status_code' => $code] + $fields);
        $settled = $this->payload($fields);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(5))->method('getTransactionStatus')->with('txn-1')
            ->willReturnOnConsecutiveCalls((object) $pending, (object) (['merchant_id' => 'other'] + $expired),
                (object) $expired, (object) $expired, (object) $settled);
        $service = new MidtransWebhookService($snap);
        $refresh = fn () => $service->refreshStatus(\App\Models\Pembayaran::findOrFail(1), 'txn-1');

        self::assertSame('pending', $refresh()['reason']);
        self::assertSame('unavailable', $refresh()['status']);
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame(8, DB::table('produk')->value('stok'));
        DB::table('produk')->where('id', 1)->update(['stok' => 14]);
        self::assertSame(['status' => 'applied', 'reason' => $reason], $refresh());
        $payment = DB::table('pembayaran')->find(1);
        self::assertSame('Gagal', $payment->status_pembayaran);
        self::assertSame($status, $payment->midtrans_status);
        self::assertSame('txn-1', $payment->midtrans_transaction_id);
        self::assertNull($payment->waktu_pembayaran);
        self::assertSame(['status' => 'acknowledged', 'reason' => $reason], $refresh());
        self::assertSame('paid_after_rejection', $refresh()['reason']);
        self::assertEquals($payment, DB::table('pembayaran')->find(1));
        self::assertSame('Ditolak', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame('Ditolak', DB::table('pengiriman')->value('status_pengiriman'));
        self::assertSame(16, DB::table('produk')->value('stok'));
        self::assertSame('prioritas_tinggi', DB::table('midtrans_rekonsiliasi')->where('catatan_manual', 'paid_after_rejection')->value('status_penanganan'));
    }

    public function test_verified_expiry_with_active_reconciliation_keeps_stock_reserved(): void
    {
        $expired = $this->payload(['transaction_status' => 'expire', 'status_code' => '407']);
        $snap = $this->createMock(MidtransSnapService::class);
        $snap->expects($this->exactly(2))->method('getTransactionStatus')
            ->willReturnCallback(function () use ($expired) {
                static $calls = 0;
                if (++$calls === 1) {
                    throw new RuntimeException('Tidak ditemukan', 404);
                }

                return (object) $expired;
            });
        $service = new MidtransWebhookService($snap);
        self::assertSame('verification_unavailable', $service->process($expired)['reason']);
        self::assertSame('terminal_payment_held', $service->process($expired)['reason']);
        self::assertSame('Menunggu', DB::table('pembayaran')->find(1)->status_pembayaran);
        self::assertSame('Menunggu', DB::table('pesanan')->find(1)->status_pesanan);
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    private function payload(array $fields = []): array
    {
        $payload = $fields + [
            'order_id' => 'KA-1',
            'transaction_id' => 'txn-1',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'payment_type' => 'qris',
            'gross_amount' => '15000.00',
            'currency' => 'IDR',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::KEY);

        return $payload;
    }
}
