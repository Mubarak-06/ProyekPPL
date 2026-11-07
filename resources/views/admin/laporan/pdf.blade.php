<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan CV Karunia Abadi</title>

    <style>
        @page {
            margin: 15mm 18mm 15mm 18mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #222222;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2 {
            font-size: 16px;
            margin: 0;
            font-weight: bold;
        }

        .header h3 {
            font-size: 12px;
            margin: 5px 0;
        }

        .header p {
            margin: 3px 0;
            font-size: 9px;
        }

        .garis {
            border-top: 2px solid #222222;
            margin-top: 12px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #777777;
            padding: 5px 7px;
            font-size: 9px;
        }

        th {
            background: #F1F5F9;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .signature {
            width: 240px;
            margin-left: auto;
            margin-top: 18px;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-space {
            height: 55px;
        }

        .footer {
            margin-top: 12px;
            font-size: 8px;
            color: #666666;
            text-align: center;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h2>CV KARUNIA ABADI</h2>
        <h3>LAPORAN PENJUALAN</h3>

        <p>
            Periode:
            {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }}
            sampai
            {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
        </p>

        <p>
            Kategori Produk: {{ $namaKategori }}
        </p>

        <div class="garis"></div>
    </div>

    {{-- A. RINGKASAN PENJUALAN --}}
    <div class="section-title">
        A. Ringkasan Penjualan
    </div>

    <table>
        <tbody>
            <tr>
                <td>Total Penjualan</td>
                <td class="text-right">
                    Rp{{ number_format($totalPenjualan, 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Jumlah Pesanan</td>
                <td class="text-right">
                    {{ number_format($jumlahPesanan, 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Produk Terjual</td>
                <td class="text-right">
                    {{ number_format($produkTerjual, 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Pesanan Selesai</td>
                <td class="text-right">
                    {{ number_format($pesananSelesai, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- B. PENJUALAN PER KATEGORI --}}
    <div class="section-title">
        B. Penjualan per Kategori
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">No.</th>
                <th>Kategori Produk</th>
                <th width="22%">Produk Terjual</th>
                <th width="30%">Total Penjualan</th>
            </tr>
        </thead>

        <tbody>
            @foreach($penjualanKategori as $item)
                <tr>
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $item->nama_kategori }}
                    </td>

                    <td class="text-center">
                        {{ number_format($item->jumlah_terjual, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp{{ number_format($item->total_penjualan, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            <tr class="bold">
                <td colspan="2" class="text-center">
                    Total
                </td>

                <td class="text-center">
                    {{ number_format($penjualanKategori->sum('jumlah_terjual'), 0, ',', '.') }}
                </td>

                <td class="text-right">
                    Rp{{ number_format($penjualanKategori->sum('total_penjualan'), 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- C. PRODUK TERLARIS --}}
    <div class="section-title">
        C. Produk Terlaris
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">No.</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Terjual</th>
                <th>Total Penjualan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($produkTerlaris as $item)
                <tr>
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $item->nama_produk }}
                    </td>

                    <td>
                        {{ $item->nama_kategori }}
                    </td>

                    <td class="text-center">
                        {{ number_format($item->jumlah_terjual, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp{{ number_format($item->total_penjualan, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        Belum ada produk terjual.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- D. RINGKASAN PENJUALAN BULANAN --}}
    <div class="section-title">
        D. Ringkasan Penjualan per Bulan
    </div>

    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Jumlah Pesanan</th>
                <th>Produk Terjual</th>
                <th>Total Penjualan</th>
            </tr>
        </thead>

        <tbody>
            @foreach($ringkasanBulanan as $item)
                <tr>
                    <td>
                        {{ $item->bulan }}
                    </td>

                    <td class="text-center">
                        {{ number_format($item->jumlah_pesanan, 0, ',', '.') }}
                    </td>

                    <td class="text-center">
                        {{ number_format($item->produk_terjual, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp{{ number_format($item->total_penjualan, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TANDA TANGAN PIMPINAN --}}
    <div class="signature">

        <p>
            Banjarmasin,
            {{ now()->locale('id')->translatedFormat('d F Y') }}
        </p>

        <p>
            Mengetahui,
        </p>

        <p class="bold">
            Pimpinan CV Karunia Abadi
        </p>

        <div class="signature-space"></div>

        <p>
            (........................................)
        </p>

    </div>

    <div class="footer">
        Laporan dihasilkan melalui Sistem E-Commerce CV Karunia Abadi.
    </div>

</body>
</html>