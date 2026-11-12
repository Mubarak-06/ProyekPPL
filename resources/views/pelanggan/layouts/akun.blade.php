@extends('pelanggan.layouts.app')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a> › Akun Saya › @yield('title')</nav>
    <div class="judul-halaman">
        <h1>Akun Saya</h1>
        <p>Kelola informasi akun Anda untuk pengalaman belanja yang lebih nyaman.</p>
    </div>
    <div class="layout-akun">
        <aside class="sidebar-akun panel">
            <div class="identitas-akun">
                @include('pelanggan.partials.avatar', ['user' => auth()->user()])
                <div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span></div>
            </div>
            <nav aria-label="Menu akun">
                <a class="{{ request()->routeIs('pelanggan.profil*') ? 'aktif' : '' }}" href="{{ route('pelanggan.profil') }}"><i class="bi bi-person-fill" aria-hidden="true"></i> Profil Saya</a>
                <a class="{{ request()->routeIs('pelanggan.pesanan*') ? 'aktif' : '' }}" href="{{ route('pelanggan.pesanan') }}"><i class="bi bi-box-seam" aria-hidden="true"></i> Pesanan Saya</a>
                <a class="{{ request()->routeIs('pelanggan.alamat*') ? 'aktif' : '' }}" href="{{ route('pelanggan.alamat') }}"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Alamat Saya</a>
            </nav>
            <form action="{{ route('pelanggan.logout') }}" method="POST">
                @csrf
                <button class="tombol-keluar" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button>
            </form>
        </aside>
        <section class="panel konten-akun">
            @yield('akun-content')
        </section>
    </div>
@endsection
