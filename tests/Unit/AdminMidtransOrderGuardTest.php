<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\PesananController;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminMidtransOrderGuardTest extends TestCase
{
    private Manager $database;

    protected function setUp(): void
    {
        parent::setUp();
        $app = new Container;
        Container::setInstance($app);
        $app->instance('config', new Repository);
        $this->database = new Manager($app);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->setEventDispatcher(new Dispatcher($app));
        Model::clearBootedModels();
        $this->database->bootEloquent();
        $app->instance('db', $this->database->getDatabaseManager());
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication($app);
        $app->instance(\Illuminate\Contracts\Validation\Factory::class, new Factory(new Translator(new ArrayLoader, 'en'), $app));
        (new FoundationServiceProvider($app))->registerRequestValidation();
        $redirector = $this->createMock(Redirector::class);
        $redirector->method('back')->willReturnCallback(function () {
            $response = new RedirectResponse('/admin/pesanan/1');
            $response->setSession(new Store('test', new ArraySessionHandler(60)));

            return $response;
        });
        $app->instance('redirect', $redirector);

        $db = $this->database->getConnection();
        foreach ([
            'CREATE TABLE pesanan (id INTEGER PRIMARY KEY, status_pesanan TEXT, updated_at TEXT)',
            'CREATE TABLE pembayaran (id INTEGER PRIMARY KEY, pesanan_id INTEGER, gateway TEXT, status_pembayaran TEXT, snap_requested_at TEXT, snap_token TEXT, snap_redirect_url TEXT, snap_token_expires_at TEXT, midtrans_transaction_id TEXT, midtrans_status TEXT, midtrans_payment_type TEXT, waktu_pembayaran TEXT)',
            'CREATE TABLE pengiriman (pesanan_id INTEGER, status_pengiriman TEXT, waktu_dikirim TEXT, waktu_selesai TEXT, updated_at TEXT)',
            'CREATE TABLE produk (id INTEGER PRIMARY KEY, stok INTEGER, updated_at TEXT)',
            'CREATE TABLE detail_pesanan (pesanan_id INTEGER, produk_id INTEGER, jumlah INTEGER)',
            "CREATE TABLE midtrans_rekonsiliasi (id INTEGER PRIMARY KEY, pembayaran_id INTEGER, status_penanganan TEXT NOT NULL, status_transaksi TEXT, midtrans_transaction_id TEXT, catatan_manual TEXT, status_penahanan TEXT NOT NULL DEFAULT 'ditahan')",
            "INSERT INTO pesanan VALUES (1, 'Menunggu', NULL)",
            "INSERT INTO pembayaran (id,pesanan_id,gateway,status_pembayaran) VALUES (1,1,'midtrans','Menunggu')",
            "INSERT INTO pengiriman (pesanan_id,status_pengiriman) VALUES (1, 'Menunggu')",
            'INSERT INTO produk VALUES (1, 8, NULL)',
            'INSERT INTO detail_pesanan VALUES (1, 1, 2)',
        ] as $sql) {
            $db->statement($sql);
        }
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        Model::clearBootedModels();
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    public static function blockedPayments(): array
    {
        return [
            'claim' => [['snap_requested_at' => '2026-11-20 10:00:00']],
            'unknown' => [['midtrans_status' => 'unknown']],
            'successful payment' => [['status_pembayaran' => 'Berhasil']],
            'expired token' => [['snap_token' => 'dummy-ciphertext', 'snap_token_expires_at' => '2026-11-20 10:00:00']],
            'gateway transaction' => [['midtrans_transaction_id' => 'dummy-transaction']],
            'pending reconciliation' => [[], true],
        ];
    }

    #[DataProvider('blockedPayments')]
    public function test_direct_rejection_with_gateway_activity_is_held(array $fields, bool $reconciliation = false): void
    {
        $db = $this->database->getConnection();
        if ($fields !== []) {
            $db->table('pembayaran')->update($fields);
        }
        if ($reconciliation) {
            $db->table('midtrans_rekonsiliasi')->insert(['pembayaran_id' => 1, 'status_penanganan' => 'menunggu']);
        }
        $before = $db->table('pembayaran')->first();
        $reply = (new PesananController)->tolak(1);

        self::assertStringContainsString('Penolakan ditahan', $reply->getSession()->get('error'));
        self::assertEquals($before, $db->table('pembayaran')->first());
        self::assertSame($reconciliation ? 1 : 0, $db->table('midtrans_rekonsiliasi')->count());
        $this->assertState('Menunggu', 8);
    }

    public function test_safe_midtrans_rejection_restores_stock_once(): void
    {
        $controller = new PesananController;
        self::assertTrue($controller->tolak(1)->getSession()->has('success'));
        $this->assertState('Ditolak', 10);
        self::assertTrue($controller->tolak(1)->getSession()->has('error'));
        $this->assertState('Ditolak', 10);
    }

    public function test_only_server_payment_status_can_authorize_processing(): void
    {
        $controller = new PesananController;
        self::assertTrue($controller->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->has('error'));
        $this->assertState('Menunggu', 8);
        $this->database->getConnection()->table('pembayaran')->update([
            'status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'paid-txn', 'midtrans_status' => 'settlement',
        ]);
        self::assertTrue($controller->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->has('success'));
        $this->assertState('Diproses', 8);
        self::assertTrue($controller->updateStatus($this->statusRequest('Dikirim'), 1)->getSession()->has('success'));
        self::assertStringContainsString('penerimaan barang', $controller->updateStatus($this->statusRequest('Selesai'), 1)->getSession()->get('error'));
        $this->assertState('Dikirim', 8);
    }

    public function test_manual_payment_is_preserved_when_processing_is_denied(): void
    {
        $db = $this->database->getConnection();
        $db->table('pembayaran')->update(['gateway' => 'manual']);
        $before = $db->table('pembayaran')->first();
        $controller = new PesananController;
        self::assertStringContainsString('pembayaran historis', $controller->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->get('error'));
        $this->assertState('Menunggu', 8);
        self::assertEquals($before, $db->table('pembayaran')->first());
        self::assertTrue($controller->tolak(1)->getSession()->has('success'));
        $this->assertState('Ditolak', 10);
        self::assertEquals($before, $db->table('pembayaran')->first());
    }

    public function test_missing_shipment_blocks_status_change_and_stock_restoration(): void
    {
        $db = $this->database->getConnection();
        $db->table('pengiriman')->delete();
        $controller = new PesananController;
        self::assertStringContainsString('catatan pengiriman', $controller->tolak(1)->getSession()->get('error'));
        $db->table('pembayaran')->update([
            'status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'paid-txn', 'midtrans_status' => 'settlement',
        ]);
        self::assertStringContainsString('Catatan pengiriman', $controller->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->get('error'));
        self::assertSame('Menunggu', $db->table('pesanan')->value('status_pesanan'));
        self::assertSame(8, $db->table('produk')->value('stok'));
    }

    public static function unverifiedShippingPayments(): array
    {
        return [
            'pending' => ['Menunggu'],
            'failed' => ['Gagal'],
            'missing' => [null],
        ];
    }

    #[DataProvider('unverifiedShippingPayments')]
    public function test_shipping_and_completion_require_verified_payment(?string $paymentStatus): void
    {
        $db = $this->database->getConnection();
        if ($paymentStatus === null) {
            $db->table('pembayaran')->delete();
        } else {
            $db->table('pembayaran')->update(['status_pembayaran' => $paymentStatus]);
        }

        $controller = new PesananController;
        foreach (['Diproses' => 'Dikirim', 'Dikirim' => 'Selesai'] as $current => $target) {
            $db->table('pesanan')->update(['status_pesanan' => $current]);
            $db->table('pengiriman')->update(['status_pengiriman' => $current]);
            self::assertTrue($controller->updateStatus($this->statusRequest($target), 1)->getSession()->has('error'));
            $this->assertState($current, 8);
        }
    }

    public static function blockingReconciliations(): array
    {
        return [
            'refund before processing' => ['Menunggu', 'Diproses', 'refund', 'funds_reversed', 'prioritas_tinggi', 'paid-txn'],
            'partial refund before processing' => ['Menunggu', 'Diproses', 'partial_refund', 'funds_reversed', 'prioritas_tinggi', 'paid-txn'],
            'deny of settled transaction' => ['Menunggu', 'Diproses', 'deny', 'attempt_unsuccessful', 'menunggu', 'paid-txn'],
            'possible reversal before processing' => ['Menunggu', 'Diproses', 'deny', 'possible_payment_reversal', 'prioritas_tinggi', 'paid-txn'],
            'possible reversal before shipping' => ['Diproses', 'Dikirim', 'deny', 'possible_payment_reversal', 'prioritas_tinggi', 'paid-txn'],
            'transaction conflict before processing' => ['Menunggu', 'Diproses', 'settlement', 'transaction_conflict', 'prioritas_tinggi', 'other-txn'],
            'refund before shipping' => ['Diproses', 'Dikirim', 'refund', 'funds_reversed', 'prioritas_tinggi', 'paid-txn'],
            'partial refund before shipping' => ['Diproses', 'Dikirim', 'partial_refund', 'funds_reversed', 'prioritas_tinggi', 'paid-txn'],
            'conflict before shipping' => ['Diproses', 'Dikirim', 'settlement', 'transaction_conflict', 'prioritas_tinggi', 'other-txn'],
            'chargeback' => ['Menunggu', 'Diproses', 'chargeback', 'funds_reversed', 'prioritas_tinggi', 'paid-txn'],
            'unclear attempt identity' => ['Menunggu', 'Diproses', 'deny', 'attempt_unsuccessful', 'menunggu', null],
            'verification unavailable' => ['Diproses', 'Dikirim', null, 'verification_unavailable', 'menunggu', null],
        ];
    }

    #[DataProvider('blockingReconciliations')]
    public function test_open_payment_cases_hold_processing_or_shipping(
        string $current, string $target, ?string $gatewayStatus, string $reason, string $handling, ?string $transactionId,
    ): void {
        $db = $this->database->getConnection();
        $db->table('pesanan')->update(['status_pesanan' => $current]);
        $db->table('pengiriman')->update(['status_pengiriman' => $current]);
        $db->table('pembayaran')->update(['status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'paid-txn', 'midtrans_status' => 'settlement']);
        $case = [
            'id' => 1,
            'pembayaran_id' => 1,
            'status_penanganan' => $handling,
            'status_transaksi' => $gatewayStatus,
            'midtrans_transaction_id' => $transactionId,
            'catatan_manual' => $reason,
        ];
        $db->table('midtrans_rekonsiliasi')->insert($case);

        $reply = (new PesananController)->updateStatus($this->statusRequest($target), 1);

        self::assertStringContainsString('hambatan pemenuhan', $reply->getSession()->get('error'));
        $this->assertState($current, 8);
        self::assertNull($db->table('pengiriman')->value('waktu_dikirim'));
        self::assertSame($case + ['status_penahanan' => 'ditahan'], (array) $db->table('midtrans_rekonsiliasi')->first());
    }

    public function test_deny_on_another_attempt_does_not_block_verified_payment(): void
    {
        $db = $this->database->getConnection();
        $db->table('pembayaran')->update(['status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'paid-txn', 'midtrans_status' => 'settlement']);
        $db->table('midtrans_rekonsiliasi')->insert([
            'pembayaran_id' => 1,
            'status_penanganan' => 'menunggu',
            'status_transaksi' => 'deny',
            'midtrans_transaction_id' => 'other-txn',
            'catatan_manual' => 'attempt_unsuccessful',
        ]);
        $controller = new PesananController;
        self::assertTrue($controller->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->has('success'));
        self::assertTrue($controller->updateStatus($this->statusRequest('Dikirim'), 1)->getSession()->has('success'));
        $this->assertState('Dikirim', 8);
        self::assertSame('menunggu', $db->table('midtrans_rekonsiliasi')->value('status_penanganan'));
    }

    public static function resolvedRiskCases(): array
    {
        return [
            'refund' => ['refund', 'funds_reversed'],
            'partial refund' => ['partial_refund', 'funds_reversed'],
            'reversal' => ['deny', 'possible_payment_reversal'],
            'conflict' => ['settlement', 'transaction_conflict'],
        ];
    }

    #[DataProvider('resolvedRiskCases')]
    public function test_resolved_risk_still_blocks_verified_payment(string $status, string $reason): void
    {
        $db = $this->database->getConnection();
        $db->table('pembayaran')->update(['status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'paid-txn', 'midtrans_status' => 'settlement']);
        $db->table('midtrans_rekonsiliasi')->insert([
            'pembayaran_id' => 1,
            'status_penanganan' => 'selesai',
            'status_transaksi' => $status,
            'catatan_manual' => $reason,
        ]);

        self::assertTrue((new PesananController)->updateStatus($this->statusRequest('Diproses'), 1)->getSession()->has('error'));
        $this->assertState('Menunggu', 8);
        $db->table('pesanan')->where('id', 1)->update(['status_pesanan' => 'Diproses']);
        $db->table('pengiriman')->where('pesanan_id', 1)->update(['status_pengiriman' => 'Diproses']);
        self::assertTrue((new PesananController)->updateStatus($this->statusRequest('Dikirim'), 1)->getSession()->has('error'));
        $this->assertState('Diproses', 8);
        self::assertNull($db->table('pengiriman')->value('waktu_dikirim'));
        self::assertSame('selesai', $db->table('midtrans_rekonsiliasi')->value('status_penanganan'));
    }

    public function test_update_status_cannot_reject_an_order(): void
    {
        try {
            (new PesananController)->updateStatus($this->statusRequest('Ditolak'), 1);
            self::fail('updateStatus accepted a rejection.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('status_pesanan', $exception->errors());
        }

        $this->assertState('Menunggu', 8);
    }

    private function statusRequest(string $status): Request
    {
        return Request::create('/admin/pesanan/1/status', 'PATCH', [
            'status_pesanan' => $status,
            'status_pembayaran' => 'Berhasil',
        ]);
    }

    private function assertState(string $status, int $stock): void
    {
        $db = $this->database->getConnection();
        self::assertSame($status, $db->table('pesanan')->value('status_pesanan'));
        self::assertSame($status, $db->table('pengiriman')->value('status_pengiriman'));
        self::assertSame($stock, $db->table('produk')->value('stok'));
    }
}
