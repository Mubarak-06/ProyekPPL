@extends('pelanggan.layouts.app')
@section('auth-content')
    <section class="auth-info">
        <a class="brand" href="{{ route('pelanggan.beranda') }}">CV Karunia Abadi</a>
        <p>Distributor Baut, Mur, dan Alat Teknik</p>
        <h1>Baut, Mur, Ring, Sekrup<br><span>dan Alat Teknik</span></h1>
        <p>Produk berkualitas untuk industri dan proyek Anda.</p>
        <div class="keunggulan-auth">
            <div><i class="bi bi-truck" aria-hidden="true"></i><span><strong>Pengiriman Cepat</strong>Pengiriman sesuai wilayah layanan</span></div>
            <div><i class="bi bi-shield-check" aria-hidden="true"></i><span><strong>Produk Berkualitas</strong>Standar industri</span></div>
            <div><i class="bi bi-tag" aria-hidden="true"></i><span><strong>Harga Bersaing</strong>Untuk kebutuhan proyek</span></div>
            <div><i class="bi bi-headset" aria-hidden="true"></i><span><strong>Layanan Pelanggan</strong>Siap membantu Anda</span></div>
        </div>
    </section>
    <section class="auth-form-area">
        <div class="panel kartu-auth">
            <a class="brand" href="{{ route('pelanggan.beranda') }}">CV Karunia Abadi</a>
            @include('pelanggan.partials.pesan')
            @yield('form-auth')
        </div>
    </section>
@endsection
