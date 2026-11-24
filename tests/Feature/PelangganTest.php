<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\MidtransSnapService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\PelangganTestCase;

class PelangganTest extends PelangganTestCase
{
    public function test_akun_tersimpan_bisa_login_dari_sesi_baru(): void
    {
        $this->post('/daftar', [
            'name' => 'Pelanggan Dua Sesi', 'email' => 'dua-sesi@example.test',
            'password' => 'password-dua-sesi', 'password_confirmation' => 'password-dua-sesi',
        ])->assertRedirect('/akun')->assertSessionHasNoErrors();

        $user = User::where('email', 'dua-sesi@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password-dua-sesi', $user->getRawOriginal('password')));

        // Lepaskan identitas dan data sesi klien pertama; database tetap sama.
        $this->app['auth']->forgetGuards();
        $this->app['session']->driver()->invalidate();
        $this->get('/akun')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'password-dua-sesi'])
            ->assertRedirect('/akun')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user, 'web');
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('web');
        $this->get('/akun')->assertRedirect('/login');

        $this->from('/login')->post('/login', ['email' => 'belum-terdaftar@example.test', 'password' => 'password-dua-sesi'])
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        $this->assertGuest('web');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_katalog_pencarian_dan_detail_memakai_produk_database(): void
    {
        $data = $this->dataBelanja();
        $this->get('/')->assertOk()->assertSee('Pilihan Produk')->assertDontSee('Produk Terlaris');
        $this->get('/produk?q=UJI-001&kategori=1')->assertOk()->assertSee('Baut Pengujian');
        $this->get('/produk?q=tidak-ada')->assertOk()->assertSee('Produk tidak ditemukan');
        $this->get('/produk/'.$data['produk']->id)->assertOk()->assertSee('Gambar belum tersedia');
        $this->get('/produk/999')->assertNotFound();
    }

    public function test_pelanggan_dapat_mendaftar_masuk_dan_keluar(): void
    {
        $this->post('/daftar', [
            'name' => 'Akun Pengujian', 'email' => 'akun@example.test',
            'password' => 'password-uji', 'password_confirmation' => 'password-uji',
        ])->assertRedirect('/akun');
        $user = User::firstOrFail();
        $this->assertTrue(Hash::check('password-uji', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'password-uji'])->assertRedirect('/akun');
        $this->assertAuthenticatedAs($user);
    }

    public function test_keranjang_menolak_jumlah_melebihi_stok_dan_bisa_diperbarui(): void
    {
        $data = $this->dataBelanja();
        $id = $data['produk']->id;
        $this->post('/keranjang/'.$id, ['jumlah' => 2])->assertSessionHas('keranjang.'.$id, 2);
        $this->post('/keranjang/'.$id, ['jumlah' => 9])->assertSessionHasErrors('jumlah')->assertSessionHas('keranjang.'.$id, 2);
        $this->put('/keranjang/'.$id, ['jumlah' => 4])->assertSessionHas('keranjang.'.$id, 4);
        $this->get('/keranjang')->assertOk()->assertSee('Baut Pengujian');
        $this->delete('/keranjang/'.$id)->assertSessionMissing('keranjang.'.$id);
    }

    public function test_checkout_tamu_diarahkan_ke_login(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
        $this->get('/akun')->assertRedirect('/login');
    }

    public function test_checkout_menyimpan_transaksi_dan_mengurangi_stok_sekali(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $this->get('/checkout')->assertOk()->assertSee('10.000');
        $form = $this->formCheckout() + ['subtotal' => 1, 'ongkos_kirim' => 0, 'total_pembayaran' => 1];
        $this->post('/checkout', $form)->assertRedirect('/pesanan/1/pembayaran')->assertSessionMissing('keranjang');
        $this->assertDatabaseHas('pesanan', ['subtotal' => 2500, 'ongkos_kirim' => 10000, 'total_pembayaran' => 12500]);
        $this->assertDatabaseHas('detail_pesanan', ['jumlah' => 2, 'harga_satuan' => 1250]);
        $this->assertDatabaseHas('pembayaran', ['status_pembayaran' => 'Menunggu', 'gateway' => 'midtrans', 'metode_pembayaran_id' => null]);
        $this->assertDatabaseHas('pengiriman', ['status_pengiriman' => 'Menunggu', 'nama_penerima' => 'Penerima Pengujian']);
        $this->assertSame(8, $data['produk']->fresh()->stok);
        $this->post('/checkout', $form)->assertRedirect('/pesanan/1/pembayaran');
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertSame(8, $data['produk']->fresh()->stok);
        $this->get('/pesanan/1')->assertOk()->assertSee('Transfer Bank');
    }

    public function test_checkout_snap_uses_one_token_and_only_server_verification_settles(): void
    {
        $data = $this->dataBelanja();
        $url = 'https://app.sandbox.midtrans.com/snap/v4/redirection/fake-snap-session';
        $gateway = $this->partialMock(MidtransSnapService::class);
        $gateway->shouldReceive('requestSnapToken')->once()->withArgs(function ($order, $payment) {
            $parameters = (new MidtransSnapService)->buildTransactionParameters($order, $payment);
            $this->assertSame(['bca_va'], $parameters['enabled_payments']);
            $this->assertSame(['order_id' => $order->kode_pesanan, 'gross_amount' => 12500], $parameters['transaction_details']);

            return true;
        })->andReturn([
            'status' => 'ready', 'token' => 'fake-snap-token', 'redirect_url' => $url,
            'snap_token_expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $form = $this->formCheckout();
        $this->post('/checkout', $form)->assertStatus(303)->assertRedirect($url)->assertSessionMissing('keranjang');
        $this->post('/checkout', $form)->assertStatus(303)->assertRedirect($url);
        $this->post('/checkout', ['metode_pembayaran' => 'QRIS'] + $form)
            ->assertRedirect('/pesanan/1/pembayaran')->assertSessionHas('error');
        $this->assertDatabaseCount('pesanan', 1);
        $order = Pesanan::findOrFail(1);
        $notice = [
            'order_id' => $order->kode_pesanan, 'transaction_id' => 'va-fixture', 'gross_amount' => '12500.00',
            'status_code' => '200', 'transaction_status' => 'settlement', 'payment_type' => 'bank_transfer',
        ];
        $gateway->shouldReceive('getTransactionStatus')->twice()->with('va-fixture')->andReturn((object) $notice);

        $this->get('/pesanan/1/pembayaran')->assertOk()->assertSee('Menunggu pembayaran')
            ->assertSee('test-only-client')->assertDontSee('test-only-server');
        $this->get('/pesanan')->assertOk()->assertSee('Bayar Sekarang')
            ->assertSee('action="'.route('pelanggan.pembayaran.snap', 1).'"', false);
        $this->get('/pesanan/1')->assertOk()->assertSee('Bayar Sekarang')
            ->assertSee('action="'.route('pelanggan.pembayaran.snap', 1).'"', false);
        $this->post('/pesanan/1/pembayaran/snap')->assertStatus(303)->assertRedirect($url);
        foreach (range(1, 2) as $unused) {
            $this->postJson('/pesanan/1/pembayaran/snap', ['status_pembayaran' => 'Berhasil'])
                ->assertExactJson(['status' => 'ready', 'token' => 'fake-snap-token', 'redirect_url' => $url]);
        }
        $this->assertDatabaseHas('pembayaran', ['id' => 1, 'status_pembayaran' => 'Menunggu']);
        $this->actingAs(User::factory()->create())->postJson('/pesanan/1/pembayaran/snap')->assertNotFound();
        $this->post('/pesanan/1/pembayaran/snap')->assertNotFound();
        $this->postJson('/pesanan/1/pembayaran/periksa')->assertNotFound();
        $this->get('/pesanan/1/pembayaran?transaction_status=settlement')->assertNotFound();
        $gateway->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)->andReturn(
            (object) (['transaction_status' => 'pending', 'status_code' => '201'] + $notice),
        );
        $gateway->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)
            ->andThrow(new \RuntimeException('Timeout', 28));
        $gateway->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)->andReturn((object) $notice);
        $this->actingAs($data['user'])->get('/pesanan/1/pembayaran?transaction_status=settlement')
            ->assertOk()->assertSee('Menunggu pembayaran')->assertDontSee('Pembayaran berhasil');
        $this->postJson('/pesanan/1/pembayaran/periksa')->assertJsonPath('reason', 'pending')->assertJsonMissingPath('redirect_url');
        $this->get('/pesanan/1')->assertOk()->assertSee('Lanjutkan Pembayaran')->assertDontSee('Bayar Sekarang');
        $this->travel(1)->minutes();
        $this->post('/pesanan/1/pembayaran/snap')->assertStatus(303)->assertRedirect($url);
        $this->postJson('/pesanan/1/pembayaran/periksa')->assertJsonPath('status', 'unavailable')->assertJsonMissingPath('redirect_url');
        $this->actingAs($data['user'])->post('/pesanan/1/pembayaran/periksa', ['status_pembayaran' => 'Berhasil'])
            ->assertRedirect('/pesanan');
        $this->travel(1)->minutes();
        $this->postJson('/pesanan/1/pembayaran/periksa')->assertOk()
            ->assertJsonPath('reason', 'already_paid')->assertJsonPath('redirect_url', '/pesanan');
        $this->assertDatabaseHas('pembayaran', ['id' => 1, 'status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'va-fixture']);
        $notice['signature_key'] = hash('sha512', $notice['order_id'].'20012500.00test-only-server');
        $this->postJson('/api/webhooks/midtrans', $notice)->assertOk();
        $this->actingAs($data['user'])->get('/pesanan/1/pembayaran')->assertRedirect('/pesanan');
        $this->get('/pesanan')->assertOk()->assertSee($order->kode_pesanan)->assertSee('Pembayaran: Berhasil')
            ->assertSee('Lihat Detail')->assertDontSee('Bayar Sekarang');
        $this->get('/pesanan/1')->assertOk()->assertDontSee('Bayar Sekarang')->assertDontSee('Instruksi Pembayaran');
        $this->postJson('/pesanan/1/pembayaran/snap')->assertExactJson(['status' => 'rejected']);
        $this->post('/pesanan/1/pembayaran/snap')->assertStatus(303)->assertRedirect('/pesanan/1/pembayaran');
        $this->assertSame(8, $data['produk']->fresh()->stok);
    }

    public function test_snap_finish_verifies_before_redirect_and_keeps_unconfirmed_payments_waiting(): void
    {
        config(['services.midtrans.is_production' => false]);
        $data = $this->dataBelanja();
        $order = $this->legacyOrder($data);
        $payment = $order->pembayaran()->sole();
        $payment->update(['gateway' => 'midtrans', 'midtrans_order_id' => $order->kode_pesanan]);
        $stock = $data['produk']->fresh()->stok;
        $transaction = [
            'order_id' => $order->kode_pesanan, 'transaction_id' => 'finish-va',
            'gross_amount' => '12500.00', 'payment_type' => 'bank_transfer',
        ];
        $snap = $this->partialMock(MidtransSnapService::class);
        $snap->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)
            ->andReturn((object) (['transaction_status' => 'pending', 'status_code' => '201'] + $transaction));
        $snap->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)
            ->andThrow(new \RuntimeException('Timeout', 28));
        $snap->shouldReceive('getTransactionStatus')->once()->with($order->kode_pesanan)
            ->andReturn((object) (['transaction_status' => 'settlement', 'status_code' => '200'] + $transaction));
        $callback = route('pelanggan.pembayaran', [
            'id' => $order->id, 'order_id' => $order->kode_pesanan, 'transaction_status' => 'settlement',
        ]);

        $this->get($callback)->assertOk()->assertSee('Midtrans masih menunggu pembayaran.')
            ->assertSee('Kembali ke Pesanan Saya')->assertDontSee('Pembayaran berhasil');
        $this->assertSame('Menunggu', $payment->fresh()->status_pembayaran);
        $this->postJson(route('pelanggan.pembayaran.periksa', $order->id))
            ->assertJsonPath('status', 'unavailable')->assertJsonMissingPath('redirect_url');
        $this->assertSame('Menunggu', $payment->fresh()->status_pembayaran);
        $this->get($callback)->assertRedirect('/pesanan');
        $this->get($callback)->assertRedirect('/pesanan');
        $this->assertSame('Berhasil', $payment->fresh()->status_pembayaran);
        $this->assertSame($stock, $data['produk']->fresh()->stok);
    }

    public function test_dana_uses_hosted_sandbox_when_popup_key_is_for_production(): void
    {
        config(['services.midtrans.client_key' => 'Mid-client-test-production', 'services.midtrans.sandbox_channels' => ['dana'],
            'services.midtrans.sandbox_merchant_id' => 'test-merchant',
            'services.midtrans.sandbox_verified_channels' => ['dana']]);
        $data = $this->dataBelanja();
        $url = 'https://app.sandbox.midtrans.com/snap/v4/redirection/dana-session';
        $gateway = $this->partialMock(MidtransSnapService::class);
        $gateway->shouldReceive('requestSnapToken')->once()->andReturn([
            'status' => 'ready', 'token' => 'fake-dana-token', 'redirect_url' => $url,
            'snap_token_expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);

        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 1]]);
        $this->post('/checkout', $this->formCheckout(metode: 'E-Wallet'))
            ->assertStatus(303)->assertRedirect($url);
        $this->get('/pesanan/1/pembayaran?transaction_id=dana-test-id')
            ->assertOk()->assertSee('dana-test-id')->assertDontSee('Mid-client-test-production')
            ->assertDontSee('/snap/snap.js')->assertSee('Buka pembayaran Snap');
        $this->postJson('/pesanan/1/pembayaran/snap')
            ->assertExactJson(['status' => 'ready', 'redirect_url' => $url]);

        $order = Pesanan::findOrFail(1);
        $gateway->shouldReceive('getTransactionStatus')->once()->with('wrong-dana-id')->andReturn((object) [
            'order_id' => 'KA-OTHER-ORDER', 'transaction_id' => 'wrong-dana-id', 'gross_amount' => '11250.00',
            'status_code' => '200', 'transaction_status' => 'settlement', 'payment_type' => 'dana', 'currency' => 'IDR',
            'merchant_id' => 'test-merchant',
        ]);
        $this->postJson('/pesanan/1/pembayaran/periksa', ['transaction_id' => 'wrong-dana-id'])
            ->assertJsonPath('status', 'unavailable');
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => 1, 'status_pembayaran' => 'Menunggu']);
        $this->postJson('/pesanan/1/pembayaran/periksa')->assertJsonPath('status', 'transaction_id_required');

        $verified = [
            'order_id' => $order->kode_pesanan, 'transaction_id' => 'dana-test-id', 'gross_amount' => '11250.00',
            'status_code' => '200', 'payment_type' => 'dana', 'currency' => 'IDR', 'merchant_id' => 'test-merchant',
        ];
        $gateway->shouldReceive('getTransactionStatus')->twice()->with('dana-test-id')->andReturn(
            (object) (['transaction_status' => 'PENDING'] + $verified),
            (object) (['transaction_status' => 'settlement'] + $verified),
        );
        $this->postJson('/pesanan/1/pembayaran/periksa', ['transaction_id' => 'dana-test-id'])
            ->assertJsonPath('reason', 'pending');
        $this->assertDatabaseHas('pembayaran', [
            'pesanan_id' => 1, 'status_pembayaran' => 'Menunggu', 'midtrans_transaction_id' => 'dana-test-id',
        ]);
        $this->artisan('midtrans:sync-pending')->assertSuccessful();
        $this->artisan('midtrans:sync-pending')->assertSuccessful();
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => 1, 'status_pembayaran' => 'Berhasil']);
        $paymentId = $order->pembayaran()->sole()->id;
        $this->getJson('/admin/pembayaran')->assertForbidden();
        $this->actingAs($this->akunAdmin())->getJson('/admin/pembayaran')
            ->assertOk()->assertJsonFragment(['id' => $paymentId, 'status_pembayaran' => 'Berhasil']);
        $this->assertSame(9, $data['produk']->fresh()->stok);
    }

    public function test_sandbox_candidates_do_not_open_checkout_without_verification(): void
    {
        config([
            'services.midtrans.sandbox_channels' => ['bca_va', 'bni_va', 'bri_va', 'echannel', 'dana', 'other_qris'],
            'services.midtrans.sandbox_verified_channels' => [],
        ]);
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $this->get('/checkout')->assertOk()->assertViewHas('metode', [])->assertSee('Kanal pembayaran Midtrans belum tersedia.');
        $this->post('/checkout', $this->formCheckout())
            ->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(10, $data['produk']->fresh()->stok);

        config(['services.midtrans.sandbox_verified_channels' => ['bni_va', 'bri_va', 'echannel', 'dana', 'other_qris', 'permata_va']]);
        $expected = ['Transfer Bank' => ['bni_va', 'bri_va', 'echannel'], 'QRIS' => ['other_qris'], 'E-Wallet' => ['dana']];
        $this->get('/checkout')->assertViewHas('metode', $expected);
        foreach ($expected as $method => $channels) {
            $order = (new Pesanan)->newFromBuilder(['id' => 1, 'total_pembayaran' => '12500.00']);
            $payment = (new Pembayaran)->newFromBuilder([
                'id' => 1, 'pesanan_id' => 1, 'gateway' => 'midtrans', 'midtrans_order_id' => 'ORDER-FIXTURE',
                'jumlah_pembayaran' => '12500.00', 'metode_pembayaran' => $method,
            ]);
            $this->assertSame($channels, (new MidtransSnapService)->buildTransactionParameters($order, $payment)['enabled_payments']);
        }

        config(['services.midtrans.is_production' => true]);
        $this->get('/checkout')->assertViewHas('metode', []);
        $this->expectException(\InvalidArgumentException::class);
        (new MidtransSnapService)->buildTransactionParameters($order, $payment);
    }

    public function test_checkout_menolak_stok_berubah_tanpa_menyimpan_transaksi(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $form = $this->formCheckout();
        $data['produk']->update(['stok' => 1]);
        $this->post('/checkout', $form)->assertSessionHasErrors('keranjang');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(1, $data['produk']->fresh()->stok);
        $this->from('/checkout')->get('/checkout')->assertRedirect('/keranjang')->assertSessionHasErrors('keranjang');
    }

    public function test_transaksi_dibatalkan_seluruhnya_jika_penyimpanan_pembayaran_gagal(): void
    {
        $data = $this->dataBelanja();
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'insert into "pembayaran"')) {
                throw new \RuntimeException('Kegagalan pembayaran untuk pengujian rollback.');
            }
        });
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $this->post('/checkout', $this->formCheckout())
            ->assertStatus(500)->assertSessionHas('keranjang.'.$data['produk']->id, 2);
        foreach (['pesanan', 'detail_pesanan', 'pembayaran', 'pengiriman'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(10, $data['produk']->fresh()->stok);
    }

    public function test_profil_dan_alamat_disimpan_untuk_pengguna_yang_login(): void
    {
        $data = $this->dataBelanja();
        $lain = User::factory()->create();
        $this->actingAs($data['user'])->put('/akun', ['name' => 'Nama Baru', 'email' => 'baru@example.test'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $data['user']->id, 'name' => 'Nama Baru', 'email' => 'baru@example.test']);
        $form = ['nama_penerima' => 'Penerima Baru', 'nomor_telepon' => '081234567890', 'alamat_lengkap' => 'Alamat Baru',
            'kelurahan_id' => 1, 'kode_pos' => '70234', 'user_id' => $lain->id];
        $this->post('/alamat', $form)->assertRedirect('/alamat')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('alamat', ['nama_penerima' => 'Penerima Baru', 'user_id' => $data['user']->id]);
        $this->put('/alamat/'.$data['alamat'], $form)->assertRedirect('/alamat')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('alamat', ['id' => $data['alamat'], 'nama_penerima' => 'Penerima Baru', 'user_id' => $data['user']->id]);
        $form['kode_pos'] = 'abc';
        $this->post('/alamat', $form)->assertSessionHasErrors('kode_pos');
    }

    public function test_checkout_menolak_metode_nonaktif_dan_ongkir_tidak_tersedia(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 1]]);
        $form = $this->formCheckout();
        config(['services.midtrans.sandbox_channels' => []]);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        config(['services.midtrans.sandbox_channels' => ['bca_va']]);
        $form = $this->formCheckout();
        DB::table('ongkir')->delete();
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('pesanan', 0);
    }

    public function test_alamat_dan_pesanan_akun_lain_tidak_bisa_diakses(): void
    {
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $lain = User::factory()->create();
        $this->actingAs($lain)->get('/alamat/'.$data['alamat'].'/edit')->assertNotFound();
        $this->put('/alamat/'.$data['alamat'], [])->assertNotFound();
        $this->get('/pesanan/'.$pesanan->id)->assertNotFound();
        $this->get('/pesanan')->assertOk()->assertDontSee($pesanan->kode_pesanan);
        $alamatLain = (array) DB::table('alamat')->find($data['alamat']);
        unset($alamatLain['id']);
        DB::table('alamat')->insert(['user_id' => $lain->id] + $alamatLain);
        $this->withSession(['keranjang' => [$data['produk']->id => 1]]);
        $this->post('/checkout', ['alamat_id' => $data['alamat']] + $this->formCheckout())
            ->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('pesanan', 1);
    }

    public function test_status_admin_terlihat_dan_penolakan_mengembalikan_stok_sekali(): void
    {
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $admin = $this->akunAdmin();
        $this->actingAs($admin)->patch('/admin/pesanan/'.$pesanan->id.'/status', ['status_pesanan' => 'Diproses'])->assertSessionHas('error');
        $this->actingAs($data['user'])->get('/pesanan/'.$pesanan->id)->assertOk()->assertSee('Status pesanan: Menunggu');
        $this->assertSame(8, $data['produk']->fresh()->stok);
        $this->actingAs($admin)->patch('/admin/pesanan/'.$pesanan->id.'/tolak')->assertSessionHas('success');
        $this->assertSame(10, $data['produk']->fresh()->stok);
        $this->patch('/admin/pesanan/'.$pesanan->id.'/tolak')->assertSessionHas('error');
        $this->assertSame(10, $data['produk']->fresh()->stok);
        $this->assertDatabaseHas('pengiriman', ['pesanan_id' => $pesanan->id, 'status_pengiriman' => 'Ditolak']);
    }

    public function test_bukti_pembayaran_terhubung_dengan_admin_tanpa_mengubah_status(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $this->post('/pesanan/'.$pesanan->id.'/bukti', ['bukti' => UploadedFile::fake()->image('bukti.png')])->assertSessionHas('success');
        $pembayaran = $pesanan->pembayaran()->firstOrFail();
        Storage::disk('local')->assertExists($pembayaran->bukti_pembayaran);
        Storage::disk('public')->assertMissing($pembayaran->bukti_pembayaran);
        $this->assertSame('Menunggu', $pembayaran->status_pembayaran);
        $buktiPelanggan = route('pelanggan.pesanan.bukti.show', $pesanan->id);
        $buktiAdmin = route('admin.pembayaran.bukti', $pembayaran->id);
        $this->get($buktiPelanggan)->assertOk()->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/'.$pembayaran->bukti_pembayaran)->assertForbidden();
        $this->get($buktiAdmin)->assertForbidden();
        $this->actingAs($this->akunAdmin())->get('/admin/pembayaran/'.$pembayaran->id)
            ->assertOk()->assertSee($buktiAdmin)->assertDontSee($pembayaran->bukti_pembayaran);
        $this->get($buktiAdmin)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())
            ->post('/pesanan/'.$pesanan->id.'/bukti', ['bukti' => UploadedFile::fake()->image('asing.png')])->assertNotFound();
        $this->get($buktiPelanggan)->assertNotFound();
        $this->get($buktiAdmin)->assertForbidden();
        $this->post('/logout')->assertRedirect();
        $this->get($buktiPelanggan)->assertRedirect('/login');
    }

    public function test_bukti_lama_tetap_tersedia_dan_path_di_luar_folder_ditolak(): void
    {
        Storage::fake('public');
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $pembayaran = $pesanan->pembayaran()->firstOrFail();
        $path = 'bukti-pembayaran/bukti-lama.jpg';
        Storage::disk('public')->put($path, 'bukti historis');
        $pembayaran->update(['bukti_pembayaran' => $path]);
        $url = route('pelanggan.pesanan.bukti.show', $pesanan->id);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($this->akunAdmin())->get(route('admin.pembayaran.bukti', $pembayaran->id))->assertOk();
        $pembayaran->update(['bukti_pembayaran' => 'bukti-pembayaran/../gambar.jpg']);
        Storage::disk('public')->put('gambar.jpg', 'bukan bukti');
        $this->actingAs($data['user'])->get($url)->assertNotFound();
        Storage::disk('public')->assertExists($path);
    }

    public function test_seluruh_halaman_akun_dan_admin_tetap_bisa_dibuka(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user']);
        foreach (['/akun', '/alamat', '/alamat/tambah', '/alamat/'.$data['alamat'].'/edit', '/pesanan', '/tentang-kami'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->akunAdmin());
        foreach (['/admin/dashboard', '/admin/produk', '/admin/pesanan', '/admin/pembayaran', '/admin/pengiriman', '/admin/data-master'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    private function buatPesanan(array $data): Pesanan
    {
        return $this->legacyOrder($data);
    }
}
