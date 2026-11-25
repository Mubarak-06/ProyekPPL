@extends('pelanggan.layouts.akun')
@section('title', 'Pesanan Saya')
@section('akun-content')
    <h1>Pesanan Saya</h1>
    <p>Lihat dan pantau semua pesanan yang telah Anda lakukan.</p>
    <form class="filter-pesanan" method="GET" action="{{ route('pelanggan.pesanan') }}">
        <label>Tampilkan <select name="per_page">@foreach([10,25,50] as $jumlah)<option value="{{ $jumlah }}" @selected(request('per_page', 10) == $jumlah)>{{ $jumlah }}</option>@endforeach</select> data per halaman</label>
        <label class="sr-only" for="cari-pesanan">Cari pesanan</label><input id="cari-pesanan" name="q" placeholder="Cari pesanan..." value="{{ request('q') }}" maxlength="100">
        <button type="submit">Tampilkan</button>
    </form>
    <table class="tabel-pesanan">
        <thead>
            <tr><th scope="col">No. Pesanan</th><th scope="col">Tanggal Pesanan</th><th scope="col">Produk</th><th scope="col">Total Pembayaran</th><th scope="col">Status</th><th scope="col">Aksi</th></tr>
        </thead>
        <tbody>
            @forelse($pesanan as $item)
                <tr>
                    <td data-label="No. Pesanan"><strong>{{ $item->kode_pesanan }}</strong></td>
                    <td data-label="Tanggal">{{ $item->created_at->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA</td>
                    <td data-label="Produk">@foreach($item->detailPesanan as $detail)<p>{{ $detail->nama_produk }} × {{ $detail->jumlah }}</p>@endforeach</td>
                    <td data-label="Total"><strong>Rp {{ number_format($item->total_pembayaran, 0, ',', '.') }}</strong></td>
                    <td data-label="Status">
                        <span class="badge-status status-{{ strtolower($item->status_pesanan) }}">{{ $item->status_pesanan === 'Ditolak' ? 'Dibatalkan' : $item->status_pesanan }}</span>
                        <small>Pembayaran: {{ $item->pembayaran?->status_pembayaran ?? 'Belum tersedia' }}</small>
                        <small>Pengiriman: {{ $item->pengiriman?->status_pengiriman === 'Ditolak' ? 'Dibatalkan' : ($item->pengiriman?->status_pengiriman ?? 'Belum tersedia') }}</small>
                        @if($item->alasanPembatalan())<small>{{ $item->alasanPembatalan() }}</small>@endif
                        @if($item->pembayaran?->rekonsiliasiAktif->contains(fn ($kasus) => $kasus->menahanPemenuhan($item->pembayaran)))
                            <small>Pembayaran sedang diperiksa.</small>
                        @endif
                        @include('pelanggan.partials.hitung-mundur-pembayaran', ['order' => $item])
                    </td>
                    <td data-label="Aksi">
                        <div class="aksi-pesanan">
                            <a class="tombol tombol-outline" href="{{ route('pelanggan.pesanan.show', $item->id) }}">Lihat Detail</a>
                            @if(in_array($item->id, $pesananBisaDibayar, true))
                                @include('pelanggan.partials.tombol-bayar-snap', ['order' => $item])
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="keadaan-kosong">Anda belum memiliki pesanan.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $pesanan->links('pelanggan.partials.pagination') }}
@endsection
