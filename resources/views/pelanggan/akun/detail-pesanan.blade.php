@extends('pelanggan.layouts.akun')
@section('title', 'Detail Pesanan')
@section('akun-content')
    <h1>Detail Pesanan</h1>
    <div class="status-pesanan"><span>Status pesanan: {{ $pesanan->status_pesanan === 'Ditolak' ? 'Dibatalkan' : $pesanan->status_pesanan }}</span><span>Pembayaran: {{ $pesanan->pembayaran?->status_pembayaran ?? 'Belum tersedia' }}</span><span>Pengiriman: {{ $pesanan->pengiriman?->status_pengiriman === 'Ditolak' ? 'Dibatalkan' : ($pesanan->pengiriman?->status_pengiriman ?? 'Belum tersedia') }}</span></div>
    @if($pesanan->alasanPembatalan())<p role="status">{{ $pesanan->alasanPembatalan() }}</p>@endif
    @if($pesanan->pembayaran?->rekonsiliasiAktif->contains(fn ($kasus) => $kasus->menahanPemenuhan($pesanan->pembayaran)))
        <p role="status">Pembayaran sedang diperiksa.</p>
    @endif
    <dl class="data-profil">
        <dt>No. Pesanan</dt><dd>{{ $pesanan->kode_pesanan }}</dd>
        <dt>Tanggal Pesanan</dt><dd>{{ $pesanan->created_at->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA</dd>
        <dt>Metode Pembayaran</dt><dd>{{ $pesanan->pembayaran?->metode_pembayaran ?? 'Belum tersedia' }} {{ $metode?->nama_penyedia }}</dd>
    </dl>
    <h2>Produk yang Dipesan</h2>
    <table class="tabel-pesanan tabel-detail">
        <thead><tr><th scope="col">No.</th><th scope="col">Produk</th><th scope="col">Harga</th><th scope="col">Jumlah</th><th scope="col">Subtotal</th></tr></thead>
        <tbody>
            @foreach($pesanan->detailPesanan as $item)
                <tr><td data-label="No.">{{ $loop->iteration }}</td><td data-label="Produk">{{ $item->nama_produk }}</td><td data-label="Harga">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td><td data-label="Jumlah">{{ $item->jumlah }}</td><td data-label="Subtotal">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="ringkasan-detail">
        <div class="baris-total"><span>Subtotal</span><strong>Rp {{ number_format($pesanan->subtotal, 0, ',', '.') }}</strong></div>
        <div class="baris-total"><span>Ongkos kirim</span><strong>Rp {{ number_format($pesanan->ongkos_kirim, 0, ',', '.') }}</strong></div>
        <div class="baris-total total-bayar"><span>Total Pembayaran</span><strong>Rp {{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}</strong></div>
    </div>
    @if($pesanan->catatan)<p>Catatan: {{ $pesanan->catatan }}</p>@endif
    @if($pesanan->pengiriman)
        <section class="informasi-pengiriman">
            <h2>Informasi Pengiriman</h2>
            <p><strong>{{ $pesanan->pengiriman->nama_penerima }}</strong> — {{ $pesanan->pengiriman->nomor_telepon }}</p>
            <p>{{ $pesanan->pengiriman->alamat_pengiriman }}</p>
            @if($pesanan->pengiriman->waktu_dikirim)<p>Dikirim: {{ $pesanan->pengiriman->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA</p>@endif
            @if($pesanan->pengiriman->waktu_selesai)<p>Selesai: {{ $pesanan->pengiriman->waktu_selesai->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA</p>@endif
            @if($pesanan->pengiriman->catatan_driver)<p>Catatan driver: {{ $pesanan->pengiriman->catatan_driver }}</p>@endif
        </section>
    @endif
    @include('pelanggan.partials.hitung-mundur-pembayaran', ['order' => $pesanan])
    <div class="aksi-form">
        <a class="tombol tombol-outline" href="{{ route('pelanggan.pesanan') }}">Kembali ke Pesanan Saya</a>
        @if($bisaDibayar)
            @include('pelanggan.partials.tombol-bayar-snap', ['order' => $pesanan])
        @elseif($pesanan->pembayaran && $pesanan->pembayaran->status_pembayaran !== 'Berhasil'
            && ($pesanan->status_pesanan !== 'Ditolak' || $pesanan->pembayaran->gateway === 'midtrans'))
            <a class="tombol" href="{{ route('pelanggan.pembayaran', $pesanan->id) }}">{{ $pesanan->pembayaran->gateway === 'midtrans' ? 'Lihat Status Pembayaran' : 'Instruksi Pembayaran' }}</a>
        @endif
    </div>
@endsection
