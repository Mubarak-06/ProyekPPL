@extends('admin.layouts.app')

@section('title', 'Pengiriman')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">
    <style>
        .pengiriman-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .pengiriman-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .pengiriman-breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .pengiriman-tabs {
            display: inline-flex;
            align-items: stretch;
            gap: 0;
            margin-bottom: 20px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            overflow: hidden;
            background: #FFFFFF;
        }

        .pengiriman-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border: none;
            border-radius: 0;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .pengiriman-tab+.pengiriman-tab {
            border-left: 1px solid #E2E8F0;
        }

        .pengiriman-tab.active {
            background: #008A50;
            color: #FFFFFF;
        }

        .pengiriman-tab:not(.active):hover {
            background: #F8FAFC;
        }

        .pengiriman-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .pengiriman-filter {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        .pengiriman-field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 7px;
            color: #111827;
        }

        .pengiriman-field input,
        .pengiriman-field select {
            width: 100%;
            height: 38px;
            padding: 8px 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #64748B;
            background: #FFFFFF;
            box-sizing: border-box;
        }

        .pengiriman-filter-btn {
            height: 38px;
            padding: 0 22px;
            background: #008A50;
            border: none;
            border-radius: 6px;
            color: #FFFFFF;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            cursor: pointer;
        }

        .pengiriman-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .pengiriman-table th,
        .pengiriman-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }

        .pengiriman-table th {
            font-weight: 600;
            color: #111827;
            white-space: nowrap;
        }

        .pengiriman-table td {
            color: #334155;
        }

        .pengiriman-subtext {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            color: #94A3B8;
        }

        .pengiriman-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
        }

        .pengiriman-status.Menunggu {
            background: #FEF3C7;
            color: #D97706;
        }

        .pengiriman-status.Diproses {
            background: #DBEAFE;
            color: #2563EB;
        }

        .pengiriman-status.Dikirim {
            background: #DCFCE7;
            color: #16A34A;
        }

        .pengiriman-status.Selesai {
            background: #DCFCE7;
            color: #15803D;
        }

        .pengiriman-status.Ditolak {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pengiriman-lihat {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #008A50;
            border-radius: 5px;
            color: #008A50;
            text-decoration: none;
            font-size: 10px;
            white-space: nowrap;
        }

        .pengiriman-lihat:hover {
            background: #ECFDF5;
        }

        .pengiriman-table-wrapper {
            overflow-x: auto;
        }

        #tabelPengiriman_wrapper {
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #64748B;
        }

        #tabelPengiriman thead th {
            padding: 14px 12px !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0 !important;
            white-space: nowrap;
        }

        #tabelPengiriman tbody td {
            padding: 12px !important;
            font-size: 11px;
            color: #334155;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9 !important;
        }

        #tabelPengiriman_wrapper .dt-length {
            margin-bottom: 18px;
            font-size: 11px;
            color: #64748B;
        }

        #tabelPengiriman_wrapper .dt-input {
            border: 1px solid #E2E8F0 !important;
            border-radius: 6px !important;
            padding: 7px 10px !important;
            background: #FFFFFF !important;
            color: #64748B !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            outline: none;
        }

        #tabelPengiriman_wrapper .dt-length select {
            min-width: 65px;
        }

        #tabelPengiriman_wrapper .dt-input:focus {
            border-color: #008A50 !important;
        }

        #tabelPengiriman_wrapper .dt-paging .dt-paging-button {
            border: 1px solid #E2E8F0 !important;
            border-radius: 5px !important;
            background: #FFFFFF !important;
            color: #64748B !important;
            font-size: 11px;
            margin-left: 4px;
        }

        #tabelPengiriman_wrapper .dt-paging .dt-paging-button.current,
        #tabelPengiriman_wrapper .dt-paging .dt-paging-button.current:hover {
            background: #008A50 !important;
            color: #FFFFFF !important;
            border-color: #008A50 !important;
        }

        #tabelPengiriman_wrapper .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) {
            background: #ECFDF5 !important;
            color: #008A50 !important;
            border-color: #A7F3D0 !important;
        }

        #tabelPengiriman_wrapper .dt-info {
            font-size: 11px;
            color: #64748B;
        }

        #tabelPengiriman_wrapper .dt-layout-row:last-child {
            margin-top: 14px;
        }

        @media (max-width: 900px) {
            .pengiriman-filter {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .pengiriman-filter {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    <h1 class="pengiriman-title">Pengiriman</h1>

    <div class="pengiriman-breadcrumb">
        <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
        &nbsp; › &nbsp; Pengiriman
    </div>

    <div class="pengiriman-tabs">
        <a href="{{ route('admin.pengiriman.index') }}" class="pengiriman-tab active">
            <i class="bi bi-box-seam"></i> Kelola Produk
        </a>

        <a href="{{ route('admin.pengiriman.dataMaster') }}" class="pengiriman-tab">
            <i class="bi bi-database"></i> Kelola Data Master
        </a>
    </div>

    <div class="pengiriman-card">
        <form id="filterPengiriman" class="pengiriman-filter">
            <div class="pengiriman-field">
                <label for="tanggalPengiriman">Tanggal Pengiriman</label>
                <input type="date" id="tanggalPengiriman">
            </div>

            <div class="pengiriman-field">
                <label for="statusPengiriman">Status</label>
                <select id="statusPengiriman">
                    <option value="">Semua Status</option>
                    @foreach(['Belum Dikirim', 'Disiapkan', 'Siap Dikirim', 'Dalam Perjalanan', 'Barang Sampai', 'Diterima', 'Dikirim', 'Selesai', 'Dibatalkan'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pengiriman-field">
                <label for="cariPengiriman">Pencarian</label>
                <input type="text" id="cariPengiriman" placeholder="Cari pengiriman...">
            </div>

            <button type="submit" class="pengiriman-filter-btn">
                Tampilkan
            </button>
        </form>
    </div>

    <div class="pengiriman-card">
        <div class="pengiriman-table-wrapper">
            <table class="pengiriman-table" id="tabelPengiriman">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Pesanan</th>
                        <th>Tanggal Pengiriman</th>
                        <th>Nama Pelanggan</th>
                        <th>Alamat Tujuan</th>
                        <th>Biaya Kirim</th>
                        <th>Estimasi Sampai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($pengiriman as $item)
                                <tr
                                    data-tanggal="{{ $item->waktu_dikirim?->copy()->timezone(config('toko.timezone'))->format('Y-m-d') ?? '' }}">
                                    <td>{{ $loop->iteration }}</td>

                                    <td>{{ $item->pesanan?->kode_pesanan ?? '-' }}</td>

                                    <td>
                                        {{ $item->waktu_dikirim
                        ? $item->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('d M Y')
                        : '-' }}

                                        @if($item->waktu_dikirim)
                                            <span class="pengiriman-subtext">
                                                {{ $item->waktu_dikirim->copy()->timezone(config('toko.timezone'))->format('H:i') }} WITA
                                            </span>
                                        @endif
                                    </td>

                                    <td>{{ $item->nama_penerima }}</td>

                                    <td>{{ $item->alamat_pengiriman }}</td>

                                    <td>
                                        Rp{{ number_format($item->ongkos_kirim, 0, ',', '.') }}
                                    </td>

                                    <td data-order="{{ $item->estimasi_tanggal ? \Carbon\Carbon::parse($item->estimasi_tanggal)->format('Y-m-d') . ' ' . ($item->estimasi_jam ?? '00:00:00') : '' }}">
                                        @if($item->estimasi_tanggal)
                                            {{ \Carbon\Carbon::parse($item->estimasi_tanggal)->format('d M Y') }}
                                            @if($item->estimasi_jam)
                                                <span class="pengiriman-subtext">
                                                    {{ substr($item->estimasi_jam, 0, 5) }} WITA
                                                </span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <span class="pengiriman-status {{ $item->status_pengiriman }}">
                                            {{ $item->tahap() }}
                                        </span>
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.pengiriman.show', $item->id) }}" class="pengiriman-lihat">
                                            <i class="bi bi-eye"></i> Lihat
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
    $(document).ready(function () {

        const tabel = $('#tabelPengiriman').DataTable({
            layout: {
                topStart: 'pageLength',
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging'
            },

            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],

            ordering: true,
            searching: true,
            paging: true,
            info: true,
            autoWidth: false,
            stateSave: false,

            columnDefs: [
                {
                    targets: [0, 8],
                    orderable: false
                }
            ],

            language: {
                emptyTable: 'Belum ada data pengiriman.',
                zeroRecords: 'Tidak ada pengiriman yang sesuai.',
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

            pagingType: 'full_numbers'
        });

        // Filter tanggal pengiriman
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {

            if (settings.nTable.id !== 'tabelPengiriman') {
                return true;
            }

            const tanggalDipilih =
                document.getElementById('tanggalPengiriman').value;

            if (!tanggalDipilih) {
                return true;
            }

            const baris = settings.aoData[dataIndex].nTr;

            const tanggalPengiriman =
                baris.getAttribute('data-tanggal');

            return tanggalPengiriman === tanggalDipilih;
        });

        // Filter status dan pencarian
        const form = document.getElementById('filterPengiriman');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const status =
                document.getElementById('statusPengiriman').value;

            const keyword =
                document.getElementById('cariPengiriman').value.trim();

            if (status === '') {
                tabel.column(7).search('');
            } else {
                tabel.column(7).search(status, false, false);
            }

            tabel.search(keyword).draw();
        });

    });
</script>

@endpush
