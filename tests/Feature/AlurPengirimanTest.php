<?php

namespace Tests\Feature;

use App\Models\Pengiriman;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\PelangganTestCase;

class AlurPengirimanTest extends PelangganTestCase
{
    public function test_alur_tiga_peran_dengan_bukti_privat_dan_kendala(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $data = $this->dataBelanja();
        $pelanggan = $data['user'];
        $pesanan = $this->legacyOrder($data);
        $kirim = $pesanan->pengiriman;
        $admin = $this->akunAdmin();
        $orangLain = User::factory()->create();
        $pesanan->pembayaran->forceFill(['gateway' => 'midtrans'])->save();
        $estimasi = ['estimasi_tanggal' => now(config('toko.timezone'))->addDay()->toDateString(), 'estimasi_jam' => '10:30'];

        $this->actingAs($admin)->patch(route('admin.pesanan.updateStatus', $pesanan), ['status_pesanan' => 'Diproses'])
            ->assertSessionHas('error');
        $this->post(route('admin.pengiriman.siapkan', $kirim), $estimasi)->assertSessionHasErrors('pengiriman');
        $this->assertSame('Menunggu', $pesanan->fresh()->status_pesanan);

        $pesanan->pembayaran->forceFill([
            'status_pembayaran' => 'Berhasil', 'midtrans_transaction_id' => 'verified-test',
            'midtrans_status' => 'settlement', 'waktu_pembayaran' => now(),
        ])->save();
        $bayarSebelum = $pesanan->pembayaran->fresh()->getAttributes();
        $stokSebelum = $data['produk']->fresh()->stok;
        $this->patch(route('admin.pesanan.updateStatus', $pesanan), ['status_pesanan' => 'Diproses'])->assertSessionHas('success');
        $this->get(route('admin.pengiriman.show', $kirim))->assertOk()->assertSee('Akun kurir aktif belum tersedia');
        $this->post(route('admin.pengiriman.siapkan', $kirim), $estimasi)->assertSessionHasErrors('pengiriman');
        $this->assertSame(Pengiriman::DISIAPKAN, $kirim->fresh()->tahap());

        $kurir = $this->kurir();
        $this->post(route('admin.pengiriman.siapkan', $kirim), $estimasi + ['catatan_driver' => 'Hubungi penerima'])
            ->assertSessionHasNoErrors();
        $this->assertEquals($kurir->id, $kirim->fresh()->kurir_id);
        $this->post(route('admin.pengiriman.siapkan', $kirim), $estimasi)->assertSessionHasErrors('pengiriman');
        $this->patch(route('admin.pesanan.updateStatus', $pesanan), ['status_pesanan' => 'Dikirim'])
            ->assertSessionHasErrors('status_pesanan');
        $this->post(route('kurir.mulai', $kirim))->assertForbidden();

        $this->actingAs($pelanggan)->post(route('pelanggan.pengiriman.terima', $kirim))->assertSessionHasErrors('pengiriman');
        $this->get(route('kurir.tugas'))->assertForbidden();
        $this->actingAs($kurir)->get(route('kurir.dashboard'))->assertOk()->assertSee('Siap Dikirim');
        $this->get(route('kurir.tugas'))->assertOk()->assertSee($pesanan->kode_pesanan);
        $this->get(route('kurir.detail', $kirim))->assertOk()->assertSee('Alamat Pengujian')->assertSee('Mulai Pengiriman');
        $this->post(route('kurir.sampai', $kirim), [
            'penerima_aktual' => 'Penerima', 'foto' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasErrors('pengiriman');
        $this->assertSame([], Storage::disk('local')->allFiles('bukti-pengiriman'));
        $case = $pesanan->pembayaran->rekonsiliasiAktif()->create([
            'midtrans_order_id' => 'uji-pengiriman', 'status_penanganan' => 'menunggu',
            'midtrans_transaction_id' => 'verified-test', 'status_transaksi' => 'refund',
        ]);
        $this->post(route('kurir.mulai', $kirim))->assertSessionHasErrors('pengiriman');
        $this->assertSame(Pengiriman::SIAP, $kirim->fresh()->tahap());
        $case->forceFill(['status_penahanan' => 'dilepas'])->save();
        $token = Str::random(40);
        $this->app['env'] = 'local';
        $this->withSession(['_token' => $token])->post(route('kurir.mulai', $kirim))->assertStatus(419);
        $this->post(route('kurir.mulai', $kirim), ['_token' => $token])->assertSessionHasNoErrors();
        $this->app['env'] = 'testing';
        $this->post(route('kurir.mulai', $kirim))->assertSessionHasErrors('pengiriman');

        $this->actingAs($pelanggan)->get(route('pelanggan.pesanan.show', $pesanan))->assertOk()
            ->assertSee('Dalam Perjalanan')->assertDontSee('Ya, Pesanan Diterima');
        $this->actingAs($kurir)->post(route('kurir.kendala', $kirim), ['catatan' => 'Penerima belum di lokasi'])
            ->assertSessionHasNoErrors();
        $kendala = $kirim->kendala()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.pengiriman.tindak-lanjut', $kirim), $estimasi + [
            'kendala_id' => $kendala->id, 'penyelesaian' => 'Pengiriman dijadwalkan ulang', 'jadwalkan_ulang' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(Pengiriman::SIAP, $kirim->fresh()->tahap());
        $this->assertSame('Diproses', $pesanan->fresh()->status_pesanan);
        $this->actingAs($kurir)->post(route('kurir.mulai', $kirim))->assertSessionHasNoErrors();
        $this->post(route('kurir.sampai', $kirim))->assertSessionHasErrors(['penerima_aktual', 'foto']);
        $this->post(route('kurir.sampai', $kirim), [
            'penerima_aktual' => 'Penerima', 'foto' => UploadedFile::fake()->create('palsu.jpg', 1, 'text/plain'),
        ])->assertSessionHasErrors('foto');
        $bukti = fn () => ['penerima_aktual' => 'Penerima Aktual', 'foto' => UploadedFile::fake()->image('bukti.jpg')];
        $this->post(route('kurir.sampai', $kirim), $bukti())->assertSessionHasNoErrors();
        $this->post(route('kurir.sampai', $kirim), $bukti())->assertSessionHasErrors('pengiriman');
        $path = $kirim->fresh()->foto_penyerahan;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame([$path], Storage::disk('local')->allFiles('bukti-pengiriman'));
        $this->assertSame('Dikirim', $pesanan->fresh()->status_pesanan);
        $this->get(route('kurir.riwayat'))->assertOk()->assertSee($pesanan->kode_pesanan);
        $this->get(route('pengiriman.bukti', $kirim))->assertOk();
        $this->get('/storage/'.$path)->assertForbidden();

        $this->actingAs($orangLain)->get(route('pengiriman.bukti', $kirim))->assertNotFound();
        $this->get(route('pelanggan.pesanan.show', $pesanan))->assertNotFound();
        $this->post(route('pelanggan.pengiriman.terima', $kirim))->assertNotFound();
        $this->actingAs($pelanggan)->get(route('pengiriman.bukti', $kirim))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('pelanggan.pesanan.show', $pesanan))->assertOk()
            ->assertSee('Barang Telah Sampai')->assertSee('Penerima Aktual')->assertSee('Ya, Pesanan Diterima');
        $this->post(route('pelanggan.pengiriman.kendala', $kirim), ['catatan' => 'Jumlah barang kurang'])->assertSessionHasNoErrors();
        $this->post(route('pelanggan.pengiriman.terima', $kirim))->assertSessionHasErrors('pengiriman');
        $this->actingAs($admin)->patch(route('admin.pesanan.updateStatus', $pesanan), [
            'status_pesanan' => 'Selesai', 'konfirmasi_penerimaan' => 1,
        ])->assertSessionHasErrors('status_pesanan');
        $this->get(route('pengiriman.bukti', $kirim))->assertOk();
        $this->get(route('admin.pengiriman.show', $kirim))->assertOk()->assertSee('Penerima Aktual')->assertSee($kurir->name);
        $this->post(route('admin.pengiriman.tindak-lanjut', $kirim), [
            'kendala_id' => $kirim->kendala()->firstOrFail()->id, 'penyelesaian' => 'Jumlah diperiksa bersama penerima',
        ])->assertSessionHasNoErrors();
        $this->actingAs($pelanggan)->post(route('pelanggan.pengiriman.terima', $kirim))->assertSessionHasNoErrors();
        $this->post(route('pelanggan.pengiriman.terima', $kirim))->assertSessionHasErrors('pengiriman');
        $this->assertSame('Selesai', $pesanan->fresh()->status_pesanan);
        $this->assertSame(Pengiriman::DITERIMA, $kirim->fresh()->tahap());
        $this->assertNotNull($kirim->fresh()->waktu_selesai);
        $this->assertSame(7, $kirim->riwayat()->count());
        $this->assertSame($stokSebelum, $data['produk']->fresh()->stok);
        $this->assertSame($bayarSebelum, $pesanan->pembayaran->fresh()->getAttributes());
    }

    public function test_login_kurir_menggunakan_login_biasa_dan_akses_terbatas(): void
    {
        $kurir = $this->kurir();
        $this->get('/kurir')->assertRedirect('/login');
        $this->post('/login', ['email' => $kurir->email, 'password' => 'SandiUjiKurir123'])->assertRedirect('/kurir');
        $this->get('/login')->assertRedirect('/kurir');
        foreach (['/admin/dashboard', '/checkout', '/pesanan', '/akun', '/keranjang'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/kurir/profil')->assertOk()->assertSee($kurir->email)->assertSee('Konfirmasi Keluar');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_pesanan_historis_tidak_mendapat_penyerahan_fiktif(): void
    {
        $migration = require database_path('migrations/2026_11_26_000001_add_alur_kurir_toko.php');
        $migration->down();
        $data = $this->dataBelanja();
        $pesanan = $this->legacyOrder($data);
        $kirim = $pesanan->pengiriman;
        $pesanan->update(['status_pesanan' => 'Dikirim']);
        $kirim->update(['status_pengiriman' => 'Dikirim']);
        $this->get(route('pelanggan.pesanan.show', $pesanan))->assertOk()
            ->assertSee('Lacak Pengiriman')->assertDontSee('Barang Telah Sampai')->assertDontSee('Konfirmasi Penerimaan');
        $this->post(route('pelanggan.pengiriman.terima', $kirim))->assertServiceUnavailable();
        $migration->up();
        $this->post(route('pelanggan.pengiriman.terima', $kirim))->assertSessionHasErrors('pengiriman');
        $this->assertNull($kirim->fresh()->tahap_kurir);
        $this->assertSame('Dikirim', $kirim->fresh()->status_pengiriman);
        $this->assertSame(0, $kirim->riwayat()->count());
    }

    public function test_perintah_pembuatan_kurir_memerlukan_admin_dan_slot_tunggal(): void
    {
        $admin = $this->akunAdmin();
        $admin->update(['password' => 'SandiAdminUji123']);
        $this->artisan('kurir:buat')->expectsQuestion('Email admin yang mengotorisasi', $admin->email)
            ->expectsQuestion('Kata sandi admin', 'salah')->assertExitCode(1);
        $this->assertDatabaseMissing('users', ['slot_kurir' => 1]);
        $this->artisan('kurir:buat')->expectsQuestion('Email admin yang mengotorisasi', $admin->email)
            ->expectsQuestion('Kata sandi admin', 'SandiAdminUji123')->expectsQuestion('Nama kurir', 'Kurir Uji')
            ->expectsQuestion('Email baru untuk kurir', 'kurir@example.test')
            ->expectsQuestion('Kata sandi kurir', 'SandiKurirUji123')
            ->expectsQuestion('Ulangi kata sandi kurir', 'SandiKurirUji123')->assertExitCode(0);
        $kurir = User::where('email', 'kurir@example.test')->firstOrFail();
        $this->assertTrue($kurir->isKurir());
        $this->assertNotSame('SandiKurirUji123', $kurir->password);
        $this->artisan('kurir:buat')->expectsQuestion('Email admin yang mengotorisasi', $admin->email)
            ->expectsQuestion('Kata sandi admin', 'SandiAdminUji123')->assertExitCode(1);
        $this->assertSame(1, User::where('slot_kurir', 1)->count());
    }

    public function test_pemilik_menyelesaikan_pengiriman_lama_terverifikasi_tanpa_bukti_fiktif(): void
    {
        $data = $this->dataBelanja();
        $pesanan = $this->legacyOrder($data);
        $pengiriman = $pesanan->pengiriman;
        $dikirim = now()->subDays(2)->startOfSecond();
        $pesanan->update(['status_pesanan' => 'Dikirim']);
        $pengiriman->update(['status_pengiriman' => 'Dikirim', 'waktu_dikirim' => $dikirim]);
        $pesanan->pembayaran->forceFill([
            'gateway' => 'midtrans', 'status_pembayaran' => 'Berhasil',
            'midtrans_transaction_id' => 'legacy-verified', 'midtrans_status' => 'settlement',
            'waktu_pembayaran' => $dikirim->copy()->subDay(),
        ])->save();
        $this->actingAs(User::factory()->create())->post(route('pelanggan.pengiriman.terima', $pengiriman))->assertNotFound();
        $this->actingAs($data['user'])->get(route('pelanggan.pesanan.show', $pesanan))
            ->assertOk()->assertSee('Pesanan lama ini tercatat dikirim')->assertDontSee('Barang Telah Sampai');
        $this->post(route('pelanggan.pengiriman.terima', $pengiriman))->assertSessionHasNoErrors();
        $this->post(route('pelanggan.pengiriman.terima', $pengiriman))->assertSessionHasErrors('pengiriman');
        $pengiriman->refresh();
        $this->assertSame('Selesai', $pesanan->fresh()->status_pesanan);
        $this->assertSame(Pengiriman::DITERIMA, $pengiriman->tahap());
        $this->assertTrue($dikirim->equalTo($pengiriman->waktu_dikirim));
        $this->assertNull($pengiriman->foto_penyerahan);
        $this->assertNull($pengiriman->penerima_aktual);
        $this->assertNull($pengiriman->kurir_id);
        $this->assertSame([Pengiriman::DITERIMA], $pengiriman->riwayat()->pluck('status_baru')->all());
    }

    private function kurir(): User
    {
        $kurir = User::factory()->create(['password' => 'SandiUjiKurir123']);
        $kurir->forceFill(['slot_kurir' => 1])->save();

        return $kurir;
    }
}
