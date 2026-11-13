<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Data contoh yang disetujui pengelola pada 12 November 2026. */
class ProdukContohBerandaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $satuanId = DB::table('satuan')->where('nama_satuan', 'Pcs')->value('id');
            $materialId = DB::table('material')->where('nama_material', 'Besi/Baja')->value('id');
            if (! $satuanId || ! $materialId) {
                throw new RuntimeException('Master Pcs dan Besi/Baja harus tersedia; tidak ada produk yang ditambahkan.');
            }

            $contoh = [
                ['nama_produk' => 'Mur Hexagonal M8', 'kategori' => 'Mur', 'ukuran' => 'M8', 'harga' => 500, 'stok' => 100, 'berat' => 5],
                ['nama_produk' => 'Ring Plat M8', 'kategori' => 'Ring', 'ukuran' => 'M8', 'harga' => 300, 'stok' => 100, 'berat' => 2],
                ['nama_produk' => 'Sekrup Plus 4 × 20 mm', 'kategori' => 'Sekrup', 'ukuran' => '4 × 20 mm', 'harga' => 250, 'stok' => 100, 'berat' => 3],
                ['nama_produk' => 'Kunci Pas 10 mm', 'kategori' => 'Alat Teknik', 'ukuran' => null, 'harga' => 15000, 'stok' => 20, 'berat' => 100],
            ];

            foreach ($contoh as $item) {
                $kategoriId = DB::table('kategori')->where('nama_kategori', $item['kategori'])->lockForUpdate()->value('id');
                if (! $kategoriId) {
                    throw new RuntimeException('Kategori '.$item['kategori'].' belum tersedia; transaksi dibatalkan.');
                }
                if (DB::table('produk')->where('nama_produk', $item['nama_produk'])->exists()) {
                    continue;
                }

                $pakaiMaterial = $item['kategori'] !== 'Alat Teknik';
                $satuanValid = DB::table('kategori_satuan')->where('kategori_id', $kategoriId)->where('satuan_id', $satuanId)->exists();
                $materialValid = ! $pakaiMaterial || DB::table('kategori_material')->where('kategori_id', $kategoriId)->where('material_id', $materialId)->exists();
                if (! $satuanValid || ! $materialValid) {
                    throw new RuntimeException('Relasi satuan/material kategori tidak sesuai; transaksi dibatalkan.');
                }

                $id = DB::table('produk')->insertGetId([
                    'kode_barang' => 'TMP-'.bin2hex(random_bytes(12)),
                    'nama_produk' => $item['nama_produk'],
                    'kategori_id' => $kategoriId,
                    'material_id' => $pakaiMaterial ? $materialId : null,
                    'satuan_id' => $satuanId,
                    'ukuran' => $item['ukuran'],
                    'berat' => $item['berat'],
                    'harga' => $item['harga'],
                    'stok' => $item['stok'],
                    'deskripsi' => null,
                    'gambar' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('produk')->where('id', $id)->update(['kode_barang' => 'BRG'.str_pad($id, 5, '0', STR_PAD_LEFT)]);
            }
        });
    }
}
