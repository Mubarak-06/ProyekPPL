<?php

use App\Http\Controllers\OperasionalPengirimanController as Pengiriman;
use App\Http\Middleware\LarangKurir;
use App\Http\Middleware\PastikanAdmin;
use App\Http\Middleware\PastikanKurir;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', PastikanKurir::class])->prefix('kurir')->name('kurir.')->group(function () {
    Route::get('/', [Pengiriman::class, 'dashboard'])->name('dashboard');
    Route::get('/tugas', [Pengiriman::class, 'daftar'])->name('tugas');
    Route::get('/riwayat', [Pengiriman::class, 'daftar'])->name('riwayat');
    Route::view('/profil', 'kurir.profil')->name('profil');
    Route::get('/tugas/{id}', [Pengiriman::class, 'detail'])->whereNumber('id')->name('detail');
    foreach (['mulai', 'sampai', 'kendala'] as $aksi) {
        Route::post("/tugas/{id}/{$aksi}", [Pengiriman::class, 'ubah'])->whereNumber('id')
            ->defaults('aksi', $aksi)->name($aksi);
    }
});

Route::middleware(['auth', PastikanAdmin::class])->prefix('admin/pengiriman')->name('admin.pengiriman.')->group(function () {
    foreach (['siapkan', 'tindak-lanjut'] as $aksi) {
        Route::post("/{id}/{$aksi}", [Pengiriman::class, 'ubah'])->whereNumber('id')
            ->defaults('aksi', $aksi)->name($aksi);
    }
});

Route::middleware(['auth', LarangKurir::class])->prefix('pesanan/pengiriman')->name('pelanggan.pengiriman.')->group(function () {
    foreach (['terima', 'kendala'] as $aksi) {
        Route::post("/{id}/{$aksi}", [Pengiriman::class, 'ubah'])->whereNumber('id')
            ->defaults('aksi', $aksi)->name($aksi);
    }
});

Route::get('/bukti-pengiriman/{id}', [Pengiriman::class, 'bukti'])->whereNumber('id')
    ->middleware('auth')->name('pengiriman.bukti');
