@extends('pelanggan.layouts.app')
@section('title', $produk->nama_produk)
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a> &gt; <a
            href="{{ route('pelanggan.produk') }}">Produk</a> &gt; {{ $produk->nama_produk }}</nav>
    <div class="detail-produk">
        <div class="galeri-produk">
            <div class="foto-utama">
                @include('pelanggan.partials.gambar', [
                    'path' => $produk->gambar[0] ?? null,
                    'nama' => $produk->nama_produk
                ])
            </div>

            @foreach(array_slice($produk->gambar ?? [], 1) as $gambar)
                <button class="foto-tambahan" type="button" data-galeri aria-label="Lihat gambar produk">
                    @include('pelanggan.partials.gambar', [
                        'path' => $gambar,
                        'nama' => $produk->nama_produk
                    ])
                </button>
            @endforeach
        </div>
        <section class="info-produk">
            <h1>{{ $produk->nama_produk }}</h1>
            <p class="harga">Rp {{ number_format($produk->harga, 0, ',', '.') }}</p>
            <div class="spesifikasi">
                <h3>Spesifikasi</h3>
                <dl>
                    <dt>Kode produk</dt>
                    <dd>{{ $produk->kode_barang }}</dd>
                    @if($produk->kategori)
                        <dt>Kategori</dt>
                    <dd>{{ $produk->kategori->nama_kategori }}</dd>@endif
                    @if($produk->ukuran)
                        <dt>Ukuran</dt>
                    <dd>{{ $produk->ukuran }}</dd>@endif
                    @if($produk->material)
                        <dt>Material</dt>
                    <dd>{{ $produk->material->nama_material }}</dd>@endif
                    @if($produk->satuan)
                        <dt>Satuan</dt>
                    <dd>{{ $produk->satuan->nama_satuan }}</dd>@endif
                </dl>
                <h3>Deskripsi Produk</h3>
                <p>{{ $produk->deskripsi ?: 'Deskripsi belum tersedia.' }}</p>
            </div>
            <p>Stok: <strong class="harga">{{ $produk->stok }}</strong></p>
            @if($produk->stok > 0)
                <form class="aksi-produk" action="{{ route('pelanggan.keranjang.store', $produk) }}" method="POST">
                    @csrf
                    <label for="jumlah">Jumlah</label>
                    <div class="kontrol-jumlah"><button class="tombol-outline" type="button" data-jumlah="-1"
                            aria-label="Kurangi jumlah">−</button><input id="jumlah" type="number" name="jumlah" min="1"
                            max="{{ $produk->stok }}" value="{{ old('jumlah', 1) }}" required><button class="tombol-outline"
                            type="button" data-jumlah="1" aria-label="Tambah jumlah">+</button></div>
                    <button type="submit" name="beli" value="1">Beli Sekarang</button>
                    <button class="tombol-outline" type="submit"><i class="bi bi-cart3" aria-hidden="true"></i> +
                        Keranjang</button>
                </form>
            @else
                <p>Stok habis.</p>
            @endif
        </section>
    </div>
@endsection