@extends('admin.layouts.app')

@section('title', 'Detail Pesanan')

@push('styles')
<style>
    .detail-page {
        font-family: 'Poppins', sans-serif;
        color: #111827;
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    .detail-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .detail-breadcrumb {
        font-size: 13px;
        color: #64748B;
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
        background: white;
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

    .detail-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .detail-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0;
    }

    .detail-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 22px;
    }

    .detail-card-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 20px;
    }

    .detail-info-row {
        display: grid;
        grid-template-columns: 130px 10px minmax(0, 1fr);
        gap: 8px;
        align-items: start;
        margin-bottom: 13px;
        font-size: 13px;
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

    .detail-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
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

    .detail-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .detail-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        white-space: nowrap;
    }

    .detail-table th,
    .detail-table td {
        padding: 13px 10px;
        text-align: left;
        border-bottom: 1px solid #E2E8F0;
        vertical-align: middle;
    }

    .detail-table th {
        color: #64748B;
        font-weight: 600;
    }

    .detail-product-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border: 1px solid #E2E8F0;
        border-radius: 7px;
    }

    .detail-product-placeholder {
        width: 45px;
        height: 45px;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #F1F5F9;
        border-radius: 7px;
        color: #64748B;
        font-size: 20px;
    }

    .detail-product-name {
        font-weight: 500;
        white-space: normal;
        min-width: 110px;
    }

    .detail-total {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 10px;
        font-size: 13px;
    }

    .detail-total-final {
        background: #F8FAFC;
        font-weight: 700;
        border-radius: 6px;
    }

    .detail-status-select {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        background: white;
        color: #111827;
        margin-bottom: 12px;
    }

    .detail-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 11px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    .detail-save-btn {
        border: 1px solid #008A50;
        background: #008A50;
        color: white;
        margin-bottom: 10px;
    }

    .detail-reject-btn {
        border: 1px solid #EF4444;
        background: white;
        color: #EF4444;
    }

    .detail-action-btn:disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    @media (max-width: 1100px) {
        .detail-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .detail-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .detail-info-row {
            grid-template-columns: 105px 8px minmax(0, 1fr);
        }

        .detail-card {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
@php
    $kelasStatus = match($pesanan->status_pesanan) {
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
                            Anda dapat memproses pesanan melalui bagian Ubah Status Pesanan.
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
                                        @if($produk?->gambar)
                                            <img
                                                src="{{ asset('storage/' . $produk->gambar) }}"
                                                class="detail-product-img"
                                                alt="Gambar Produk"
                                            >
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
                <h3 class="detail-card-title">Ubah Status Pesanan</h3>

                @php
                    $pilihanStatus = match($pesanan->status_pesanan) {
                        'Menunggu' => ['Diproses'],
                        'Diproses' => ['Dikirim'],
                        'Dikirim' => ['Selesai'],
                        default => []
                    };

                    $bolehDitolak = in_array(
                        $pesanan->status_pesanan,
                        ['Menunggu', 'Diproses'],
                        true
                    );
                @endphp

                <form
                    action="{{ route('admin.pesanan.updateStatus', $pesanan->id) }}"
                    method="POST"
                >
                    @csrf
                    @method('PATCH')

                    <label class="detail-info-label" for="status_pesanan">
                        Status
                    </label>

                    <select
                        id="status_pesanan"
                        name="status_pesanan"
                        class="detail-status-select"
                        {{ empty($pilihanStatus) ? 'disabled' : '' }}
                        required
                    >
                        <option value="" selected>
                            {{ $pesanan->status_pesanan }}
                        </option>

                        @foreach($pilihanStatus as $status)
                            <option value="{{ $status }}">
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="detail-action-btn detail-save-btn"
                        {{ empty($pilihanStatus) ? 'disabled' : '' }}
                    >
                        Simpan Perubahan
                    </button>
                </form>

                <form
                    action="{{ route('admin.pesanan.tolak', $pesanan->id) }}"
                    method="POST"
                    onsubmit="return confirm('Apakah Anda yakin ingin menolak pesanan ini?');"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="detail-action-btn detail-reject-btn"
                        {{ !$bolehDitolak ? 'disabled' : '' }}
                    >
                        <i class="bi bi-x-circle"></i>
                        Tolak Pesanan
                    </button>
                </form>
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
                        {{ $pesanan->pengiriman?->status_pengiriman ?? 'Belum tersedia' }}
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
                            ? \Carbon\Carbon::parse($pesanan->pengiriman->waktu_dikirim)->format('d M Y H:i')
                            : '-' }}
                    </span>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection