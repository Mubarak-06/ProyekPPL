
@extends('admin.layouts.app')

@section('title', 'Produk')

@push('styles')
<link rel="stylesheet"
      href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">

<style>
    .produk-title {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
    }

    .produk-breadcrumb {
        font-size: 11px;
        color: #64748B;
        margin: 5px 0 20px;
    }

    .produk-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 20px;
    }

    .produk-tab {
        padding: 10px 18px;
        border: 1px solid #E2E8F0;
        background: white;
        color: #64748B;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        text-decoration: none;
        cursor: pointer;
    }

    .produk-tab:first-child {
        border-radius: 6px 0 0 6px;
    }

    .produk-tab:last-child {
        border-radius: 0 6px 6px 0;
    }

    .produk-tab.active {
        background: #008A50;
        color: white;
        border-color: #008A50;
    }

    .produk-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 20px;
    }

    .produk-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .produk-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }

    .produk-card-header p {
        margin: 3px 0 0;
        font-size: 11px;
        color: #64748B;
    }

    .btn-tambah {
        background: #008A50;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 15px;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        font-weight: 500;
    }

    .produk-empty {
        padding: 55px 15px;
        text-align: center;
        font-size: 12px;
        color: #94A3B8;
    }

    @media (max-width: 768px) {
        .produk-card-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    
    /* DataTables Produk */
    .produk-table-wrapper {
        margin-top: 25px;
        overflow-x: auto;
    }

    #tabelProduk {
        width: 100% !important;
        font-size: 11px;
        border-collapse: collapse;
    }

    #tabelProduk thead th {
        font-size: 10px;
        font-weight: 600;
        color: #64748B;
        text-transform: uppercase;
        padding: 14px 12px;
        border-bottom: 1px solid #E2E8F0;
    }

    #tabelProduk tbody td {
        padding: 12px;
        color: #334155;
        vertical-align: middle;
        border-bottom: 1px solid #F1F5F9;
    }

    .foto-produk {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border: 1px solid #E2E8F0;
        border-radius: 5px;
    }

    .nama-produk {
        font-weight: 600;
        color: #111827;
    }

    .stok-tersedia {
        color: #008A50;
        font-weight: 600;
    }

    .stok-rendah {
        color: #F59E0B;
        font-weight: 600;
    }

    .stok-habis {
        color: #F43F5E;
        font-weight: 600;
    }

    .aksi-produk {
        display: flex;
        gap: 6px;
    }

    .btn-edit-produk,
    .btn-hapus-produk {
        border: none;
        border-radius: 4px;
        padding: 7px;
        cursor: pointer;
    }

    .btn-edit-produk {
        background: #ECFDF5;
        color: #008A50;
    }

    .btn-hapus-produk {
        background: #FFF1F2;
        color: #F43F5E;
    }

    .dt-container {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        color: #64748B;
    }

    .dt-container .dt-input {
        border: 1px solid #E2E8F0 !important;
        border-radius: 5px !important;
        padding: 6px 10px !important;
    }

    .dt-container .dt-paging .current {
        background: #008A50 !important;
        color: #FFFFFF !important;
        border: 1px solid #008A50 !important;
    }
    .btn-edit-produk {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .aksi-produk form {
        margin: 0;
    }

    .aksi-produk {
        align-items: center;
    }
    .produk-alert-success,
    .produk-alert-error {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 7px;
        margin-bottom: 18px;
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
    }

    .produk-alert-success {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #047857;
    }

    .produk-alert-error {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #B91C1C;
    }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="produk-alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="produk-alert-error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<h1 class="produk-title">Produk</h1>

<div class="produk-breadcrumb">
    <a href="{{ url('/admin/dashboard') }}"
       style="color: inherit; text-decoration: none;">
        Dashboard
    </a>
    &nbsp; › &nbsp; Produk
</div>

<div class="produk-tabs">
    <a href="{{ route('admin.produk.index') }}" class="produk-tab active">
        <i class="bi bi-box"></i>
        Kelola Produk
    </a>

    <a href="{{ route('admin.data-master.index') }}" class="produk-tab">
        <i class="bi bi-database"></i>
        Kelola Data Master
    </a>
</div>

<div class="produk-card">

    <div class="produk-card-header">
        <div>
            <h3>Daftar Produk</h3>
            <p>Kelola data produk yang ditampilkan pada toko.</p>
        </div>

        <a href="{{ route('admin.produk.create') }}"
        class="btn-tambah"
        style="text-decoration:none; display:inline-flex;
                align-items:center; gap:6px;">
            <i class="bi bi-plus-lg"></i>
            Tambah Produk
        </a>
    </div>

    <div class="produk-table-wrapper">

        <table id="tabelProduk" class="display">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Foto</th>
                    <th>Kode Barang</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @foreach($produk as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>
                            @if($item->gambar)
                                <img
                                    src="{{ asset('storage/' . $item->gambar) }}"
                                    class="foto-produk"
                                    alt="{{ $item->nama_produk }}">
                            @else
                                <i class="bi bi-image"
                                style="font-size: 22px; color: #94A3B8;"></i>
                            @endif
                        </td>

                        <td>{{ $item->kode_barang }}</td>

                        <td>
                            <span class="nama-produk">
                                {{ $item->nama_produk }}
                            </span>
                        </td>

                        <td>{{ $item->nama_kategori }}</td>

                        <td data-order="{{ $item->harga }}">
                            Rp {{ number_format($item->harga, 0, ',', '.') }}
                        </td>

                        <td data-order="{{ $item->stok }}">
                            @php
                                $stokClass = $item->stok == 0
                                    ? 'stok-habis'
                                    : ($item->stok <= 30
                                        ? 'stok-rendah'
                                        : 'stok-tersedia');
                            @endphp

                            <span class="{{ $stokClass }}">
                                {{ $item->stok }}
                            </span>
                        </td>

                        <td>
                            <div class="aksi-produk">

                                <!-- Tombol Edit Produk -->
                                <a href="{{ route('admin.produk.edit', $item->id) }}"
                                class="btn-edit-produk"
                                title="Edit Produk">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <!-- Tombol Hapus Produk -->
                                <form action="{{ route('admin.produk.destroy', $item->id) }}"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini beserta seluruh gambarnya?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn-hapus-produk"
                                            title="Hapus Produk">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            </div>
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
        $('#tabelProduk').DataTable({
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            columnDefs: [
                {
                    orderable: false,
                    targets: [1, 7]
                }
            ],
            language: {
                emptyTable: "Belum ada produk yang ditambahkan.",
                zeroRecords: "Produk tidak ditemukan."
            }
        });
    });
</script>

@endpush
