<?php

use App\Http\Controllers\Pelanggan\AkunController;
use App\Http\Controllers\Pelanggan\AuthController;
use App\Http\Controllers\Pelanggan\CheckoutController;
use App\Http\Controllers\Pelanggan\KeranjangController;
use App\Http\Controllers\Pelanggan\ProdukController;
use Illuminate\Support\Facades\Route;

Route::get('/produk', [ProdukController::class, 'index'])->name('pelanggan.produk');
Route::get('/produk/{produk}', [ProdukController::class, 'show'])->whereNumber('produk')->name('pelanggan.produk.show');
Route::view('/tentang-kami', 'pelanggan.tentang')->name('pelanggan.tentang');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'pelanggan.auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::view('/daftar', 'pelanggan.auth.daftar')->name('pelanggan.daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:5,1');
    Route::view('/lupa-password', 'pelanggan.auth.lupa-password')->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'kirimReset'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('pelanggan.auth.reset-password', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::get('/keranjang', [KeranjangController::class, 'index'])->name('pelanggan.keranjang');
Route::post('/keranjang/pilihan', [KeranjangController::class, 'pilih'])->name('pelanggan.keranjang.pilih');
Route::post('/keranjang/{produk}', [KeranjangController::class, 'store'])->whereNumber('produk')->name('pelanggan.keranjang.store');
Route::put('/keranjang/{produk}', [KeranjangController::class, 'update'])->whereNumber('produk')->name('pelanggan.keranjang.update');
Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy'])->whereNumber('id')->name('pelanggan.keranjang.destroy');

Route::middleware('auth')->name('pelanggan.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/akun', [AkunController::class, 'profil'])->name('profil');
    Route::get('/akun/edit', [AkunController::class, 'editProfil'])->name('profil.edit');
    Route::put('/akun', [AkunController::class, 'updateProfil'])->name('profil.update');
    Route::get('/alamat', [AkunController::class, 'alamat'])->name('alamat');
    Route::get('/alamat/tambah', [AkunController::class, 'formAlamat'])->name('alamat.create');
    Route::post('/alamat', [AkunController::class, 'simpanAlamat'])->name('alamat.store');
    Route::get('/alamat/{id}/edit', [AkunController::class, 'formAlamat'])->whereNumber('id')->name('alamat.edit');
    Route::put('/alamat/{id}', [AkunController::class, 'simpanAlamat'])->whereNumber('id')->name('alamat.update');
    Route::delete('/alamat/{id}', [AkunController::class, 'hapusAlamat'])->whereNumber('id')->name('alamat.destroy');
    Route::get('/wilayah', [AkunController::class, 'wilayah'])->name('wilayah');
    Route::get('/pesanan', [AkunController::class, 'pesanan'])->name('pesanan');
    Route::get('/pesanan/{id}', [AkunController::class, 'detailPesanan'])->whereNumber('id')->name('pesanan.show');
    Route::get('/pesanan/{id}/pembayaran', [AkunController::class, 'pembayaran'])->whereNumber('id')->name('pembayaran');
    Route::post('/pesanan/{id}/bukti', [AkunController::class, 'unggahBukti'])->whereNumber('id')->middleware('throttle:10,1')->name('pesanan.bukti');
});
