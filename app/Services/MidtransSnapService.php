<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use RuntimeException;
use Throwable;

class MidtransSnapService
{
    protected const SUPPORTED_CHANNELS = [
        'Transfer Bank' => ['bca_va', 'bni_va', 'bri_va', 'echannel'],
        'QRIS' => ['other_qris'],
        'E-Wallet' => ['dana'],
    ];

    public function checkoutMethods(): array
    {
        $client = config('services.midtrans.client_key');
        $server = config('services.midtrans.server_key');
        if (! is_string($client) || trim($client) === ''
            || ! is_string($server) || trim($server) === '') {
            return [];
        }

        return $this->sandboxMethods();
    }

    public function sandboxClientKey(): ?string
    {
        $key = config('services.midtrans.client_key');

        return config('services.midtrans.is_production') === false && is_string($key)
            && preg_match('/\ASB-Mid-client-[A-Za-z0-9_-]+\z/', $key) === 1 ? $key : null;
    }

    public function isSandboxRedirectUrl(string $url): bool
    {
        return preg_match('~\Ahttps://app\.sandbox\.midtrans\.com/snap/v[0-9]+/redirection/[A-Za-z0-9_-]+\z~', $url) === 1;
    }

    protected function sandboxMethods(): array
    {
        $candidates = config('services.midtrans.sandbox_channels', []);
        $verified = config('services.midtrans.sandbox_verified_channels', []);
        if (config('services.midtrans.is_production') !== false
            || ! is_array($candidates) || ! array_is_list($candidates)
            || ! is_array($verified) || ! array_is_list($verified)) {
            return [];
        }

        return array_filter(array_map(
            fn (array $channels) => array_values(array_filter($channels, fn (string $channel) => in_array($channel, $candidates, true) && in_array($channel, $verified, true))),
            self::SUPPORTED_CHANNELS,
        ));
    }

    /**
     * @param  list<string>|null  $sandboxChannels  Pembatas tambahan untuk pembayaran ini.
     */
    public function buildTransactionParameters(Pesanan $pesanan, Pembayaran $pembayaran, ?array $sandboxChannels = null): array
    {
        if (! $pesanan->exists || ! $pembayaran->exists
            || $pesanan->getRawOriginal('id') === null
            || $pembayaran->getRawOriginal('id') === null
            || (string) $pembayaran->getRawOriginal('pesanan_id') !== (string) $pesanan->getRawOriginal('id')
            || $pembayaran->getRawOriginal('gateway') !== 'midtrans') {
            throw new InvalidArgumentException('Pesanan dan pembayaran Midtrans terkait harus sudah tersimpan.');
        }

        $orderId = $pembayaran->getRawOriginal('midtrans_order_id');
        $this->validateIdentifier($orderId);

        $amount = $this->wholeRupiah($pembayaran->getRawOriginal('jumlah_pembayaran'));
        if ($amount !== $this->wholeRupiah($pesanan->getRawOriginal('total_pembayaran'))) {
            throw new InvalidArgumentException('Nominal pembayaran berbeda dari total pesanan.');
        }

        $methodChannels = $this->sandboxMethods()[$pembayaran->getRawOriginal('metode_pembayaran')] ?? [];
        $enabledChannels = array_values(array_filter(
            $methodChannels,
            static fn (string $channel): bool => $sandboxChannels === null || in_array($channel, $sandboxChannels, true),
        ));

        if ($enabledChannels === []) {
            throw new InvalidArgumentException('Kanal Sandbox untuk metode pembayaran ini tidak tersedia.');
        }

        $expiryMinutes = config('services.midtrans.snap_page_expiry_minutes', 15);
        if (! is_int($expiryMinutes) || $expiryMinutes < 5 || $expiryMinutes > 60) {
            throw new RuntimeException('Durasi halaman Snap harus berupa integer antara 5 dan 60 menit.');
        }

        return [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'enabled_payments' => $enabledChannels,
            'page_expiry' => ['duration' => $expiryMinutes, 'unit' => 'minutes'],
        ];
    }

    /**
     * Gunakan snapshot sebelum klaim. Pemanggil harus mengunci pembayaran dan
     * menyimpan snap_requested_at sebelum request, lalu melepas row lock.
     *
     * @param  list<string>|null  $sandboxChannels
     * @return array{status: 'ready', token: string, redirect_url: string, snap_token_expires_at: DateTimeImmutable}|array{status: 'unknown'}
     */
    public function requestSnapToken(
        Pesanan $pesanan,
        Pembayaran $pembayaran,
        ?array $sandboxChannels = null,
        ?DateTimeImmutable $requestedAt = null,
    ): array {
        if ($pembayaran->getRawOriginal('snap_requested_at') !== null
            || $pembayaran->getRawOriginal('snap_token') !== null
            || $pembayaran->getRawOriginal('midtrans_transaction_id') !== null) {
            return ['status' => 'unknown'];
        }

        $parameters = $this->buildTransactionParameters($pesanan, $pembayaran, $sandboxChannels);
        $parameters['callbacks']['finish'] = rtrim(config('app.url'), '/')
            .route('pelanggan.pembayaran', ['id' => $pesanan->id, 'snap_return' => 1], false);
        if (in_array('dana', $parameters['enabled_payments'], true)) {
            $parameters['dana']['callback_url'] = $parameters['callbacks']['finish'];
        }
        if ($requestedAt === null || $requestedAt > CarbonImmutable::now()) {
            throw new InvalidArgumentException('Waktu klaim permintaan Snap harus sudah dicatat.');
        }

        // Batas penggunaan ulang lokal dihitung dari waktu sebelum panggilan API.
        $expiresAt = CarbonImmutable::instance($requestedAt)->addMinutes($parameters['page_expiry']['duration']);
        if ($expiresAt <= CarbonImmutable::now()) {
            return ['status' => 'unknown'];
        }

        $response = $this->callSandbox(static fn () => Snap::createTransaction($parameters), unknownOnFailure: true);

        if (! is_string($response->token ?? null) || $response->token === ''
            || ! is_string($response->redirect_url ?? null) || $response->redirect_url === ''
            || $expiresAt <= CarbonImmutable::now()) {
            return ['status' => 'unknown'];
        }

        return [
            'status' => 'ready',
            'token' => $response->token,
            'redirect_url' => $response->redirect_url,
            'snap_token_expires_at' => $expiresAt,
        ];
    }

    /**
     * Gunakan transaction_id Midtrans untuk DANA. Untuk kanal lain, order_id juga dapat digunakan.
     */
    public function getTransactionStatus(string $identifier): object
    {
        $this->validateIdentifier($identifier);
        $request = static fn () => Transaction::status($identifier);
        try {
            $response = $this->callSandbox($request, snapSource: false);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            $response = null;
        }

        // DANA's standard response may use the transaction ID as the order ID.
        // The Snap source resolves the merchant order ID needed for verification.
        if ($response === null || (($response->payment_type ?? null) === 'dana'
            && is_string($response->transaction_id ?? null)
            && ($response->order_id ?? null) === $response->transaction_id)) {
            $response = $this->callSandbox($request);
        }

        if (! is_string($response->transaction_status ?? null) || $response->transaction_status === '') {
            throw new RuntimeException('Status pembayaran Midtrans belum dapat dipastikan.');
        }

        return $response;
    }

    private function validateIdentifier(mixed $identifier): void
    {
        if (! is_string($identifier) || preg_match('/\A[A-Za-z0-9_~.\-]{1,50}\z/', $identifier) !== 1
            || in_array($identifier, ['.', '..'], true)) {
            throw new InvalidArgumentException('ID Midtrans tidak valid atau belum tersimpan.');
        }
    }

    private function wholeRupiah(mixed $amount): int
    {
        if ((! is_string($amount) && ! is_int($amount))
            || preg_match('/\A([1-9][0-9]*)(?:\.0{1,2})?\z/', (string) $amount, $parts) !== 1) {
            throw new InvalidArgumentException('Nominal Midtrans harus berupa rupiah bulat positif.');
        }

        $rupiah = filter_var($parts[1], FILTER_VALIDATE_INT);
        if ($rupiah === false) {
            throw new InvalidArgumentException('Nominal Midtrans melebihi batas integer PHP.');
        }

        return $rupiah;
    }

    private function callSandbox(Closure $request, bool $unknownOnFailure = false, bool $snapSource = true): mixed
    {
        $config = config('services.midtrans');
        if (! is_array($config) || ($config['is_production'] ?? null) !== false
            || ! is_string($config['server_key'] ?? null) || trim($config['server_key']) === ''
            || ! is_string($config['client_key'] ?? null) || trim($config['client_key']) === '') {
            throw new RuntimeException('Konfigurasi Midtrans Sandbox tidak lengkap atau tidak valid.');
        }

        $original = [Config::$serverKey, Config::$clientKey, Config::$isProduction, Config::$curlOptions];

        try {
            Config::$serverKey = $config['server_key'];
            Config::$clientKey = $config['client_key'];
            Config::$isProduction = false;
            Config::$curlOptions = [
                CURLOPT_HTTPHEADER => $snapSource ? ['transaction-source: SNAP_API'] : ['Accept: application/json'],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
            ];
            $caBundle = $config['sandbox_ca_bundle'] ?? null;
            if (is_string($caBundle) && $caBundle !== '') {
                Config::$curlOptions[CURLOPT_CAINFO] = $caBundle;
            }

            return $request();
        } catch (Throwable $exception) {
            if ($unknownOnFailure) {
                return null;
            }

            // SDK dapat menyertakan body respons dan URL dalam pesan exception.
            $code = (int) $exception->getCode();
            $message = match ($code) {
                28 => 'Permintaan Midtrans melewati batas waktu. Hasil pembayaran belum diketahui.',
                404 => 'Transaksi belum ditemukan di Midtrans. Status pembayaran belum dapat dipastikan.',
                default => 'Permintaan Midtrans gagal. Status pembayaran belum dapat dipastikan.',
            };

            throw new RuntimeException($message, in_array($code, [28, 404], true) ? $code : 0);
        } finally {
            [Config::$serverKey, Config::$clientKey, Config::$isProduction, Config::$curlOptions] = $original;
        }
    }
}
