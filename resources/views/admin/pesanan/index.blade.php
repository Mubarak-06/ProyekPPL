@extends('admin.layouts.app')

@section('title', 'Pesanan')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">
    <style>
        .pesanan-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .pesanan-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .pesanan-breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .pesanan-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .pesanan-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            cursor: pointer;
        }

        .pesanan-filter-btn.active {
            background: #008A50;
            border-color: #008A50;
            color: #FFFFFF;
        }

        .pesanan-filter-count {
            padding: 2px 6px;
            border-radius: 4px;
            background: #F1F5F9;
            color: #475569;
            font-size: 10px;
            font-weight: 600;
        }

        .pesanan-filter-btn.active .pesanan-filter-count {
            background: #FFFFFF;
            color: #008A50;
        }

        .pesanan-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 20px;
        }

        .pesanan-table-wrapper {
            overflow-x: auto;
        }

        .pesanan-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .pesanan-table th,
        .pesanan-table td {
            padding: 12px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #E2E8F0;
        }

        .pesanan-table th {
            font-weight: 600;
            color: #111827;
            white-space: nowrap;
        }

        .pesanan-table td {
            color: #334155;
        }

        .pesanan-kode {
            font-weight: 600;
            color: #111827;
        }

        .pesanan-subtext {
            display: block;
            margin-top: 3px;
            color: #94A3B8;
            font-size: 10px;
        }

        .pesanan-produk {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 170px;
        }

        .pesanan-produk-img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            flex-shrink: 0;
        }

        .pesanan-produk-placeholder {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            color: #94A3B8;
            flex-shrink: 0;
        }

        .pesanan-produk-nama {
            font-weight: 600;
            color: #111827;
        }

        .pesanan-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
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
            background: #D1FAE5;
            color: #059669;
        }

        .status-selesai {
            background: #DCFCE7;
            color: #16A34A;
        }

        .status-ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pesanan-btn-lihat {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid #A7F3D0;
            border-radius: 5px;
            background: #FFFFFF;
            color: #008A50;
            font-family: 'Poppins', sans-serif;
            font-size: 10px;
            text-decoration: none;
            white-space: nowrap;
            justify-content: center;
            cursor: pointer;
        }

        .pesanan-btn-lihat:hover {
            background: #ECFDF5;
        }

        .pesanan-btn-lihat:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .pesanan-table .dataTables_empty {
            text-align: center;
            padding: 30px;
            color: #94A3B8;
        }

        @media (max-width: 768px) {
            .pesanan-card {
                padding: 15px;
            }
        }

        #tabelPesanan_wrapper {
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #64748B;
        }

        #tabelPesanan thead th {
            font-size: 10px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            text-transform: uppercase;
            padding: 14px 12px !important;
            border-bottom: 1px solid #E2E8F0 !important;
        }

        #tabelPesanan tbody td {
            font-size: 11px;
            padding: 12px !important;
            color: #334155;
            border-bottom: 1px solid #F1F5F9 !important;
        }

        #tabelPesanan_wrapper .dt-length,
        #tabelPesanan_wrapper .dt-search {
            margin-bottom: 18px;
            font-size: 11px;
        }

        #tabelPesanan_wrapper .dt-input {
            border: 1px solid #E2E8F0 !important;
            border-radius: 6px !important;
            padding: 7px 10px !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #64748B;
            background: #FFFFFF;
        }

        #tabelPesanan_wrapper .dt-length select {
            min-width: 65px;
        }

        #tabelPesanan_wrapper .dt-search input {
            width: 200px;
        }

        #tabelPesanan_wrapper .dt-paging .dt-paging-button {
            border: 1px solid #E2E8F0 !important;
            border-radius: 5px !important;
            background: #FFFFFF !important;
            color: #64748B !important;
            font-size: 11px;
            margin-left: 4px;
        }

        #tabelPesanan_wrapper .dt-paging .dt-paging-button.current,
        #tabelPesanan_wrapper .dt-paging .dt-paging-button.current:hover {
            background: #008A50 !important;
            border-color: #008A50 !important;
            color: #FFFFFF !important;
        }

        #tabelPesanan_wrapper .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) {
            background: #ECFDF5 !important;
            color: #008A50 !important;
            border-color: #A7F3D0 !important;
        }

        #tabelPesanan_wrapper .dt-info {
            font-size: 11px;
            color: #64748B;
        }

        #tabelPesanan_wrapper .dt-layout-row:last-child {
            margin-top: 14px;
        }

        @media (max-width: 768px) {
            #tabelPesanan_wrapper .dt-search input {
                width: 160px;
                max-width: 100%;
            }
        }
    </style>
@endpush

@section('content')

    <h1 class="pesanan-title">Pesanan</h1>

    <div class="pesanan-breadcrumb">
        <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
        &nbsp; › &nbsp; Pesanan
    </div>

    <!-- Filter Status -->
    <div class="pesanan-filter">
        @foreach(['Semua', 'Menunggu', 'Diproses', 'Dikirim', 'Selesai', 'Ditolak'] as $status)
            <button type="button" class="pesanan-filter-btn {{ $status === 'Semua' ? 'active' : '' }}"
                data-status="{{ $status }}">

                {{ $status }}

                <span class="pesanan-filter-count">
                    {{ $jumlahStatus[$status] ?? 0 }}
                </span>
            </button>
        @endforeach
    </div>

    <div class="pesanan-card">

        <div class="pesanan-table-wrapper">
            <table class="pesanan-table" id="tabelPesanan">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Pesanan</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($pesanan as $item)
                        @php
                            $detailPertama = $item->detailPesanan->first();
                            $produkPertama = $detailPertama?->produk;

                            $jumlahBarang = $item->detailPesanan->sum('jumlah');

                            $kelasStatus = match ($item->status_pesanan) {
                                'Menunggu' => 'status-menunggu',
                                'Diproses' => 'status-diproses',
                                'Dikirim' => 'status-dikirim',
                                'Selesai' => 'status-selesai',
                                'Ditolak' => 'status-ditolak',
                                default => ''
                            };
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <span class="pesanan-kode">
                                    {{ $item->kode_pesanan }}
                                </span>
                            </td>

                            <td data-order="{{ $item->created_at->timestamp }}">
                                {{ $item->created_at->format('d M Y') }}

                                <span class="pesanan-subtext">
                                    {{ $item->created_at->format('H:i') }}
                                </span>
                            </td>

                            <td>
                                {{ $item->user->name ?? '-' }}

                                <span class="pesanan-subtext">
                                    {{ $item->alamat?->alamat_lengkap ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <div class="pesanan-produk">

                                    @php
                                        $gambarUtama = $produkPertama?->gambar[0] ?? null;
                                    @endphp

                                    @if($gambarUtama)
                                        <img src="{{ asset('storage/' . $gambarUtama) }}" alt="Foto Produk"
                                            class="pesanan-produk-img">
                                    @else
                                        <div class="pesanan-produk-placeholder">
                                            <i class="bi bi-box"></i>
                                        </div>
                                    @endif

                                    <div>
                                        <span class="pesanan-produk-nama">
                                            {{ $detailPertama?->nama_produk ?? '-' }}
                                        </span>

                                        @if($item->detailPesanan->count() > 1)
                                            <span class="pesanan-subtext">
                                                +{{ $item->detailPesanan->count() - 1 }} produk lainnya
                                            </span>
                                        @endif
                                    </div>

                                </div>
                            </td>

                            <td>{{ $jumlahBarang }} pcs</td>

                            <td>
                                Rp{{ number_format($item->total_pembayaran, 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="pesanan-status {{ $kelasStatus }}">
                                    {{ $item->status_pesanan }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('admin.pesanan.show', $item->id) }}" class="pesanan-btn-lihat">
                                    <i class="bi bi-eye"></i>
                                    Lihat
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const tabel = $('#tabelPesanan').DataTable({
                pagingType: 'full_numbers',
                stateSave: false,
                scrollX: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,

                language: {
                    emptyTable: 'Belum ada data pesanan.',
                    zeroRecords: 'Tidak ada pesanan yang sesuai.',
                    search: 'Search:',
                    lengthMenu: '_MENU_ entries per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    paginate: {
                        first: '«',
                        previous: '‹',
                        next: '›',
                        last: '»'
                    }
                },

                columnDefs: [
                    {
                        targets: [0, 8],
                        orderable: false
                    }
                ]
            });

            // Filter status pesanan
            const tombolFilter = document.querySelectorAll('.pesanan-filter-btn');
            tombolFilter.forEach(function (tombol) {
                tombol.addEventListener('click', function () {

                    const statusDipilih = this.dataset.status;

                    // Mengubah tampilan tombol aktif
                    tombolFilter.forEach(function (item) {
                        item.classList.remove('active');
                    });

                    this.classList.add('active');

                    // Menyaring berdasarkan kolom Status (index 7)
                    if (statusDipilih === 'Semua') {
                        tabel.column(7).search('').draw();
                    } else {
                        tabel.column(7).search(statusDipilih, false, false).draw();
                    }

                });
            });
        });
    </script>
@endpush