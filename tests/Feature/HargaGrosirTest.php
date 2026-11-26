<?php

namespace Tests\Feature;

use App\Services\MidtransSnapService;
use Illuminate\Support\Facades\DB;
use Tests\PelangganTestCase;

class HargaGrosirTest extends PelangganTestCase
{
    public function test_admin_menyimpan_mengubah_dan_memvalidasi_tingkatan_grosir(): void
    {
        $produk = $this->dataBelanja()['produk'];
        DB::table('kategori')->where('id', $produk->kategori_id)->update(['nama_kategori' => 'Peralatan']);
        DB::table('kategori_satuan')->insert(['kategori_id' => $produk->kategori_id, 'satuan_id' => $produk->satuan_id]);
        $this->actingAs($this->akunAdmin());
        $this->get(route('admin.produk.create'))->assertOk()->assertSee('+ Tambah Harga Grosir')
            ->assertSee('title="Hapus harga grosir" aria-label="Hapus harga grosir"', false);
        $form = $produk->only(['nama_produk', 'kategori_id', 'satuan_id', 'berat', 'stok'])
            + ['stok_awal' => 10, 'harga' => 65000, 'grosir_aktif' => 1];
        $tiers = [
            ['min_qty' => 12, 'harga_satuan' => 60000],
            ['min_qty' => 6, 'harga_satuan' => 63000],
        ];
        $store = route('admin.produk.store');
        $this->post($store, $form)->assertSessionHasErrors('grosir');
        foreach ([
            [[['min_qty' => 1, 'harga_satuan' => 60000]], 'grosir.0.min_qty'],
            [[['min_qty' => 2.5, 'harga_satuan' => 60000]], 'grosir.0.min_qty'],
            [[['min_qty' => 6, 'harga_satuan' => 0]], 'grosir.0.harga_satuan'],
            [[['min_qty' => 6, 'harga_satuan' => 63000.5]], 'grosir.0.harga_satuan'],
            [[['min_qty' => 6, 'harga_satuan' => 65000]], 'grosir'],
            [[$tiers[1], ['min_qty' => 6, 'harga_satuan' => 60000]], 'grosir.0.min_qty'],
            [[$tiers[1], ['min_qty' => 12, 'harga_satuan' => 64000]], 'grosir'],
        ] as [$grosir, $error]) {
            $this->post($store, $form + ['grosir' => $grosir])->assertSessionHasErrors($error);
        }
        $this->post($store, $form + ['grosir' => $tiers])->assertSessionHasNoErrors();
        $id = DB::table('produk')->max('id');
        $this->assertDatabaseHas('harga_grosir', ['produk_id' => $id, 'min_qty' => 6, 'max_qty' => 11]);
        $this->assertDatabaseHas('harga_grosir', ['produk_id' => $id, 'min_qty' => 12, 'max_qty' => null]);
        $this->get(route('admin.produk.edit', $id))->assertOk()->assertSee('63000');
        $update = route('admin.produk.update', $id);
        $this->put($update, ['harga' => 62000, 'grosir' => $tiers] + $form)->assertSessionHasErrors('grosir');
        $this->assertDatabaseHas('produk', ['id' => $id, 'harga' => 65000, 'grosir_aktif' => 1]);

        $this->put($update, ['grosir_aktif' => 0, 'grosir' => [['min_qty' => 7, 'harga_satuan' => 61500]]] + $form)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('produk', ['id' => $id, 'grosir_aktif' => 0]);
        $this->assertDatabaseCount('harga_grosir', 1);
        $this->assertDatabaseHas('harga_grosir', ['produk_id' => $id, 'min_qty' => 7, 'max_qty' => null, 'harga_satuan' => 61500]);
        $this->put($update, ['grosir_aktif' => 0] + $form)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('harga_grosir', 0);
    }

    public function test_label_dan_harga_keranjang_mengikuti_tingkatan_per_produk(): void
    {
        $produk = $this->dataBelanja()['produk'];
        $lain = $produk->replicate()->fill(['kode_barang' => 'UJI-LAIN', 'nama_produk' => 'Produk Lain']);
        $lain->save();
        $this->aturTingkatan($produk->id);
        $produk->update(['stok' => 20, 'harga' => 65000]);

        foreach (['/', '/produk', '/produk?q=Baut', '/produk?kategori=1', '/produk/'.$produk->id] as $url) {
            $this->get($url)->assertOk()->assertSee('>Grosir</small>', false)->assertSee('65.000')->assertDontSee('63.000');
        }

        $path = '/keranjang/'.$produk->id;
        $this->post($path, ['jumlah' => 5])->assertSessionHasNoErrors();
        $this->post('/keranjang/'.$lain->id, ['jumlah' => 2])->assertSessionHasNoErrors();
        $this->get('/keranjang')->assertViewHas('subtotalPilihan', 327500.0)->assertDontSee('Hemat Rp');
        $this->post($path, ['jumlah' => 1])->assertSessionHas('keranjang', [$produk->id => 6, $lain->id => 2]);
        $this->get('/keranjang')->assertSee('Hemat Rp12.000')->assertSee('mulai 12 barang')
            ->assertSee('<del>Rp65.000</del>', false)->assertViewHas('subtotalPilihan', 380500.0);

        foreach ([11 => 63000, 12 => 60000, 5 => '65000.00'] as $jumlah => $harga) {
            $this->put($path, ['jumlah' => $jumlah])->assertSessionHasNoErrors();
            $this->get('/keranjang')->assertViewHas('hargaProduk', fn ($items) => $items[$produk->id]['harga_satuan'] === $harga);
        }

        $produk->update(['grosir_aktif' => false]);
        $this->put($path, ['jumlah' => 12])->assertSessionHasNoErrors();
        $this->get('/keranjang')->assertDontSee('Hemat Rp');
        $this->get('/produk/'.$produk->id)->assertDontSee('>Grosir</small>', false);
    }

    public function test_checkout_keranjang_hanya_menghitung_pilihan_dan_meminta_konfirmasi_ulang(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $lain = $produk->replicate()->fill(['kode_barang' => 'UJI-LAIN']);
        $lain->save();
        $this->aturTingkatan($produk->id);
        $this->actingAs($data['user'])->withSession([
            'keranjang' => [$produk->id => 6, $lain->id => 2],
            'keranjang_pilihan' => [$produk->id],
        ]);
        $this->get('/checkout')->assertViewHas('subtotal', 390000)->assertViewHas('potongan', 12000)
            ->assertViewHas('total', 388000);
        $form = $this->formCheckout();
        DB::table('harga_grosir')->where('produk_id', $produk->id)->where('min_qty', 6)->update(['harga_satuan' => 62000]);
        $this->post('/checkout', $form)->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertSame(10, $produk->fresh()->stok);

        $this->partialMock(MidtransSnapService::class)->shouldReceive('requestSnapToken')->once()
            ->withArgs(function ($order, $payment) {
                $payload = (new MidtransSnapService)->buildTransactionParameters($order, $payment);
                $this->assertSame(382000, $payload['transaction_details']['gross_amount']);

                return true;
            })->andReturn(['status' => 'unknown']);
        $formBaru = $this->formCheckout();
        $this->post('/checkout', $formBaru + ['harga_satuan' => 1, 'subtotal' => 1, 'ongkos_kirim' => 0, 'total_pembayaran' => 1])
            ->assertSessionHasNoErrors()->assertSessionHas('keranjang', [$lain->id => 2]);
        $this->assertDatabaseCount('detail_pesanan', 1);
        $this->assertDatabaseHas('detail_pesanan', ['produk_id' => $produk->id, 'jumlah' => 6, 'harga_satuan' => 62000, 'subtotal' => 372000]);
        $this->assertDatabaseHas('pembayaran', ['jumlah_pembayaran' => 382000]);
    }

    public function test_beli_sekarang_memakai_jumlah_langsung_dan_menyimpan_harga_aktual(): void
    {
        $data = $this->dataBelanja();
        $id = $data['produk']->id;
        $this->aturTingkatan($id);
        $this->actingAs($data['user'])->withSession(['keranjang' => [$id => 2]]);
        $response = $this->post('/keranjang/'.$id, ['jumlah' => 6, 'beli' => 1])
            ->assertRedirectContains('/checkout?langsung=')->assertSessionHas('keranjang', [$id => 2]);
        $url = $response->headers->get('Location');
        $this->get($url)->assertViewHas('total', 388000);
        $this->post('/checkout', $this->formCheckout($url))->assertSessionHasNoErrors()
            ->assertSessionHas('keranjang', [$id => 2]);
        DB::table('harga_grosir')->update(['harga_satuan' => 59000]);
        $this->assertDatabaseHas('detail_pesanan', ['jumlah' => 6, 'harga_satuan' => 63000]);
    }

    private function aturTingkatan(int $id): void
    {
        DB::table('produk')->where('id', $id)->update(['grosir_aktif' => true, 'harga' => 65000]);
        DB::table('harga_grosir')->insert([
            ['produk_id' => $id, 'min_qty' => 6, 'max_qty' => 11, 'harga_satuan' => 63000],
            ['produk_id' => $id, 'min_qty' => 12, 'max_qty' => null, 'harga_satuan' => 60000],
        ]);
    }
}
