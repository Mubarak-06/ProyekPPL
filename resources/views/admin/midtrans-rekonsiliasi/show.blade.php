@extends('admin.layouts.app')
@section('title', 'Detail Rekonsiliasi Midtrans')
@include('admin.midtrans-rekonsiliasi.styles')

@section('content')
    <div class="rekon">
        <h1>Kasus Midtrans #{{ $kasus['id'] }}</h1>
        <div class="rekon-links">
            <a href="{{ route('admin.midtrans-rekonsiliasi.index') }}">Kembali ke daftar</a>
            @if ($kasus['pesanan'])
                <a href="{{ route('admin.pesanan.show', $kasus['pesanan']->id) }}">Detail pesanan</a>
            @endif
        </div>

        @if (session('hasil_rekonsiliasi'))
            <p role="status">{{ session('hasil_rekonsiliasi') }}</p>
        @endif
        @if($kasus['bisa_diperiksa'])
            <form method="POST" action="{{ route('admin.midtrans-rekonsiliasi.review', $kasus['id']) }}" class="rekon-filter">
                @csrf
                <button type="submit">Periksa status Midtrans</button>
            </form>
        @else
            <p>Kasus memerlukan pemeriksaan manual karena belum memiliki ID transaksi tersimpan.</p>
        @endif

        <section class="rekon-card rekon-pad" aria-label="Informasi kasus">
            <dl class="rekon-fields">
                @foreach ([
                    'ID pembayaran' => $kasus['pembayaran_id'],
                    'Kode pesanan' => $kasus['pesanan']?->kode_pesanan,
                    'Status pesanan' => $kasus['pesanan']?->status_pesanan,
                    'Status penanganan' => $kasus['penanganan'],
                    'Kanal' => $kasus['kanal'],
                    'Status pembayaran lokal' => $kasus['lokal'],
                    'Status transaksi gateway pada kasus' => $kasus['gateway'],
                    'Jenis pembayaran gateway pada kasus' => $kasus['jenis'],
                    'Metode pembayaran lokal' => $kasus['metode'],
                    'Nominal pembayaran lokal' => isset($kasus['nominal']) ? 'Rp '.number_format($kasus['nominal'], 2, ',', '.') : null,
                    'Alasan rekonsiliasi' => $kasus['alasan'],
                    'Waktu pembuatan kasus' => $kasus['dibuat']?->format('d/m/Y H:i:s'),
                    'Pemeriksaan terakhir' => $kasus['diperiksa']?->format('d/m/Y H:i:s'),
                    'Jadwal pemeriksaan ulang tersimpan' => $kasus['jadwal']?->format('d/m/Y H:i:s'),
                    'Pemrosesan / pengiriman' => $kasus['hambatan'],
                ] as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value ?? 'Belum tersedia' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rekon-card">
            <h2 class="rekon-pad">Riwayat pemeriksaan</h2>
            <div class="rekon-scroll">
                <table aria-label="Riwayat pemeriksaan">
                    <thead><tr><th>Waktu (UTC)</th><th>Aktor</th><th>Tindakan</th><th>Hasil</th><th>Keputusan</th></tr></thead>
                    <tbody>
                        @forelse($riwayat as $entri)
                            <tr>
                                <td>{{ $entri->waktu_tindakan }}</td>
                                <td>{{ $entri->jenis_aktor === 'admin' ? 'Admin #'.$entri->admin_id : $entri->proses_sistem }}</td>
                                <td>{{ str_replace('_', ' ', $entri->jenis_tindakan) }}</td>
                                <td>{{ str_replace('_', ' ', $entri->hasil_pemeriksaan) }}</td>
                                <td>{{ str_replace('_', ' ', $entri->keputusan_operasional) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Belum ada riwayat pemeriksaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.midtrans-rekonsiliasi.pagination', ['paginator' => $riwayat, 'label' => 'Halaman riwayat'])
        </section>
    </div>
@endsection
