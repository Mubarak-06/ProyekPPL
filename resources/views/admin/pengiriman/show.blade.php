@extends('admin.layouts.app')

@section('title', 'Detail Pengiriman')

@push('styles')
    <style>
        /* Header halaman */
        .kirim-detail-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .kirim-detail-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .kirim-detail-breadcrumb {
            margin: 5px 0 0;
            color: #64748B;
            font-size: 11px;
        }

        .kirim-detail-breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .kirim-detail-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background: #FFFFFF;
            color: #111827;
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        .kirim-detail-back:hover {
            background: #F1F5F9;
        }

        /* Card informasi */
        .kirim-detail-card,
        .operasional-kirim {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 18px;
            margin: 0 0 16px;
            min-width: 0;
        }

        .kirim-detail-card h3,
        .operasional-kirim h2 {
            margin: 0 0 18px;
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .kirim-detail-info {
            max-width: 900px;
        }

        .kirim-detail-row {
            display: grid;
            grid-template-columns: 150px 8px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
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
            color: #64748B;
            font-size: 11px;
            margin-top: 3px;
        }

        /* Status pengiriman */
        .kirim-detail-status {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            background: #F1F5F9;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
        }

        .kirim-detail-status.Menunggu,
        .kirim-detail-status.Belum {
            background: #FEF3C7;
            color: #B45309;
        }

        .kirim-detail-status.Diproses,
        .kirim-detail-status.Disiapkan,
        .kirim-detail-status.Siap {
            background: #DBEAFE;
            color: #2563EB;
        }

        .kirim-detail-status.Dikirim,
        .kirim-detail-status.Dalam {
            background: #EDE9FE;
            color: #6D28D9;
        }

        .kirim-detail-status.Selesai,
        .kirim-detail-status.Barang,
        .kirim-detail-status.Diterima {
            background: #DCFCE7;
            color: #15803D;
        }

        .kirim-detail-status.Ditolak,
        .kirim-detail-status.Dibatalkan {
            background: #FEE2E2;
            color: #DC2626;
        }

        /* Tabel produk */
        .kirim-detail-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .kirim-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            white-space: nowrap;
        }

        .kirim-detail-table th,
        .kirim-detail-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
            vertical-align: middle;
        }

        .kirim-detail-table th {
            background: #F8FAFC;
            color: #64748B;
            font-weight: 600;
        }

        .kirim-detail-table tfoot td {
            background: #F0FDF4;
            color: #008A50;
            font-weight: 600;
        }

        .kirim-detail-image {
            width: 44px;
            height: 44px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            object-fit: contain;
        }

        .kirim-detail-product-name {
            font-weight: 500;
            white-space: normal;
            min-width: 120px;
        }

        /* Operasional pengiriman */
        .operasional-kirim {
            color: #111827;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .operasional-kirim p {
            margin: 0 0 10px;
            line-height: 1.7;
        }

        .operasional-kirim strong {
            font-weight: 600;
        }

        .kirim-operasional-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kirim-operasional-title i {
            color: #008A50;
        }

        .kirim-section-title {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 600;
        }

        /* Notifikasi */
        .kirim-alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid;
            border-radius: 8px;
            line-height: 1.6;
        }

        .kirim-alert i {
            flex-shrink: 0;
            font-size: 15px;
        }

        .kirim-alert-success {
            background: #F0FDF4;
            border-color: #BBF7D0;
            color: #15803D;
        }

        .kirim-alert-danger {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #B91C1C;
        }

        .kirim-alert-warning {
            background: #FFFBEB;
            border-color: #FDE68A;
            color: #92400E;
        }

        /* Timeline pengiriman */
        .kirim-timeline {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .kirim-timeline li {
            position: relative;
            margin-left: 7px;
            padding: 0 0 20px 23px;
            border-left: 2px solid #BBE5CF;
            line-height: 1.6;
            font-weight: 500;
        }

        .kirim-timeline li::before {
            content: '';
            position: absolute;
            top: 4px;
            left: -6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #008A50;
            box-shadow: 0 0 0 3px #DCFCE7;
        }

        .kirim-timeline li:last-child {
            border-left-color: transparent;
            padding-bottom: 0;
        }

        .kirim-timeline time,
        .kirim-timeline small {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            font-weight: 400;
            color: #64748B;
        }

        /* Form pengiriman */
        .kirim-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 0;
            padding: 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background: #F8FAFC;
        }

        .kirim-form label {
            display: grid;
            gap: 6px;
            min-width: 0;
            color: #334155;
            font-size: 12px;
            font-weight: 500;
        }

        .kirim-form .kirim-section-title,
        .kirim-form .kirim-full {
            grid-column: 1 / -1;
        }

        .kirim-form input:not([type="checkbox"]),
        .kirim-form textarea {
            width: 100%;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #FFFFFF;
            color: #111827;
            font: inherit;
        }

        .kirim-form input:not([type="checkbox"]) {
            min-height: 42px;
        }

        .kirim-form textarea {
            min-height: 85px;
            resize: vertical;
        }

        .kirim-form label:has(input[type="checkbox"]) {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .kirim-form input[type="checkbox"] {
            accent-color: #008A50;
        }

        .kirim-form input:focus,
        .kirim-form textarea:focus {
            border-color: #008A50;
            outline: 2px solid #DCFCE7;
        }

        .kirim-tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: fit-content;
            min-height: 40px;
            padding: 10px 16px;
            border: 1px solid #008A50;
            border-radius: 8px;
            background: #008A50;
            color: #FFFFFF;
            font: inherit;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .kirim-tombol:hover {
            background: #007344;
            border-color: #007344;
        }

        .kirim-tombol:focus-visible,
        .kirim-detail-back:focus-visible {
            outline: 2px solid #008A50;
            outline-offset: 3px;
        }

        /* Kendala pengiriman */
        .operasional-kirim .kirim-panel {
            margin: 16px 0;
            padding: 14px;
            border: 1px solid #FDE68A;
            border-radius: 8px;
            background: #FFFBEB;
        }

        .operasional-kirim .kirim-panel h3 {
            margin: 0 0 8px;
            color: #92400E;
            font-size: 13px;
            font-weight: 600;
        }

        /* Bukti penyerahan */
        .kirim-foto {
            display: block;
            width: min(100%, 320px);
            max-height: 280px;
            margin: 10px 0 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            object-fit: contain;
            object-position: left;
        }

        /* Responsif */
        @media (max-width: 768px) {

            .kirim-detail-card,
            .operasional-kirim {
                padding: 14px;
            }

            .kirim-detail-row {
                grid-template-columns: 105px 6px minmax(0, 1fr);
                gap: 6px;
            }

            .kirim-form {
                grid-template-columns: minmax(0, 1fr);
                padding: 12px;
            }

            .kirim-tombol {
                width: 100%;
                min-height: 44px;
            }

            .kirim-detail-top {
                flex-wrap: wrap;
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
        ? $pengiriman->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('d M Y, H:i') . ' WITA'
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