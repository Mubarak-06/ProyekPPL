@extends('admin.layouts.app')

@section('title', 'Detail Pesanan')
@push('styles')
    <style>
        /* Halaman detail pesanan */
        .detail-page {
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .detail-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 0;
        }

        .detail-breadcrumb a {
            color: #64748B;
            text-decoration: none;
        }

        .detail-breadcrumb a:hover {
            color: #008A50;
        }

        .detail-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .detail-back:hover {
            color: #008A50;
            border-color: #008A50;
        }

        /* Layout dan card */
        .detail-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            gap: 16px;
            align-items: start;
        }

        .detail-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .detail-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 18px;
            min-width: 0;
        }

        .detail-card-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 16px;
        }

        /* Informasi pesanan */
        .detail-info-row {
            display: grid;
            grid-template-columns: 115px 8px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            margin-bottom: 12px;
            font-size: 12px;
        }

        .detail-info-row:last-child {
            margin-bottom: 0;
        }

        .detail-info-label {
            color: #64748B;
        }

        .detail-info-value {
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        /* Status pesanan */
        .detail-status {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-menunggu {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-diproses {
            background: #DBEAFE;
            color: #2563EB;
        }

        .status-dikirim {
            background: #E0E7FF;
            color: #4F46E5;
        }

        .status-selesai {
            background: #DCFCE7;
            color: #15803D;
        }

        .status-ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        /* Peringatan pesanan */
        .detail-warning {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 18px;
            padding: 13px;
            border: 1px solid #FDE68A;
            border-radius: 8px;
            background: #FFFBEB;
            color: #B45309;
            font-size: 12px;
            line-height: 1.6;
        }

        .detail-warning i {
            font-size: 17px;
        }

        /* Tabel produk */
        .detail-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            white-space: nowrap;
        }

        .detail-table th,
        .detail-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }

        .detail-table th {
            color: #64748B;
            font-weight: 600;
        }

        .detail-product-img,
        .detail-product-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 7px;
        }

        .detail-product-img {
            object-fit: cover;
            border: 1px solid #E2E8F0;
        }

        .detail-product-placeholder {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #F1F5F9;
            color: #64748B;
            font-size: 20px;
        }

        .detail-product-name {
            font-weight: 500;
            white-space: normal;
            min-width: 110px;
        }

        /* Ringkasan pembayaran */
        .detail-total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 10px;
            font-size: 12px;
        }

        .detail-total-final {
            background: #F0FDF4;
            color: #008A50;
            font-weight: 700;
            border-radius: 6px;
            margin-top: 4px;
        }

        /* Notifikasi */
        .detail-page .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 15px;
            margin-bottom: 16px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
        }

        .detail-page .alert-success {
            background: #F0FDF4;
            border-color: #BBF7D0;
            color: #15803D;
        }

        .detail-page .alert-danger {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #B91C1C;
        }

        /* Panel tindakan pesanan */
        .detail-action-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .detail-action-header .detail-card-title {
            margin: 0;
        }

        .detail-action-info {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background: #F8FAFC;
            color: #475569;
            font-size: 11px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .detail-action-info i {
            font-size: 17px;
            flex-shrink: 0;
            color: #64748B;
        }

        .detail-action-info strong {
            display: block;
            color: #334155;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .detail-action-info p {
            margin: 0;
        }

        .detail-action-info p+p {
            margin-top: 5px;
        }

        .detail-action-info.warning {
            background: #FFFBEB;
            border-color: #FDE68A;
            color: #92400E;
        }

        .detail-action-info.warning i,
        .detail-action-info.warning strong {
            color: #B45309;
        }

        .detail-action-info.success {
            background: #F0FDF4;
            border-color: #BBF7D0;
            color: #15803D;
        }

        .detail-action-info.success i,
        .detail-action-info.success strong {
            color: #15803D;
        }

        .detail-action-info.danger {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #B91C1C;
        }

        .detail-action-info.danger i,
        .detail-action-info.danger strong {
            color: #B91C1C;
        }

        /* Tombol tindakan */
        .detail-action-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .detail-action-group form {
            margin: 0;
        }

        .detail-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 40px;
            padding: 10px 12px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            line-height: 1.5;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }

        .detail-action-btn i {
            font-size: 15px;
        }

        .detail-save-btn {
            border: 1px solid #008A50;
            background: #008A50;
            color: #FFFFFF;
            margin: 0;
        }

        .detail-save-btn:hover:not(:disabled) {
            background: #007344;
            border-color: #007344;
        }

        .detail-shipping-btn {
            background: #FFFFFF;
            border: 1px solid #008A50;
            color: #008A50;
        }

        .detail-shipping-btn:hover {
            background: #F0FDF4;
        }

        .detail-shipping-primary {
            background: #008A50;
            border-color: #008A50;
            color: #FFFFFF;
        }

        .detail-shipping-primary:hover {
            background: #007344;
            border-color: #007344;
        }

        .detail-reject-btn {
            border: 1px solid #EF4444;
            background: #FFFFFF;
            color: #EF4444;
        }

        .detail-reject-btn:hover:not(:disabled) {
            background: #FEF2F2;
        }

        .detail-action-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .detail-action-btn:focus-visible,
        .detail-back:focus-visible {
            outline: 2px solid #008A50;
            outline-offset: 3px;
        }

        /* Responsif */
        @media (max-width: 1100px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .detail-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .detail-title {
                font-size: 20px;
            }

            .detail-card {
                padding: 16px;
            }

            .detail-info-row {
                grid-template-columns: 105px 8px minmax(0, 1fr);
                gap: 6px;
                font-size: 11px;
            }

            .detail-action-btn {
                min-height: 44px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $kelasStatus = match ($pesanan->status_pesanan) {
            'Menunggu' => 'status-menunggu',
            'Diproses' => 'status-diproses',
            'Dikirim' => 'status-dikirim',
            'Selesai' => 'status-selesai',
            'Ditolak' => 'status-ditolak',
            default => ''
        };
    @endphp

    <div class="detail-page">

        <div class="detail-header">
            <div>
                <h1 class="detail-title">Detail Pesanan</h1>
                <div class="detail-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    &nbsp; › &nbsp;
                    <a href="{{ route('admin.pesanan.index') }}">Pesanan</a>
                    &nbsp; › &nbsp; Detail Pesanan
                </div>
            </div>

            <a href="{{ route('admin.pesanan.index') }}" class="detail-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                {{ $errors->first() }}
            </div>
        @endif
        <div class="detail-layout">

            {{-- KOLOM KIRI --}}
            <div class="detail-column">

                <div class="detail-card">
                    <h3 class="detail-card-title">Informasi Pesanan</h3>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Kode Pesanan</span>
                        <span>:</span>
                        <span class="detail-info-value">{{ $pesanan->kode_pesanan }}</span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Tanggal Pesanan</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->created_at->format('d M Y H:i') }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Status</span>
                        <span>:</span>
                        <span>
                            <span class="detail-status {{ $kelasStatus }}">
                                {{ $pesanan->status_pesanan }}
                            </span>
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Total</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            Rp{{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Catatan</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->catatan ?: '-' }}
                        </span>
                    </div>

                    @if($pesanan->status_pesanan === 'Menunggu')
                        <div class="detail-warning">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>
                                Pesanan ini masih berstatus Menunggu.
                                Periksa informasi pembayaran dan tindakan yang tersedia pada panel Tindakan Pesanan.
                            </span>
                        </div>
                    @endif
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">Daftar Produk</h3>

                    <div class="detail-table-wrapper">
                        <table class="detail-table">
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pesanan->detailPesanan as $detail)
                                    @php
                                        $produk = $detail->produk;

                                        $material = $produk?->material_id
                                            ? DB::table('material')
                                                ->where('id', $produk->material_id)
                                                ->value('nama_material')
                                            : null;
                                    @endphp

                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                            @php
                                                $gambarUtama = $produk?->gambar[0] ?? null;
                                            @endphp

                                            @if($gambarUtama)
                                                <img src="{{ asset('storage/' . $gambarUtama) }}" class="detail-product-img"
                                                    alt="Gambar Produk">
                                            @else
                                                <div class="detail-product-placeholder">
                                                    <i class="bi bi-box"></i>
                                                </div>
                                            @endif
                                        </td>

                                        <td>{{ $produk?->kode_barang ?? '-' }}</td>

                                        <td>
                                            <div class="detail-product-name">
                                                {{ $detail->nama_produk }}
                                            </div>
                                        </td>

                                        <td>{{ $produk?->ukuran ?: '-' }}</td>

                                        <td>{{ $material ?: '-' }}</td>

                                        <td>{{ $detail->jumlah }}</td>

                                        <td>
                                            Rp{{ number_format($detail->harga_satuan, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="detail-total">
                        <span>Subtotal</span>
                        <span>
                            Rp{{ number_format($pesanan->subtotal, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="detail-total">
                        <span>Ongkos Kirim</span>
                        <span>
                            Rp{{ number_format($pesanan->ongkos_kirim, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="detail-total detail-total-final">
                        <span>Total Pembayaran</span>
                        <span>
                            Rp{{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">Alamat Pengiriman</h3>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Nama</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->pengiriman?->nama_penerima
        ?? $pesanan->alamat?->nama_penerima
        ?? '-' }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">No. Telepon</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->pengiriman?->nomor_telepon
        ?? $pesanan->alamat?->nomor_telepon
        ?? '-' }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Alamat</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->pengiriman?->alamat_pengiriman
        ?? $pesanan->alamat?->alamat_lengkap
        ?? '-' }}
                        </span>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN --}}
            <div class="detail-column">

                <div class="detail-card">
                    @php
                        $pilihanStatus = $pesanan->status_pesanan === 'Menunggu'
                            && !$hambatanProses ? ['Diproses'] : [];

                        $bolehDitolak = in_array(
                            $pesanan->status_pesanan,
                            ['Menunggu', 'Diproses'],
                            true
                        );
                    @endphp

                    <div class="detail-action-header">
                        <h3 class="detail-card-title">
                            Tindakan Pesanan
                        </h3>

                        <span class="detail-status {{ $kelasStatus }}">
                            {{ $pesanan->status_pesanan }}
                        </span>
                    </div>

                    @if($pesanan->alasanPembatalan())
                        <div class="detail-action-info danger" role="status">
                            <i class="bi bi-exclamation-circle"></i>
                            <div>
                                <strong>Informasi Pembatalan</strong>
                                <p>{{ $pesanan->alasanPembatalan() }}</p>

                                @if($pesanan->pengiriman?->status_pengiriman === 'Ditolak' && $pesanan->detailPesanan->isNotEmpty())
                                    <p>Stok reservasi dikembalikan.</p>
                                @else
                                    <p>Riwayat stok perlu diperiksa.</p>
                                @endif
                            </div>
                        </div>

                    @elseif($pesanan->status_pesanan === 'Menunggu')
                        <div class="detail-action-info warning">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong>Menunggu Diproses</strong>
                                <p>Pastikan pembayaran telah berhasil sebelum memproses pesanan.</p>

                                @if($pesanan->pembayaran?->gateway === 'midtrans')
                                    <p>Stok reservasi masih ditahan.</p>
                                @endif
                            </div>
                        </div>

                    @elseif($pesanan->status_pesanan === 'Diproses')
                        <div class="detail-action-info">
                            <i class="bi bi-box-seam"></i>
                            <div>
                                <strong>Pesanan Sedang Diproses</strong>
                                <p>Kelola persiapan dan pantau perkembangan pengiriman melalui halaman pengiriman.</p>
                            </div>
                        </div>

                    @elseif($pesanan->status_pesanan === 'Dikirim')
                        <div class="detail-action-info">
                            <i class="bi bi-truck"></i>
                            <div>
                                <strong>Tahap Pengiriman</strong>
                                <p>Pelanggan menyelesaikan pesanan setelah kurir mencatat penyerahan.</p>
                            </div>
                        </div>

                    @elseif($pesanan->status_pesanan === 'Selesai')
                        <div class="detail-action-info success">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <strong>Pesanan Selesai</strong>
                                <p>Pesanan telah diselesaikan. Informasi pengiriman tetap dapat dilihat.</p>
                            </div>
                        </div>

                    @elseif($pesanan->status_pesanan === 'Ditolak')
                        <div class="detail-action-info danger">
                            <i class="bi bi-x-circle"></i>
                            <div>
                                <strong>Pesanan Ditolak</strong>
                                <p>Pesanan tidak dapat diproses lebih lanjut.</p>
                            </div>
                        </div>
                    @endif

                    @if($pesanan->pembayaran?->rekonsiliasiAktif->contains(fn($kasus) => $kasus->menahanPemenuhan($pesanan->pembayaran)))
                        <div class="detail-action-info warning" role="status">
                            <i class="bi bi-shield-exclamation"></i>
                            <div>
                                <strong>Pemeriksaan Pembayaran</strong>
                                <p>Pembayaran sedang diperiksa melalui rekonsiliasi.</p>
                            </div>
                        </div>
                    @elseif($hambatanProses)
                        <div class="detail-action-info warning" role="status">
                            <i class="bi bi-exclamation-triangle"></i>
                            <div>
                                <strong>Perhatian</strong>
                                <p>{{ $hambatanProses }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="detail-action-group">

                        @if(!empty($pilihanStatus))
                            <form action="{{ route('admin.pesanan.updateStatus', $pesanan->id) }}" method="POST"
                                onsubmit="return confirm('Proses pesanan ini sekarang?');">
                                @csrf
                                @method('PATCH')

                                <input type="hidden" name="status_pesanan" value="Diproses">

                                <button type="submit" class="detail-action-btn detail-save-btn">
                                    <i class="bi bi-box-seam"></i>
                                    Proses Pesanan
                                </button>
                            </form>
                        @endif

                        @if($pesanan->pengiriman)
                            <a href="{{ route('admin.pengiriman.show', $pesanan->pengiriman) }}"
                                class="detail-action-btn detail-shipping-btn {{ $pesanan->status_pesanan === 'Diproses' ? 'detail-shipping-primary' : '' }}">
                                <i class="bi bi-truck"></i>

                                @if($pesanan->status_pesanan === 'Diproses')
                                    Kelola Pengiriman
                                @else
                                    Lihat Pengiriman
                                @endif
                            </a>
                        @endif

                        @if($bolehDitolak)
                            <form action="{{ route('admin.pesanan.tolak', $pesanan->id) }}" method="POST"
                                onsubmit="return confirm('Apakah Anda yakin ingin menolak pesanan ini?');">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="detail-action-btn detail-reject-btn">
                                    <i class="bi bi-x-circle"></i>
                                    Tolak Pesanan
                                </button>
                            </form>
                        @endif

                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">
                        <i class="bi bi-person-fill" style="color:#008A50"></i>
                        Informasi Pelanggan
                    </h3>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Nama</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->user?->name ?? '-' }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">No. Telepon</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->alamat?->nomor_telepon ?? '-' }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Email</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->user?->email ?? '-' }}
                        </span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">
                        <i class="bi bi-truck" style="color:#008A50"></i>
                        Informasi Pengiriman
                    </h3>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Kurir</span>
                        <span>:</span>
                        <span class="detail-info-value">Kurir Toko</span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Status Pengiriman</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->pengiriman?->tahap() ?? 'Belum tersedia' }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Ongkos Kirim</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            Rp{{ number_format($pesanan->ongkos_kirim, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="detail-info-row">
                        <span class="detail-info-label">Waktu Dikirim</span>
                        <span>:</span>
                        <span class="detail-info-value">
                            {{ $pesanan->pengiriman?->waktu_dikirim
        ? $pesanan->pengiriman->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('d M Y H:i') . ' WITA'
        : '-' }}
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection