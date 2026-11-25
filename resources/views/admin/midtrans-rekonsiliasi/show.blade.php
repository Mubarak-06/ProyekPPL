@extends('admin.layouts.app')

@section('title', 'Detail Pemeriksaan Pembayaran')

@include('admin.midtrans-rekonsiliasi.styles')

@section('content')
    <div class="rekon">

        {{-- Judul dan Breadcrumb --}}
        <div class="rekon-detail-header">

            <div>
                <h1>Detail Pemeriksaan Pembayaran</h1>

                <div class="rekon-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">
                        Dashboard
                    </a>

                    &nbsp; › &nbsp;

                    <a href="{{ route('admin.midtrans-rekonsiliasi.index') }}">
                        Pemeriksaan Pembayaran
                    </a>

                    &nbsp; › &nbsp; Detail Pemeriksaan
                </div>
            </div>

            <a href="{{ route('admin.midtrans-rekonsiliasi.index') }}"
                class="rekon-back">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>

        </div>

        {{-- Pesan Hasil Pemeriksaan --}}
        @if (session('hasil_rekonsiliasi'))
            <div class="rekon-notice" role="status">
                {{ session('hasil_rekonsiliasi') }}
            </div>
        @endif

        {{-- Tombol Pemeriksaan --}}
        <div class="rekon-detail-actions">

            @if ($kasus['bisa_diperiksa'])

                <form method="POST"
                    action="{{ route('admin.midtrans-rekonsiliasi.review', $kasus['id']) }}">

                    @csrf

                    <button type="submit" class="rekon-btn">
                        <i class="bi bi-arrow-clockwise"></i>
                        Periksa Status Midtrans
                    </button>

                </form>

            @endif

            @if ($kasus['pesanan'])

                <a href="{{ route('admin.pesanan.show', $kasus['pesanan']->id) }}"
                    class="rekon-back">

                    <i class="bi bi-eye"></i>
                    Detail Pesanan

                </a>

            @endif

        </div>

        @if (!$kasus['bisa_diperiksa'])

            <div class="rekon-notice">
                <i class="bi bi-info-circle"></i>
                Pembayaran memerlukan pemeriksaan manual karena
                belum memiliki ID transaksi yang tersimpan.
            </div>

        @endif

        {{-- Informasi Pembayaran --}}
        <section class="rekon-card">

            <div class="rekon-card-header">
                Informasi Pembayaran
            </div>

            <div class="rekon-card-body">

                <div class="rekon-info-grid">

                    @foreach ([
                        'ID Pembayaran' => $kasus['pembayaran_id'],
                        'Kode Pesanan' => $kasus['pesanan']?->kode_pesanan,
                        'Status Pesanan' => $kasus['pesanan']?->status_pesanan,
                        'Kanal Pembayaran' => $kasus['kanal'],
                        'Metode Pembayaran' => $kasus['metode'],
                        'Jumlah Pembayaran' => isset($kasus['nominal'])
                            ? 'Rp '.number_format($kasus['nominal'], 2, ',', '.')
                            : null,
                        'Status Transaksi Midtrans' => $kasus['gateway'],
                        'Jenis Pembayaran Midtrans' => $kasus['jenis'],
                    ] as $label => $value)

                        <div>

                            <div class="rekon-info-label">
                                {{ $label }}
                            </div>

                            <div class="rekon-info-value">
                                {{ $value ?? 'Belum tersedia' }}
                            </div>

                        </div>

                    @endforeach

                    {{-- Status Pembayaran --}}
                    <div>

                        <div class="rekon-info-label">
                            Status Pembayaran Lokal
                        </div>

                        <div class="rekon-info-value">

                            @if ($kasus['lokal'] === 'Berhasil')

                                <span class="rekon-status success">
                                    Berhasil
                                </span>

                            @elseif ($kasus['lokal'] === 'Menunggu')

                                <span class="rekon-status pending">
                                    Menunggu
                                </span>

                            @elseif ($kasus['lokal'] === 'Gagal')

                                <span class="rekon-status failed">
                                    Gagal
                                </span>

                            @else

                                {{ $kasus['lokal'] ?? 'Belum tersedia' }}

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </section>

        {{-- Informasi Pemeriksaan --}}
        <section class="rekon-card">

            <div class="rekon-card-header">
                Informasi Pemeriksaan
            </div>

            <div class="rekon-card-body">

                <div class="rekon-info-grid">

                    @foreach ([
                        'ID Kasus' => '#'.$kasus['id'],
                        'Status Penanganan' => $kasus['penanganan'],
                        'Jenis Kendala' => $kasus['alasan'],
                        'Tanggal Dibuat' => $kasus['dibuat']?->format('d/m/Y H:i:s'),
                        'Pemeriksaan Terakhir' => $kasus['diperiksa']?->format('d/m/Y H:i:s'),
                        'Jadwal Pemeriksaan Ulang' => $kasus['jadwal']?->format('d/m/Y H:i:s'),
                        'Pemrosesan / Pengiriman' => $kasus['hambatan'],
                    ] as $label => $value)

                        <div>

                            <div class="rekon-info-label">
                                {{ $label }}
                            </div>

                            <div class="rekon-info-value">
                                {{ $value ?? 'Belum tersedia' }}
                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>

        {{-- Riwayat Pemeriksaan --}}
        <section class="rekon-card">

            <div class="rekon-card-header">
                Riwayat Pemeriksaan
            </div>

            <div class="rekon-scroll">

                <table aria-label="Riwayat pemeriksaan">

                    <thead>
                        <tr>
                            <th>Waktu (UTC)</th>
                            <th>Diperiksa Oleh</th>
                            <th>Tindakan</th>
                            <th>Hasil Pemeriksaan</th>
                            <th>Keputusan</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($riwayat as $entri)

                            <tr>

                                <td>
                                    {{ $entri->waktu_tindakan }}
                                </td>

                                <td>
                                    {{ $entri->jenis_aktor === 'admin'
                                        ? 'Admin #'.$entri->admin_id
                                        : $entri->proses_sistem }}
                                </td>

                                <td>
                                    {{ str_replace('_', ' ', $entri->jenis_tindakan) }}
                                </td>

                                <td>
                                    {{ str_replace('_', ' ', $entri->hasil_pemeriksaan) }}
                                </td>

                                <td>
                                    {{ str_replace('_', ' ', $entri->keputusan_operasional) }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="rekon-empty">
                                    Belum ada riwayat pemeriksaan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.midtrans-rekonsiliasi.pagination', [
                'paginator' => $riwayat,
                'label' => 'Halaman riwayat'
            ])

        </section>
    </div>
@endsection
