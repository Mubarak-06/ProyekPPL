<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        if (is_file($app->getCachedConfigPath()) || is_file($app->getCachedRoutesPath())) {
            throw new RuntimeException('Pengujian memerlukan konfigurasi dan route tanpa cache.');
        }
        $app->instance(LoadEnvironmentVariables::class, new class
        {
            public function bootstrap(): void {}
        });
        $app->afterBootstrapping(LoadConfiguration::class, static function ($app) {
            $app['config']->set([
                'app.env' => 'testing',
                'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                'database.connections.sqlite.url' => null,
                'services.midtrans' => [
                    'server_key' => 'test-only-server', 'client_key' => 'SB-Mid-client-test-only-client',
                    'is_production' => false, 'sandbox_channels' => ['bca_va'], 'sandbox_verified_channels' => ['bca_va'],
                ],
            ]);
            $app->instance('env', 'testing');
        });
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
