@extends('pelanggan.layouts.app')
@section('title', 'Pembayaran Midtrans')
@section('main-class', 'halaman-pembayaran')
@section('content')
    <nav aria-label="Breadcrumb">
        <a href="{{ route('pelanggan.beranda') }}">Beranda</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('pelanggan.pesanan') }}">Pesanan Saya</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>Pembayaran</span>
    </nav>
    <div class="judul-halaman">
        <h1>Pembayaran Midtrans</h1>
        <p>Kelola dan pantau status pembayaran pesanan Anda.</p>
    </div>
    <section class="panel panel-pembayaran-snap">
        <div class="ringkasan-pembayaran">
            <div>
                <span>Nomor Pesanan</span>
                <strong>{{ $pesanan->kode_pesanan }}</strong>
            </div>
            <div class="nominal-pembayaran">
                <span>Total Pembayaran</span>
                <strong>Rp {{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="informasi-status-pembayaran">
            @if($pesanan->alasanPembatalan())
                <p class="status-bayar status-bayar-gagal" role="status">
                    {{ $pesanan->alasanPembatalan() }}
                </p>
            @endif
            @if($perluPemeriksaan)
                <p class="status-bayar status-bayar-menunggu" role="status">
                    Pembayaran sedang diperiksa.
                </p>
            @elseif($pesanan->status_pesanan === 'Ditolak')
                <p class="status-bayar status-bayar-gagal">
                    Status pembayaran: {{ $pesanan->pembayaran->status_pembayaran }}.
                </p>
            @elseif($pesanan->pembayaran->status_pembayaran === 'Berhasil')
                <p class="status-bayar status-bayar-berhasil" role="status">
                    Pembayaran berhasil dan sudah diverifikasi server.
                </p>
            @elseif($pesanan->pembayaran->status_pembayaran === 'Gagal' && $pesanan->pembayaran->midtrans_status === 'expire')
                <p class="status-bayar status-bayar-gagal" role="status">
                    Pembayaran telah kedaluwarsa.
                </p>
            @elseif($pesanan->pembayaran->status_pembayaran === 'Gagal' && in_array($pesanan->pembayaran->midtrans_status, ['cancel', 'deny'], true))
                <p class="status-bayar status-bayar-gagal" role="status">
                    Pembayaran gagal.
                </p>
            @elseif($pesanan->pembayaran->status_pembayaran === 'Menunggu')
                <p class="status-bayar status-bayar-menunggu" role="status" data-snap-status>
                    {{ $statusKembali ?? 'Menunggu pembayaran.' }}
                </p>
                @include('pelanggan.partials.hitung-mundur-pembayaran', ['order' => $pesanan])
                @if($snapAvailable)
                    <form method="POST"
                          action="{{ route('pelanggan.pembayaran.snap', $pesanan->id) }}"
                          data-snap-payment
                          @if(session('buka_snap_dana')) data-snap-autostart @endif>
                        @csrf
                        <button class="tombol" type="submit">
                            <i class="bi bi-wallet2" aria-hidden="true"></i>
                            Buka Pembayaran
                        </button>
                    </form>
                    <noscript>
                        <p>Aktifkan JavaScript untuk membuka pembayaran Snap.</p>
                    </noscript>
                    @if($clientKey !== null)
                        <script defer
                                src="https://app.sandbox.midtrans.com/snap/snap.js"
                                data-client-key="{{ $clientKey }}">
                        </script>
                    @endif
                @else
                    <p>Pembayaran Snap sedang tidak tersedia.</p>
                @endif
            @else
                <p class="status-bayar status-bayar-menunggu" role="status">
                    Pembayaran perlu pemeriksaan.
                </p>
            @endif
            @if(session('status_midtrans'))
                <p class="status-bayar status-bayar-menunggu" role="status">
                    {{ session('status_midtrans') }}
                </p>
            @endif
        </div>
        @if(config('services.midtrans.is_production') === false && !$perluPemeriksaan && $pesanan->status_pesanan !== 'Ditolak' && $pesanan->pembayaran->status_pembayaran === 'Menunggu')
            <form method="POST"
                  action="{{ route('pelanggan.pembayaran.periksa', $pesanan->id) }}"
                  data-snap-check>
                @csrf
                @if($pesanan->pembayaran->metode_pembayaran === 'E-Wallet' && !$pesanan->pembayaran->midtrans_transaction_id)
                    <label for="midtrans-transaction-id">
                        ID transaksi DANA di Midtrans Sandbox
                    </label>
                    <input id="midtrans-transaction-id"
                           name="transaction_id"
                           value="{{ old('transaction_id', $returnId) }}"
                           maxlength="50"
                           required>
                    @error('transaction_id')
                        <p role="alert">{{ $message }}</p>
                    @enderror
                @endif
                <button class="tombol tombol-outline" type="submit">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Periksa Status Pembayaran
                </button>
            </form>
        @endif
        <a class="tombol tombol-outline muat-ulang-pembayaran"
           href="{{ route('pelanggan.pembayaran', $pesanan->id) }}">
            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            Muat Ulang Status Pembayaran
        </a>
        <div class="navigasi-pembayaran">
            <a class="tombol tombol-outline"
               href="{{ route('pelanggan.pesanan') }}">
                Kembali ke Pesanan Saya
            </a>
            <a class="tombol"
               href="{{ route('pelanggan.pesanan.show', $pesanan->id) }}">
                Detail Pesanan
            </a>
        </div>
    </section>
@endsection
