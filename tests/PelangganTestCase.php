<?php

namespace Tests;

use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class PelangganTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Pengujian pelanggan hanya boleh memakai SQLite dalam memori.');
        }

        foreach (['kategori' => 'nama_kategori', 'material' => 'nama_material', 'satuan' => 'nama_satuan', 'provinsi' => 'nama_provinsi'] as $nama => $kolom) {
            Schema::create($nama, function (Blueprint $table) use ($kolom) {
                $table->id();
                $table->string($kolom);
                $table->timestamps();
            });
        }
        foreach (['kota' => 'provinsi', 'kecamatan' => 'kota', 'kelurahan' => 'kecamatan'] as $nama => $induk) {
            Schema::create($nama, function (Blueprint $table) use ($nama, $induk) {
                $table->id();
                $table->foreignId($induk.'_id')->constrained($induk);
                $table->string('nama_'.$nama);
                $table->timestamps();
            });
        }
        foreach (['material', 'satuan'] as $nama) {
            Schema::create('kategori_'.$nama, function (Blueprint $table) use ($nama) {
                $table->id();
                $table->foreignId('kategori_id')->constrained('kategori');
                $table->foreignId($nama.'_id')->constrained($nama);
                $table->timestamps();
            });
        }
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('nama_produk');
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->foreignId('material_id')->nullable()->constrained('material');
            $table->foreignId('satuan_id')->constrained('satuan');
            $table->string('ukuran')->nullable();
            $table->decimal('berat', 10, 2);
            $table->decimal('harga', 15, 2);
            $table->unsignedInteger('stok');
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
        Schema::create('gambar_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk');
            $table->string('path_gambar');
            $table->integer('urutan');
            $table->timestamps();
        });
        Schema::create('alamat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('nama_penerima', 100);
            $table->string('nomor_telepon', 20);
            $table->text('alamat_lengkap');
            $table->foreignId('kelurahan_id')->constrained('kelurahan');
            $table->string('kode_pos', 5);
            $table->timestamps();
        });
        Schema::create('ongkir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->unique()->constrained('kelurahan');
            $table->decimal('jarak_km', 6, 2);
            $table->decimal('biaya_ongkir', 12, 2);
            $table->timestamps();
        });

        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    protected function akunAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    protected function dataBelanja(): array
    {
        DB::table('kategori')->insert(['id' => 1, 'nama_kategori' => 'Baut']);
        DB::table('satuan')->insert(['id' => 1, 'nama_satuan' => 'Pcs']);
        DB::table('provinsi')->insert(['id' => 1, 'nama_provinsi' => 'Provinsi Pengujian']);
        DB::table('kota')->insert(['id' => 1, 'provinsi_id' => 1, 'nama_kota' => 'Kota Pengujian']);
        DB::table('kecamatan')->insert(['id' => 1, 'kota_id' => 1, 'nama_kecamatan' => 'Kecamatan Pengujian']);
        DB::table('kelurahan')->insert(['id' => 1, 'kecamatan_id' => 1, 'nama_kelurahan' => 'Kelurahan Pengujian']);
        DB::table('ongkir')->insert(['kelurahan_id' => 1, 'jarak_km' => 2, 'biaya_ongkir' => 10000]);
        $user = User::factory()->create();
        $alamat = DB::table('alamat')->insertGetId([
            'user_id' => $user->id, 'nama_penerima' => 'Penerima Pengujian', 'nomor_telepon' => '081234567890',
            'alamat_lengkap' => 'Alamat Pengujian', 'kelurahan_id' => 1, 'kode_pos' => '70234',
        ]);
        $metode = DB::table('metode_pembayaran')->insertGetId([
            'jenis_metode' => 'transfer_bank', 'nama_penyedia' => 'BCA', 'status' => 'aktif',
            'nomor_rekening' => '1234567890', 'nama_pemilik' => 'Pemilik Pengujian',
        ]);
        $produk = Produk::create([
            'kode_barang' => 'UJI-001', 'nama_produk' => 'Baut Pengujian', 'kategori_id' => 1,
            'satuan_id' => 1, 'berat' => 1, 'harga' => '1250.25', 'stok' => 10,
        ]);

        return compact('user', 'alamat', 'metode', 'produk');
    }
}
