@extends('admin.layouts.app')

@section('title', 'Laporan')

@push('styles')
    <style>
        .lp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .lp-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .lp-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .lp-breadcrumb a {
            color: #64748B;
            text-decoration: none;
        }

        .lp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #008A50;
            color: white;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }

        .lp-btn:hover {
            background: #007344;
        }

        .lp-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 20px;
            min-width: 0;
        }

        .lp-filter {
            display: grid;
            grid-template-columns: 1.4fr 1fr auto;
            gap: 14px;
            align-items: end;
            margin-bottom: 16px;
        }

        .lp-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }

        .lp-input {
            width: 100%;
            height: 40px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 0 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #334155;
            background: white;
        }

        .lp-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .lp-summary-card {
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #E2E8F0;
            min-width: 0;
        }

        .lp-summary-card.green {
            background: #F0FDF4;
        }

        .lp-summary-card.blue {
            background: #EFF6FF;
        }

        .lp-summary-card.yellow {
            background: #FFFBEB;
        }

        .lp-summary-card.red {
            background: #FEF2F2;
        }

        .lp-summary-label {
            font-size: 11px;
            color: #64748B;
            margin-bottom: 8px;
        }

        .lp-summary-value {
            font-size: 21px;
            font-weight: 700;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .lp-summary-note {
            font-size: 10px;
            color: #64748B;
            margin-top: 8px;
        }

        .lp-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .lp-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 20px;
        }

        .lp-chart {
            position: relative;
            height: 270px;
        }

        .lp-chart canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .lp-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .lp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .lp-table th {
            padding: 12px 10px;
            text-align: left;
            color: #64748B;
            font-weight: 600;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }

        .lp-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
        }

        .lp-empty {
            text-align: center;
            color: #64748B;
            font-size: 12px;
            padding: 35px 10px;
        }

        .lp-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lp-empty-message {
            text-align: center;
            color: #94A3B8;
            font-size: 12px;
        }

        .lp-empty-message i {
            display: block;
            font-size: 32px;
            margin-bottom: 12px;
            color: #CBD5E1;
        }

        .lp-empty-message p {
            margin: 0;
        }

        .lp-export-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 11px 16px;

            background-color: #008A50;
            color: #FFFFFF;

            border: none;
            border-radius: 8px;

            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;

            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .lp-export-btn:hover {
            background-color: #007344;
            color: #FFFFFF;
        }

        .lp-export-btn:focus {
            outline: none;
        }

        .lp-export-btn i {
            font-size: 14px;
        }

        .lp-export-wrapper {
            position: relative;
            display: inline-block;
        }

        .lp-export-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;

            width: 170px;
            padding: 8px;

            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;

            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            z-index: 100;
        }

        .lp-export-menu[hidden] {
            display: none;
        }

        .lp-export-item {
            display: flex;
            align-items: center;
            gap: 12px;

            width: 100%;
            padding: 12px 14px;

            background: transparent;
            border: none;
            border-radius: 8px;

            color: #334155;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            text-align: left;

            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
        }

        .lp-export-item:hover {
            background: #F0FDF4;
            color: #008A50;
            text-decoration: none;
        }

        .lp-export-item .bi-file-earmark-pdf {
            color: #DC2626;
            font-size: 17px;
        }

        .lp-export-item .bi-file-earmark-excel {
            color: #15803D;
            font-size: 17px;
        }

        @media (max-width: 1100px) {
            .lp-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {

            .lp-filter,
            .lp-grid {
                grid-template-columns: 1fr;
            }

            .lp-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    {{-- Header --}}
    <div class="lp-header">
        <div>
            <h1 class="lp-title">Laporan</h1>

            <div class="lp-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                &nbsp; › &nbsp; Laporan
            </div>
        </div>

        <div class="lp-export-wrapper">

            <button type="button" class="lp-export-btn" id="btnExportLaporan" aria-expanded="false"
                aria-controls="menuExportLaporan">

                <i class="bi bi-download"></i>
                Ekspor Laporan
                <i class="bi bi-chevron-down"></i>

            </button>

            <div class="lp-export-menu" id="menuExportLaporan" hidden>

                <a href="{{ route('admin.laporan.export.pdf', [
                    'tanggal_awal' => $tanggalAwal,
                    'tanggal_akhir' => $tanggalAkhir,
                    'kategori_id' => request('kategori_id')
                ]) }}" class="lp-export-item">

                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Ekspor PDF</span>

                </a>

                <a href="{{ route('admin.laporan.export.excel', [
                    'tanggal_awal' => $tanggalAwal,
                    'tanggal_akhir' => $tanggalAkhir,
                    'kategori_id' => request('kategori_id')
                ]) }}" class="lp-export-item">

                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Ekspor Excel</span>

                </a>

            </div>

        </div>
    </div>

    {{-- Filter --}}
    <div class="lp-card" style="margin-bottom:16px;">

        <form method="GET" action="{{ route('admin.laporan.index') }}" class="lp-filter">

            <div>
                <label class="lp-label">Periode</label>

                <div style="display:flex; gap:8px;">
                    <input type="date" name="tanggal_awal" class="lp-input" value="{{ $tanggalAwal }}" required>

                    <input type="date" name="tanggal_akhir" class="lp-input" value="{{ $tanggalAkhir }}" required>
                </div>
            </div>

            <div>
                <label class="lp-label">Kategori Produk</label>

                <select name="kategori_id" class="lp-input">
                    <option value="">Semua Kategori</option>

                    @foreach($kategori as $item)
                        <option value="{{ $item->id }}" {{ request('kategori_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->nama_kategori }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="lp-btn" style="height:40px;">
                <i class="bi bi-search"></i>
                Tampilkan
            </button>

        </form>

        @if($errors->any())
            <div style="color:#DC2626; font-size:11px;">
                {{ $errors->first() }}
            </div>
        @endif

    </div>

    {{-- Ringkasan --}}
    <div class="lp-summary">

        <div class="lp-summary-card green">
            <div class="lp-summary-label">Total Penjualan</div>
            <div class="lp-summary-value">
                Rp {{ number_format($totalPenjualan, 0, ',', '.') }}
            </div>
            <div class="lp-summary-note">Pembayaran berhasil</div>
        </div>

        <div class="lp-summary-card blue">
            <div class="lp-summary-label">Jumlah Pesanan</div>
            <div class="lp-summary-value">
                {{ number_format($jumlahPesanan, 0, ',', '.') }}
            </div>
            <div class="lp-summary-note">Pesanan dalam periode</div>
        </div>

        <div class="lp-summary-card yellow">
            <div class="lp-summary-label">Produk Terjual</div>
            <div class="lp-summary-value">
                {{ number_format($produkTerjual, 0, ',', '.') }}
            </div>
            <div class="lp-summary-note">Unit dari pesanan dibayar</div>
        </div>

        <div class="lp-summary-card red">
            <div class="lp-summary-label">Pesanan Selesai</div>
            <div class="lp-summary-value">
                {{ number_format($pesananSelesai, 0, ',', '.') }}
            </div>
            <div class="lp-summary-note">Pesanan berstatus selesai</div>
        </div>

    </div>

    {{-- Grafik dan Kategori --}}
    <div class="lp-grid">

        <div class="lp-card">
            <h3 class="lp-section-title">Grafik Penjualan</h3>

            @if($grafikPenjualan->count() > 0)
                <div class="lp-chart">
                    <canvas id="grafikPenjualan"></canvas>
                </div>
            @else
                <div class="lp-chart lp-chart-empty">
                    <div class="lp-empty-message">
                        <i class="bi bi-graph-up"></i>
                        <p>Belum ada data penjualan pada periode ini.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="lp-card">
            <h3 class="lp-section-title">Penjualan per Kategori</h3>

            @if($penjualanKategori->count() > 0)
                <div class="lp-chart">
                    <canvas id="grafikKategori"></canvas>
                </div>
            @else
                <div class="lp-empty">
                    Belum ada data penjualan berdasarkan kategori.
                </div>
            @endif
        </div>

    </div>

    {{-- Tabel Produk Terlaris dan Ringkasan Bulanan --}}
    <div class="lp-grid">

        <div class="lp-card">
            <h3 class="lp-section-title">Produk Terlaris</h3>

            <div class="lp-table-wrap">
                <table class="lp-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Jumlah Terjual</th>
                            <th>Total Penjualan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($produkTerlaris as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->nama_produk }}</td>
                                <td>{{ $item->nama_kategori }}</td>
                                <td>{{ number_format($item->jumlah_terjual, 0, ',', '.') }}</td>
                                <td>
                                    Rp {{ number_format($item->total_penjualan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="lp-empty">
                                    Belum ada produk terjual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lp-card">
            <h3 class="lp-section-title">Ringkasan Penjualan per Bulan</h3>

            <div class="lp-table-wrap">
                <table class="lp-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Jumlah Pesanan</th>
                            <th>Produk Terjual</th>
                            <th>Total Penjualan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($ringkasanBulanan as $item)
                            <tr>
                                <td>{{ $item->bulan }}</td>
                                <td>{{ $item->jumlah_pesanan }}</td>
                                <td>{{ $item->produk_terjual }}</td>
                                <td>
                                    Rp {{ number_format($item->total_penjualan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="lp-empty">
                                    Belum ada data laporan bulanan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const dataPenjualan = @json($grafikPenjualan);
            const dataKategori = @json($penjualanKategori);

            if (typeof Chart === 'undefined') {
                console.warn('Chart.js belum berhasil dimuat.');
                return;
            }

            // Grafik Penjualan
            const canvasPenjualan = document.getElementById('grafikPenjualan');

            if (canvasPenjualan) {
                new Chart(canvasPenjualan, {
                    type: 'line',
                    data: {
                        labels: dataPenjualan.map(item => item.tanggal),
                        datasets: [{
                            label: 'Total Penjualan',
                            data: dataPenjualan.map(item => Number(item.total)),
                            borderColor: '#008A50',
                            backgroundColor: 'rgba(0,138,80,0.08)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Diagram Penjualan per Kategori
            const canvasKategori = document.getElementById('grafikKategori');

            if (canvasKategori && dataKategori.length > 0) {
                new Chart(canvasKategori, {
                    type: 'doughnut',
                    data: {
                        labels: dataKategori.map(item => item.nama_kategori),
                        datasets: [{
                            data: dataKategori.map(item => Number(item.total_penjualan)),
                            backgroundColor: [
                                '#2563EB',
                                '#10B981',
                                '#F59E0B',
                                '#EF4444',
                                '#8B5CF6'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }

            const btnExport = document.getElementById('btnExportLaporan');
            const menuExport = document.getElementById('menuExportLaporan');

            if (!btnExport || !menuExport) {
                return;
            }

            btnExport.addEventListener('click', function (event) {
                event.stopPropagation();

                const akanDibuka = menuExport.hidden;

                menuExport.hidden = !akanDibuka;
                btnExport.setAttribute('aria-expanded', String(akanDibuka));
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.lp-export-wrapper')) {
                    menuExport.hidden = true;
                    btnExport.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    menuExport.hidden = true;
                    btnExport.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>
@endpush