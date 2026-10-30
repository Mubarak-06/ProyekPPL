@extends('admin.layouts.app')
@section('title', 'Edit Produk')
@push('styles')
<style>
    /* Halaman Tambah Produk */
    .tambah-produk-page h1 {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }

    .tambah-produk-page .breadcrumb {
        font-size: 12px;
        color: #64748B;
        margin-bottom: 22px;
    }

    .form-produk-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .form-produk-card {
        background: #FFFFFF;
        padding: 20px;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
    }

    .form-produk-card h3 {
        font-size: 15px;
        font-weight: 600;
        margin: 0 0 20px;
        color: #111827;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #334155;
        margin-bottom: 7px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        color: #334155;
        background: #FFFFFF;
    }

    .form-group select {
        appearance: none;
        -webkit-appearance: none;

        padding: 11px 40px 11px 12px;

        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748B' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m4 6 4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #008A50;
    }

    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .form-row-three {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .form-material-box {
        background: #F3FCF7;
        border: 1px solid #D1FAE5;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 15px;
    }

    .form-material-box .form-group {
        margin-bottom: 0;
    }

    .form-material-info {
        font-size: 10px;
        color: #008A50;
        margin-top: 10px;
    }

    .upload-produk-area {
        border: 2px dashed #86EFAC;
        background: #F8FFFA;
        border-radius: 8px;
        padding: 40px 15px;
        text-align: center;
        color: #008A50;
    }

    .upload-produk-area i {
        font-size: 32px;
    }

    .upload-produk-area p {
        font-size: 12px;
        font-weight: 600;
        margin: 12px 0 4px;
    }

    .upload-produk-area small {
        font-size: 10px;
        color: #94A3B8;
    }

    .form-produk-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-batal {
        padding: 10px 18px;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        color: #334155;
        text-decoration: none;
        font-size: 12px;
        background: white;
    }

    .btn-simpan {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        color: white;
        background: #008A50;
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        font-weight: 500;
    }

    .btn-simpan:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    button[type="submit"] {
        cursor: pointer;
    }

    @media (max-width: 900px) {
        .form-produk-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .form-row,
        .form-row-three {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="tambah-produk-page">
    @if ($errors->any())
        <div style="background:#FEF2F2; color:#B91C1C;
                    border:1px solid #FECACA; padding:14px;
                    border-radius:8px; margin-bottom:20px;">
            <strong>Produk belum berhasil disimpan.</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <h1>Edit Produk</h1>

    <div class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        &nbsp; › &nbsp;
        <a href="{{ route('admin.produk.index') }}">Produk</a>
        &nbsp; › &nbsp; Tambah Produk
    </div>
    <form action="{{ route('admin.produk.update', $produk->id) }}"
        method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-produk-layout">

            <!-- INFORMASI PRODUK -->
            <div class="form-produk-card">

                <h3>Informasi Produk</h3>

                <!-- Nama Produk -->
                <div class="form-group">
                    <label>Nama Produk <span style="color:red">*</span></label>
                    <input type="text"
                        name="nama_produk"
                        value="{{ old('nama_produk', $produk->nama_produk) }}"
                        placeholder="Masukkan nama produk">
                </div>

                <!-- Kategori dan Harga -->
                <div class="form-row">

                    <div class="form-group">
                        <label>Kategori <span style="color:red">*</span></label>
                        <select name="kategori_id" id="kategori_id">
                            <option value="">Pilih kategori</option>

                            @foreach($kategori as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('kategori_id', $produk->kategori_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Harga <span style="color:red">*</span></label>
                        <input type="number"
                            name="harga"
                            min="0"
                            value="{{ old('harga', $produk->harga) }}"
                            placeholder="Masukkan harga">
                    </div>

                </div>

                <!-- Ukuran dan Material -->
                <div class="form-material-box">

                    <div class="form-row">

                        <div class="form-group">
                            <label>Ukuran</label>
                            <input type="text"
                                name="ukuran"
                                id="ukuran"
                                value="{{ old('ukuran', $produk->ukuran) }}"
                                placeholder="Masukkan ukuran"
                                {{ empty($material->count()) ? 'disabled' : '' }}>
                        </div>

                        <div class="form-group">
                            <label>Material</label>
                            <select name="material_id"
                                id="material_id"
                                {{ $material->isEmpty() ? 'disabled' : '' }}>

                            <option value="">Pilih material</option>

                            @foreach($material as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('material_id', $produk->material_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama_material }}
                                </option>
                            @endforeach
                        </select>
                        </div>

                    </div>

                    <div class="form-material-info">
                        ⓘ Kolom ukuran dan material hanya tersedia
                        untuk kategori Baut, Mur, Ring, dan Sekrup.
                    </div>

                </div>

                <!-- Stok, Satuan, Berat -->
                <div class="form-row-three">

                    <div class="form-group">
                        <label>Stok <span style="color:red">*</span></label>
                        <input type="number"
                            name="stok"
                            min="0"
                            value="{{ old('stok', $produk->stok) }}"
                            placeholder="Masukkan stok">
                    </div>

                    <div class="form-group">
                        <label>Satuan <span style="color:red">*</span></label>
                        <select name="satuan_id" id="satuan_id">
                            <option value="">Pilih satuan</option>

                            @foreach($satuan as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('satuan_id', $produk->satuan_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama_satuan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Berat (gram) <span style="color:red">*</span></label>
                        <input type="number"
                            name="berat"
                            min="0.01"
                            step="0.01"
                            value="{{ old('berat', $produk->berat) }}"
                            placeholder="Masukkan berat">
                    </div>

                </div>

                <!-- Deskripsi -->
                <div class="form-group">
                    <label>Deskripsi Produk <span style="color:red">*</span></label>
                    <textarea name="deskripsi"
                            placeholder="Masukkan deskripsi produk di sini...">{{ old('deskripsi', $produk->deskripsi) }}</textarea>
                </div>

            </div>

            <!-- GAMBAR PRODUK -->
            <div class="form-produk-card">

                <h3>Gambar Produk</h3>
                
                @if($gambarProduk->count() > 0)
                    <div style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                        @foreach($gambarProduk as $gambar)
                            <div style="border:1px solid #E2E8F0;
                                        border-radius:8px;
                                        padding:8px;
                                        text-align:center;">

                                <img src="{{ asset('storage/' . $gambar->path_gambar) }}"
                                    alt="Gambar Produk"
                                    style="width:90px; height:90px;
                                            object-fit:contain; display:block;
                                            margin-bottom:8px;">

                                @if($produk->gambar === $gambar->path_gambar)
                                    <span style="display:block; font-size:10px;
                                                color:#008A50; margin-bottom:6px;">
                                        Gambar Utama
                                    </span>
                                @endif

                                <button type="button"
                                        onclick="hapusGambarProduk({{ $gambar->id }})"
                                        style="background:#FEF2F2;
                                            color:#DC2626;
                                            border:1px solid #FECACA;
                                            border-radius:5px;
                                            padding:5px 10px;
                                            font-size:11px;
                                            cursor:pointer;">
                                    Hapus
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <div class="upload-produk-area">
                    <i class="bi bi-image"></i>
                    <p>Klik untuk memilih gambar</p>
                    <small>
                        atau seret dan lepas gambar di sini
                        <br>
                        Format JPG, PNG (maksimal 2 MB)
                    </small>
                </div>

                <input type="file"
                    name="gambar[]"
                    accept=".jpg,.jpeg,.png"
                    multiple
                    style="margin-top:15px; font-size:12px;">

                <p style="font-size:11px; color:#94A3B8; margin-top:12px;">
                    Gambar akan ditampilkan pada halaman detail produk.
                </p>

            </div>

        </div>

        <!-- TOMBOL AKSI -->
        <div class="form-produk-actions">

            <a href="{{ route('admin.produk.index') }}"
            class="btn-batal">
                Batal
            </a>

            <button type="submit" class="btn-simpan">
                Simpan Perubahan
            </button>

        </div>
    </form>
    <form id="form-hapus-gambar"
        method="POST"
        style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const kategori = document.getElementById('kategori_id');
    const material = document.getElementById('material_id');
    const satuan = document.getElementById('satuan_id');
    const ukuran = document.getElementById('ukuran');

    const urlDropdown = @json(route('admin.produk.dropdown', ['kategoriId' => '__ID__']));

    function resetDropdown(select, placeholder) {
        select.innerHTML = '';
        select.add(new Option(placeholder, ''));
        select.disabled = true;
    }

    kategori.addEventListener('change', async function () {

        const kategoriId = this.value;
        const namaKategori = this.options[this.selectedIndex]
            .text.trim().toLowerCase();

        resetDropdown(material, 'Pilih material');
        resetDropdown(satuan, 'Pilih satuan');

        ukuran.value = '';
        ukuran.disabled = true;

        if (!kategoriId) {
            return;
        }

        const menggunakanMaterial = [
            'baut', 'mur', 'ring', 'sekrup'
        ].includes(namaKategori);

        ukuran.disabled = !menggunakanMaterial;

        try {
            const response = await fetch(
                urlDropdown.replace('__ID__', encodeURIComponent(kategoriId)),
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error('Gagal mengambil data dropdown');
            }

            const data = await response.json();

            // Mengisi pilihan material
            if (menggunakanMaterial && data.material.length > 0) {
                data.material.forEach(function (item) {
                    material.add(
                        new Option(item.nama_material, item.id)
                    );
                });

                material.disabled = false;
            }

            // Mengisi pilihan satuan
            if (data.satuan.length > 0) {
                data.satuan.forEach(function (item) {
                    satuan.add(
                        new Option(item.nama_satuan, item.id)
                    );
                });

                satuan.disabled = false;
            }

        } catch (error) {
            console.error(error);
            alert('Data material dan satuan gagal dimuat.');
        }

    });

});
</script>
@endpush

@push('scripts')
<script>
function hapusGambarProduk(id) {
    const konfirmasi = confirm(
        'Apakah Anda yakin ingin menghapus gambar ini?'
    );

    if (!konfirmasi) {
        return;
    }

    const form = document.getElementById('form-hapus-gambar');

    const url = @json(route('admin.produk.gambar.hapus', ['id' => '__ID__']));

    form.action = url.replace('__ID__', id);
    form.submit();
}
</script>
@endpush