@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dashboard-title {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
    }

    .dashboard-subtitle {
        font-size: 12px;
        color: #64748B;
        margin: 3px 0 26px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px;
    }

    .stat-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 20px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 102px;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-icon.green {
        background: #DCFCE7;
        color: #16A34A;
    }

    .stat-icon.blue {
        background: #E0F2FE;
        color: #0284C7;
    }

    .stat-icon.yellow {
        background: #FEF3C7;
        color: #F59E0B;
    }

    .stat-icon.red {
        background: #FEE2E2;
        color: #EF4444;
    }

    .stat-label {
        font-size: 10px;
        color: #64748B;
        margin: 0;
    }

    .stat-value {
        font-size: 19px;
        font-weight: 700;
        margin: 2px 0;
        line-height: 1.3;
    }

    .stat-description {
        font-size: 10px;
        color: #94A3B8;
        margin: 0;
    }

    /* Tabel Pesanan Terbaru */
    .recent-orders {
        margin-top: 20px;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        overflow: hidden;
    }

    .recent-orders-header {
        padding: 16px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #E2E8F0;
    }

    .recent-orders-header h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
    }

    .recent-orders-header a {
        color: #008A50;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
    }

    .recent-orders-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        text-align: left;
    }

    .recent-orders-table th {
        background: #F8FAFC;
        padding: 13px 18px;
        color: #64748B;
        font-weight: 500;
    }

    .recent-orders-table td {
        padding: 14px 18px;
        border-top: 1px solid #F1F5F9;
    }

    .empty-orders {
        text-align: center;
        color: #94A3B8;
        padding: 28px !important;
    }

    .orders-table-wrapper {
        overflow-x: auto;
    }

    @media (max-width: 1100px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    <h1 class="dashboard-title">Dashboard</h1>

    <p class="dashboard-subtitle">
        Selamat datang di panel administrator CV Karunia Abadi.
    </p>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-box"></i>
            </div>
            <div>
                <p class="stat-label">Total Produk</p>
                <h3 class="stat-value">0</h3>
                <p class="stat-description">Produk terdaftar</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-cart3"></i>
            </div>
            <div>
                <p class="stat-label">Total Pesanan</p>
                <h3 class="stat-value">0</h3>
                <p class="stat-description">Semua pesanan</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="bi bi-clock"></i>
            </div>
            <div>
                <p class="stat-label">Pesanan Diproses</p>
                <h3 class="stat-value">0</h3>
                <p class="stat-description">Menunggu pengiriman</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <i class="bi bi-bar-chart-line"></i>
            </div>
            <div>
                <p class="stat-label">Total Penjualan</p>
                <h3 class="stat-value">Rp 0</h3>
                <p class="stat-description">Pesanan selesai</p>
            </div>
        </div>

    </div>
    
    <div class="recent-orders">

        <div class="recent-orders-header">
            <h3>Pesanan Terbaru</h3>
            <a href="#">Lihat Semua</a>
        </div>

        <div class="orders-table-wrapper">
            <table class="recent-orders-table">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="5" class="empty-orders">
                            Belum ada pesanan terbaru.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

@endsection
