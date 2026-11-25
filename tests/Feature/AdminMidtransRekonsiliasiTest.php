<?php

namespace Tests\Feature;

use App\Models\MidtransRekonsiliasiRiwayat;
use App\Models\User;
use App\Services\MidtransSnapService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AdminMidtransRekonsiliasiTest extends TestCase
{
    private const URI = '/admin/rekonsiliasi-midtrans';

    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        if (is_file($app->getCachedConfigPath()) || is_file($app->getCachedRoutesPath())) {
            throw new RuntimeException('Pengujian memerlukan konfigurasi dan route tanpa cache.');
        }
        $app->instance(LoadEnvironmentVariables::class, new class {
            public function bootstrap(): void
            {
            }
        });
        $app->afterBootstrapping(LoadConfiguration::class, static function ($app) {
            $app['config']->set([
                'app.env' => 'testing',
                'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
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

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'CREATE TABLE users (id INTEGER PRIMARY KEY)',
            'CREATE TABLE pesanan (id INTEGER PRIMARY KEY, kode_pesanan TEXT, status_pesanan TEXT, catatan TEXT, total_pembayaran DECIMAL(15,2))',
            'CREATE TABLE pembayaran (id INTEGER PRIMARY KEY, pesanan_id INTEGER, status_pembayaran TEXT, jumlah_pembayaran DECIMAL(15,2), metode_pembayaran TEXT, bukti_pembayaran TEXT)',
            'CREATE TABLE produk (id INTEGER PRIMARY KEY, stok INTEGER)',
            'INSERT INTO produk VALUES (1, 8)',
        ] as $sql) {
            DB::statement($sql);
        }
        foreach ([
            '2026_11_20_000001_add_midtrans_metadata_to_pembayaran_table.php',
            '2026_11_20_000002_create_midtrans_rekonsiliasi_table.php',
            '2026_11_20_000003_add_midtrans_fulfillment_audit.php',
        ] as $file) {
            (require database_path('migrations/' . $file))->up();
        }
    }

    public function test_access_and_review_are_admin_only(): void
    {
        $this->payment(1, 'KA-DETAIL');
        $this->caseFor(1, 'menunggu', 'amount_conflict');

        foreach ([self::URI, self::URI . '/1'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $this->actingAs((new User)->forceFill(['id' => 2, 'is_admin' => false]));
        foreach ([self::URI, self::URI . '/1'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
        $this->post(self::URI . '/1/periksa')->assertForbidden();
        $this->asAdmin();
        $this->get(self::URI)->assertOk()->assertSee('KA-DETAIL')->assertSee('.rekon-card', false);
        $this->get(self::URI . '/1')
            ->assertOk()
            ->assertSee('Detail Pemeriksaan Pembayaran')
            ->assertSeeInOrder(['ID Kasus', '#1'])
            ->assertSee('.rekon-card', false);
        $this->get(self::URI . '/999')->assertNotFound();

        $this->get(self::URI . '/1/periksa')->assertMethodNotAllowed();
    }

    public function test_review_uses_stored_id_and_records_timeout_once_per_window(): void
    {
        $this->asAdmin();
        DB::table('users')->insert(['id' => 1]);
        $this->payment(1, 'KA-REVIEW', ['midtrans_order_id' => 'gateway-1']);
        $this->caseFor(1, 'prioritas_tinggi', 'funds_reversed', ['midtrans_transaction_id' => 'stored-transaction']);
        $this->mock(MidtransSnapService::class)->shouldReceive('getTransactionStatus')->once()
            ->with('stored-transaction')->andThrow(new RuntimeException('private gateway response', 28));
        $this->post(self::URI . '/1/periksa', ['transaction_id' => 'input-ignored'])->assertRedirect(self::URI . '/1');
        $this->post(self::URI . '/1/periksa')->assertRedirect(self::URI . '/1');
        $this->assertDatabaseCount('midtrans_rekonsiliasi_riwayat', 1);
        $this->assertDatabaseHas('midtrans_rekonsiliasi_riwayat', ['admin_id' => 1, 'hasil_pemeriksaan' => 'timeout']);
        $this->assertDatabaseHas('midtrans_rekonsiliasi', ['status_penanganan' => 'prioritas_tinggi', 'status_penahanan' => 'ditahan']);
        $this->get(self::URI . '/1')->assertSee('timeout')->assertDontSee('private gateway response');

        $entry = MidtransRekonsiliasiRiwayat::firstOrFail();
        $this->expectException(\LogicException::class);
        $entry->forceFill(['hasil_pemeriksaan' => 'overwritten'])->save();
    }

    public function test_review_closes_only_a_verified_failure_of_a_different_transaction(): void
    {
        $this->asAdmin();
        DB::table('users')->insert(['id' => 1]);
        $this->payment(1, 'KA-REVIEW', [
            'midtrans_order_id' => 'gateway-1',
            'midtrans_transaction_id' => 'paid-txn',
            'midtrans_status' => 'settlement',
            'status_pembayaran' => 'Berhasil',
        ]);
        $this->caseFor(1, 'menunggu', 'attempt_unsuccessful', [
            'midtrans_transaction_id' => 'failed-txn',
            'status_transaksi' => 'deny',
        ]);
        $this->mock(MidtransSnapService::class)->shouldReceive('getTransactionStatus')->once()
            ->with('failed-txn')->andReturn((object) [
                'transaction_id' => 'failed-txn',
                'order_id' => 'gateway-1',
                'payment_type' => 'qris',
                'transaction_status' => 'deny',
                'status_code' => '202',
                'gross_amount' => '15000.00',
            ]);
        $this->post(self::URI . '/1/periksa')->assertRedirect(self::URI . '/1');
        $this->assertDatabaseHas('midtrans_rekonsiliasi', ['id' => 1, 'status_penanganan' => 'selesai', 'status_penahanan' => 'ditahan']);
        $this->assertDatabaseHas('midtrans_rekonsiliasi_riwayat', ['jenis_tindakan' => 'tutup_kasus', 'keputusan_operasional' => 'tanpa_perubahan']);
        $this->get(self::URI . '/1')->assertSee('Tidak ada hambatan pembayaran Midtrans');
    }

    public function test_filter_search_and_pagination(): void
    {
        $this->asAdmin();
        $this->payment(1, 'KA-SEL-001');
        $this->payment(2, 'KA-HI-002');
        $this->caseFor(1, 'selesai', 'amount_conflict');
        $this->caseFor(2, 'prioritas_tinggi', 'transaction_conflict');
        foreach (range(3, 17) as $id) {
            $this->payment($id, 'KA-REG-' . $id);
            $this->caseFor($id, 'menunggu', 'amount_conflict');
        }
        $this->get(self::URI . '?status=terbuka')
            ->assertSeeInOrder(['KA-HI-002', 'KA-REG-17'])->assertDontSee('KA-SEL-001')
            ->assertSee('Halaman 1 dari 2')->assertSee('status=terbuka');
        $this->get(self::URI . '?status=terbuka&page=2')->assertSee('KA-REG-3')->assertDontSee('KA-HI-002');
        $this->get(self::URI . '?status=selesai')->assertSee('KA-SEL-001')->assertDontSee('KA-HI-002');
        $this->get(self::URI . '?prioritas=tinggi')->assertSee('KA-HI-002')->assertDontSee('KA-REG-17');
        $this->get(self::URI . '?prioritas=reguler')->assertSee('KA-REG-17')->assertDontSee('KA-HI-002');
        $this->get(self::URI . '?jenis=transaction_conflict')->assertSee('KA-HI-002')->assertDontSee('KA-REG-17');
        $this->get(self::URI . '?kode=SEL-001')->assertSee('KA-SEL-001')->assertDontSee('KA-HI-002');
        $this->get(self::URI . '?kode=%25')->assertSee('Tidak ada kendala pembayaran yang cocok.');
    }

    public function test_list_and_detail_use_safe_metadata_and_preserve_database_state(): void
    {
        $this->asAdmin();
        $token = Crypt::encryptString('private-snap-token');
        $url = Crypt::encryptString('https://snap.example.test/private-url');
        $this->payment(1, '<b>KA-DET</b>', [
            'snap_token' => $token,
            'snap_redirect_url' => $url,
            'bukti_pembayaran' => 'private-proof',
        ]);
        $this->payment(2, 'KA-MANUAL', ['gateway' => 'manual', 'status_pembayaran' => 'Berhasil']);
        $this->caseFor(1, 'selesai', 'amount_conflict', [
            'status_transaksi' => 'settlement',
            'jenis_pembayaran' => 'qris',
            'terakhir_diperiksa_at' => '2026-11-20 11:00:00',
            'periksa_ulang_at' => '2026-11-20 12:00:00',
            'referensi_konfirmasi_pembatalan' => 'private-confirmation-reference',
        ]);
        $this->caseFor(1, 'menunggu', 'private-raw-payload-signature');
        $tables = ['pembayaran', 'produk', 'pesanan', 'midtrans_rekonsiliasi'];
        $before = array_map(fn($table) => DB::table($table)->orderBy('id')->get()->all(), $tables);

        foreach ([self::URI, self::URI . '/1', self::URI . '/2'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('&lt;b&gt;KA-DET&lt;/b&gt;', false)
                ->assertDontSee('<b>KA-DET</b>', false)
                ->assertDontSee('private-snap-token')->assertDontSee($token)->assertDontSee($url)
                ->assertDontSee('private-url')->assertDontSee('private-proof')
                ->assertDontSee('private-customer-notes')->assertDontSee('private-confirmation-reference')
                ->assertDontSee('private-raw-payload-signature')->assertDontSee('KA-MANUAL');
        }
        $this->get(self::URI . '/1')->assertSee('Nominal transaksi Midtrans berbeda dari nominal yang diharapkan.')
            ->assertSeeInOrder(['Status Transaksi Midtrans', 'settlement', 'Status Pembayaran Lokal', 'Menunggu'])
            ->assertSee('QRIS')->assertSee('Rp 15.000,00')->assertSee('20/11/2026 11:00:00')->assertSee('20/11/2026 12:00:00')
            ->assertSee('Tertahan · pembayaran belum terverifikasi berhasil');
        $this->get(self::URI . '/2')->assertSee('Belum tersedia');
        $this->assertEquals($before, array_map(fn($table) => DB::table($table)->orderBy('id')->get()->all(), $tables));
    }

    public function test_other_deny_attempt_does_not_mask_a_successful_payment_or_other_blockers(): void
    {
        $this->asAdmin();
        $this->payment(1, 'KA-SUKSES', ['status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'successful-id', 'midtrans_status' => 'settlement']);
        $this->caseFor(1, 'menunggu', 'attempt_unsuccessful', [
            'status_transaksi' => 'deny',
            'midtrans_transaction_id' => 'other-id',
        ]);
        $this->get(self::URI . '/1')->assertSee('Tidak ada hambatan pembayaran Midtrans')
            ->assertSee('Satu percobaan pembayaran berakhir tanpa keberhasilan.');
        $this->caseFor(1, 'prioritas_tinggi', 'transaction_conflict');
        $this->get(self::URI . '/1')->assertSee('Tertahan · ada kasus yang perlu diperiksa');
    }

    public function test_candidates_use_the_raw_database_column_and_coordinator_wait(): void
    {
        $this->asAdmin();
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));
        $claimed = ['snap_requested_at' => now()->subSeconds(30)];
        $this->payment(1, 'KA-CANDIDATE', $claimed);
        $this->payment(2, 'KA-ENCRYPTED', $claimed + ['snap_token' => Crypt::encryptString('token')]);
        $this->payment(3, 'KA-FRESH', ['snap_requested_at' => now()->subSeconds(29)]);
        $this->payment(4, 'KA-RECONCILED', $claimed);
        $this->caseFor(4, 'selesai', 'amount_conflict');
        $this->payment(5, 'KA-MANUAL', $claimed + ['gateway' => 'manual']);
        $this->payment(6, 'KA-NO-CLAIM');
        $this->payment(7, 'KA-OPAQUE', $claimed + ['snap_token' => 'unreadable-ciphertext']);

        $this->get(self::URI . '?status=terbuka')->assertOk()
            ->assertSee('Sesi Pembayaran yang Perlu Diperiksa')->assertSee('KA-CANDIDATE')
            ->assertDontSee('KA-ENCRYPTED')->assertDontSee('KA-FRESH')->assertDontSee('KA-RECONCILED')
            ->assertDontSee('KA-MANUAL')->assertDontSee('KA-NO-CLAIM')->assertDontSee('KA-OPAQUE');
        $this->assertDatabaseCount('midtrans_rekonsiliasi', 1);
        $this->assertDatabaseHas('pembayaran', ['id' => 1, 'status_pembayaran' => 'Menunggu', 'snap_token' => null]);
    }

    private function asAdmin(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 1, 'is_admin' => true]));
    }

    private function payment(int $id, string $code, array $fields = []): void
    {
        DB::table('pesanan')->insert([
            'id' => $id,
            'kode_pesanan' => $code,
            'status_pesanan' => 'Menunggu',
            'catatan' => 'private-customer-notes',
            'total_pembayaran' => 15000,
        ]);
        DB::table('pembayaran')->insert($fields + [
            'id' => $id,
            'pesanan_id' => $id,
            'gateway' => 'midtrans',
            'status_pembayaran' => 'Menunggu',
            'jumlah_pembayaran' => 15000,
            'metode_pembayaran' => 'QRIS',
        ]);
    }

    private function caseFor(int $payment, string $status, string $reason, array $fields = []): void
    {
        DB::table('midtrans_rekonsiliasi')->insert($fields + [
            'pembayaran_id' => $payment,
            'midtrans_order_id' => 'gateway-' . $payment,
            'status_penanganan' => $status,
            'catatan_manual' => $reason,
            'created_at' => '2026-11-20 10:00:00',
        ]);
    }
}
