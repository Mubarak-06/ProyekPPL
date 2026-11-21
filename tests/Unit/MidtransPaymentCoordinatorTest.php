<?php

namespace Tests\Unit;

use App\Models\MidtransRekonsiliasi;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\MidtransPaymentCoordinator;
use App\Services\MidtransSnapService;
use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MidtransPaymentCoordinatorTest extends TestCase
{
    private Manager $database;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'services' => ['midtrans' => [
                'is_production' => false, 'sandbox_channels' => ['other_qris'], 'sandbox_verified_channels' => ['other_qris'],
            ]],
        ]));
        $this->database = new Manager($container);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->bootEloquent();
        $container->instance('db', $this->database->getDatabaseManager());
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication($container);
        Model::encryptUsing(new Encrypter(random_bytes(32), 'AES-256-CBC'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-20 10:00:00'));

        $schema = $this->database->getConnection()->getSchemaBuilder();
        $schema->create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status_pesanan');
            $table->decimal('total_pembayaran', 15, 2);
            $table->timestamps();
        });
        $schema->create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan');
            $table->string('gateway');
            $table->string('metode_pembayaran')->default('QRIS');
            $table->decimal('jumlah_pembayaran', 15, 2)->default(15000);
            $table->string('status_pembayaran')->default('Menunggu');
            $table->timestamp('waktu_pembayaran')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable()->unique();
            $table->string('midtrans_status')->nullable();
            $table->timestamp('snap_requested_at')->nullable();
            $table->timestamp('snap_token_expires_at')->nullable();
            $table->text('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->timestamps();
        });
        $schema->create('midtrans_rekonsiliasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran');
            $table->string('midtrans_order_id');
            $table->string('status_penanganan');
            $table->text('catatan_manual');
            $table->dateTime('periksa_ulang_at');
            $table->timestamps();
        });
        $schema->create('users', fn (Blueprint $table) => $table->id());
        (require __DIR__.'/../../database/migrations/2026_11_20_000003_add_midtrans_fulfillment_audit.php')->up();
        $schema->create('produk', function (Blueprint $table) {
            $table->id();
            $table->integer('stok');
        });

        DB::table('produk')->insert(['id' => 1, 'stok' => 12]);
        DB::table('pesanan')->insert([
            ['id' => 1, 'user_id' => 7, 'status_pesanan' => 'Menunggu', 'total_pembayaran' => 15000],
            ['id' => 2, 'user_id' => 7, 'status_pesanan' => 'Menunggu', 'total_pembayaran' => 15000],
        ]);
        DB::table('pembayaran')->insert([
            ['id' => 1, 'pesanan_id' => 1, 'gateway' => 'midtrans', 'midtrans_order_id' => 'KA-TEST-1'],
            ['id' => 2, 'pesanan_id' => 2, 'gateway' => 'manual', 'midtrans_order_id' => null],
        ]);
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        Model::unsetConnectionResolver();
        Model::encryptUsing(null);
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public static function rejectedOrders(): array
    {
        return [
            'foreign owner' => [8, 1, 'Menunggu', 'Menunggu'],
            'manual' => [7, 2, 'Menunggu', 'Menunggu'],
            'rejected order' => [7, 1, 'Ditolak', 'Menunggu'],
            'paid' => [7, 1, 'Menunggu', 'Berhasil'],
        ];
    }

    #[DataProvider('rejectedOrders')]
    public function test_ineligible_payments_are_rejected(int $user, int $order, string $orderStatus, string $paymentStatus): void
    {
        DB::table('pesanan')->where('id', $order)->update(['status_pesanan' => $orderStatus]);
        DB::table('pembayaran')->where('pesanan_id', $order)->update(['status_pembayaran' => $paymentStatus]);
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $snap->expects($this->never())->method('requestSnapToken');

        self::assertSame(['status' => 'rejected'], (new MidtransPaymentCoordinator($snap))->requestToken($user, $order));
        self::assertNull(Pembayaran::findOrFail($order)->snap_requested_at);
        self::assertSame(0, MidtransRekonsiliasi::count());
    }

    public function test_first_claim_wins_and_an_overlapping_request_cannot_issue_another_token(): void
    {
        $historical = Pembayaran::findOrFail(2)->getAttributes();
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $coordinator = new MidtransPaymentCoordinator($snap);
        $snap->expects($this->once())->method('requestSnapToken')->willReturnCallback(
            function (Pesanan $order, Pembayaran $snapshot, ?array $channels, CarbonImmutable $claimedAt) use ($coordinator) {
                self::assertSame(0, DB::transactionLevel());
                self::assertNull($snapshot->getRawOriginal('snap_requested_at'));
                self::assertSame('KA-TEST-1', $snapshot->midtrans_order_id);
                self::assertTrue(Pembayaran::findOrFail(1)->snap_requested_at->equalTo($claimedAt));
                self::assertSame(['status' => 'needs_reconciliation'], $coordinator->requestToken(7, 1));

                return $this->readyResult();
            }
        );

        $result = ['status' => 'ready', 'token' => 'dummy-token', 'redirect_url' => 'https://example.test/snap'];
        self::assertSame($result, $coordinator->requestToken(7, 1));
        self::assertSame($result, $coordinator->requestToken(7, 1));

        $payment = Pembayaran::findOrFail(1);
        self::assertSame('dummy-token', $payment->snap_token);
        self::assertSame('https://example.test/snap', $payment->snap_redirect_url);
        self::assertNotSame($payment->snap_token, $payment->getRawOriginal('snap_token'));
        self::assertNotSame($payment->snap_redirect_url, $payment->getRawOriginal('snap_redirect_url'));
        self::assertSame('Menunggu', $payment->status_pembayaran);
        self::assertSame('Menunggu', Pesanan::findOrFail(1)->status_pesanan);
        self::assertSame(0, MidtransRekonsiliasi::count());
        self::assertSame(12, DB::table('produk')->where('id', 1)->value('stok'));
        self::assertSame($historical, Pembayaran::findOrFail(2)->getAttributes());
    }

    public static function uncertainResults(): array
    {
        return ['unknown' => [false], 'timeout' => [true]];
    }

    #[DataProvider('uncertainResults')]
    public function test_uncertain_result_retains_claim_and_gateway_id_and_opens_one_case(bool $timeout): void
    {
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $snap->expects($this->once())->method('requestSnapToken')->willReturnCallback(function () use ($timeout) {
            if ($timeout) {
                throw new RuntimeException('SDK dummy secret', 28);
            }

            return ['status' => 'unknown'];
        });
        $coordinator = new MidtransPaymentCoordinator($snap);

        self::assertSame(['status' => 'needs_reconciliation'], $coordinator->requestToken(7, 1));
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(20));
        self::assertSame(['status' => 'needs_reconciliation'], $coordinator->requestToken(7, 1));

        $payment = Pembayaran::findOrFail(1);
        self::assertNotNull($payment->snap_requested_at);
        self::assertSame('KA-TEST-1', $payment->midtrans_order_id);
        self::assertSame('Menunggu', $payment->status_pembayaran);
        self::assertSame(1, MidtransRekonsiliasi::count());
        self::assertStringNotContainsString('SDK dummy secret', MidtransRekonsiliasi::firstOrFail()->catatan_manual);
    }

    public function test_expired_token_is_not_reissued(): void
    {
        Pembayaran::findOrFail(1)->update([
            'snap_requested_at' => CarbonImmutable::now()->subMinutes(16),
            'snap_token_expires_at' => CarbonImmutable::now()->subMinute(),
            'snap_token' => 'expired',
            'snap_redirect_url' => 'https://example.test/expired',
        ]);
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $snap->expects($this->never())->method('requestSnapToken');

        self::assertSame(['status' => 'needs_reconciliation'], (new MidtransPaymentCoordinator($snap))->requestToken(7, 1));
        self::assertSame(1, MidtransRekonsiliasi::count());
    }

    public function test_committed_claim_without_a_saved_result_is_reconciled(): void
    {
        Pembayaran::findOrFail(1)->update(['snap_requested_at' => CarbonImmutable::now()->subMinute()]);
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $snap->expects($this->never())->method('requestSnapToken');
        $coordinator = new MidtransPaymentCoordinator($snap);

        self::assertSame(['status' => 'needs_reconciliation'], $coordinator->requestToken(7, 1));
        self::assertSame(['status' => 'needs_reconciliation'], $coordinator->requestToken(7, 1));
        self::assertSame(1, MidtransRekonsiliasi::count());
        self::assertNotNull(Pembayaran::findOrFail(1)->snap_requested_at);
    }

    public function test_state_change_during_gateway_call_withholds_token(): void
    {
        $snap = $this->createPartialMock(MidtransSnapService::class, ['requestSnapToken']);
        $snap->expects($this->once())->method('requestSnapToken')->willReturnCallback(function () {
            DB::table('pesanan')->where('id', 1)->update(['status_pesanan' => 'Ditolak']);

            return $this->readyResult();
        });

        self::assertSame(['status' => 'needs_reconciliation'], (new MidtransPaymentCoordinator($snap))->requestToken(7, 1));
        self::assertNull(Pembayaran::findOrFail(1)->snap_token);
        self::assertSame(1, MidtransRekonsiliasi::count());
    }

    private function readyResult(): array
    {
        return [
            'status' => 'ready',
            'token' => 'dummy-token',
            'redirect_url' => 'https://example.test/snap',
            'snap_token_expires_at' => CarbonImmutable::now()->addMinutes(15),
        ];
    }
}
