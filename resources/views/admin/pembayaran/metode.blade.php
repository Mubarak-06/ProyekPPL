@extends('admin.layouts.app')

@section('title', 'Metode Pembayaran')

@push('styles')
    <style>
        .mp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .mp-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .mp-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .mp-breadcrumb a {
            color: #64748B;
            text-decoration: none;
        }

        .mp-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            background: #008A50;
            color: #FFFFFF;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .mp-btn:hover {
            background: #007344;
        }

        .mp-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(300px, 1fr);
            gap: 20px;
            align-items: start;
        }

        .mp-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 20px;
            min-width: 0;
        }

        .mp-card-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .mp-description {
            margin: 5px 0 0;
            font-size: 11px;
            color: #64748B;
        }

        .mp-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .mp-search {
            width: 190px;
            padding: 10px 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            outline: none;
        }

        .mp-search:focus {
            border-color: #008A50;
        }

        .mp-table-wrap {
            overflow-x: auto;
        }

        .mp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .mp-table th {
            padding: 13px 10px;
            border-bottom: 1px solid #E2E8F0;
            color: #111827;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }

        .mp-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #E2E8F0;
            color: #334155;
            vertical-align: middle;
        }

        .mp-table tbody tr:last-child td {
            border-bottom: none;
        }

        .mp-info-name {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
        }

        .mp-info-detail {
            font-size: 10px;
            color: #64748B;
            margin-top: 3px;
        }

        .mp-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
        }

        .mp-status.active {
            background: #DCFCE7;
            color: #15803D;
        }

        .mp-status.inactive {
            background: #FEE2E2;
            color: #DC2626;
        }

        .mp-actions {
            display: flex;
            gap: 8px;
        }

        .mp-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 29px;
            height: 29px;
            border: 1px solid #008A50;
            border-radius: 5px;
            background: #FFFFFF;
            color: #008A50;
            cursor: pointer;
        }

        .mp-action.delete {
            border-color: #FF4D67;
            color: #FF4D67;
        }

        .mp-actions a.mp-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .mp-field {
            margin-bottom: 16px;
        }

        .mp-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .mp-required {
            color: #DC2626;
        }

        .mp-input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #334155;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            outline: none;
        }

        .mp-input:focus {
            border-color: #008A50;
        }

        .mp-upload {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 22px;
            border: 1px dashed #CBD5E1;
            border-radius: 8px;
            cursor: pointer;
            color: #64748B;
            text-align: center;
        }

        .mp-upload i {
            font-size: 20px;
        }

        .mp-upload span {
            font-size: 11px;
            font-weight: 500;
        }

        .mp-upload small {
            font-size: 10px;
        }

        .mp-form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }

        .mp-btn-cancel {
            padding: 10px 18px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            cursor: pointer;
        }

        .mp-empty {
            padding: 28px 12px !important;
            text-align: center;
            color: #64748B !important;
        }

        @media (max-width: 1000px) {
            .mp-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {

            .mp-header,
            .mp-table-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .mp-search {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $modeEdit = isset($metode);
    @endphp
    @if(session('success'))
        <div
            style="padding:12px 16px; margin-bottom:16px;
                                                                                                                                        background:#DCFCE7; color:#15803D;
                                                                                                                                        border:1px solid #BBF7D0; border-radius:6px;
                                                                                                                                        font-size:12px;">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="padding:12px 16px; margin-bottom:16px;
                        background:#FEE2E2; color:#DC2626;
                        border:1px solid #FECACA; border-radius:6px;
                        font-size:12px;">

            <i class="bi bi-exclamation-circle"></i>
            {{ session('error') }}

        </div>
    @endif

    @if($errors->any())
        <div
            style="padding:12px 16px; margin-bottom:16px;
                                                                                                                                        background:#FEE2E2; color:#DC2626;
                                                                                                                                        border:1px solid #FECACA; border-radius:6px;
                                                                                                                                        font-size:12px;">
            <strong>Data belum berhasil disimpan:</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- Header --}}
    <div class="mp-header">
        <div>
            <h1 class="mp-title">Metode Pembayaran</h1>
            
            <div class="mp-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                &nbsp; › &nbsp;
                <a href="{{ route('admin.pembayaran.index') }}">Pembayaran</a>
                &nbsp; › &nbsp; Metode Pembayaran
            </div>
        </div>

    </div>

    <div class="mp-layout">

        {{-- Daftar Metode Pembayaran --}}
        <div class="mp-card">

            <div class="mp-table-header">
                <div>
                    <h3 class="mp-card-title">Daftar Metode Pembayaran</h3>
                    <p class="mp-description">
                        Kelola rekening bank dan metode pembayaran yang tersedia.
                    </p>
                </div>

                <input type="text" class="mp-search" id="cariMetode" placeholder="Cari metode pembayaran...">
            </div>

            <div class="mp-table-wrap">
                <table class="mp-table" id="tabelMetode">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Metode</th>
                            <th>Nama / Informasi</th>
                            <th>Logo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($metodePembayaran as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                {{-- Jenis Metode --}}
                                <td>
                                    @if($item->jenis_metode === 'transfer_bank')
                                        Transfer Bank
                                    @elseif($item->jenis_metode === 'ewallet')
                                        E-Wallet
                                    @elseif($item->jenis_metode === 'qris')
                                        QRIS
                                    @endif
                                </td>

                                {{-- Nama dan Informasi --}}
                                <td>
                                    <div class="mp-info-name">
                                        {{ $item->nama_penyedia }}
                                    </div>

                                    @if($item->jenis_metode !== 'qris')
                                        <div class="mp-info-detail">
                                            {{ $item->nomor_rekening }}
                                        </div>

                                        <div class="mp-info-detail">
                                            a.n. {{ $item->nama_pemilik }}
                                        </div>
                                    @else
                                        <div class="mp-info-detail">
                                            Scan kode QR untuk pembayaran
                                        </div>
                                    @endif
                                </td>

                                {{-- Logo --}}
                                <td>
                                    @if($item->logo)
                                        <img src="{{ asset('storage/' . $item->logo) }}" alt="Logo {{ $item->nama_penyedia }}"
                                            style="max-width:65px; max-height:35px; object-fit:contain;">
                                    @else
                                        <span class="mp-info-detail">
                                            Tidak ada logo
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if($item->status === 'aktif')
                                        <span class="mp-status active">Aktif</span>
                                    @else
                                        <span class="mp-status inactive">Nonaktif</span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td>
                                    <div class="mp-actions">
                                        <a href="{{ route('admin.pembayaran.metode.edit', $item->id) }}" class="mp-action"
                                            title="Edit Metode Pembayaran">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form action="{{ route('admin.pembayaran.metode.destroy', $item->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus metode pembayaran {{ $item->nama_penyedia }}?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="mp-action delete" title="Hapus Metode Pembayaran">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="mp-empty">
                                    Belum ada metode pembayaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>

        {{-- Form Tambah Metode Pembayaran --}}
        <div class="mp-card" id="bagianFormMetode">

            <h3 class="mp-card-title" style="margin-bottom:20px;">
                {{ $modeEdit ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran' }}
            </h3>

            <form id="formTambahMetode" action="{{ $modeEdit
        ? route('admin.pembayaran.metode.update', $metode->id)
        : route('admin.pembayaran.metode.store') }}" method="POST" enctype="multipart/form-data">

                @csrf

                @if($modeEdit)
                    @method('PUT')
                @endif
                <div class="mp-field">
                    <label class="mp-label" for="jenisMetode">
                        Jenis Metode <span class="mp-required">*</span>
                    </label>

                    <select class="mp-input" id="jenisMetode" name="jenis_metode" {{ $modeEdit ? 'disabled' : '' }}
                        required>
                        <option value="">Pilih Jenis Metode</option>
                        <option value="transfer_bank" {{ old('jenis_metode', $metode->jenis_metode ?? '') === 'transfer_bank' ? 'selected' : '' }}>
                            Transfer Bank
                        </option>
                        <option value="ewallet" {{ old('jenis_metode', $metode->jenis_metode ?? '') === 'ewallet' ? 'selected' : '' }}>
                            E-Wallet (DANA)
                        </option>
                        <option value="qris" {{ old('jenis_metode', $metode->jenis_metode ?? '') === 'qris' ? 'selected' : '' }}>
                            QRIS
                        </option>
                    </select>
                </div>

                <div class="mp-field" id="fieldNamaBank">
                    <label class="mp-label" for="namaBank">
                        Nama Bank / Penyedia
                        <span class="mp-required">*</span>
                    </label>

                    <select class="mp-input" id="namaBank" name="nama_penyedia"
                        data-selected="{{ old('nama_penyedia', $metode->nama_penyedia ?? '') }}">
                        <option value="">Pilih Bank / Penyedia</option>
                        <option value="BCA">BCA</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="DANA">DANA</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>

                <div class="mp-field" id="fieldNomorRekening">
                    <label class="mp-label" for="nomorRekening">
                        Nomor Rekening / Informasi
                        <span class="mp-required">*</span>
                    </label>

                    <input type="text" class="mp-input" id="nomorRekening" name="nomor_rekening"
                        placeholder="Masukkan nomor rekening"
                        value="{{ old('nomor_rekening', $metode->nomor_rekening ?? '') }}">
                </div>

                <div class="mp-field" id="fieldPemilik">
                    <label class="mp-label" for="namaPemilik">
                        Nama Pemilik Rekening
                        <span class="mp-required">*</span>
                    </label>

                    <input type="text" class="mp-input" id="namaPemilik" name="nama_pemilik"
                        value="{{ old('nama_pemilik', $metode->nama_pemilik ?? '') }}"
                        placeholder="Contoh: CV Karunia Abadi">
                </div>

                <div class="mp-field">
                    <label class="mp-label" for="logoMetode">
                        Logo Bank / Penyedia
                    </label>

                    <label for="logoMetode" class="mp-upload">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>Upload Logo Bank</span>
                        <small>Format PNG, JPG (Maks. 2MB)</small>
                    </label>

                    <input type="file" id="logoMetode" name="logo" accept=".png,.jpg,.jpeg" style="display:none;">
                    @if($modeEdit && $metode->logo)
                        <p style="font-size:11px; color:#777; margin-top:6px;">
                            Logo saat ini:
                            <a href="{{ asset('storage/' . $metode->logo) }}" target="_blank" rel="noopener noreferrer">
                                Lihat Logo
                            </a>
                        </p>
                    @endif
                </div>

                <div class="mp-field" id="fieldGambarQRIS" style="display:none;">
                    <label class="mp-label" for="gambarQRIS">
                        Gambar Kode QRIS <span class="mp-required">*</span>
                    </label>

                    <label for="gambarQRIS" class="mp-upload">
                        <i class="bi bi-qr-code"></i>
                        <span>Upload Gambar QRIS</span>
                        <small>Format PNG, JPG (Maks. 2MB)</small>
                    </label>

                    <input type="file" id="gambarQRIS" name="gambar_qris" accept=".png,.jpg,.jpeg" style="display:none;">
                    @if($modeEdit && $metode->jenis_metode === 'qris' && $metode->gambar_qris)
                        <p style="font-size:11px; color:#777; margin-top:6px;">
                            Gambar QRIS saat ini:
                            <a href="{{ asset('storage/' . $metode->gambar_qris) }}" target="_blank" rel="noopener noreferrer">
                                Lihat QRIS
                            </a>
                        </p>
                    @endif
                </div>

                <div class="mp-field">
                    <label class="mp-label" for="statusMetode">
                        Status <span class="mp-required">*</span>
                    </label>

                    <select class="mp-input" id="statusMetode" name="status" required>
                        <option value="aktif" {{ old('status', $metode->status ?? 'aktif') === 'aktif' ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="nonaktif" {{ old('status', $metode->status ?? 'aktif') === 'nonaktif' ? 'selected' : '' }}>
                            Nonaktif
                        </option>

                    </select>
                </div>

                <div class="mp-form-footer">
                    @if($modeEdit)
                        <a href="{{ route('admin.pembayaran.metode') }}" class="mp-btn-cancel"
                            style="text-decoration:none; display:inline-flex; align-items:center;">
                            Batal
                        </a>
                    @else
                        <button type="reset" class="mp-btn-cancel">Batal</button>
                    @endif

                    <button type="submit" class="mp-btn">
                        {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>

            </form>

        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const formTambah = document.getElementById('formTambahMetode');
            const cariMetode = document.getElementById('cariMetode');
            const tabelMetode = document.getElementById('tabelMetode');

            // Pencarian pada tabel
            cariMetode.addEventListener('input', function () {
                const kataKunci = this.value.toLowerCase();

                tabelMetode.querySelectorAll('tbody tr').forEach(function (baris) {
                    const isiBaris = baris.textContent.toLowerCase();

                    baris.style.display = isiBaris.includes(kataKunci)
                        ? ''
                        : 'none';
                });
            });

            // Form dinamis berdasarkan jenis pembayaran
            const jenisMetode = document.getElementById('jenisMetode');
            const namaBank = document.getElementById('namaBank');
            const nomorRekening = document.getElementById('nomorRekening');
            const namaPemilik = document.getElementById('namaPemilik');
            const gambarQRIS = document.getElementById('gambarQRIS');

            const fieldNamaBank = document.getElementById('fieldNamaBank');
            const fieldNomorRekening = document.getElementById('fieldNomorRekening');
            const fieldPemilik = document.getElementById('fieldPemilik');
            const fieldGambarQRIS = document.getElementById('fieldGambarQRIS');

            function aturFormMetode() {
                const jenis = jenisMetode.value;

                // Atur pilihan penyedia sesuai metode
                const penyedia = {
                    transfer_bank: ['BCA', 'BRI', 'BNI', 'Mandiri'],
                    ewallet: ['DANA'],
                    qris: ['QRIS']
                };

                namaBank.innerHTML = '<option value="">Pilih Bank / Penyedia</option>';

                (penyedia[jenis] || []).forEach(function (nama) {
                    const option = document.createElement('option');
                    option.value = nama;
                    option.textContent = nama;
                    namaBank.appendChild(option);
                });

                const penyediaTerpilih = namaBank.dataset.selected;

                if (penyediaTerpilih) {
                    namaBank.value = penyediaTerpilih;
                }

                // Tampilkan input sesuai jenis
                const tampilRekening = jenis === 'transfer_bank' || jenis === 'ewallet';
                const tampilQRIS = jenis === 'qris';

                fieldNamaBank.style.display = jenis ? '' : 'none';
                fieldNomorRekening.style.display = tampilRekening ? '' : 'none';
                fieldPemilik.style.display = tampilRekening ? '' : 'none';
                fieldGambarQRIS.style.display = tampilQRIS ? '' : 'none';

                namaBank.required = Boolean(jenis);
                nomorRekening.required = tampilRekening;
                namaPemilik.required = tampilRekening;
                gambarQRIS.required = tampilQRIS && !@json($modeEdit);

                if (jenis === 'ewallet') {
                    fieldNomorRekening.querySelector('label').innerHTML =
                        'Nomor DANA <span class="mp-required">*</span>';

                    fieldPemilik.querySelector('label').innerHTML =
                        'Nama Pemilik DANA <span class="mp-required">*</span>';

                    nomorRekening.placeholder = 'Masukkan nomor DANA';
                } else {
                    fieldNomorRekening.querySelector('label').innerHTML =
                        'Nomor Rekening <span class="mp-required">*</span>';

                    fieldPemilik.querySelector('label').innerHTML =
                        'Nama Pemilik Rekening <span class="mp-required">*</span>';

                    nomorRekening.placeholder = 'Masukkan nomor rekening';
                }
            }

            jenisMetode.addEventListener('change', function () {
                namaBank.dataset.selected = '';
                aturFormMetode();
            });
            formTambah.addEventListener('reset', function () {
                setTimeout(aturFormMetode, 0);
            });
            aturFormMetode();
        });
    </script>
@endpush