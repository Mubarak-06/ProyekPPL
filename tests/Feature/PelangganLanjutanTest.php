<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\PelangganTestCase;

class PelangganLanjutanTest extends PelangganTestCase
{
    public function test_filter_material_ukuran_dan_kategori_bisa_digabung(): void
    {
        $data = $this->dataBelanja();
        DB::table('material')->insert(['id' => 1, 'nama_material' => 'Stainless Steel']);
        $data['produk']->update(['ukuran' => 'M8', 'material_id' => 1]);
        $this->get('/produk?kategori=1&material=1&ukuran=M8&q=UJI')->assertOk()->assertSee('Baut Pengujian');
        $this->get('/produk?kategori=1&material=1&ukuran=M99')->assertOk()->assertSee('Produk tidak ditemukan');
        $this->get('/produk?material=999')->assertSessionHasErrors('material');
        $this->get('/produk?ukuran[]=M8')->assertSessionHasErrors('ukuran');
    }

    public function test_foto_dan_telepon_profil_disimpan_untuk_akun_sendiri(): void
    {
        Storage::fake('public');
        $data = $this->dataBelanja();
        $lain = User::factory()->create();
        $this->actingAs($data['user'])->get('/akun')->assertOk()->assertSee('Edit Profil');
        $this->get('/akun/edit')->assertOk()->assertSee('Foto Profil');
        $this->post('/akun', ['_method' => 'PUT', 'name' => 'Nama Baru', 'email' => $data['user']->email,
            'nomor_telepon' => '083821167555', 'foto_profil' => UploadedFile::fake()->image('foto.png'), 'id' => $lain->id])
            ->assertRedirect('/akun')->assertSessionHasNoErrors();
        $user = $data['user']->fresh();
        $this->assertSame('083821167555', $user->nomor_telepon);
        Storage::disk('public')->assertExists($user->foto_profil);
        $this->assertNull($lain->fresh()->foto_profil);
        $this->post('/akun', ['_method' => 'PUT', 'name' => 'Nama Baru', 'email' => $user->email,
            'foto_profil' => UploadedFile::fake()->create('buruk.txt', 1, 'text/plain')])->assertSessionHasErrors('foto_profil');
        $this->assertSame($user->foto_profil, $user->fresh()->foto_profil);
    }

    public function test_wilayah_bertingkat_hanya_mengembalikan_anak_wilayah_yang_dipilih(): void
    {
        $data = $this->dataBelanja();
        $this->get('/wilayah?jenis=kota&induk=1')->assertRedirect('/login');
        $this->actingAs($data['user'])->get('/wilayah?jenis=kota&induk=1')->assertOk()->assertJsonFragment(['id' => 1, 'nama' => 'Kota Pengujian']);
        $this->get('/wilayah?jenis=kecamatan&induk=1')->assertJsonCount(1);
        $this->get('/wilayah?jenis=kelurahan&induk=999')->assertJsonCount(0);
        $this->get('/wilayah?jenis=users&induk=1')->assertSessionHasErrors('jenis');
    }

    public function test_hapus_alamat_menolak_akun_lain_dan_alamat_yang_sudah_dipakai(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs(User::factory()->create())->delete('/alamat/'.$data['alamat'])->assertNotFound();
        $this->pesan($data);
        $this->delete('/alamat/'.$data['alamat'])->assertSessionHas('error');
        $this->assertDatabaseHas('alamat', ['id' => $data['alamat']]);
        $baru = Alamat::findOrFail($data['alamat'])->replicate();
        $baru->save();
        $this->delete('/alamat/'.$baru->id)->assertSessionHas('success');
        $this->assertDatabaseMissing('alamat', ['id' => $baru->id]);
    }

    public function test_checkout_hanya_memesan_produk_terpilih_dan_menyisakan_yang_lain(): void
    {
        $data = $this->dataBelanja();
        $lain = $data['produk']->replicate();
        $lain->kode_barang = 'UJI-002';
        $lain->nama_produk = 'Produk yang tidak dipilih';
        $lain->save();
        $token = str_repeat('s', 40);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2, $lain->id => 3], 'checkout_token' => $token]);
        $this->post('/keranjang/pilihan', ['pilihan' => [$data['produk']->id]])->assertSessionHas('keranjang_pilihan', [$data['produk']->id]);
        $this->get('/checkout')->assertOk()->assertDontSee($lain->nama_produk);
        $this->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran' => 'Transfer Bank', 'checkout_token' => $token])->assertSessionHasNoErrors()
            ->assertSessionHas('keranjang.'.$lain->id, 3)->assertSessionMissing('keranjang.'.$data['produk']->id);
        $this->assertDatabaseCount('detail_pesanan', 1);
        $this->assertSame(10, $lain->fresh()->stok);
        $this->assertSame(8, $data['produk']->fresh()->stok);
    }

    public function test_pilihan_kosong_dan_hapus_terpilih_tidak_memesan_produk_lain(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $this->post('/keranjang/pilihan', [])->assertSessionHas('keranjang_pilihan', []);
        $this->get('/checkout')->assertRedirect('/keranjang');
        $this->post('/keranjang/pilihan', ['pilihan' => [$data['produk']->id], 'aksi' => 'hapus'])->assertSessionHas('keranjang', []);
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(10, $data['produk']->fresh()->stok);
    }

    public function test_jumlah_dari_form_html_dan_session_lama_bisa_dipakai_checkout(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 1]])
            ->put('/keranjang/'.$data['produk']->id, ['jumlah' => '3'])->assertSessionHas('keranjang.'.$data['produk']->id, 3);
        $this->get('/checkout')->assertOk()->assertSessionHasNoErrors();
        $token = str_repeat('j', 40);
        $this->withSession(['keranjang' => [$data['produk']->id => '2'], 'checkout_token' => $token])
            ->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran' => 'Transfer Bank', 'checkout_token' => $token])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('detail_pesanan', ['jumlah' => 2]);
        $this->assertSame(8, $data['produk']->fresh()->stok);
    }

    public function test_metode_belum_lengkap_tidak_bisa_dipakai_checkout(): void
    {
        $data = $this->dataBelanja();
        config(['services.midtrans.sandbox_channels' => []]);
        $token = str_repeat('q', 40);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 1], 'checkout_token' => $token]);
        $this->get('/checkout')->assertOk()->assertSee('Kanal pembayaran Midtrans belum tersedia.');
        $this->post('/checkout', ['alamat_id' => $data['alamat'], 'metode_pembayaran' => 'Transfer Bank', 'checkout_token' => $token])
            ->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(10, $data['produk']->fresh()->stok);
    }

    public function test_instruksi_bank_ewallet_dan_qris_memakai_metode_database(): void
    {
        Storage::fake('public');
        $data = $this->dataBelanja();
        $pesanan = $this->pesan($data);
        DB::table('metode_pembayaran')->where('id', $data['metode'])->update(['nomor_rekening' => '1234567890', 'nama_pemilik' => 'Pemilik Pengujian']);
        $this->get('/pesanan/'.$pesanan->id.'/pembayaran')->assertOk()->assertSee('1234567890')->assertSee('Kirim Bukti Pembayaran');
        DB::table('metode_pembayaran')->where('id', $data['metode'])->update(['jenis_metode' => 'ewallet', 'nama_penyedia' => 'DANA']);
        $this->get('/pesanan/'.$pesanan->id.'/pembayaran')->assertSee('DANA');
        $gambar = UploadedFile::fake()->image('qris.png')->store('metode-pembayaran/qris', 'public');
        DB::table('metode_pembayaran')->where('id', $data['metode'])->update(['jenis_metode' => 'qris', 'nama_penyedia' => 'QRIS', 'gambar_qris' => $gambar]);
        $this->get('/pesanan/'.$pesanan->id.'/pembayaran')->assertSee('Pindai kode QRIS')->assertSee($gambar);
        $this->actingAs(User::factory()->create())->get('/pesanan/'.$pesanan->id.'/pembayaran')->assertNotFound();
        $this->actingAs($data['user']);
        $pesanan->update(['status_pesanan' => 'Ditolak']);
        $this->get('/pesanan/'.$pesanan->id.'/pembayaran')->assertSee('Jangan melakukan pembayaran')->assertDontSee('Kirim Bukti Pembayaran');
    }

    public function test_reset_password_bertokennya_valid_sekali_dan_email_tidak_dibocorkan(): void
    {
        Notification::fake();
        $data = $this->dataBelanja();
        $this->get('/lupa-password')->assertOk();
        $this->post('/lupa-password', ['email' => 'tidak-ada@example.test'])->assertSessionHas('success');
        $this->post('/lupa-password', ['email' => $data['user']->email])->assertSessionHas('success');
        Notification::assertSentTo($data['user'], ResetPassword::class);
        $token = Password::createToken($data['user']);
        $form = ['token' => $token, 'email' => $data['user']->email, 'password' => 'password-baru', 'password_confirmation' => 'password-baru'];
        $this->get('/reset-password/'.$token.'?email='.urlencode($data['user']->email))->assertOk();
        $this->post('/reset-password', $form)->assertRedirect('/login')->assertSessionHas('success');
        $this->assertTrue(Hash::check('password-baru', $data['user']->fresh()->password));
        $this->post('/reset-password', $form)->assertSessionHasErrors('email');
    }

    public function test_pencarian_pesanan_tidak_membocorkan_pesanan_akun_lain(): void
    {
        $data = $this->dataBelanja();
        $pesanan = $this->pesan($data);
        $this->get('/pesanan?q=tidak-ada&per_page=25')->assertOk()->assertDontSee($pesanan->kode_pesanan);
        $this->get('/pesanan?q='.$pesanan->kode_pesanan)->assertSee($pesanan->kode_pesanan);
        $this->get('/pesanan?per_page=999')->assertSessionHasErrors('per_page');
        $this->actingAs(User::factory()->create())->get('/pesanan?q='.$pesanan->kode_pesanan)
            ->assertViewHas('pesanan', fn ($daftar) => $daftar->isEmpty());
    }

    public function test_halaman_tentang_memakai_whatsapp_yang_dikonfirmasi(): void
    {
        $this->get('/tentang-kami')->assertOk()->assertSee('083821167555')->assertSee('https://wa.me/6283821167555')->assertSee('Jam Operasional');
    }

    public function test_checkout_mengelompokkan_penyedia_aktif_tanpa_mengaktifkan_metode_belum_siap(): void
    {
        $data = $this->dataBelanja();
        $response = $this->actingAs($data['user'])
            ->withSession(['keranjang' => [$data['produk']->id => 2]])->get('/checkout');
        $response->assertOk()->assertSee('Transfer Bank')->assertSee('E-Wallet')->assertSee('QRIS')
            ->assertViewHas('metode', ['Transfer Bank' => ['bca_va']])
            ->assertDontSee('Pemilik Pengujian')->assertDontSee('test-only-server');
        $this->assertSame(3, substr_count($response->getContent(), 'name="metode_pembayaran"'));
        $this->assertDatabaseCount('pesanan', 0);
    }

    private function pesan(array $data): Pesanan
    {
        return $this->legacyOrder($data);
    }
}
