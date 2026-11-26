@extends('kurir.layout')

@section('title', 'Profil Kurir')

@section('content')
    <div class="kurir-page-heading">
        <h1>Profil Kurir</h1>
        <p>Informasi akun kurir yang sedang digunakan.</p>
    </div>

    <section class="kirim-panel kurir-profil">

        <div class="kurir-profil-header">
            <div class="kurir-avatar">
                <i class="bi bi-person-fill" aria-hidden="true"></i>
            </div>

            <div>
                <h2>{{ auth()->user()->name }}</h2>
                <p>Kurir Toko</p>
            </div>
        </div>

        <div class="kurir-profil-data">

            <div class="kurir-info-row">
                <div class="kurir-info-icon">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </div>

                <div class="kurir-info-content">
                    <small>Nama Lengkap</small>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>
            </div>

            <div class="kurir-info-row">
                <div class="kurir-info-icon">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                </div>

                <div class="kurir-info-content">
                    <small>Alamat Email</small>
                    <strong>{{ auth()->user()->email }}</strong>
                </div>
            </div>

            <div class="kurir-info-row">
                <div class="kurir-info-icon">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                </div>

                <div class="kurir-info-content">
                    <small>Peran Akun</small>
                    <strong>Kurir</strong>
                </div>
            </div>

        </div>

        <button class="kurir-profil-keluar"
            type="button"
            data-buka-konfirmasi-keluar
            aria-haspopup="dialog">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            Keluar dari Akun
        </button>

    </section>
@endsection