@extends('pelanggan.layouts.app')
@section('title', 'Beranda')
@section('body-class', 'beranda')
@section('main-class', 'halaman-beranda')
@section('content')
    <section class="hero">
        <svg class="hero-gambar" viewBox="640 112 606 325" preserveAspectRatio="xMaxYMid meet" role="img" aria-label="Baut, mur, dan ring di atas meja kerja">
            <defs>
                <clipPath id="potong-banner"><rect x="640" y="112" width="606" height="325" /></clipPath>
                <linearGradient id="tepi-banner" x1="640" y1="0" x2="720" y2="0" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="black" />
                    <stop offset="1" stop-color="white" />
                </linearGradient>
                <mask id="baur-banner" x="640" y="112" width="606" height="325" maskUnits="userSpaceOnUse">
                    <rect x="640" y="112" width="606" height="325" fill="url(#tepi-banner)" />
                </mask>
            </defs>
            <image href="{{ asset('images/pelanggan/referensi-beranda.png') }}" width="1280" height="1229" clip-path="url(#potong-banner)" mask="url(#baur-banner)" />
        </svg>
        <div class="hero-isi">
            <h1>Baut, Mur, dan<br>Alat Teknik</h1>
            <p>Produk berkualitas untuk industri dan proyek Anda</p>
            <a class="tombol" href="{{ route('pelanggan.produk') }}">Lihat Produk <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        </div>
    </section>
    <section class="bagian-beranda">
        <h2 class="judul-kategori">Kategori Produk</h2>
        @php
            $gambarKategori = [
                'Baut' => '96 558 100 76',
                'Mur' => '344 558 100 76',
                'Ring' => '592 558 100 76',
                'Sekrup' => '840 558 100 76',
                'Alat Teknik' => '1086 558 104 76',
                'Teknik' => '1086 558 104 76',
            ];
        @endphp
        <nav class="kategori-produk" aria-label="Kategori produk">
            @foreach($kategori as $item)
                <article class="kartu-kategori">
                    @if(isset($gambarKategori[$item->nama_kategori]))
                        @php($batasGambar = explode(' ', $gambarKategori[$item->nama_kategori]))
                        <svg class="gambar-kategori" viewBox="{{ $gambarKategori[$item->nama_kategori] }}" role="img" aria-label="{{ $item->nama_kategori }}">
                            <defs>
                                <clipPath id="potong-kategori-{{ $item->id }}">
                                    <rect x="{{ $batasGambar[0] }}" y="{{ $batasGambar[1] }}" width="{{ $batasGambar[2] }}" height="{{ $batasGambar[3] }}" />
                                </clipPath>
                            </defs>
                            <image href="{{ asset('images/pelanggan/referensi-beranda.png') }}" width="1280" height="1229" clip-path="url(#potong-kategori-{{ $item->id }})" />
                        </svg>
                    @else
                        <div class="gambar-kosong">Gambar belum tersedia</div>
                    @endif
                    <h3>{{ $item->nama_kategori }}</h3>
                    <a class="tombol" href="{{ route('pelanggan.produk', ['kategori' => $item->id]) }}">Lihat Produk <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </article>
            @endforeach
        </nav>
        <div class="judul-bagian">
            <h2><i class="bi bi-fire" aria-hidden="true"></i> {{ $adaPenjualan ? 'Produk Terlaris' : 'Pilihan Produk' }}</h2>
            <a href="{{ route('pelanggan.produk') }}">Lihat Semua <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        </div>
        <div class="grid-produk">
            @forelse($produk->take(5) as $item)
                @include('pelanggan.partials.produk', ['labelProduk' => $adaPenjualan ? 'Terlaris' : 'Pilihan Produk'])
            @empty
                <p class="keadaan-kosong">Belum ada produk tersedia.</p>
            @endforelse
        </div>
    </section>
@endsection
