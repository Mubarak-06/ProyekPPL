@extends('admin.layouts.app')
@section('title', 'Pemeriksaan Pembayaran')
@include('admin.midtrans-rekonsiliasi.styles')

@section('content')
    <div class="rekon">
        {{-- Judul dan Breadcrumb --}}
        <div class="rekon-header">
            <h1>Pemeriksaan Pembayaran</h1>

            <div class="rekon-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                &nbsp; › &nbsp; Pemeriksaan Pembayaran
            </div>
        </div>

        {{-- Filter Pemeriksaan Pembayaran --}}
        <form class="rekon-card rekon-pad rekon-filter"
            method="GET"
            action="{{ route('admin.midtrans-rekonsiliasi.index') }}">

            <label>
                Kode Pesanan
                <input type="text"
                    name="kode"
                    maxlength="30"
                    placeholder="Cari kode pesanan..."
                    value="{{ $filters['kode'] ?? '' }}">
            </label>

            <label>
                Status Kasus
                <select name="status">
                    <option value="">Semua Status</option>

                    <option value="terbuka"
                        @selected(($filters['status'] ?? '') === 'terbuka')>
                        Terbuka
                    </option>

                    <option value="selesai"
                        @selected(($filters['status'] ?? '') === 'selesai')>
                        Selesai
                    </option>
                </select>
            </label>

            <label>
                Prioritas Kasus Terbuka
                <select name="prioritas">
                    <option value="">Semua Prioritas</option>

                    <option value="tinggi"
                        @selected(($filters['prioritas'] ?? '') === 'tinggi')>
                        Tinggi
                    </option>

                    <option value="reguler"
                        @selected(($filters['prioritas'] ?? '') === 'reguler')>
                        Reguler
                    </option>
                </select>
            </label>

            <label>
                Jenis Kendala
                <select name="jenis">
                    <option value="">Semua Jenis</option>

                    @foreach ($alasan as $kode => $label)
                        <option value="{{ $kode }}"
                            @selected(($filters['jenis'] ?? '') === $kode)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button type="submit">
                <i class="bi bi-search"></i>
                Tampilkan
            </button>

            <a href="{{ route('admin.midtrans-rekonsiliasi.index') }}"
                class="rekon-reset">
                <i class="bi bi-arrow-clockwise"></i>
                Reset
            </a>

        </form>

        {{-- Daftar Kendala Pembayaran --}}
        <section class="rekon-card">

            <div class="rekon-pad">
                <h2>Daftar Kendala Pembayaran</h2>

                <small>
                    Daftar transaksi pembayaran yang mengalami kendala
                    atau memerlukan pemeriksaan lebih lanjut.
                </small>
            </div>

            <div class="rekon-scroll">

                <table aria-label="Daftar kendala pembayaran">

                    <thead>
                        <tr>
                            <th>ID Kasus</th>
                            <th>Kode Pesanan</th>
                            <th>Status Penanganan</th>
                            <th>Jenis Kendala</th>
                            <th>Kanal Pembayaran</th>
                            <th>Tanggal Dibuat</th>
                            <th>Status Pembayaran</th>
                            <th>Pemrosesan / Pengiriman</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($kasus as $item)
                            <tr>
                                <td>#{{ $item['id'] }}</td>

                                <td>
                                    @if ($item['pesanan'])
                                        <a href="{{ route('admin.pesanan.show', $item['pesanan']->id) }}">
                                            {{ $item['pesanan']->kode_pesanan }}
                                        </a>
                                    @else
                                        Belum tersedia
                                    @endif
                                </td>

                                <td>{{ $item['penanganan'] }}</td>

                                <td>{{ $item['alasan'] }}</td>

                                <td>{{ $item['kanal'] }}</td>

                                <td>
                                    {{ $item['dibuat']?->format('d/m/Y H:i:s') ?? 'Belum tersedia' }}
                                </td>

                                <td>
                                    {{ $item['lokal'] ?? 'Belum tersedia' }}
                                </td>

                                <td>{{ $item['hambatan'] }}</td>

                                <td>
                                    <a href="{{ route('admin.midtrans-rekonsiliasi.show', $item['id']) }}"
                                        class="rekon-action">

                                        <i class="bi bi-eye"></i>
                                        Detail

                                    </a>
                                </td>
                            </tr>
                        @empty

                            <tr>
                                <td colspan="9" class="rekon-empty">
                                    Tidak ada kendala pembayaran yang cocok.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.midtrans-rekonsiliasi.pagination', [
                'paginator' => $kasus,
                'label' => 'Halaman kasus'
            ])
        </section>

        {{-- Sesi Pembayaran yang Perlu Diperiksa --}}
        <section class="rekon-card">
            <div class="rekon-pad">
                <h2>Sesi Pembayaran yang Perlu Diperiksa</h2>
                <small>
                    Daftar permintaan sesi pembayaran yang belum dapat
                    dipastikan hasilnya dan memerlukan pemeriksaan
                    lebih lanjut.
                </small>
            </div>

            <div class="rekon-scroll">
                <table aria-label="Sesi pembayaran yang perlu diperiksa">
                    <thead>
                        <tr>
                            <th>ID Pembayaran</th>
                            <th>Kode Pesanan</th>
                            <th>Waktu Permintaan</th>
                            <th>Status Pembayaran</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($kandidat as $item)
                            <tr>
                                <td>#{{ $item->id }}</td>
                                <td>
                                    @if ($item->pesanan)
                                        <a href="{{ route('admin.pesanan.show', $item->pesanan->id) }}">
                                            {{ $item->pesanan->kode_pesanan }}
                                        </a>
                                    @else
                                        Belum tersedia
                                    @endif
                                </td>

                                <td>
                                    {{ $item->snap_requested_at?->format('d/m/Y H:i:s') ?? 'Belum tersedia' }}
                                </td>

                                <td>{{ $item->status_pembayaran }}</td>
                            </tr>
                        @empty

                            <tr>
                                <td colspan="4" class="rekon-empty">
                                    Belum ada sesi pembayaran yang perlu diperiksa.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.midtrans-rekonsiliasi.pagination', [
                'paginator' => $kandidat,
                'label' => 'Halaman sesi pembayaran'
            ])
        </section>
    </div>
@endsection