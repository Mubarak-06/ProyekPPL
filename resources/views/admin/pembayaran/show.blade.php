@extends('admin.layouts.app')

@section('title', 'Detail Pembayaran')

@push('styles')
    <style>
        .pd-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        .pd-breadcrumb {
            font-size: 12px;
            color: #64748B;
            margin-top: 4px;
        }

        .pd-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .pd-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
            font-weight: 600;
        }

        .pd-card-body {
            padding: 20px;
        }

        .pd-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
        }

        .pd-empty {
            font-size: 13px;
            color: #64748B;
            line-height: 1.8;
        }

        .pd-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px 30px;
        }

        .pd-info-label {
            font-size: 11px;
            color: #64748B;
            margin-bottom: 6px;
        }

        .pd-info-value {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .pd-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .pd-status.success {
            background: #DCFCE7;
            color: #15803D;
        }

        .pd-status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .pd-status.failed {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pd-proof {
            max-width: 350px;
            max-height: 400px;
            width: auto;
            height: auto;
            object-fit: contain;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .pd-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h1 class="pd-title">Detail Pembayaran</h1>

            <div class="pd-breadcrumb">
                <a href="{{ route('admin.dashboard') }}" style="color:#64748B; text-decoration:none;">
                    Dashboard
                </a>

                &nbsp; › &nbsp;

                <a href="{{ route('admin.pembayaran.index') }}" style="color:#64748B; text-decoration:none;">
                    Pembayaran
                </a>

                &nbsp; › &nbsp; Detail Pembayaran
            </div>
        </div>

        <a href="{{ route('admin.pembayaran.index') }}" class="pd-back">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
    </div>

    <div class="pd-card">
        <div class="pd-card-header">Informasi Pesanan</div>

        <div class="pd-card-body">
            <div class="pd-info-grid">

                <div>
                    <div class="pd-info-label">Kode Pesanan</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->kode_pesanan }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Tanggal Pesanan</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->tanggal_pesanan
        ? \Carbon\Carbon::parse($pembayaran->tanggal_pesanan)->format('d/m/Y H:i')
        : '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Nama Pelanggan</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->nama_pelanggan }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Email Pelanggan</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->email_pelanggan }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Subtotal</div>
                    <div class="pd-info-value">
                        Rp {{ number_format($pembayaran->subtotal, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Ongkos Kirim</div>
                    <div class="pd-info-value">
                        Rp {{ number_format($pembayaran->ongkos_kirim, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Total Pesanan</div>
                    <div class="pd-info-value">
                        Rp {{ number_format($pembayaran->total_pembayaran, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Status Pesanan</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->status_pesanan }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="pd-card">
        <div class="pd-card-header">Informasi Pembayaran</div>

        <div class="pd-card-body">
            <div class="pd-info-grid">

                <div>
                    <div class="pd-info-label">Metode Pembayaran</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->metode_pembayaran }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Bank / Penyedia</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->nama_penyedia ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Nomor Rekening / DANA</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->nomor_rekening ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Nama Pemilik</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->nama_pemilik ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Jumlah Pembayaran</div>
                    <div class="pd-info-value">
                        Rp {{ number_format($pembayaran->jumlah_pembayaran, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Tanggal Pembayaran</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->waktu_pembayaran
        ? \Carbon\Carbon::parse($pembayaran->waktu_pembayaran)->format('d/m/Y H:i')
        : '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Referensi Pembayaran</div>
                    <div class="pd-info-value">
                        {{ $pembayaran->referensi_pembayaran ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="pd-info-label">Status Pembayaran</div>
                    <div class="pd-info-value">
                        @if($pembayaran->status_pembayaran === 'Berhasil')
                            <span class="pd-status success">Berhasil</span>
                        @elseif($pembayaran->status_pembayaran === 'Menunggu')
                            <span class="pd-status pending">Menunggu</span>
                        @elseif($pembayaran->status_pembayaran === 'Gagal')
                            <span class="pd-status failed">Gagal</span>
                        @else
                            {{ $pembayaran->status_pembayaran }}
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="pd-card">
        <div class="pd-card-header">Bukti Pembayaran</div>

        <div class="pd-card-body">

            @if($pembayaran->bukti_pembayaran)

                <a href="{{ asset('storage/' . $pembayaran->bukti_pembayaran) }}" target="_blank" rel="noopener noreferrer">

                    <img src="{{ asset('storage/' . $pembayaran->bukti_pembayaran) }}" alt="Bukti Pembayaran" class="pd-proof">
                </a>

                <p class="pd-empty" style="margin-top:10px;">
                    Klik gambar untuk melihat bukti pembayaran.
                </p>

            @else

                <p class="pd-empty">
                    Belum ada bukti pembayaran yang tersedia.
                </p>

            @endif

        </div>
    </div>

@endsection