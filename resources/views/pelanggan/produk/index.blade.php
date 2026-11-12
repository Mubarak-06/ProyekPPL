@extends('pelanggan.layouts.app')
@section('title', $kategoriDipilih?->nama_kategori ?? 'Semua Produk')
@section('main-class', 'halaman-katalog')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a> &gt; Produk</nav>
    <div class="kepala-katalog">
        <div>
            <h1>{{ $kategoriDipilih?->nama_kategori ?? 'Semua Produk' }}</h1>
            <p>Temukan produk sesuai kebutuhan Anda.</p>
        </div>
        <form class="filter-produk" method="GET" action="{{ route('pelanggan.produk') }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <label class="sr-only" for="material">Material</label>
            <select id="material" name="material">
                <option value="">Semua Material</option>
                @foreach($material as $item)
                    <option value="{{ $item->id }}" @selected(request('material') == $item->id)>{{ $item->nama_material }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="ukuran">Ukuran</label>
            <select id="ukuran" name="ukuran">
                <option value="">Semua Ukuran</option>
                @foreach($ukuran as $nilai)
                    <option value="{{ $nilai }}" @selected(request('ukuran') === $nilai)>{{ $nilai }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="kategori">Kategori</label>
            <select id="kategori" name="kategori">
                <option value="">Semua Kategori</option>
                @foreach($kategori as $item)
                    <option value="{{ $item->id }}" @selected(request('kategori') == $item->id)>{{ $item->nama_kategori }}</option>
                @endforeach
            </select>
            <button type="submit">Terapkan</button>
            <a href="{{ route('pelanggan.produk') }}">Reset</a>
        </form>
    </div>
    <div class="grid-produk">
        @forelse($produk as $item)
            @include('pelanggan.partials.produk')
        @empty
            <p class="keadaan-kosong">Produk tidak ditemukan. Coba kata pencarian atau kategori lainnya.</p>
        @endforelse
    </div>
    {{ $produk->links('pelanggan.partials.pagination') }}
@endsection
