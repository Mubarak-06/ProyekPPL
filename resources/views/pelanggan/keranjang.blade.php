@extends('pelanggan.layouts.app')
@section('title', 'Keranjang Belanja')
@section('body-class', 'keranjang')
@section('main-class', 'halaman-keranjang')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><i class="bi bi-chevron-right" aria-hidden="true"></i><a href="{{ route('pelanggan.produk') }}">Produk</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Keranjang</span></nav>
    <h1 class="sr-only">Keranjang Belanja</h1>
    @if($produkHilang)
        <p role="alert">Ada produk yang sudah tidak tersedia. Hapus produk tersebut sebelum checkout.</p>
        @foreach(array_diff(array_keys($keranjang), $produk->modelKeys()) as $id)
            <form action="{{ route('pelanggan.keranjang.destroy', $id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit">Hapus produk yang tidak tersedia ({{ $id }})</button>
            </form>
        @endforeach
    @endif
    <div class="layout-belanja">
        <section class="panel daftar-belanja" aria-label="Daftar produk keranjang">
            @if($produk->isNotEmpty())
                <form id="pilihan-keranjang" action="{{ route('pelanggan.keranjang.pilih') }}" method="POST" data-pilihan>
                    @csrf
                    <div class="pilihan-keranjang">
                        <label><input type="checkbox" data-pilih-semua @checked(count(array_intersect($pilihan, $produk->modelKeys())) === $produk->count())> Pilih Semua ({{ $produk->count() }} produk)</label>
                        <button class="tombol-hapus" name="aksi" value="hapus" type="submit" data-konfirmasi-tombol="Hapus produk yang dipilih?" @disabled(count($pilihan) === 0)><i class="bi bi-trash" aria-hidden="true"></i> Hapus Terpilih</button>
                        <noscript><button class="tombol-outline" type="submit">Perbarui Pilihan</button></noscript>
                    </div>
                </form>
            @endif
            @forelse($produk as $item)
                <article class="item-keranjang">
                    <label class="pilih-item"><input type="checkbox" name="pilihan[]" value="{{ $item->id }}" form="pilihan-keranjang" data-pilih-item @checked(in_array($item->id, $pilihan))><span class="sr-only">Pilih {{ $item->nama_produk }}</span></label>
                    <div class="foto-keranjang">
                        @include('pelanggan.partials.gambar', ['path' => $item->gambar ?: $item->gambarProduk->first()?->path_gambar, 'nama' => $item->nama_produk])
                    </div>
                    <div class="info-item">
                        <h2><a href="{{ route('pelanggan.produk.show', $item) }}">{{ $item->nama_produk }}</a></h2>
                        <p class="harga">Rp{{ number_format($item->harga, 0, ',', '.') }}</p>
                        <small>Stok: {{ $item->stok }}{{ $item->satuan?->nama_satuan ? ' '.$item->satuan->nama_satuan : '' }}</small>
                        @if($keranjang[$item->id] > $item->stok)
                            <p role="alert">Jumlah melebihi stok. Ubah jumlah atau hapus produk.</p>
                        @endif
                    </div>
                    <form class="jumlah-item" data-jumlah-langsung action="{{ route('pelanggan.keranjang.update', $item) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <label class="sr-only" for="jumlah-{{ $item->id }}">Jumlah {{ $item->nama_produk }}</label>
                        <div class="kontrol-jumlah"><button type="button" data-jumlah="-1" aria-label="Kurangi jumlah {{ $item->nama_produk }}" @disabled($keranjang[$item->id] <= 1 || $item->stok < 1)>−</button><input id="jumlah-{{ $item->id }}" type="number" name="jumlah" value="{{ $keranjang[$item->id] }}" min="1" max="{{ $item->stok }}" required @disabled($item->stok < 1)><button type="button" data-jumlah="1" aria-label="Tambah jumlah {{ $item->nama_produk }}" @disabled($keranjang[$item->id] >= $item->stok)>+</button></div>
                        <noscript><button class="tombol-outline" type="submit" @disabled($item->stok < 1)>Perbarui</button></noscript>
                    </form>
                    <div class="subtotal-item"><span>Subtotal</span><strong class="harga">Rp{{ number_format($item->harga * $keranjang[$item->id], 0, ',', '.') }}</strong></div>
                    <form class="hapus-item" action="{{ route('pelanggan.keranjang.destroy', $item->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" aria-label="Hapus {{ $item->nama_produk }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                </article>
            @empty
                <div class="keranjang-kosong"><i class="bi bi-cart3" aria-hidden="true"></i><p>Keranjang Anda masih kosong.</p><a class="tombol" href="{{ route('pelanggan.produk') }}">Lihat Produk</a></div>
            @endforelse
        </section>
        @if($produk->isNotEmpty())
            <aside class="panel ringkasan">
                <h2><i class="bi bi-cart3" aria-hidden="true"></i> Ringkasan Pesanan</h2>
                <div class="baris-total"><span>Total Produk ({{ $produk->whereIn('id', $pilihan)->count() }} item)</span><strong>Rp{{ number_format($subtotalPilihan, 0, ',', '.') }}</strong></div>
                <div class="baris-total"><span>Subtotal</span><strong>Rp{{ number_format($subtotalPilihan, 0, ',', '.') }}</strong></div>
                <div class="baris-total total-bayar"><span>Total Pembayaran</span><strong>Rp{{ number_format($subtotalPilihan, 0, ',', '.') }}</strong></div>
                @if(!$produkHilang && $produk->whereIn('id', $pilihan)->isNotEmpty())
                    <a class="tombol tombol-penuh" href="{{ route('pelanggan.checkout') }}">Checkout</a>
                @else
                    <button class="tombol-penuh" type="button" disabled>Checkout</button>
                @endif
            </aside>
        @endif
    </div>
@endsection
