@extends('pelanggan.layouts.app')
@section('title', 'Pembayaran Midtrans')
@section('main-class', 'halaman-pembayaran')
@section('content')
    <div class="judul-halaman"><h1>Pembayaran Midtrans</h1></div>
    <section class="panel">
        <p>Pesanan <strong>{{ $pesanan->kode_pesanan }}</strong></p>
        <p>Total <strong>Rp {{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}</strong></p>
        @if($pesanan->status_pesanan === 'Ditolak')
            <p role="status">Pesanan telah ditolak.</p>
        @elseif($perluPemeriksaan)
            <p role="status">Pembayaran perlu pemeriksaan.</p>
        @elseif($pesanan->pembayaran->status_pembayaran === 'Berhasil')
            <p role="status">Pembayaran berhasil dan sudah diverifikasi server.</p>
        @elseif($pesanan->pembayaran->status_pembayaran === 'Menunggu')
            <p role="status" data-snap-status>Menunggu pembayaran.</p>
            @if($snapAvailable)
                <form method="POST" action="{{ route('pelanggan.pembayaran.snap', $pesanan->id) }}" data-snap-payment @if(session('buka_snap_dana')) data-snap-autostart @endif>
                    @csrf
                    <button class="tombol" type="submit">Buka pembayaran Snap</button>
                </form>
                <noscript><p>Aktifkan JavaScript untuk membuka pembayaran Snap.</p></noscript>
                @if($clientKey !== null)
                    <script defer src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
                @endif
            @else
                <p>Pembayaran Snap sedang tidak tersedia.</p>
            @endif
        @else
            <p role="status">Pembayaran perlu pemeriksaan.</p>
        @endif
        @if(session('status_midtrans'))
            <p role="status">{{ session('status_midtrans') }}</p>
        @endif
        @if(config('services.midtrans.is_production') === false && !$perluPemeriksaan && $pesanan->pembayaran->status_pembayaran === 'Menunggu')
            <form method="POST" action="{{ route('pelanggan.pembayaran.periksa', $pesanan->id) }}" data-snap-check>
                @csrf
                @if($pesanan->pembayaran->metode_pembayaran === 'E-Wallet' && !$pesanan->pembayaran->midtrans_transaction_id)
                    <label for="midtrans-transaction-id">ID transaksi DANA di Midtrans Sandbox</label>
                    <input id="midtrans-transaction-id" name="transaction_id" value="{{ old('transaction_id', $returnId) }}" maxlength="50" required>
                    @error('transaction_id')<p role="alert">{{ $message }}</p>@enderror
                @endif
                <button class="tombol" type="submit">Periksa status pembayaran</button>
            </form>
        @endif
        <a href="{{ route('pelanggan.pembayaran', $pesanan->id) }}">Muat ulang status pembayaran</a>
        <a href="{{ route('pelanggan.pesanan.show', $pesanan->id) }}">Detail pesanan</a>
    </section>
@endsection
