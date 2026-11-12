@extends('pelanggan.layouts.app')
@section('title', 'Tentang Kami')
@section('body-class', 'tentang')
@section('main-class', 'halaman-tentang')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Tentang Kami</span></nav>
    <section class="panel tentang-toko">
        <svg class="foto-toko" viewBox="65 196 460 344" role="img" aria-label="Foto asli toko CV Karunia Abadi" preserveAspectRatio="xMidYMid slice">
            <defs><clipPath id="foto-toko-potong"><rect x="65" y="196" width="460" height="344" rx="10" /></clipPath></defs>
            <image href="{{ asset('images/pelanggan/referensi-tentang.png') }}" width="1280" height="1031" clip-path="url(#foto-toko-potong)" />
        </svg>
        <div>
            <h1>CV Karunia Abadi</h1>
            <p>CV Karunia Abadi adalah usaha yang bergerak di bidang penjualan baut, mur, alat teknik, dan berbagai perlengkapan industri. Kami menyediakan produk berkualitas dengan harga yang kompetitif serta pelayanan yang ramah dan profesional.</p>
            <p>Dengan pengalaman dalam melayani kebutuhan teknik dan industri, kami selalu berupaya menjadi mitra terpercaya bagi pelanggan, baik untuk kebutuhan proyek, perbaikan, maupun penggunaan sehari-hari.</p>
            <div class="keunggulan-toko">
                <div><i class="bi bi-gear" aria-hidden="true"></i><strong>Produk Lengkap</strong><span>Berbagai jenis baut, mur, dan alat teknik.</span></div>
                <div><i class="bi bi-shield-check" aria-hidden="true"></i><strong>Kualitas Terjamin</strong><span>Produk pilihan dengan kualitas terbaik.</span></div>
                <div><i class="bi bi-tag" aria-hidden="true"></i><strong>Harga Kompetitif</strong><span>Harga sesuai kualitas dan kebutuhan pasar.</span></div>
                <div><i class="bi bi-people" aria-hidden="true"></i><strong>Pelayanan Ramah</strong><span>Siap membantu kebutuhan pelanggan.</span></div>
            </div>
        </div>
    </section>
    <section class="panel kontak-toko">
        <h2>Informasi Kontak</h2>
        <div class="daftar-kontak">
            <a class="kontak-whatsapp" href="https://wa.me/62{{ substr(config('toko.whatsapp'), 1) }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp" aria-hidden="true"></i><span>WhatsApp<strong>{{ config('toko.whatsapp') }}</strong></span></a>
            <a class="kontak-telepon" href="tel:{{ preg_replace('/\D/', '', config('toko.telepon')) }}"><i class="bi bi-telephone-fill" aria-hidden="true"></i><span>Telepon<strong>{{ config('toko.telepon') }}</strong></span></a>
            <a class="kontak-email" href="mailto:{{ config('toko.email') }}"><i class="bi bi-envelope" aria-hidden="true"></i><span>Email<strong>{{ config('toko.email') }}</strong></span></a>
            <a class="kontak-instagram" href="https://www.instagram.com/{{ config('toko.instagram') }}/" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram" aria-hidden="true"></i><span>Instagram<strong>{{ '@'.config('toko.instagram') }}</strong></span></a>
        </div>
        <div class="lokasi-jam">
            <div><h3><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Alamat Toko</h3><p>{{ config('toko.alamat') }}</p></div>
            <div><h3><i class="bi bi-clock" aria-hidden="true"></i> Jam Operasional</h3><dl>@foreach(config('toko.jam_buka') as $hari => $jam)<dt>{{ preg_replace('/\s*[–-]\s*/u', ' – ', $hari) }}</dt><dd>{{ preg_replace('/\s*[–-]\s*/u', ' – ', $jam) }}</dd>@endforeach</dl></div>
        </div>
    </section>
@endsection
