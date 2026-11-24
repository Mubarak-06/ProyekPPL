<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use Database\Seeders\ProdukContohBerandaSeeder;
use Illuminate\Support\Facades\DB;
use Tests\PelangganTestCase;

class BerandaProdukTest extends PelangganTestCase
{
    private function limaProduk(): array
    {
        $data = $this->dataBelanja();
        $data['produk']->update(['nama_produk' => 'Baut Hexagonal M8']);
        DB::table('material')->insert(['id' => 1, 'nama_material' => 'Besi/Baja']);
        foreach ([2 => 'Mur', 3 => 'Ring', 4 => 'Sekrup', 5 => 'Alat Teknik'] as $id => $nama) {
            DB::table('kategori')->insert(['id' => $id, 'nama_kategori' => $nama]);
            DB::table('kategori_satuan')->insert(['kategori_id' => $id, 'satuan_id' => 1]);
            if ($id < 5) {
                DB::table('kategori_material')->insert(['kategori_id' => $id, 'material_id' => 1]);
            }
        }
        $this->seed(ProdukContohBerandaSeeder::class);

        return $data;
    }

    public function test_seeder_menambah_empat_produk_tanpa_duplikat_atau_mengubah_produk_lama(): void
    {
        $data = $this->limaProduk();
        $lama = $data['produk']->fresh()->getAttributes();
        $this->seed(ProdukContohBerandaSeeder::class);
        $this->assertDatabaseCount('produk', 5);
        $this->assertSame($lama, $data['produk']->fresh()->getAttributes());
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Mur Hexagonal M8', 'harga' => 500, 'stok' => 100, 'berat' => 5]);
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Ring Plat M8', 'harga' => 300, 'stok' => 100, 'berat' => 2]);
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Sekrup Plus 4 × 20 mm', 'harga' => 250, 'stok' => 100, 'berat' => 3]);
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Kunci Pas 10 mm', 'harga' => 15000, 'stok' => 20, 'berat' => 100]);
        $response = $this->get('/')->assertOk()->assertSee('Pilihan Produk');
        $this->assertSame(5, substr_count($response->getContent(), '<article class="kartu-produk">'));
        foreach (Produk::all() as $produk) {
            $response->assertSee($produk->nama_produk)->assertSee(number_format($produk->harga, 0, ',', '.'));
        }
    }

    public function test_penjualan_selesai_tetap_diprioritaskan_dan_slot_diisi_hingga_lima(): void
    {
        $data = $this->limaProduk();
        $mur = Produk::where('nama_produk', 'Mur Hexagonal M8')->firstOrFail();
        $extra = Produk::create([
            'kode_barang' => 'TAMBAHAN', 'nama_produk' => 'Produk Tambahan', 'kategori_id' => 1,
            'satuan_id' => 1, 'berat' => 1, 'harga' => 1000, 'stok' => 10,
        ]);
        foreach (['Selesai' => $mur, 'Menunggu' => $extra] as $status => $produk) {
            $pesanan = Pesanan::create([
                'kode_pesanan' => 'UJI-'.$status, 'user_id' => $data['user']->id, 'alamat_id' => $data['alamat'],
                'subtotal' => $produk->harga * 10, 'ongkos_kirim' => 0,
                'total_pembayaran' => $produk->harga * 10, 'status_pesanan' => $status,
            ]);
            $pesanan->detailPesanan()->create([
                'produk_id' => $produk->id, 'nama_produk' => $produk->nama_produk,
                'harga_satuan' => $produk->harga, 'jumlah' => 10, 'subtotal' => $produk->harga * 10,
            ]);
        }
        $response = $this->get('/')->assertOk()->assertSee('Produk Terlaris');
        $response->assertViewHas('produk', fn ($produk) => $produk->count() === 5 && $produk->first()->id === $mur->id);
        $this->assertSame(5, substr_count($response->getContent(), '<article class="kartu-produk">'));
        $this->assertSame(1, substr_count($response->getContent(), 'aria-hidden="true"></i> Terlaris</span>'));
    }

    public function test_produk_tersedia_di_katalog_admin_dan_tombol_keranjang(): void
    {
        $this->limaProduk();
        $produk = Produk::all();
        $katalog = $this->get('/produk')->assertOk();
        foreach ($produk as $item) {
            $katalog->assertSee($item->nama_produk);
            $this->post('/keranjang/'.$item->id, ['jumlah' => 1])->assertSessionHas('keranjang.'.$item->id, 1);
        }
        $this->post('/keranjang/'.$produk->first()->id, ['jumlah' => 1, 'beli' => 1])
            ->assertRedirectContains('/checkout?langsung=')
            ->assertSessionHas('keranjang.'.$produk->first()->id, 1);
        $this->actingAs($this->akunAdmin());
        $admin = $this->get('/admin/produk')->assertOk();
        foreach ($produk as $item) {
            $admin->assertSee($item->nama_produk);
            $this->get('/admin/produk/'.$item->id.'/edit')->assertOk();
        }
    }
}
