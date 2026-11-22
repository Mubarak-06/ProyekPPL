<?php

namespace Tests\Feature;

use App\Services\MidtransWebhookService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MidtransWebhookEndpointTest extends TestCase
{
    private const URI = '/api/webhooks/midtrans';

    private const PAYLOAD = [
        'order_id' => 'KA-TEST-1',
        'transaction_id' => 'test-transaction',
        'gross_amount' => '15000.00',
        'status_code' => '200',
        'transaction_status' => 'settlement',
        'payment_type' => 'qris',
        'signature_key' => 'test-signature',
    ];

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        if (is_file($app->getCachedConfigPath()) || is_file($app->getCachedRoutesPath())) {
            throw new RuntimeException('Run the webhook feature tests with uncached Laravel configuration and routes.');
        }
        $app->instance(LoadEnvironmentVariables::class, new class
        {
            public function bootstrap(): void {}
        });
        $app->afterBootstrapping(LoadConfiguration::class, static function ($app) {
            $app['config']->set([
                'app.env' => 'testing',
                'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
                'app.debug' => true,
                'database.default' => 'sqlite',
                'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']],
                'session.driver' => 'array',
                'cache.default' => 'array',
                'services.midtrans' => [],
            ]);
            $app->instance('env', 'testing');
        });
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public static function outcomes(): array
    {
        return [
            'applied' => [['status' => 'applied', 'reason' => 'payment_verified'], 200, 'OK'],
            'reconciled' => [['status' => 'needs_reconciliation', 'reason' => 'funds_reversed'], 200, 'OK'],
            'gateway timeout can be retried' => [['status' => 'unavailable', 'reason' => 'gateway_unavailable'], 503, 'Temporarily unavailable'],
            'unavailable before acceptance' => [['status' => 'unavailable', 'reason' => 'invalid_configuration'], 503, 'Temporarily unavailable'],
            'invalid signature' => [['status' => 'rejected', 'reason' => 'invalid_signature'], 403, 'Forbidden'],
            'invalid payload fields' => [['status' => 'rejected', 'reason' => 'invalid_payload'], 400, 'Invalid payload'],
            'unknown payment' => [['status' => 'rejected', 'reason' => 'unknown_payment'], 404, 'Not found'],
            'unknown result' => [['status' => 'unrecognized'], 500, 'Internal error'],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_service_results_map_to_safe_guest_http_responses(array $result, int $status, string $message): void
    {
        $service = $this->createMock(MidtransWebhookService::class);
        $service->expects($this->once())->method('process')->with(self::PAYLOAD)->willReturn($result);
        $this->app->instance(MidtransWebhookService::class, $service);

        $this->postJson(self::URI.'?gross_amount=1&status_pembayaran=Berhasil', self::PAYLOAD)
            ->assertStatus($status)->assertExactJson(['message' => $message]);
    }

    public function test_actual_routes_keep_checkout_csrf_and_webhook_stateless(): void
    {
        // Disable Laravel's unit-test CSRF bypass while exercising the actual HTTP kernel.
        $this->app->instance('env', 'local');
        $this->app->make(HttpKernel::class);
        $router = $this->app['router'];
        $route = $router->getRoutes()->getByName('webhooks.midtrans');
        $middleware = $router->gatherRouteMiddleware($route);
        self::assertSame(['POST'], $route->methods());
        self::assertNotContains(ValidateCsrfToken::class, $middleware);
        self::assertNotContains(StartSession::class, $middleware);
        foreach (['pelanggan.checkout.store', 'pelanggan.pembayaran.snap', 'admin.midtrans-rekonsiliasi.review'] as $name) {
            self::assertContains(ValidateCsrfToken::class, $router->gatherRouteMiddleware($router->getRoutes()->getByName($name)));
        }

        $service = $this->createMock(MidtransWebhookService::class);
        $service->expects($this->once())->method('process')->willReturn(['status' => 'acknowledged']);
        $this->app->instance(MidtransWebhookService::class, $service);

        $this->postJson('/checkout', [])->assertStatus(419);
        $this->postJson('/pesanan/1/pembayaran/snap', [])->assertStatus(419);
        $this->postJson('/admin/rekonsiliasi-midtrans/1/periksa', [])->assertStatus(419);
        $this->postJson(self::URI, self::PAYLOAD)->assertOk();
    }

    public function test_transport_rejects_non_json_bad_shapes_and_oversize_without_calling_service(): void
    {
        $service = $this->createMock(MidtransWebhookService::class);
        $service->expects($this->never())->method('process');
        $this->app->instance(MidtransWebhookService::class, $service);

        $this->post(self::URI, self::PAYLOAD)->assertStatus(415);
        foreach (['{', '[]', '{}', 'null'] as $body) {
            $this->call('POST', self::URI, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        }
        $body = json_encode(self::PAYLOAD + ['extra' => str_repeat('x', 65536)], JSON_THROW_ON_ERROR);
        $this->call('POST', self::URI, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(413);
    }

    public function test_repeated_post_and_get_do_not_modify_stock_or_historical_payment_in_controller(): void
    {
        DB::statement('CREATE TABLE produk (id INTEGER PRIMARY KEY, stok INTEGER)');
        DB::statement('CREATE TABLE pembayaran (id INTEGER PRIMARY KEY, gateway TEXT, status_pembayaran TEXT)');
        DB::table('produk')->insert(['id' => 1, 'stok' => 8]);
        DB::table('pembayaran')->insert(['id' => 1, 'gateway' => 'manual', 'status_pembayaran' => 'Menunggu']);
        $payment = DB::table('pembayaran')->first();
        $service = $this->createMock(MidtransWebhookService::class);
        $service->expects($this->exactly(2))->method('process')->with(self::PAYLOAD)->willReturnOnConsecutiveCalls(
            ['status' => 'applied', 'reason' => 'payment_verified'],
            ['status' => 'acknowledged', 'reason' => 'already_paid'],
        );
        $this->app->instance(MidtransWebhookService::class, $service);

        $this->getJson(self::URI)->assertStatus(405);
        $this->postJson(self::URI, self::PAYLOAD)->assertOk();
        $this->postJson(self::URI, self::PAYLOAD)->assertOk();
        self::assertEquals($payment, DB::table('pembayaran')->first());
        self::assertSame(8, DB::table('produk')->value('stok'));
    }

    public function test_database_failure_and_internal_exception_never_acknowledge_delivery(): void
    {
        $service = $this->createMock(MidtransWebhookService::class);
        $service->expects($this->exactly(2))->method('process')->willReturnCallback(
            static function () {
                static $first = true;
                if ($first) {
                    $first = false;
                    throw new QueryException('test', 'INSERT fixture', ['dummy-sensitive'], new PDOException('dummy-sensitive'));
                }

                throw new RuntimeException('dummy-sensitive');
            }
        );
        $this->app->instance(MidtransWebhookService::class, $service);
        $this->postJson(self::URI, self::PAYLOAD)->assertStatus(503)->assertExactJson(['message' => 'Temporarily unavailable']);
        $this->postJson(self::URI, self::PAYLOAD)->assertStatus(500)->assertExactJson(['message' => 'Internal error']);
    }
}
