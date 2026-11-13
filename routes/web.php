<?php

use App\Http\Controllers\Admin\ProdukController;//ini gsn mehubung akn route lwn controller produk
use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\PengirimanController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Pelanggan\ProdukController as ProdukPelangganController;
use App\Http\Middleware\BahasaPelanggan;
use Illuminate\Support\Facades\Route;

//ini gsn halaman awal
Route::middleware(BahasaPelanggan::class)->group(function () {
    Route::get('/', [ProdukPelangganController::class, 'beranda'])
        ->name('pelanggan.beranda');

    require __DIR__.'/pelanggan.php';
});

// Seluruh endpoint admin, termasuk perubahan data dan ekspor, wajib memiliki izin.
Route::middleware(['auth', \App\Http\Middleware\PastikanAdmin::class])->group(function () {
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

Route::get(
    '/admin/produk/dropdown/{kategoriId}',
    [ProdukController::class, 'getDropdownData']
)
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

Route::get('/admin/pengiriman', [PengirimanController::class, 'index'])
    ->name('admin.pengiriman.index');

Route::get('/admin/pengiriman/data-master', [PengirimanController::class, 'dataMaster'])
    ->name('admin.pengiriman.dataMaster');

Route::post(
    '/admin/pengiriman/data-master/provinsi',
    [PengirimanController::class, 'storeProvinsi']
)->name('admin.pengiriman.provinsi.store');

Route::put(
    '/admin/pengiriman/data-master/provinsi/{id}',
    [PengirimanController::class, 'updateProvinsi']
)->name('admin.pengiriman.provinsi.update');

Route::delete(
    '/admin/pengiriman/data-master/provinsi/{id}',
    [PengirimanController::class, 'destroyProvinsi']
)->name('admin.pengiriman.provinsi.destroy');

Route::post(
    '/admin/pengiriman/data-master/kota',
    [PengirimanController::class, 'storeKota']
)->name('admin.pengiriman.kota.store');

Route::put(
    '/admin/pengiriman/data-master/kota/{id}',
    [PengirimanController::class, 'updateKota']
)->name('admin.pengiriman.kota.update');

Route::delete(
    '/admin/pengiriman/data-master/kota/{id}',
    [PengirimanController::class, 'destroyKota']
)->name('admin.pengiriman.kota.destroy');

Route::post(
    '/admin/pengiriman/data-master/kecamatan',
    [PengirimanController::class, 'storeKecamatan']
)->name('admin.pengiriman.kecamatan.store');

Route::put(
    '/admin/pengiriman/data-master/kecamatan/{id}',
    [PengirimanController::class, 'updateKecamatan']
)->name('admin.pengiriman.kecamatan.update');

Route::delete(
    '/admin/pengiriman/data-master/kecamatan/{id}',
    [PengirimanController::class, 'destroyKecamatan']
)->name('admin.pengiriman.kecamatan.destroy');

Route::post(
    '/admin/pengiriman/data-master/kelurahan',
    [PengirimanController::class, 'storeKelurahan']
)->name('admin.pengiriman.kelurahan.store');

Route::put(
    '/admin/pengiriman/data-master/kelurahan/{id}',
    [PengirimanController::class, 'updateKelurahan']
)->name('admin.pengiriman.kelurahan.update');

Route::delete(
    '/admin/pengiriman/data-master/kelurahan/{id}',
    [PengirimanController::class, 'destroyKelurahan']
)->name('admin.pengiriman.kelurahan.destroy');

Route::post(
    '/admin/pengiriman/data-master/ongkir',
    [PengirimanController::class, 'storeOngkir']
)->name('admin.pengiriman.ongkir.store');

Route::put(
    '/admin/pengiriman/data-master/ongkir/{id}',
    [PengirimanController::class, 'updateOngkir']
)->name('admin.pengiriman.ongkir.update');

Route::delete(
    '/admin/pengiriman/data-master/ongkir/{id}',
    [PengirimanController::class, 'destroyOngkir']
)->name('admin.pengiriman.ongkir.destroy');

Route::get('/admin/pengiriman/{id}', [PengirimanController::class, 'show'])
    ->name('admin.pengiriman.show');

Route::get('/admin/pembayaran', [PembayaranController::class, 'index'])
    ->name('admin.pembayaran.index');

Route::get('/admin/pembayaran/metode', [PembayaranController::class, 'metode'])
    ->name('admin.pembayaran.metode');

Route::get('/admin/pembayaran/metode/{id}/edit', [PembayaranController::class, 'editMetode'])
    ->name('admin.pembayaran.metode.edit');

Route::put('/admin/pembayaran/metode/{id}', [PembayaranController::class, 'updateMetode'])
    ->name('admin.pembayaran.metode.update');

Route::delete('/admin/pembayaran/metode/{id}', [PembayaranController::class, 'destroyMetode'])
    ->name('admin.pembayaran.metode.destroy');

Route::get('/admin/pembayaran/{id}', [PembayaranController::class, 'show'])
    ->name('admin.pembayaran.show');

Route::post('/admin/pembayaran/metode', [PembayaranController::class, 'storeMetode'])
    ->name('admin.pembayaran.metode.store');

Route::get('/admin/laporan', [LaporanController::class, 'index'])
    ->name('admin.laporan.index');

Route::get('/admin/laporan/export/pdf', [LaporanController::class, 'exportPdf'])
    ->name('admin.laporan.export.pdf');

Route::get('/admin/laporan/export/excel', [LaporanController::class, 'exportExcel'])
    ->name('admin.laporan.export.excel');
});
