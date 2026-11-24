<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pelanggan') - CV Karunia Abadi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/pelanggan.css') }}?v={{ hash_file('sha256', public_path('css/pelanggan.css')) }}">
    <script src="{{ asset('js/pelanggan.js') }}?v={{ hash_file('sha256', public_path('js/pelanggan.js')) }}" defer></script>
</head>
<body class="pelanggan @yield('body-class')">
    @unless(View::hasSection('auth-content'))
        <header class="header-pelanggan">
            <div class="navbar-pelanggan">
                <a class="brand" href="{{ route('pelanggan.beranda') }}">CV Karunia Abadi</a>
                <nav class="menu-utama" aria-label="Navigasi utama">
                    <a class="{{ request()->routeIs('pelanggan.beranda') ? 'aktif' : '' }}" href="{{ route('pelanggan.beranda') }}">Beranda</a>
                    <a class="{{ request()->routeIs('pelanggan.produk*') ? 'aktif' : '' }}" href="{{ route('pelanggan.produk') }}">Produk</a>
                    <a class="{{ request()->routeIs('pelanggan.tentang') ? 'aktif' : '' }}" href="{{ route('pelanggan.tentang') }}">Tentang Kami</a>
                </nav>
                <form class="pencarian" action="{{ route('pelanggan.produk') }}" method="GET" role="search">
                    @if(request()->routeIs('pelanggan.produk'))
                        @foreach(['kategori', 'material', 'ukuran'] as $filter)
                            @if(is_scalar(request($filter)) && request($filter) !== '')
                                <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
                            @endif
                        @endforeach
                    @endif
                    <label class="sr-only" for="pencarian">Cari produk</label>
                    <button type="submit" aria-label="Cari produk"><i class="bi bi-search" aria-hidden="true"></i></button>
                    <input id="pencarian" name="q" type="search" value="{{ request()->routeIs('pelanggan.produk') && is_string(request('q')) ? request('q') : '' }}" placeholder="Cari produk (misal: Baut M10, Mur, Ring...)" maxlength="150">
                </form>
                <div class="menu-akun">
                    @php($jumlahItemKeranjang = count(session('keranjang', [])))
                    <a class="ikon-keranjang" href="{{ route('pelanggan.keranjang') }}" aria-label="Keranjang ({{ $jumlahItemKeranjang }})">
                        <i class="bi bi-cart3" aria-hidden="true"></i><span>{{ $jumlahItemKeranjang }}</span>
                    </a>
                    @auth
                        <details class="dropdown-akun">
                            <summary><i class="bi bi-person-fill" aria-hidden="true"></i> Akun <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                            <div class="dropdown-menu">
                                <a href="{{ route('pelanggan.profil') }}">Profil Saya</a>
                                <a href="{{ route('pelanggan.pesanan') }}">Pesanan Saya</a>
                                <a href="{{ route('pelanggan.alamat') }}">Alamat Saya</a>
                                <form action="{{ route('pelanggan.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit">Keluar</button>
                                </form>
                            </div>
                        </details>
                    @else
                        <a class="akun-masuk" href="{{ route('login') }}"><i class="bi bi-person-fill" aria-hidden="true"></i> Akun <i class="bi bi-chevron-down" aria-hidden="true"></i></a>
                    @endauth
                </div>
            </div>
        </header>
    @endunless
    <main class="@hasSection('auth-content') auth-layout @else halaman @yield('main-class') @endif">
        @unless(View::hasSection('auth-content'))
            @include('pelanggan.partials.pesan')
        @endunless
        @hasSection('auth-content')
            @yield('auth-content')
        @else
            @yield('content')
        @endif
    </main>
    <dialog id="dialog-konfirmasi" class="dialog-konfirmasi" aria-labelledby="judul-konfirmasi">
        <h2 id="judul-konfirmasi">Konfirmasi Hapus</h2>
        <p data-konfirmasi-teks></p>
        <div class="aksi-form"><button class="tombol-outline" type="button" data-tutup>Batal</button><button type="button" data-setuju-hapus>Ya, Hapus</button></div>
    </dialog>
</body>
</html>
