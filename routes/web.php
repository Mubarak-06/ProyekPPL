<?php

use App\Http\Controllers\Admin\ProdukController;//ini gsn mehubung akn route lwn controller produk
use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\PesananController;
use Illuminate\Support\Facades\Route;

//ini gsn halaman awal
Route::get('/', function () {
    return view('welcome');
});

//yg ini gsn halaman dashboard admin
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

/*ini gsn kelola produk di admin
/admin/produk = ini alamat halaman pengelolaan produk
ProdukController::class = ini gsn menentu akn controller yg diguna akn
index = gsn menjalan akan fungsi index()
admin.produk.index = nama route ny gsn dipanggil dari Blade*/
Route::get('/admin/produk', [ProdukController::class, 'index'])
    ->name('admin.produk.index');

Route::get('/admin/produk/tambah', [ProdukController::class, 'create'])
    ->name('admin.produk.create');

Route::post('/admin/produk/simpan', [ProdukController::class, 'store'])
    ->name('admin.produk.store');

Route::get('/admin/produk/dropdown/{kategoriId}',
    [ProdukController::class, 'getDropdownData'])
    ->whereNumber('kategoriId')
    ->name('admin.produk.dropdown');

Route::get('/admin/produk/{id}/edit', [ProdukController::class, 'edit'])
    ->whereNumber('id')
    ->name('admin.produk.edit');

Route::put('/admin/produk/{id}', [ProdukController::class, 'update'])
    ->whereNumber('id')
    ->name('admin.produk.update');

Route::delete('/admin/produk/{id}', [ProdukController::class, 'destroy'])
    ->whereNumber('id')
    ->name('admin.produk.destroy');

Route::delete('/admin/produk/gambar/{id}', [ProdukController::class, 'hapusGambar'])
    ->whereNumber('id')
    ->name('admin.produk.gambar.hapus');

Route::get('/admin/data-master', [DataMasterController::class, 'index'])
    ->name('admin.data-master.index');

Route::post('/admin/data-master/kategori', [DataMasterController::class, 'storeKategori'])
    ->name('admin.data-master.kategori.store');

Route::put('/admin/data-master/kategori/{id}', [DataMasterController::class, 'updateKategori'])
    ->whereNumber('id')
    ->name('admin.data-master.kategori.update');

Route::delete('/admin/data-master/kategori/{id}', [DataMasterController::class, 'destroyKategori'])
    ->whereNumber('id')
    ->name('admin.data-master.kategori.destroy');

Route::post('/admin/data-master/material', [DataMasterController::class, 'storeMaterial'])
    ->name('admin.data-master.material.store');

Route::delete('/admin/data-master/material/{id}', [DataMasterController::class, 'destroyMaterial'])
    ->whereNumber('id')
    ->name('admin.data-master.material.destroy');

Route::post('/admin/data-master/satuan', [DataMasterController::class, 'storeSatuan'])
    ->name('admin.data-master.satuan.store');

Route::delete('/admin/data-master/satuan/{id}', [DataMasterController::class, 'destroySatuan'])
    ->whereNumber('id')
    ->name('admin.data-master.satuan.destroy');

Route::get('/admin/pesanan', [PesananController::class, 'index'])
    ->name('admin.pesanan.index');

Route::get('/admin/pesanan/{id}', [PesananController::class, 'show'])
    ->name('admin.pesanan.show');

Route::patch('/admin/pesanan/{id}/status', [PesananController::class, 'updateStatus'])
    ->name('admin.pesanan.updateStatus');

Route::patch('/admin/pesanan/{id}/tolak', [PesananController::class, 'tolak'])
    ->name('admin.pesanan.tolak');