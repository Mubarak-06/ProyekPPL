<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\PelangganTestCase;

class AdminStokTest extends PelangganTestCase
{
    public function test_checkout_membuat_form_stok_lama_ditolak_dan_form_baru_dapat_disimpan(): void
    {
        $data = $this->dataBelanja();
        $produk = $data['produk'];
        $admin = $this->akunAdmin();
        $editUrl = route('admin.produk.edit', $produk->id);
        $updateUrl = route('admin.produk.update', $produk->id);
        DB::table('kategori')->where('id', $produk->kategori_id)->update(['nama_kategori' => 'Peralatan']);
        DB::table('kategori_satuan')->insert(['kategori_id' => $produk->kategori_id, 'satuan_id' => $produk->satuan_id]);
        $form = $produk->only(['nama_produk', 'kategori_id', 'satuan_id', 'berat', 'harga', 'stok'])
            + ['stok_awal' => 10, 'deskripsi' => 'Deskripsi dari form lama'];
        $this->actingAs($admin)->get($editUrl)->assertOk()->assertSee('name="stok_awal" value="10"', false);

        $this->actingAs($data['user'])->withSession([
            'keranjang' => [$produk->id => 2],
        ]);
        $this->post('/checkout', $this->formCheckout())->assertSessionHasNoErrors()->assertRedirect('/pesanan/1/pembayaran');
        $this->assertSame(8, $produk->fresh()->stok);

        $this->actingAs($admin)->put($updateUrl, $form)->assertRedirect($editUrl)->assertSessionHasErrors('stok');
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'stok' => 8, 'deskripsi' => $produk->deskripsi]);
        $this->get($editUrl)->assertOk()->assertSee('Stok berubah sejak formulir dibuka')
            ->assertSee('name="stok_awal" value="8"', false);
        $this->put($updateUrl, ['stok' => 12] + $form)->assertSessionHasErrors('stok');
        $this->assertSame(8, $produk->fresh()->stok);

        $this->put($updateUrl, ['stok_awal' => 8, 'stok' => 12] + $form)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.produk.index'));
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'stok' => 12, 'deskripsi' => $form['deskripsi']]);
        $this->assertDatabaseCount('pesanan', 1);
    }
}
