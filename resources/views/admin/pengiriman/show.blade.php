@extends('admin.layouts.app')

@section('title', 'Detail Pengiriman')

@push('styles')
    <style>
        .kirim-detail-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .kirim-detail-breadcrumb {
            margin: 5px 0 20px;
            color: #64748B;
            font-size: 12px;
        }

        .kirim-detail-breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .kirim-detail-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .kirim-detail-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #111827;
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        .kirim-detail-back:hover {
            background: #F8FAFC;
        }

        .kirim-detail-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 18px;
        }

        .kirim-detail-card h3 {
            margin: 0 0 20px;
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .kirim-detail-info {
            max-width: 760px;
        }

        .kirim-detail-row {
            display: grid;
            grid-template-columns: 190px 12px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 14px;
            font-size: 12px;
            align-items: start;
        }

        .kirim-detail-row:last-child {
            margin-bottom: 0;
        }

        .kirim-detail-label {
            color: #64748B;
        }

        .kirim-detail-value {
            color: #111827;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .kirim-detail-muted {
            display: block;
            color: #94A3B8;
            font-size: 11px;
            margin-top: 3px;
        }

        .kirim-detail-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        .kirim-detail-status.Menunggu {
            background: #FEF3C7;
            color: #D97706;
        }

        .kirim-detail-status.Diproses {
            background: #DBEAFE;
            color: #2563EB;
        }

        .kirim-detail-status.Dikirim {
            background: #DCFCE7;
            color: #16A34A;
        }

        .kirim-detail-status.Selesai {
            background: #DCFCE7;
            color: #15803D;
        }

        .kirim-detail-status.Ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        .kirim-detail-table-wrap {
            overflow-x: auto;
        }

        .kirim-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .kirim-detail-table th,
        .kirim-detail-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            vertical-align: middle;
        }

        .kirim-detail-table th {
            color: #111827;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .kirim-detail-table td {
            color: #334155;
        }

        .kirim-detail-table tfoot td {
            background: #F8FAFC;
            color: #111827;
            font-weight: 600;
            border-bottom: none;
        }

        .kirim-detail-image {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
        }

        .kirim-detail-product {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .kirim-detail-product-name {
            font-weight: 500;
            color: #111827;
        }

        @media (max-width: 600px) {
            .kirim-detail-row {
                grid-template-columns: 120px 10px minmax(0, 1fr);
            }

            .kirim-detail-title {
                font-size: 20px;
            }
        }
    </style>
@endpush

@section('content')

    <div class="kirim-detail-top">
        <div>
            <h1 class="kirim-detail-title">Detail Pengiriman</h1>

            <div class="kirim-detail-breadcrumb">
                <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
                &nbsp; › &nbsp;
                <a href="{{ route('admin.pengiriman.index') }}">Pengiriman</a>
                &nbsp; › &nbsp; Detail Pengiriman
            </div>
        </div>

        <a href="{{ route('admin.pengiriman.index') }}" class="kirim-detail-back">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Informasi Pengiriman --}}
    <div class="kirim-detail-card">
        <h3>Informasi Pengiriman</h3>

        <div class="kirim-detail-info">

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Kode Pesanan</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->pesanan?->kode_pesanan ?? '-' }}
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Tanggal Pengiriman</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->waktu_dikirim
        ? $pengiriman->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('d M Y, H:i').' WITA'
        : '-' }}
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Metode Pengiriman</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    Kurir Toko
                    <span class="kirim-detail-muted">
                        Dalam Kota Banjarmasin
                    </span>
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Biaya Kirim</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    Rp{{ number_format($pengiriman->ongkos_kirim, 0, ',', '.') }}
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Status</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    <span class="kirim-detail-status {{ $pengiriman->status_pengiriman }}">
                        {{ $pengiriman->tahap() }}
                    </span>
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Catatan</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->catatan_driver ?: '-' }}
                </span>
            </div>

        </div>
    </div>

    {{-- Informasi Penerima --}}
    <div class="kirim-detail-card">
        <h3>Informasi Penerima</h3>

        <div class="kirim-detail-info">

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Nama</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->nama_penerima }}
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">No. Telepon</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->nomor_telepon ?: '-' }}
                </span>
            </div>

            <div class="kirim-detail-row">
                <span class="kirim-detail-label">Alamat</span>
                <span>:</span>
                <span class="kirim-detail-value">
                    {{ $pengiriman->alamat_pengiriman }}
                </span>
            </div>

        </div>
    </div>

    {{-- Daftar Produk --}}
    <div class="kirim-detail-card">
        <h3>Daftar Produk</h3>

        <div class="kirim-detail-table-wrap">
            <table class="kirim-detail-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Gambar</th>
                        <th>Kode Barang</th>
                        <th>Nama Produk</th>
                        <th>Ukuran</th>
                        <th>Material</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($pengiriman->pesanan?->detailPesanan ?? [] as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                @php
                                    $gambarUtama = $detail->produk?->gambar[0] ?? null;
                                @endphp
                                @if($gambarUtama)
                                    <img src="{{ asset('storage/' . $gambarUtama) }}" alt="{{ $detail->nama_produk }}"
                                        class="kirim-detail-image">
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                {{ $detail->produk?->kode_barang ?? '-' }}
                            </td>

                            <td>
                                <span class="kirim-detail-product-name">
                                    {{ $detail->nama_produk }}
                                </span>
                            </td>

                            <td>{{ $detail->produk?->ukuran ?? '-' }}</td>

                            <td>
                                {{ $detail->produk?->material?->nama_material ?? '-' }}
                            </td>

                            <td>{{ $detail->jumlah }}</td>

                            <td>
                                Rp{{ number_format($detail->harga_satuan, 0, ',', '.') }}
                            </td>

                            <td>
                                Rp{{ number_format($detail->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; color:#94A3B8;">
                                Tidak ada data produk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="8" style="text-align:right;">Total</td>
                        <td>
                            Rp{{ number_format(
                                $pengiriman->pesanan?->detailPesanan?->sum('subtotal') ?? 0,
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @include('admin.pengiriman.operasional')
@endsection
