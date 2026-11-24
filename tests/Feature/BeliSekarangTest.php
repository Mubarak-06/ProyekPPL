<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Services\MidtransSnapService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Tests\PelangganTestCase;

class BeliSekarangTest extends PelangganTestCase
{
    public function test_pembelian_langsung_berlanjut_setelah_login_dan_memakai_snap(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $lain = $this->produkLain($produk);
        $keranjang = [$produk->id => 9, $lain->id => 2];
        $pilihan = [$lain->id];
        $data['user']->update(['password' => Hash::make('kata-sandi-uji')]);
        $snapUrl = 'https://app.sandbox.midtrans.com/snap/v4/redirection/beli-langsung';
        $this->partialMock(MidtransSnapService::class)->shouldReceive('requestSnapToken')->once()
            ->andReturn([
                'status' => 'ready', 'token' => 'snap-uji', 'redirect_url' => $snapUrl,
                'snap_token_expires_at' => CarbonImmutable::now()->addMinutes(10),
            ]);

        $mulai = $this->withSession(['keranjang' => $keranjang, 'keranjang_pilihan' => $pilihan])
            ->post('/keranjang/'.$produk->id, ['jumlah' => 3, 'beli' => 1])
            ->assertRedirectContains('/checkout?langsung=')
            ->assertSessionHas('keranjang', $keranjang)->assertSessionHas('keranjang_pilihan', $pilihan);
        $url = $mulai->headers->get('Location');
        $this->get($url)->assertRedirect('/login')->assertSessionHas('url.intended', $url);
        $this->post('/login', ['email' => $data['user']->email, 'password' => 'kata-sandi-uji'])->assertRedirect($url);
        $checkout = $this->get($url.'&alamat_id='.$data['alamat'])->assertOk()
            ->assertSee('Keranjang (2)')->assertDontSee($lain->nama_produk)
            ->assertViewHas('keranjang', [$produk->id => 3])->assertViewHas('subtotal', 3750);
        $this->get('/keranjang')->assertViewHas('keranjang', $keranjang);
        $this->get('/checkout')->assertViewHas('keranjang', [$lain->id => 2]);
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertSame(10, $produk->fresh()->stok);

        $form = $this->formCheckout($url);
        $this->post('/checkout', $form)->assertStatus(303)->assertRedirect($snapUrl)
            ->assertSessionHas('keranjang', $keranjang)->assertSessionHas('keranjang_pilihan', $pilihan)
            ->assertSessionMissing('checkout_langsung');
        $this->post('/checkout', $form)->assertStatus(303)->assertRedirect($snapUrl);
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertDatabaseCount('detail_pesanan', 1);
        $this->assertDatabaseHas('detail_pesanan', ['produk_id' => $produk->id, 'jumlah' => 3, 'subtotal' => 3750]);
        $this->assertDatabaseHas('pembayaran', ['gateway' => 'midtrans', 'jumlah_pembayaran' => 13750]);
        $this->assertDatabaseHas('pengiriman', ['nama_penerima' => 'Penerima Pengujian', 'ongkos_kirim' => 10000]);
        $this->assertSame(7, $produk->fresh()->stok);
        $this->assertSame(10, $lain->fresh()->stok);
    }

    public function test_badge_menghitung_item_unik_setelah_setiap_perubahan(): void
    {
        $produk = $this->dataBelanja()['produk'];
        $lain = $this->produkLain($produk);
        $path = '/keranjang/'.$produk->id;

        $this->post($path, ['jumlah' => 1]);
        $this->get('/')->assertSee('Keranjang (1)');
        $this->post($path, ['jumlah' => 4])->assertSessionHas('keranjang', [$produk->id => 5]);
        $this->get('/produk')->assertSee('Keranjang (1)');
        $this->post('/keranjang/'.$lain->id, ['jumlah' => 2]);
        $this->get('/produk/'.$produk->id)->assertSee('Keranjang (2)');
        $this->put($path, ['jumlah' => 10]);
        $this->get('/keranjang')->assertSee('Keranjang (2)')->assertViewHas('subtotal', 15000.0);
        $this->delete($path);
        $this->get('/keranjang')->assertSee('Keranjang (1)');
        $this->post('/keranjang/pilihan', ['pilihan' => [$lain->id], 'aksi' => 'hapus']);
        $this->get('/keranjang')->assertSee('Keranjang (0)');
    }

    public function test_alamat_baru_mengembalikan_pelanggan_ke_pembelian_langsung_dan_stok_diperiksa_ulang(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $path = '/keranjang/'.$produk->id;
        $this->actingAs($data['user'])->from('/produk/'.$produk->id);
        $this->post($path, ['jumlah' => 11, 'beli' => 1])->assertSessionHasErrors('jumlah')
            ->assertSessionMissing('checkout_langsung')->assertSessionMissing('keranjang');

        $response = $this->post($path, ['jumlah' => 3, 'beli' => 1])->assertRedirectContains('/checkout?langsung=');
        $checkout = $this->get($response->headers->get('Location'))->assertOk();
        $parameter = ['langsung' => $checkout->viewData('langsung')];
        $formUrl = route('pelanggan.alamat.store', $parameter);
        $this->get(route('pelanggan.alamat.create', $parameter))->assertOk()->assertSee($formUrl);
        $alamatBaru = $data['alamat'] + 1;
        $url = route('pelanggan.checkout', $parameter + ['alamat_id' => $alamatBaru]);
        $this->post($formUrl, [
            'nama_penerima' => 'Penerima Baru', 'nomor_telepon' => '081234567890',
            'alamat_lengkap' => 'Alamat Baru', 'kelurahan_id' => 1, 'kode_pos' => '70234',
        ])->assertRedirect($url);
        $this->get($url)->assertViewHas('alamatDipilih', fn ($alamat) => $alamat->id === $alamatBaru);
        $form = $this->formCheckout($url);
        $produk->update(['stok' => 2]);
        $this->post('/checkout', $form)->assertSessionHasErrors('keranjang')->assertSessionMissing('keranjang');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    private function produkLain(Produk $produk): Produk
    {
        $lain = $produk->replicate();
        $lain->fill(['kode_barang' => 'UJI-002', 'nama_produk' => 'Produk Lain Pengujian'])->save();

        return $lain;
    }
}
