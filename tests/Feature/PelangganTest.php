<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\PelangganTestCase;

class PelangganTest extends PelangganTestCase
{
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
        $token = str_repeat('a', 40);
        $form = ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token,
            'subtotal' => 1, 'ongkos_kirim' => 0, 'total_pembayaran' => 1];
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2], 'checkout_token' => $token]);
        $this->get('/checkout')->assertOk()->assertSee('10.000');
        $this->post('/checkout', $form)->assertRedirect('/pesanan/1')->assertSessionMissing('keranjang');
        $this->assertDatabaseHas('pesanan', ['subtotal' => 2500.50, 'ongkos_kirim' => 10000, 'total_pembayaran' => 12500.50]);
        $this->assertDatabaseHas('detail_pesanan', ['jumlah' => 2, 'harga_satuan' => 1250.25]);
        $this->assertDatabaseHas('pembayaran', ['status_pembayaran' => 'Menunggu', 'metode_pembayaran_id' => $data['metode']]);
        $this->assertDatabaseHas('pengiriman', ['status_pengiriman' => 'Menunggu', 'nama_penerima' => 'Penerima Pengujian']);
        $this->assertSame(8, $data['produk']->fresh()->stok);
        // Meniru dua permintaan bersamaan yang membawa salinan session yang sama.
        $this->withSession(['keranjang' => [$data['produk']->id => 2], 'checkout_token' => $token])
            ->post('/checkout', $form)->assertRedirect('/pesanan/1');
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertSame(8, $data['produk']->fresh()->stok);
        $this->get('/pesanan/1')->assertOk()->assertSee('BCA');
    }

    public function test_checkout_menolak_stok_berubah_tanpa_menyimpan_transaksi(): void
    {
        $data = $this->dataBelanja();
        $token = str_repeat('b', 40);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 11], 'checkout_token' => $token])
            ->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token])
            ->assertSessionHasErrors('keranjang');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(10, $data['produk']->fresh()->stok);
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
        $token = str_repeat('f', 40);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2], 'checkout_token' => $token])
            ->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token])
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
        $token = str_repeat('c', 40);
        $form = ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token];
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 1], 'checkout_token' => $token]);
        DB::table('metode_pembayaran')->update(['status' => 'nonaktif']);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        DB::table('metode_pembayaran')->update(['status' => 'aktif']);
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
        $token = str_repeat('d', 40);
        $this->withSession(['keranjang' => [$data['produk']->id => 1], 'checkout_token' => $token])
            ->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token])
            ->assertNotFound();
        $this->assertDatabaseCount('pesanan', 1);
    }

    public function test_status_admin_terlihat_dan_penolakan_mengembalikan_stok_sekali(): void
    {
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $this->patch('/admin/pesanan/'.$pesanan->id.'/status', ['status_pesanan' => 'Diproses'])->assertSessionHas('success');
        $this->get('/pesanan/'.$pesanan->id)->assertOk()->assertSee('Diproses');
        $this->assertSame(8, $data['produk']->fresh()->stok);
        $this->patch('/admin/pesanan/'.$pesanan->id.'/tolak')->assertSessionHas('success');
        $this->assertSame(10, $data['produk']->fresh()->stok);
        $this->patch('/admin/pesanan/'.$pesanan->id.'/tolak')->assertSessionHas('error');
        $this->assertSame(10, $data['produk']->fresh()->stok);
        $this->assertDatabaseHas('pengiriman', ['pesanan_id' => $pesanan->id, 'status_pengiriman' => 'Ditolak']);
    }

    public function test_bukti_pembayaran_terhubung_dengan_admin_tanpa_mengubah_status(): void
    {
        Storage::fake('public');
        $data = $this->dataBelanja();
        $pesanan = $this->buatPesanan($data);
        $this->post('/pesanan/'.$pesanan->id.'/bukti', ['bukti' => UploadedFile::fake()->image('bukti.png')])->assertSessionHas('success');
        $pembayaran = $pesanan->pembayaran()->firstOrFail();
        Storage::disk('public')->assertExists($pembayaran->bukti_pembayaran);
        $this->assertSame('Menunggu', $pembayaran->status_pembayaran);
        $this->get('/admin/pembayaran/'.$pembayaran->id)->assertOk()->assertSee($pembayaran->bukti_pembayaran);
        $this->actingAs(User::factory()->create())
            ->post('/pesanan/'.$pesanan->id.'/bukti', ['bukti' => UploadedFile::fake()->image('asing.png')])->assertNotFound();
    }

    public function test_seluruh_halaman_akun_dan_admin_tetap_bisa_dibuka(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user']);
        foreach (['/akun', '/alamat', '/alamat/tambah', '/alamat/'.$data['alamat'].'/edit', '/pesanan', '/tentang-kami',
            '/admin/dashboard', '/admin/produk', '/admin/pesanan', '/admin/pembayaran', '/admin/pengiriman', '/admin/data-master'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    private function buatPesanan(array $data): Pesanan
    {
        $token = str_repeat('e', 40);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2], 'checkout_token' => $token])
            ->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran_id' => $data['metode'], 'checkout_token' => $token])
            ->assertSessionHasNoErrors();

        return Pesanan::latest('id')->firstOrFail();
    }
}
