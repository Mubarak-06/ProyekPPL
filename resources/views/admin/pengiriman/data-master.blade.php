@extends('admin.layouts.app')

@section('title', 'Data Master Pengiriman')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">
    <style>
        .dm-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .dm-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .dm-breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .dm-main-tabs {
            display: inline-flex;
            align-items: stretch;
            gap: 0;
            margin-bottom: 20px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            overflow: hidden;
            background: #FFFFFF;
        }

        .dm-main-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 17px;
            border: none;
            border-radius: 0;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .dm-main-tab+.dm-main-tab {
            border-left: 1px solid #E2E8F0;
        }

        .dm-main-tab.active {
            background: #008A50;
            color: #FFFFFF;
        }

        .dm-main-tab:not(.active):hover {
            background: #F8FAFC;
        }

        /* Tab Provinsi, Kota, Kecamatan, Kelurahan, dan Ongkir */
        .dm-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .dm-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 17px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .dm-tab.active {
            background: #008A50;
            border-color: #008A50;
            color: #FFFFFF;
        }

        .dm-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 22px;
        }

        .dm-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .dm-card-header h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: #111827;
        }

        .dm-description {
            font-size: 12px;
            color: #64748B;
            margin: 5px 0 0;
        }

        .dm-add {
            background: #008A50;
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            cursor: pointer;
        }

        .dm-tabs {
            margin-bottom: 24px;
        }

        .dm-panel {
            display: none;
        }

        .dm-panel.active {
            display: block;
        }

        .dm-table-wrap {
            overflow-x: auto;
        }

        .dm-table {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 12px;
        }

        .dm-table th,
        .dm-table td {
            padding: 12px;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
        }

        .dm-table th {
            font-weight: 600;
            color: #111827;
        }

        .dm-table td {
            color: #334155;
        }

        .dm-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 1px solid #008A50;
            border-radius: 6px;
            color: #008A50;
            background: #FFFFFF;
            margin-right: 5px;
        }

        .dm-action.delete {
            border-color: #EF4444;
            color: #EF4444;
        }

        @media (max-width: 600px) {
            .dm-card-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        .dm-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .dm-modal-overlay.active {
            display: flex;
        }

        .dm-modal {
            background: #FFFFFF;
            width: 100%;
            max-width: 460px;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }

        .dm-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .dm-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .dm-modal-close {
            border: none;
            background: transparent;
            font-size: 24px;
            color: #64748B;
            cursor: pointer;
        }

        .dm-form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #111827;
        }

        .dm-form-input {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            box-sizing: border-box;
        }

        .dm-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 26px;
        }

        .dm-btn-cancel,
        .dm-btn-save {
            padding: 10px 18px;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            cursor: pointer;
        }

        .dm-btn-cancel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            color: #64748B;
        }

        .dm-btn-save {
            background: #008A50;
            border: 1px solid #008A50;
            color: #FFFFFF;
        }

        .dm-btn-save:hover {
            background: #007344;
        }


        /* Tampilan Data Master mengikuti tabel Produk */

        .dm-table-wrap {
            margin-top: 25px;
            overflow-x: auto;
        }

        .dm-table-wrap .dm-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px !important;
        }

        .dm-table-wrap .dm-table thead th {
            font-size: 10px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            text-transform: uppercase;
            padding: 14px 12px !important;
            border-bottom: 1px solid #E2E8F0 !important;
        }

        .dm-table-wrap .dm-table tbody td {
            font-size: 11px !important;
            color: #334155;
            padding: 12px !important;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9 !important;
        }

        /* Kontrol DataTables versi 2 */

        .dt-container {
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #64748B;
        }

        .dt-container .dt-length,
        .dt-container .dt-search {
            margin-bottom: 18px;
            font-size: 11px;
            color: #64748B;
        }

        .dt-container .dt-input {
            border: 1px solid #E2E8F0 !important;
            border-radius: 6px !important;
            padding: 7px 10px !important;
            background: #FFFFFF !important;
            color: #64748B !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px !important;
            outline: none;
        }

        .dt-container .dt-length select {
            min-width: 65px;
        }

        .dt-container .dt-search input {
            width: 200px;
        }

        .dt-container .dt-input:focus {
            border-color: #008A50 !important;
        }

        /* Pagination */

        .dt-container .dt-paging .dt-paging-button {
            border: 1px solid #E2E8F0 !important;
            border-radius: 5px !important;
            background: #FFFFFF !important;
            color: #64748B !important;
            font-size: 11px;
            margin-left: 4px;
        }

        .dt-container .dt-paging .dt-paging-button.current,
        .dt-container .dt-paging .dt-paging-button.current:hover {
            background: #008A50 !important;
            color: #FFFFFF !important;
            border-color: #008A50 !important;
        }

        .dt-container .dt-paging .dt-paging-button.current {
            -webkit-text-fill-color: #FFFFFF !important;
            opacity: 1 !important;
        }

        .dt-container .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) {
            background: #ECFDF5 !important;
            color: #008A50 !important;
            border-color: #A7F3D0 !important;
        }

        .dt-container .dt-info {
            font-size: 11px;
            color: #64748B;
        }

        .dt-container .dt-layout-row:last-child {
            margin-top: 14px;
        }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <div style="padding:12px 16px; margin-bottom:16px;
                                                                                    background:#DCFCE7; color:#166534;
                                                                                    border-radius:6px; font-size:12px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="padding:12px 16px; margin-bottom:16px;
                                                                                    background:#FEE2E2; color:#991B1B;
                                                                                    border-radius:6px; font-size:12px;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:12px 16px; margin-bottom:16px;
                                                                                    background:#FEE2E2; color:#991B1B;
                                                                                    border-radius:6px; font-size:12px;">
            {{ $errors->first() }}
        </div>
    @endif
    <h1 class="dm-title">Pengiriman</h1>

    <div class="dm-breadcrumb">
        <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
        &nbsp; › &nbsp; Pengiriman
    </div>

    <div class="dm-main-tabs">
        <a href="{{ route('admin.pengiriman.index') }}" class="dm-main-tab">
            <i class="bi bi-box-seam"></i> Kelola Produk
        </a>

        <a href="{{ route('admin.pengiriman.dataMaster') }}" class="dm-main-tab active">
            <i class="bi bi-database"></i> Kelola Data Master
        </a>
    </div>

    <div class="dm-card">

        <div class="dm-card-header">
            <div>
                <h2>Data Master</h2>
                <p class="dm-description">Kelola ongkir dan wilayah pengiriman.</p>
            </div>

            <button type="button" class="dm-add" id="dmTambah">
                <i class="bi bi-plus-lg"></i> Tambah Provinsi
            </button>
        </div>

        <div class="dm-tabs">
            @foreach(['provinsi', 'kota', 'kecamatan', 'kelurahan', 'ongkir'] as $jenis)
                <button type="button" class="dm-tab {{ $jenis === 'provinsi' ? 'active' : '' }}" data-tab="{{ $jenis }}">
                    {{ ucfirst($jenis) }}
                </button>
            @endforeach
        </div>

        @php
            $kolom = [
                'provinsi' => ['Nama Provinsi'],
                'kota' => ['Nama Kota', 'Provinsi'],
                'kecamatan' => ['Nama Kecamatan', 'Kota'],
                'kelurahan' => ['Nama Kelurahan', 'Kecamatan'],
                'ongkir' => ['Kelurahan', 'Kecamatan', 'Jarak (km)', 'Biaya Ongkir'],
            ];

            $dataMaster = compact('provinsi', 'kota', 'kecamatan', 'kelurahan', 'ongkir');
        @endphp

        @foreach($dataMaster as $jenis => $data)
            <div class="dm-panel {{ $jenis === 'provinsi' ? 'active' : '' }}" id="panel-{{ $jenis }}">

                <div class="dm-table-wrap">
                    <table class="dm-table" id="table-{{ $jenis }}">
                        <thead>
                            <tr>
                                <th>No.</th>
                                @foreach($kolom[$jenis] as $judul)
                                    <th>{{ $judul }}</th>
                                @endforeach
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    @if($jenis === 'provinsi')
                                        <td>{{ $item->nama_provinsi }}</td>

                                    @elseif($jenis === 'kota')
                                        <td>{{ $item->nama_kota }}</td>
                                        <td>{{ $item->nama_provinsi }}</td>

                                    @elseif($jenis === 'kecamatan')
                                        <td>{{ $item->nama_kecamatan }}</td>
                                        <td>{{ $item->nama_kota }}</td>

                                    @elseif($jenis === 'kelurahan')
                                        <td>{{ $item->nama_kelurahan }}</td>
                                        <td>{{ $item->nama_kecamatan }}</td>

                                    @elseif($jenis === 'ongkir')
                                        <td>{{ $item->nama_kelurahan }}</td>
                                        <td>{{ $item->nama_kecamatan }}</td>
                                        <td>{{ number_format($item->jarak_km, 2, ',', '.') }}</td>
                                        <td>Rp{{ number_format($item->biaya_ongkir, 0, ',', '.') }}</td>
                                    @endif

                                    <td>
                                        @if($jenis === 'provinsi')
                                            <button type="button" class="dm-action dm-edit-provinsi" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_provinsi }}" title="Edit Provinsi">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                        @elseif($jenis === 'kota')
                                            <button type="button" class="dm-action dm-edit-kota" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kota }}" data-provinsi="{{ $item->provinsi_id }}"
                                                title="Edit Kota">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                        @elseif($jenis === 'kecamatan')
                                            <button type="button" class="dm-action dm-edit-kecamatan" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kecamatan }}" data-kota="{{ $item->kota_id }}"
                                                title="Edit Kecamatan">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                        @elseif($jenis === 'kelurahan')
                                            <button type="button" class="dm-action dm-edit-kelurahan" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kelurahan }}" data-kecamatan="{{ $item->kecamatan_id }}"
                                                title="Edit Kelurahan">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                        @elseif($jenis === 'ongkir')
                                            <button type="button" class="dm-action dm-edit-ongkir" data-id="{{ $item->id }}"
                                                data-kelurahan="{{ $item->kelurahan_id }}" data-jarak="{{ $item->jarak_km }}"
                                                data-biaya="{{ $item->biaya_ongkir }}" title="Edit Ongkir">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @else

                                            <span class="dm-action" title="Edit belum diaktifkan">
                                                <i class="bi bi-pencil"></i>
                                            </span>
                                        @endif

                                        @if($jenis === 'provinsi')
                                            <button type="button" class="dm-action delete dm-delete-provinsi" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_provinsi }}" title="Hapus Provinsi">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @elseif($jenis === 'kota')
                                            <button type="button" class="dm-action delete dm-delete-kota" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kota }}" title="Hapus Kota">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @elseif($jenis === 'kecamatan')
                                            <button type="button" class="dm-action delete dm-delete-kecamatan" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kecamatan }}" title="Hapus Kecamatan">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @elseif($jenis === 'kelurahan')
                                            <button type="button" class="dm-action delete dm-delete-kelurahan" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kelurahan }}" title="Hapus Kelurahan">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @elseif($jenis === 'ongkir')
                                            <button type="button" class="dm-action delete dm-delete-ongkir" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_kelurahan }}" data-kecamatan="{{ $item->nama_kecamatan }}"
                                                title="Hapus Ongkir">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @else
                                            <span class="dm-action delete" title="Hapus belum diaktifkan">
                                                <i class="bi bi-trash"></i>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

    </div>

    {{-- Popup Tambah Provinsi --}}
    <div class="dm-modal-overlay" id="modalTambahProvinsi">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahProvinsi">

            <div class="dm-modal-header">
                <h3 id="judulTambahProvinsi">Tambah Provinsi</h3>

                <button type="button" class="dm-modal-close" id="tutupModalProvinsi" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.pengiriman.provinsi.store') }}" method="POST">
                @csrf

                <label for="nama_provinsi" class="dm-form-label">
                    Nama Provinsi
                </label>

                <input type="text" name="nama_provinsi" id="nama_provinsi" class="dm-form-input"
                    placeholder="Masukkan nama provinsi" maxlength="100" required>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalModalProvinsi">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Edit Provinsi --}}
    <div class="dm-modal-overlay" id="modalEditProvinsi">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulEditProvinsi">

            <div class="dm-modal-header">
                <h3 id="judulEditProvinsi">Edit Provinsi</h3>

                <button type="button" class="dm-modal-close" id="tutupEditProvinsi" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form id="formEditProvinsi" method="POST">
                @csrf
                @method('PUT')

                <label for="edit_nama_provinsi" class="dm-form-label">
                    Nama Provinsi
                </label>

                <input type="text" name="nama_provinsi" id="edit_nama_provinsi" class="dm-form-input" maxlength="100"
                    required>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalEditProvinsi">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Konfirmasi Hapus Provinsi --}}
    <div class="dm-modal-overlay" id="modalHapusProvinsi">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulHapusProvinsi">

            <div class="dm-modal-header">
                <h3 id="judulHapusProvinsi">Hapus Provinsi</h3>

                <button type="button" class="dm-modal-close" id="tutupHapusProvinsi" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <p style="font-size:13px; color:#64748B; line-height:1.8;">
                Apakah Anda yakin ingin menghapus provinsi
                <strong id="namaProvinsiHapus"></strong>?
            </p>

            <p style="font-size:12px; color:#DC2626;">
                Provinsi yang masih digunakan oleh data kota
                tidak dapat dihapus.
            </p>

            <form id="formHapusProvinsi" method="POST">
                @csrf
                @method('DELETE')

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalHapusProvinsi">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save" style="background:#DC2626; border-color:#DC2626;">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Tambah Kota --}}
    <div class="dm-modal-overlay" id="modalTambahKota">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahKota">

            <div class="dm-modal-header">
                <h3 id="judulTambahKota">Tambah Kota</h3>

                <button type="button" class="dm-modal-close" id="tutupTambahKota" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.pengiriman.kota.store') }}" method="POST">
                @csrf

                <div style="margin-bottom:18px;">
                    <label for="provinsi_id" class="dm-form-label">
                        Provinsi
                    </label>

                    <select name="provinsi_id" id="provinsi_id" class="dm-form-input" required>

                        <option value="">Pilih Provinsi</option>

                        @foreach($provinsi as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_provinsi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="nama_kota" class="dm-form-label">
                        Nama Kota
                    </label>

                    <input type="text" name="nama_kota" id="nama_kota" class="dm-form-input"
                        placeholder="Masukkan nama kota" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalTambahKota">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Edit Kota --}}
    <div class="dm-modal-overlay" id="modalEditKota">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulEditKota">

            <div class="dm-modal-header">
                <h3 id="judulEditKota">Edit Kota</h3>

                <button type="button" class="dm-modal-close" id="tutupEditKota" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form id="formEditKota" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom:18px;">
                    <label for="edit_provinsi_id" class="dm-form-label">
                        Provinsi
                    </label>

                    <select name="provinsi_id" id="edit_provinsi_id" class="dm-form-input" required>

                        <option value="">Pilih Provinsi</option>

                        @foreach($provinsi as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_provinsi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_nama_kota" class="dm-form-label">
                        Nama Kota
                    </label>

                    <input type="text" name="nama_kota" id="edit_nama_kota" class="dm-form-input"
                        placeholder="Masukkan nama kota" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalEditKota">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Konfirmasi Hapus Kota --}}
    <div class="dm-modal-overlay" id="modalHapusKota">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulHapusKota">

            <div class="dm-modal-header">
                <h3 id="judulHapusKota">Hapus Kota</h3>

                <button type="button" class="dm-modal-close" id="tutupHapusKota" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <p style="font-size:13px; color:#64748B; line-height:1.8;">
                Apakah Anda yakin ingin menghapus kota
                <strong id="namaKotaHapus"></strong>?
            </p>

            <p style="font-size:12px; color:#DC2626;">
                Kota yang masih digunakan oleh data kecamatan
                tidak dapat dihapus.
            </p>

            <form id="formHapusKota" method="POST">
                @csrf
                @method('DELETE')

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalHapusKota">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save" style="background:#DC2626; border-color:#DC2626;">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Tambah Kecamatan --}}
    <div class="dm-modal-overlay" id="modalTambahKecamatan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahKecamatan">

            <div class="dm-modal-header">
                <h3 id="judulTambahKecamatan">Tambah Kecamatan</h3>

                <button type="button" class="dm-modal-close" id="tutupTambahKecamatan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.pengiriman.kecamatan.store') }}" method="POST">
                @csrf

                <div style="margin-bottom:18px;">
                    <label for="tambah_kota_id" class="dm-form-label">
                        Kota
                    </label>

                    <select name="kota_id" id="tambah_kota_id" class="dm-form-input" required>

                        <option value="">Pilih Kota</option>

                        @foreach($kota as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kota }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tambah_nama_kecamatan" class="dm-form-label">
                        Nama Kecamatan
                    </label>

                    <input type="text" name="nama_kecamatan" id="tambah_nama_kecamatan" class="dm-form-input"
                        placeholder="Masukkan nama kecamatan" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalTambahKecamatan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Edit Kecamatan --}}
    <div class="dm-modal-overlay" id="modalEditKecamatan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulEditKecamatan">

            <div class="dm-modal-header">
                <h3 id="judulEditKecamatan">Edit Kecamatan</h3>

                <button type="button" class="dm-modal-close" id="tutupEditKecamatan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form id="formEditKecamatan" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom:18px;">
                    <label for="edit_kecamatan_kota_id" class="dm-form-label">
                        Kota
                    </label>

                    <select name="kota_id" id="edit_kecamatan_kota_id" class="dm-form-input" required>

                        <option value="">Pilih Kota</option>

                        @foreach($kota as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kota }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_nama_kecamatan" class="dm-form-label">
                        Nama Kecamatan
                    </label>

                    <input type="text" name="nama_kecamatan" id="edit_nama_kecamatan" class="dm-form-input"
                        placeholder="Masukkan nama kecamatan" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalEditKecamatan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Konfirmasi Hapus Kecamatan --}}
    <div class="dm-modal-overlay" id="modalHapusKecamatan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulHapusKecamatan">

            <div class="dm-modal-header">
                <h3 id="judulHapusKecamatan">Hapus Kecamatan</h3>

                <button type="button" class="dm-modal-close" id="tutupHapusKecamatan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <p style="font-size:13px; color:#64748B; line-height:1.8;">
                Apakah Anda yakin ingin menghapus kecamatan
                <strong id="namaKecamatanHapus"></strong>?
            </p>

            <p style="font-size:12px; color:#DC2626;">
                Kecamatan yang masih memiliki data kelurahan
                tidak dapat dihapus.
            </p>

            <form id="formHapusKecamatan" method="POST">
                @csrf
                @method('DELETE')

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalHapusKecamatan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save" style="background:#DC2626; border-color:#DC2626;">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Tambah Kelurahan --}}
    <div class="dm-modal-overlay" id="modalTambahKelurahan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahKelurahan">

            <div class="dm-modal-header">
                <h3 id="judulTambahKelurahan">Tambah Kelurahan</h3>

                <button type="button" class="dm-modal-close" id="tutupTambahKelurahan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.pengiriman.kelurahan.store') }}" method="POST">
                @csrf

                <div style="margin-bottom:18px;">
                    <label for="tambah_kecamatan_id" class="dm-form-label">
                        Kecamatan
                    </label>

                    <select name="kecamatan_id" id="tambah_kecamatan_id" class="dm-form-input" required>

                        <option value="">Pilih Kecamatan</option>

                        @foreach($kecamatan as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kecamatan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tambah_nama_kelurahan" class="dm-form-label">
                        Nama Kelurahan
                    </label>

                    <input type="text" name="nama_kelurahan" id="tambah_nama_kelurahan" class="dm-form-input"
                        placeholder="Masukkan nama kelurahan" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalTambahKelurahan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Edit Kelurahan --}}
    <div class="dm-modal-overlay" id="modalEditKelurahan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulEditKelurahan">

            <div class="dm-modal-header">
                <h3 id="judulEditKelurahan">Edit Kelurahan</h3>

                <button type="button" class="dm-modal-close" id="tutupEditKelurahan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form id="formEditKelurahan" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom:18px;">
                    <label for="edit_kelurahan_kecamatan_id" class="dm-form-label">
                        Kecamatan
                    </label>

                    <select name="kecamatan_id" id="edit_kelurahan_kecamatan_id" class="dm-form-input" required>

                        <option value="">Pilih Kecamatan</option>

                        @foreach($kecamatan as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kecamatan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_nama_kelurahan" class="dm-form-label">
                        Nama Kelurahan
                    </label>

                    <input type="text" name="nama_kelurahan" id="edit_nama_kelurahan" class="dm-form-input"
                        placeholder="Masukkan nama kelurahan" maxlength="100" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalEditKelurahan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Konfirmasi Hapus Kelurahan --}}
    <div class="dm-modal-overlay" id="modalHapusKelurahan">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulHapusKelurahan">

            <div class="dm-modal-header">
                <h3 id="judulHapusKelurahan">Hapus Kelurahan</h3>

                <button type="button" class="dm-modal-close" id="tutupHapusKelurahan" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <p style="font-size:13px; color:#64748B; line-height:1.8;">
                Apakah Anda yakin ingin menghapus kelurahan
                <strong id="namaKelurahanHapus"></strong>?
            </p>

            <p style="font-size:12px; color:#DC2626;">
                Kelurahan yang masih memiliki data ongkir
                tidak dapat dihapus.
            </p>

            <form id="formHapusKelurahan" method="POST">
                @csrf
                @method('DELETE')

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalHapusKelurahan">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save" style="background:#DC2626; border-color:#DC2626;">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Tambah Ongkir --}}
    <div class="dm-modal-overlay" id="modalTambahOngkir">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahOngkir">

            <div class="dm-modal-header">
                <h3 id="judulTambahOngkir">Tambah Ongkir</h3>

                <button type="button" class="dm-modal-close" id="tutupTambahOngkir" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.pengiriman.ongkir.store') }}" method="POST" id="formTambahOngkir">
                @csrf

                {{-- Pilih Kelurahan --}}
                <div style="margin-bottom:18px;">
                    <label for="tambah_ongkir_kelurahan_id" class="dm-form-label">
                        Kelurahan
                    </label>

                    <select name="kelurahan_id" id="tambah_ongkir_kelurahan_id" class="dm-form-input" required>

                        <option value="">Pilih Kelurahan</option>

                        @foreach($kelurahan as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kelurahan }}
                                - {{ $item->nama_kecamatan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Jarak Pengiriman --}}
                <div style="margin-bottom:18px;">
                    <label for="tambah_ongkir_jarak" class="dm-form-label">
                        Jarak (km)
                    </label>

                    <input type="number" name="jarak_km" id="tambah_ongkir_jarak" class="dm-form-input"
                        placeholder="Contoh: 8.5" min="0.01" step="0.01" required>
                </div>

                {{-- Biaya Ongkir --}}
                <div>
                    <label for="tambah_ongkir_biaya" class="dm-form-label">
                        Biaya Ongkir (Rp)
                    </label>

                    <input type="number" name="biaya_ongkir" id="tambah_ongkir_biaya" class="dm-form-input"
                        placeholder="Contoh: 15000" min="0" step="1" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalTambahOngkir">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Edit Ongkir --}}
    <div class="dm-modal-overlay" id="modalEditOngkir">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulEditOngkir">

            <div class="dm-modal-header">
                <h3 id="judulEditOngkir">Edit Ongkir</h3>

                <button type="button" class="dm-modal-close" id="tutupEditOngkir" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <form id="formEditOngkir" method="POST">
                @csrf
                @method('PUT')

                {{-- Pilih Kelurahan --}}
                <div style="margin-bottom:18px;">
                    <label for="edit_ongkir_kelurahan_id" class="dm-form-label">
                        Kelurahan
                    </label>

                    <select name="kelurahan_id" id="edit_ongkir_kelurahan_id" class="dm-form-input" required>

                        <option value="">Pilih Kelurahan</option>

                        @foreach($kelurahan as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_kelurahan }}
                                - {{ $item->nama_kecamatan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Jarak Pengiriman --}}
                <div style="margin-bottom:18px;">
                    <label for="edit_ongkir_jarak" class="dm-form-label">
                        Jarak (km)
                    </label>

                    <input type="number" name="jarak_km" id="edit_ongkir_jarak" class="dm-form-input"
                        placeholder="Masukkan jarak pengiriman" min="0.01" step="0.01" required>
                </div>

                {{-- Biaya Ongkir --}}
                <div>
                    <label for="edit_ongkir_biaya" class="dm-form-label">
                        Biaya Ongkir (Rp)
                    </label>

                    <input type="number" name="biaya_ongkir" id="edit_ongkir_biaya" class="dm-form-input"
                        placeholder="Masukkan biaya ongkir" min="0" step="1" required>
                </div>

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalEditOngkir">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup Konfirmasi Hapus Ongkir --}}
    <div class="dm-modal-overlay" id="modalHapusOngkir">
        <div class="dm-modal" role="dialog" aria-modal="true" aria-labelledby="judulHapusOngkir">

            <div class="dm-modal-header">
                <h3 id="judulHapusOngkir">Hapus Ongkir</h3>

                <button type="button" class="dm-modal-close" id="tutupHapusOngkir" aria-label="Tutup">
                    &times;
                </button>
            </div>

            <p style="font-size:13px; color:#64748B; line-height:1.8;">
                Apakah Anda yakin ingin menghapus data ongkir untuk kelurahan
                <strong id="namaOngkirHapus"></strong>?
            </p>

            <p style="font-size:12px; color:#DC2626; line-height:1.8;">
                Data ongkir yang dihapus tidak dapat digunakan lagi
                untuk perhitungan biaya pengiriman baru.
            </p>

            <form id="formHapusOngkir" method="POST">
                @csrf
                @method('DELETE')

                <div class="dm-modal-footer">
                    <button type="button" class="dm-btn-cancel" id="batalHapusOngkir">
                        Batal
                    </button>

                    <button type="submit" class="dm-btn-save" style="background:#DC2626; border-color:#DC2626;">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tables = {};

            document.querySelectorAll('.dm-panel').forEach(function (panel) {
                const jenis = panel.id.replace('panel-', '');

                tables[jenis] = $('#table-' + jenis).DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    autoWidth: false,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { targets: [0, -1], orderable: false }
                    ],
                    language: {
                        emptyTable: 'Belum ada data.',
                        zeroRecords: 'Data tidak ditemukan.',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 to 0 of 0 entries',
                        paginate: {
                            previous: '‹',
                            next: '›'
                        }
                    }
                });
            });

            document.querySelectorAll('.dm-tab').forEach(function (button) {
                button.addEventListener('click', function () {
                    const jenis = this.dataset.tab;

                    document.querySelectorAll('.dm-tab').forEach(function (tab) {
                        tab.classList.remove('active');
                    });

                    document.querySelectorAll('.dm-panel').forEach(function (panel) {
                        panel.classList.remove('active');
                    });

                    this.classList.add('active');
                    document.getElementById('panel-' + jenis).classList.add('active');

                    tables[jenis].columns.adjust().draw();

                    document.getElementById('dmTambah').innerHTML =
                        '<i class="bi bi-plus-lg"></i> Tambah ' +
                        jenis.charAt(0).toUpperCase() + jenis.slice(1);
                });
            });

            // Popup Tambah Provinsi
            const modalProvinsi = document.getElementById('modalTambahProvinsi');
            const tombolTambah = document.getElementById('dmTambah');

            tombolTambah.addEventListener('click', function () {
                const tabAktif = document.querySelector('.dm-tab.active')?.dataset.tab;

                if (tabAktif === 'provinsi') {
                    modalProvinsi.classList.add('active');
                    document.getElementById('nama_provinsi').focus();
                }
            });

            function tutupModalProvinsi() {
                modalProvinsi.classList.remove('active');
            }

            document.getElementById('tutupModalProvinsi')
                .addEventListener('click', tutupModalProvinsi);

            document.getElementById('batalModalProvinsi')
                .addEventListener('click', tutupModalProvinsi);

            modalProvinsi.addEventListener('click', function (event) {
                if (event.target === modalProvinsi) {
                    tutupModalProvinsi();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupModalProvinsi();
                }
            });

            // Popup Edit Provinsi
            const modalEditProvinsi = document.getElementById('modalEditProvinsi');
            const formEditProvinsi = document.getElementById('formEditProvinsi');
            const inputEditProvinsi = document.getElementById('edit_nama_provinsi');

            const urlUpdateProvinsi = @json(
                route('admin.pengiriman.provinsi.update', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolEdit = event.target.closest('.dm-edit-provinsi');

                if (!tombolEdit) return;

                const id = tombolEdit.dataset.id;
                const nama = tombolEdit.dataset.nama;

                inputEditProvinsi.value = nama;

                formEditProvinsi.action = urlUpdateProvinsi.replace('__ID__', id);

                modalEditProvinsi.classList.add('active');
                inputEditProvinsi.focus();
            });

            function tutupPopupEditProvinsi() {
                modalEditProvinsi.classList.remove('active');
            }

            document.getElementById('tutupEditProvinsi')
                .addEventListener('click', tutupPopupEditProvinsi);

            document.getElementById('batalEditProvinsi')
                .addEventListener('click', tutupPopupEditProvinsi);

            modalEditProvinsi.addEventListener('click', function (event) {
                if (event.target === modalEditProvinsi) {
                    tutupPopupEditProvinsi();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupEditProvinsi();
                }
            });

            // Popup Hapus Provinsi
            const modalHapusProvinsi = document.getElementById('modalHapusProvinsi');
            const formHapusProvinsi = document.getElementById('formHapusProvinsi');
            const namaProvinsiHapus = document.getElementById('namaProvinsiHapus');

            const urlHapusProvinsi = @json(
                route('admin.pengiriman.provinsi.destroy', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolHapus = event.target.closest('.dm-delete-provinsi');

                if (!tombolHapus) return;

                const id = tombolHapus.dataset.id;
                const nama = tombolHapus.dataset.nama;

                namaProvinsiHapus.textContent = nama;
                formHapusProvinsi.action = urlHapusProvinsi.replace('__ID__', id);

                modalHapusProvinsi.classList.add('active');
            });

            function tutupPopupHapusProvinsi() {
                modalHapusProvinsi.classList.remove('active');
            }

            document.getElementById('tutupHapusProvinsi')
                .addEventListener('click', tutupPopupHapusProvinsi);

            document.getElementById('batalHapusProvinsi')
                .addEventListener('click', tutupPopupHapusProvinsi);

            modalHapusProvinsi.addEventListener('click', function (event) {
                if (event.target === modalHapusProvinsi) {
                    tutupPopupHapusProvinsi();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupHapusProvinsi();
                }
            });

            // Popup Tambah Kota
            const modalTambahKota = document.getElementById('modalTambahKota');

            tombolTambah.addEventListener('click', function () {
                const tabAktif = document.querySelector('.dm-tab.active')?.dataset.tab;

                if (tabAktif === 'kota') {
                    modalTambahKota.classList.add('active');
                    document.getElementById('provinsi_id').focus();
                }
            });

            function tutupPopupTambahKota() {
                modalTambahKota.classList.remove('active');
            }

            document.getElementById('tutupTambahKota')
                .addEventListener('click', tutupPopupTambahKota);

            document.getElementById('batalTambahKota')
                .addEventListener('click', tutupPopupTambahKota);

            modalTambahKota.addEventListener('click', function (event) {
                if (event.target === modalTambahKota) {
                    tutupPopupTambahKota();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupTambahKota();
                }
            });

            // Popup Edit Kota
            const modalEditKota = document.getElementById('modalEditKota');
            const formEditKota = document.getElementById('formEditKota');
            const inputEditNamaKota = document.getElementById('edit_nama_kota');
            const selectEditProvinsi = document.getElementById('edit_provinsi_id');

            const urlUpdateKota = @json(
                route('admin.pengiriman.kota.update', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolEditKota = event.target.closest('.dm-edit-kota');

                if (!tombolEditKota) return;

                const id = tombolEditKota.dataset.id;
                const nama = tombolEditKota.dataset.nama;
                const provinsiId = tombolEditKota.dataset.provinsi;

                inputEditNamaKota.value = nama;
                selectEditProvinsi.value = provinsiId;

                formEditKota.action = urlUpdateKota.replace('__ID__', id);

                modalEditKota.classList.add('active');
                selectEditProvinsi.focus();
            });

            function tutupPopupEditKota() {
                modalEditKota.classList.remove('active');
            }

            document.getElementById('tutupEditKota')
                .addEventListener('click', tutupPopupEditKota);

            document.getElementById('batalEditKota')
                .addEventListener('click', tutupPopupEditKota);

            modalEditKota.addEventListener('click', function (event) {
                if (event.target === modalEditKota) {
                    tutupPopupEditKota();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupEditKota();
                }
            });

            // Popup Hapus Kota
            const modalHapusKota = document.getElementById('modalHapusKota');
            const formHapusKota = document.getElementById('formHapusKota');
            const namaKotaHapus = document.getElementById('namaKotaHapus');

            const urlHapusKota = @json(
                route('admin.pengiriman.kota.destroy', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolHapusKota = event.target.closest('.dm-delete-kota');

                if (!tombolHapusKota) return;

                const id = tombolHapusKota.dataset.id;
                const nama = tombolHapusKota.dataset.nama;

                namaKotaHapus.textContent = nama;
                formHapusKota.action = urlHapusKota.replace('__ID__', id);

                modalHapusKota.classList.add('active');
            });

            function tutupPopupHapusKota() {
                modalHapusKota.classList.remove('active');
            }

            document.getElementById('tutupHapusKota')
                .addEventListener('click', tutupPopupHapusKota);

            document.getElementById('batalHapusKota')
                .addEventListener('click', tutupPopupHapusKota);

            modalHapusKota.addEventListener('click', function (event) {
                if (event.target === modalHapusKota) {
                    tutupPopupHapusKota();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupHapusKota();
                }
            });

            // Popup Tambah Kecamatan
            const modalTambahKecamatan = document.getElementById('modalTambahKecamatan');

            tombolTambah.addEventListener('click', function () {
                const tabAktif = document.querySelector('.dm-tab.active')?.dataset.tab;

                if (tabAktif === 'kecamatan') {
                    modalTambahKecamatan.classList.add('active');
                    document.getElementById('tambah_kota_id').focus();
                }
            });

            function tutupPopupTambahKecamatan() {
                modalTambahKecamatan.classList.remove('active');
            }

            document.getElementById('tutupTambahKecamatan')
                .addEventListener('click', tutupPopupTambahKecamatan);

            document.getElementById('batalTambahKecamatan')
                .addEventListener('click', tutupPopupTambahKecamatan);

            modalTambahKecamatan.addEventListener('click', function (event) {
                if (event.target === modalTambahKecamatan) {
                    tutupPopupTambahKecamatan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupTambahKecamatan();
                }
            });

            // Popup Edit Kecamatan
            const modalEditKecamatan = document.getElementById('modalEditKecamatan');
            const formEditKecamatan = document.getElementById('formEditKecamatan');
            const inputEditKecamatan = document.getElementById('edit_nama_kecamatan');
            const selectEditKota = document.getElementById('edit_kecamatan_kota_id');

            const urlUpdateKecamatan = @json(
                route('admin.pengiriman.kecamatan.update', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolEdit = event.target.closest('.dm-edit-kecamatan');

                if (!tombolEdit) return;

                const id = tombolEdit.dataset.id;
                const nama = tombolEdit.dataset.nama;
                const kotaId = tombolEdit.dataset.kota;

                inputEditKecamatan.value = nama;
                selectEditKota.value = kotaId;

                formEditKecamatan.action = urlUpdateKecamatan.replace('__ID__', id);

                modalEditKecamatan.classList.add('active');
                selectEditKota.focus();
            });

            function tutupPopupEditKecamatan() {
                modalEditKecamatan.classList.remove('active');
            }

            document.getElementById('tutupEditKecamatan')
                .addEventListener('click', tutupPopupEditKecamatan);

            document.getElementById('batalEditKecamatan')
                .addEventListener('click', tutupPopupEditKecamatan);

            modalEditKecamatan.addEventListener('click', function (event) {
                if (event.target === modalEditKecamatan) {
                    tutupPopupEditKecamatan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupEditKecamatan();
                }
            });

            // Popup Hapus Kecamatan
            const modalHapusKecamatan = document.getElementById('modalHapusKecamatan');
            const formHapusKecamatan = document.getElementById('formHapusKecamatan');
            const namaKecamatanHapus = document.getElementById('namaKecamatanHapus');

            const urlHapusKecamatan = @json(
                route('admin.pengiriman.kecamatan.destroy', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolHapus = event.target.closest('.dm-delete-kecamatan');

                if (!tombolHapus) return;

                const id = tombolHapus.dataset.id;
                const nama = tombolHapus.dataset.nama;

                namaKecamatanHapus.textContent = nama;

                formHapusKecamatan.action = urlHapusKecamatan.replace('__ID__', id);

                modalHapusKecamatan.classList.add('active');
            });

            function tutupPopupHapusKecamatan() {
                modalHapusKecamatan.classList.remove('active');
            }

            document.getElementById('tutupHapusKecamatan')
                .addEventListener('click', tutupPopupHapusKecamatan);

            document.getElementById('batalHapusKecamatan')
                .addEventListener('click', tutupPopupHapusKecamatan);

            modalHapusKecamatan.addEventListener('click', function (event) {
                if (event.target === modalHapusKecamatan) {
                    tutupPopupHapusKecamatan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupHapusKecamatan();
                }
            });

            // Popup Tambah Kelurahan
            const modalTambahKelurahan = document.getElementById('modalTambahKelurahan');

            tombolTambah.addEventListener('click', function () {
                const tabAktif = document.querySelector('.dm-tab.active')?.dataset.tab;

                if (tabAktif === 'kelurahan') {
                    modalTambahKelurahan.classList.add('active');
                    document.getElementById('tambah_kecamatan_id').focus();
                }
            });

            function tutupPopupTambahKelurahan() {
                modalTambahKelurahan.classList.remove('active');
            }

            document.getElementById('tutupTambahKelurahan')
                .addEventListener('click', tutupPopupTambahKelurahan);

            document.getElementById('batalTambahKelurahan')
                .addEventListener('click', tutupPopupTambahKelurahan);

            modalTambahKelurahan.addEventListener('click', function (event) {
                if (event.target === modalTambahKelurahan) {
                    tutupPopupTambahKelurahan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupTambahKelurahan();
                }
            });

            // Popup Edit Kelurahan
            const modalEditKelurahan = document.getElementById('modalEditKelurahan');
            const formEditKelurahan = document.getElementById('formEditKelurahan');
            const inputEditKelurahan = document.getElementById('edit_nama_kelurahan');
            const selectEditKecamatan = document.getElementById('edit_kelurahan_kecamatan_id');

            const urlUpdateKelurahan = @json(
                route('admin.pengiriman.kelurahan.update', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolEditKelurahan = event.target.closest('.dm-edit-kelurahan');

                if (!tombolEditKelurahan) return;

                const id = tombolEditKelurahan.dataset.id;
                const nama = tombolEditKelurahan.dataset.nama;
                const kecamatanId = tombolEditKelurahan.dataset.kecamatan;

                inputEditKelurahan.value = nama;
                selectEditKecamatan.value = kecamatanId;

                formEditKelurahan.action = urlUpdateKelurahan.replace('__ID__', id);

                modalEditKelurahan.classList.add('active');
                selectEditKecamatan.focus();
            });

            function tutupPopupEditKelurahan() {
                modalEditKelurahan.classList.remove('active');
            }

            document.getElementById('tutupEditKelurahan')
                .addEventListener('click', tutupPopupEditKelurahan);

            document.getElementById('batalEditKelurahan')
                .addEventListener('click', tutupPopupEditKelurahan);

            modalEditKelurahan.addEventListener('click', function (event) {
                if (event.target === modalEditKelurahan) {
                    tutupPopupEditKelurahan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupEditKelurahan();
                }
            });

            // Popup Hapus Kelurahan
            const modalHapusKelurahan = document.getElementById('modalHapusKelurahan');
            const formHapusKelurahan = document.getElementById('formHapusKelurahan');
            const namaKelurahanHapus = document.getElementById('namaKelurahanHapus');

            const urlHapusKelurahan = @json(
                route('admin.pengiriman.kelurahan.destroy', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolHapusKelurahan = event.target.closest('.dm-delete-kelurahan');

                if (!tombolHapusKelurahan) return;

                const id = tombolHapusKelurahan.dataset.id;
                const nama = tombolHapusKelurahan.dataset.nama;

                namaKelurahanHapus.textContent = nama;

                formHapusKelurahan.action = urlHapusKelurahan.replace('__ID__', id);

                modalHapusKelurahan.classList.add('active');
            });

            function tutupPopupHapusKelurahan() {
                modalHapusKelurahan.classList.remove('active');
            }

            document.getElementById('tutupHapusKelurahan')
                .addEventListener('click', tutupPopupHapusKelurahan);

            document.getElementById('batalHapusKelurahan')
                .addEventListener('click', tutupPopupHapusKelurahan);

            modalHapusKelurahan.addEventListener('click', function (event) {
                if (event.target === modalHapusKelurahan) {
                    tutupPopupHapusKelurahan();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupHapusKelurahan();
                }
            });

            // Popup Tambah Ongkir
            const modalTambahOngkir = document.getElementById('modalTambahOngkir');
            const formTambahOngkir = document.getElementById('formTambahOngkir');

            tombolTambah.addEventListener('click', function () {
                const tabAktif = document.querySelector('.dm-tab.active')?.dataset.tab;

                if (tabAktif === 'ongkir') {
                    formTambahOngkir.reset();

                    modalTambahOngkir.classList.add('active');

                    document.getElementById('tambah_ongkir_kelurahan_id').focus();
                }
            });

            function tutupPopupTambahOngkir() {
                modalTambahOngkir.classList.remove('active');
            }

            document.getElementById('tutupTambahOngkir')
                .addEventListener('click', tutupPopupTambahOngkir);

            document.getElementById('batalTambahOngkir')
                .addEventListener('click', tutupPopupTambahOngkir);

            modalTambahOngkir.addEventListener('click', function (event) {
                if (event.target === modalTambahOngkir) {
                    tutupPopupTambahOngkir();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupTambahOngkir();
                }
            });

            // Popup Edit Ongkir
            const modalEditOngkir = document.getElementById('modalEditOngkir');
            const formEditOngkir = document.getElementById('formEditOngkir');

            const selectEditKelurahan = document.getElementById('edit_ongkir_kelurahan_id');
            const inputEditJarak = document.getElementById('edit_ongkir_jarak');
            const inputEditBiaya = document.getElementById('edit_ongkir_biaya');

            const urlUpdateOngkir = @json(
                route('admin.pengiriman.ongkir.update', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolEditOngkir = event.target.closest('.dm-edit-ongkir');

                if (!tombolEditOngkir) return;

                const id = tombolEditOngkir.dataset.id;
                const kelurahanId = tombolEditOngkir.dataset.kelurahan;
                const jarak = tombolEditOngkir.dataset.jarak;
                const biaya = tombolEditOngkir.dataset.biaya;

                // Isi input berdasarkan data yang dipilih
                selectEditKelurahan.value = kelurahanId;
                inputEditJarak.value = jarak;
                inputEditBiaya.value = biaya;

                // Tentukan URL update berdasarkan ID ongkir
                formEditOngkir.action = urlUpdateOngkir.replace('__ID__', id);

                // Tampilkan popup
                modalEditOngkir.classList.add('active');
                selectEditKelurahan.focus();
            });

            function tutupPopupEditOngkir() {
                modalEditOngkir.classList.remove('active');
            }

            document.getElementById('tutupEditOngkir')
                .addEventListener('click', tutupPopupEditOngkir);

            document.getElementById('batalEditOngkir')
                .addEventListener('click', tutupPopupEditOngkir);

            modalEditOngkir.addEventListener('click', function (event) {
                if (event.target === modalEditOngkir) {
                    tutupPopupEditOngkir();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupEditOngkir();
                }
            });

            // Popup Hapus Ongkir
            const modalHapusOngkir = document.getElementById('modalHapusOngkir');
            const formHapusOngkir = document.getElementById('formHapusOngkir');
            const namaOngkirHapus = document.getElementById('namaOngkirHapus');

            const urlHapusOngkir = @json(
                route('admin.pengiriman.ongkir.destroy', ['id' => '__ID__'])
            );

            document.addEventListener('click', function (event) {
                const tombolHapusOngkir = event.target.closest('.dm-delete-ongkir');

                if (!tombolHapusOngkir) return;

                const id = tombolHapusOngkir.dataset.id;
                const nama = tombolHapusOngkir.dataset.nama;
                const kecamatan = tombolHapusOngkir.dataset.kecamatan;

                // Tampilkan nama kelurahan dan kecamatan
                namaOngkirHapus.textContent = nama + ' - ' + kecamatan;

                // Tentukan URL penghapusan berdasarkan ID
                formHapusOngkir.action = urlHapusOngkir.replace('__ID__', id);

                // Tampilkan popup
                modalHapusOngkir.classList.add('active');
            });

            function tutupPopupHapusOngkir() {
                modalHapusOngkir.classList.remove('active');
            }

            document.getElementById('tutupHapusOngkir')
                .addEventListener('click', tutupPopupHapusOngkir);

            document.getElementById('batalHapusOngkir')
                .addEventListener('click', tutupPopupHapusOngkir);

            modalHapusOngkir.addEventListener('click', function (event) {
                if (event.target === modalHapusOngkir) {
                    tutupPopupHapusOngkir();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    tutupPopupHapusOngkir();
                }
            });
        });
    </script>
@endpush