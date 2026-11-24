<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\PelangganTestCase;

class CheckoutRingkasanTest extends PelangganTestCase
{
    public static function perubahanRingkasan(): array
    {
        return [
            'harga' => ['harga', 14000],
            'ongkir' => ['ongkir', 14500],
            'kuantitas dari tab lain' => ['jumlah', 13750],
            'pilihan produk dari tab lain' => ['produk', 11000],
            'atribut variasi' => ['ukuran', 12500],
            'alamat tersunting' => ['alamat', 12500],
        ];
    }

    #[DataProvider('perubahanRingkasan')]
    public function test_ringkasan_yang_berubah_harus_ditinjau_ulang(string $perubahan, int $total): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $this->actingAs($data['user'])->withSession(['keranjang' => [$produk->id => 2]]);
        $form = $this->formCheckout();
        switch ($perubahan) {
            case 'harga':
                $produk->update(['harga' => '2000.00']);
                break;
            case 'ongkir':
                DB::table('ongkir')->update(['biaya_ongkir' => 12000]);
                break;
            case 'jumlah':
                $this->put('/keranjang/'.$produk->id, ['jumlah' => 3])->assertSessionHasNoErrors();
                break;
            case 'produk':
                $lain = $produk->replicate()->fill(['kode_barang' => 'UJI-002', 'harga' => '1000.00']);
                $lain->save();
                $this->post('/keranjang/'.$lain->id, ['jumlah' => 1])->assertSessionHasNoErrors();
                $this->post('/keranjang/pilihan', ['pilihan' => [$lain->id]])->assertSessionHasNoErrors();
                break;
            case 'ukuran':
                $produk->update(['ukuran' => 'M12']);
                break;
            case 'alamat':
                DB::table('alamat')->where('id', $data['alamat'])->update(['alamat_lengkap' => 'Alamat baru pengujian']);
                break;
        }

        $revisi = $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->assertBelumMemesan();
        $this->assertSame(10, $produk->fresh()->stok);
        $url = $revisi->headers->get('Location');
        $this->get($url)->assertViewHas('total', $total)->assertSee('konfirmasi ulang');
        $formBaru = $this->formCheckout($url);
        $this->assertNotSame($form['checkout_token'], $formBaru['checkout_token']);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->assertBelumMemesan();
        $this->post('/checkout', $formBaru)->assertSessionHasNoErrors()->assertRedirect('/pesanan/1/pembayaran');
        $this->assertDatabaseHas('pembayaran', ['jumlah_pembayaran' => $total]);
    }

    public function test_produk_hilang_setelah_ringkasan_ditampilkan(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $this->actingAs($data['user'])->withSession(['keranjang' => [$produk->id => 1]]);
        $form = $this->formCheckout();
        $produk->delete();
        $response = $this->post('/checkout', $form)->assertSessionHasErrors('keranjang');
        $this->get($response->headers->get('Location'))->assertRedirect('/keranjang')->assertSessionHasErrors('keranjang');
        $this->assertBelumMemesan();
    }

    public function test_token_tidak_valid_atau_kedaluwarsa_ditolak_tetapi_retry_pesanan_sah_idempoten(): void
    {
        $data = $this->dataBelanja();
        $this->actingAs($data['user'])->withSession(['keranjang' => [$data['produk']->id => 2]]);
        $form = $this->formCheckout();
        $this->assertSame($form, $this->formCheckout());
        $this->post('/checkout', ['checkout_token' => str_repeat('x', 40)] + $form)->assertSessionHasErrors('checkout');
        $this->travel(31)->minutes();
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->assertBelumMemesan();

        $formBaru = $this->formCheckout();
        $this->assertNotSame($form['checkout_token'], $formBaru['checkout_token']);
        $this->post('/checkout', $formBaru)->assertSessionHasNoErrors()->assertRedirect('/pesanan/1/pembayaran');
        $this->travel(31)->minutes();
        $this->post('/checkout', $formBaru)->assertRedirect('/pesanan/1/pembayaran');
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertSame(8, $data['produk']->fresh()->stok);
    }

    public function test_ringkasan_beli_sekarang_dan_keranjang_tetap_terpisah_di_beberapa_tab(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $path = '/keranjang/'.$produk->id;
        $this->actingAs($data['user'])->withSession(['keranjang' => [$produk->id => 5]]);
        $formKeranjang = $this->formCheckout();
        $langsung = $this->post($path, ['jumlah' => 2, 'beli' => 1])->assertRedirectContains('/checkout?langsung=');
        $formLangsung = $this->formCheckout($langsung->headers->get('Location'));
        $this->assertNotSame($formKeranjang['checkout_token'], $formLangsung['checkout_token']);
        $this->put($path, ['jumlah' => 6])->assertSessionHasNoErrors();
        $this->post('/checkout', $formKeranjang)->assertSessionHasErrors('checkout');
        $this->assertBelumMemesan();
        $this->post('/checkout', $formLangsung)->assertSessionHasNoErrors()
            ->assertSessionHas('keranjang', [$produk->id => 6]);
        $this->assertDatabaseHas('detail_pesanan', ['produk_id' => $produk->id, 'jumlah' => 2]);
    }

    public function test_nominal_historis_pecahan_ditolak_meski_total_akhir_bulat(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $this->actingAs($data['user'])->withSession(['keranjang' => [$produk->id => 2]]);
        $form = $this->formCheckout();
        $produk->update(['harga' => '1250.50']);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->get('/checkout')->assertRedirect('/keranjang')->assertSessionHasErrors('checkout');
        $this->assertSame('1250.50', $produk->fresh()->harga);
        $produk->update(['harga' => '1250.00']);

        $form = $this->formCheckout();
        DB::table('ongkir')->update(['biaya_ongkir' => '10000.50']);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->get('/checkout')->assertRedirect('/keranjang')->assertSessionHasErrors('checkout');
        $this->assertDatabaseHas('ongkir', ['biaya_ongkir' => 10000.50]);
        $this->assertBelumMemesan();
    }

    public function test_admin_menolak_harga_dan_ongkir_pecahan_saat_tambah_atau_edit(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $this->actingAs($this->akunAdmin());
        DB::table('kategori')->where('id', $produk->kategori_id)->update(['nama_kategori' => 'Peralatan']);
        DB::table('kategori_satuan')->insert(['kategori_id' => $produk->kategori_id, 'satuan_id' => $produk->satuan_id]);
        $form = $produk->only(['nama_produk', 'kategori_id', 'satuan_id', 'berat', 'stok']) + ['stok_awal' => 10];
        $this->post(route('admin.produk.store'), ['harga' => '1250.50'] + $form)->assertSessionHasErrors('harga');
        $this->put(route('admin.produk.update', $produk->id), ['harga' => '1250.50'] + $form)->assertSessionHasErrors('harga');
        $this->assertDatabaseCount('produk', 1);
        $this->assertSame('1250.00', $produk->fresh()->harga);
        $this->put(route('admin.produk.update', $produk->id), ['harga' => '1500.00'] + $form)->assertSessionHasNoErrors();
        $this->assertSame('1500.00', $produk->fresh()->harga);

        $ongkir = DB::table('ongkir')->sole();
        $formOngkir = ['kelurahan_id' => $ongkir->kelurahan_id, 'jarak_km' => 2];
        $this->post(route('admin.pengiriman.ongkir.store'), ['biaya_ongkir' => '12000.50'] + $formOngkir)
            ->assertSessionHasErrors('biaya_ongkir');
        $this->put(route('admin.pengiriman.ongkir.update', $ongkir->id), ['biaya_ongkir' => '12000.50'] + $formOngkir)
            ->assertSessionHasErrors('biaya_ongkir');
        $this->assertDatabaseHas('ongkir', ['id' => $ongkir->id, 'biaya_ongkir' => 10000]);
        $this->put(route('admin.pengiriman.ongkir.update', $ongkir->id), ['biaya_ongkir' => '12000.00'] + $formOngkir)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ongkir', ['id' => $ongkir->id, 'biaya_ongkir' => 12000]);
    }

    private function assertBelumMemesan(): void
    {
        foreach (['pesanan', 'detail_pesanan', 'pembayaran', 'pengiriman'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
