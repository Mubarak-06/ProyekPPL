@extends('admin.layouts.app')
@section('title', 'Rekonsiliasi Midtrans')
@include('admin.midtrans-rekonsiliasi.styles')

@section('content')
    <div class="rekon">
        <h1>Rekonsiliasi Midtrans</h1>
        <form class="rekon-card rekon-pad rekon-filter" method="GET" action="{{ route('admin.midtrans-rekonsiliasi.index') }}">
            <label>Kode pesanan
                <input name="kode" maxlength="30" value="{{ $filters['kode'] ?? '' }}">
            </label>
            <label>Status kasus
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="terbuka" @selected(($filters['status'] ?? '') === 'terbuka')>Terbuka</option>
                    <option value="selesai" @selected(($filters['status'] ?? '') === 'selesai')>Selesai</option>
                </select>
            </label>
            <label>Prioritas kasus terbuka
                <select name="prioritas">
                    <option value="">Semua prioritas</option>
                    <option value="tinggi" @selected(($filters['prioritas'] ?? '') === 'tinggi')>Tinggi</option>
                    <option value="reguler" @selected(($filters['prioritas'] ?? '') === 'reguler')>Reguler</option>
                </select>
            </label>
            <label>Jenis kasus
                <select name="jenis">
                    <option value="">Semua jenis</option>
                    @foreach ($alasan as $kode => $label)
                        <option value="{{ $kode }}" @selected(($filters['jenis'] ?? '') === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Terapkan filter</button>
            <a href="{{ route('admin.midtrans-rekonsiliasi.index') }}">Reset</a>
        </form>

        <section class="rekon-card">
            <div class="rekon-scroll">
                <table aria-label="Kasus rekonsiliasi">
                    <thead>
                        <tr><th>Kasus</th><th>Pesanan</th><th>Penanganan</th><th>Alasan</th><th>Kanal</th><th>Dibuat</th><th>Pembayaran lokal</th><th>Pemrosesan / pengiriman</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($kasus as $item)
                            <tr>
                                <td><a href="{{ route('admin.midtrans-rekonsiliasi.show', $item['id']) }}">#{{ $item['id'] }}</a></td>
                                <td>
                                    @if ($item['pesanan'])
                                        <a href="{{ route('admin.pesanan.show', $item['pesanan']->id) }}">{{ $item['pesanan']->kode_pesanan }}</a>
                                    @else
                                        Belum tersedia
                                    @endif
                                </td>
                                <td>{{ $item['penanganan'] }}</td>
                                <td>{{ $item['alasan'] }}</td>
                                <td>{{ $item['kanal'] }}</td>
                                <td>{{ $item['dibuat']?->format('d/m/Y H:i:s') ?? 'Belum tersedia' }}</td>
                                <td>{{ $item['lokal'] ?? 'Belum tersedia' }}</td>
                                <td>{{ $item['hambatan'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">Tidak ada kasus yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.midtrans-rekonsiliasi.pagination', ['paginator' => $kasus, 'label' => 'Halaman kasus'])
        </section>

        <section class="rekon-card">
            <div class="rekon-pad">
                <h2>Kandidat klaim Snap terlantar</h2>
                <small>Klaim berikut perlu diperiksa. Status sesi Snap dan hasil pembayaran di Midtrans belum dapat disimpulkan dari daftar ini.</small>
            </div>
            <div class="rekon-scroll">
                <table aria-label="Kandidat klaim Snap terlantar">
                    <thead><tr><th>ID pembayaran</th><th>Pesanan</th><th>Waktu klaim</th><th>Pembayaran lokal</th></tr></thead>
                    <tbody>
                        @forelse ($kandidat as $item)
                            <tr>
                                <td>#{{ $item->id }}</td>
                                <td>
                                    @if ($item->pesanan)
                                        <a href="{{ route('admin.pesanan.show', $item->pesanan->id) }}">{{ $item->pesanan->kode_pesanan }}</a>
                                    @else
                                        Belum tersedia
                                    @endif
                                </td>
                                <td>{{ $item->snap_requested_at?->format('d/m/Y H:i:s') ?? 'Belum tersedia' }}</td>
                                <td>{{ $item->status_pembayaran }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada kandidat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.midtrans-rekonsiliasi.pagination', ['paginator' => $kandidat, 'label' => 'Halaman kandidat'])
        </section>
    </div>
@endsection
