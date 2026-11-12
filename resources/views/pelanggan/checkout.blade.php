@extends('pelanggan.layouts.app')
@section('title', 'Checkout')
@section('body-class', 'checkout')
@section('main-class', 'halaman-checkout')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><i class="bi bi-chevron-right" aria-hidden="true"></i><a href="{{ route('pelanggan.keranjang') }}">Keranjang</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Checkout</span></nav>
    <div class="judul-halaman"><h1>Checkout</h1><p>Lengkapi data pengiriman dan pilih metode pembayaran untuk menyelesaikan pesanan Anda.</p></div>
    <div class="layout-belanja">
        <div class="daftar-belanja">
            <section class="panel checkout-alamat">
                <div class="judul-bagian"><h2><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Alamat Pengiriman</h2><a href="{{ route('pelanggan.alamat.create') }}">+ Tambah Alamat Baru</a></div>
                <form class="pilihan-alamat" action="{{ route('pelanggan.checkout') }}" method="GET" data-pilih-alamat>
                    @forelse($alamat as $item)
                        @php
                            $wilayah = $wilayahAlamat->get($item->kelurahan_id);
                        @endphp
                        <div class="alamat-dipilih">
                            <label for="alamat-{{ $item->id }}"><input id="alamat-{{ $item->id }}" type="radio" name="alamat_id" value="{{ $item->id }}" @checked($alamatDipilih?->id === $item->id) required><span><strong>{{ $item->nama_penerima }}</strong><span class="detail-alamat">{{ $item->alamat_lengkap }}</span><span class="detail-alamat">{{ collect([$wilayah?->nama_kecamatan, $wilayah?->nama_kota, $wilayah?->nama_provinsi])->filter()->implode(', ') }} {{ $item->kode_pos }}</span><span class="detail-alamat">No. HP: {{ $item->nomor_telepon }}</span></span></label>
                            <a class="ubah-alamat" href="{{ route('pelanggan.alamat.edit', $item->id) }}">Ubah <i class="bi bi-pencil" aria-hidden="true"></i></a>
                        </div>
                    @empty
                        <p>Tambahkan alamat terlebih dahulu untuk melanjutkan checkout.</p>
                    @endforelse
                    <noscript><button class="tombol-outline" type="submit">Gunakan Alamat</button></noscript>
                </form>
            </section>
            <section class="panel checkout-pembayaran">
                <h2><i class="bi bi-credit-card" aria-hidden="true"></i> Metode Pembayaran</h2>
                <p>Pilih salah satu metode pembayaran yang tersedia.</p>
                @php
                    $jenisPembayaran = [
                        'transfer_bank' => ['Transfer Bank', 'bank', 'Pembayaran melalui transfer bank.'],
                        'ewallet' => ['E-Wallet', 'wallet2', 'Pembayaran menggunakan dompet digital.'],
                        'qris' => ['QRIS', 'qr-code', 'Pembayaran dengan scan QRIS.'],
                    ];
                    $metodeAwal = old('metode_pembayaran_id', $metode->firstWhere('tersedia', true)?->id);
                @endphp
                <form id="form-checkout" action="{{ route('pelanggan.checkout.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="checkout_token" value="{{ $token }}">
                    <input type="hidden" name="alamat_id" value="{{ $alamatDipilih?->id }}">
                    @foreach($jenisPembayaran as $jenis => [$nama, $ikon, $deskripsi])
                        @php
                            $penyedia = $metode->where('jenis_metode', $jenis);
                            $siap = $penyedia->where('tersedia', true);
                            $terpilih = $siap->firstWhere('id', $metodeAwal) ?? $siap->first();
                        @endphp
                        <div class="grup-pembayaran">
                            <label class="pilihan-bayar" for="metode-{{ $jenis }}">
                                <input id="metode-{{ $jenis }}" type="radio" name="metode_pembayaran_id" value="{{ $terpilih?->id }}" @checked($terpilih && $terpilih->id == $metodeAwal) @disabled($siap->isEmpty()) required>
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                                <span class="deskripsi-metode"><strong>{{ $nama }}</strong><small>{{ $siap->isNotEmpty() ? $deskripsi : 'Belum tersedia: metode belum aktif atau informasi pembayaran belum lengkap.' }}</small></span>
                                <span class="label-penyedia">@foreach($penyedia as $provider)<span>{{ $provider->nama_penyedia }}</span>@endforeach</span>
                            </label>
                            @if($siap->count() > 1)
                                <div class="pilih-penyedia"><label for="penyedia-{{ $jenis }}">Pilih penyedia {{ $nama }}</label><select id="penyedia-{{ $jenis }}" data-penyedia-metode="metode-{{ $jenis }}">@foreach($siap as $provider)<option value="{{ $provider->id }}" @selected($terpilih?->id === $provider->id)>{{ $provider->nama_penyedia }}</option>@endforeach</select></div>
                            @endif
                        </div>
                    @endforeach
                    <details class="catatan-checkout" @if(old('catatan')) open @endif><summary>Catatan (opsional)</summary><label class="sr-only" for="catatan">Catatan pesanan</label><textarea id="catatan" name="catatan" maxlength="2000">{{ old('catatan') }}</textarea></details>
                </form>
            </section>
        </div>
        <aside class="panel ringkasan">
            <div class="judul-bagian"><h2><i class="bi bi-cart3" aria-hidden="true"></i> Ringkasan Pesanan</h2><a href="{{ route('pelanggan.keranjang') }}">Ubah Pesanan <i class="bi bi-pencil" aria-hidden="true"></i></a></div>
            <ul class="daftar-checkout">
                @foreach($produk as $item)
                    <li><div class="foto-checkout">@include('pelanggan.partials.gambar', ['path' => $item->gambar ?: $item->gambarProduk->first()?->path_gambar, 'nama' => $item->nama_produk])</div><div class="info-checkout"><strong>{{ $item->nama_produk }}</strong>@if($item->material)<small>{{ $item->material->nama_material }}</small>@endif @if($item->ukuran)<small>{{ $item->ukuran }}</small>@endif</div><div class="harga-checkout"><strong>Rp{{ number_format($item->harga, 0, ',', '.') }}</strong><small>× {{ $keranjang[$item->id] }}</small></div></li>
                @endforeach
            </ul>
            <div class="baris-total"><span>Subtotal Produk ({{ $produk->count() }} item)</span><strong>Rp{{ number_format($subtotal / 100, 0, ',', '.') }}</strong></div>
            @if($ongkir)
                <div class="baris-total"><span>Ongkos Kirim ({{ $alamatDipilih->kelurahan?->nama_kelurahan }})</span><strong>Rp{{ number_format($ongkir->biaya_ongkir, 0, ',', '.') }}</strong></div>
                <div class="baris-total total-bayar"><span>Total Pembayaran</span><strong>Rp{{ number_format(($subtotal + (int) round($ongkir->biaya_ongkir * 100)) / 100, 0, ',', '.') }}</strong></div>
            @elseif($alamatDipilih)
                <p role="alert">Tarif ongkir untuk alamat ini belum tersedia. Pilih alamat lain.</p>
            @endif
            <button class="tombol-penuh" type="submit" form="form-checkout" @disabled(!$ongkir || !$metode->contains('tersedia', true))><i class="bi bi-credit-card" aria-hidden="true"></i> Bayar Sekarang</button>
        </aside>
    </div>
@endsection
