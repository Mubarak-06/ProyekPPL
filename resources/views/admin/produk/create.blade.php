@extends('admin.layouts.app')
@section('title', 'Tambah Produk')
@push('styles')
    <style>
        /* Halaman Tambah Produk */
        .tambah-produk-page h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .tambah-produk-page .breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .form-produk-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
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

        .gambar-produk-section {
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .gambar-produk-section h4 {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 12px;
            color: #111827;
        }

        .gambar-preview-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }

        .gambar-preview-list:empty {
            display: none;
        }

        .gambar-preview-item {
            position: relative;
            width: 110px;
            height: 110px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
        }

        .gambar-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .gambar-preview-hapus {
            position: absolute;
            top: -8px;
            right: -8px;
            border: 0;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            background: #DC2626;
            color: white;
            cursor: pointer;
            font-size: 17px;
            line-height: 3px;
            padding: 0;
        }

        .gambar-produk-section input[type="file"] {
            font-size: 12px;
            max-width: 100%;
        }

        .gambar-produk-info {
            font-size: 11px;
            color: #94A3B8;
            margin: 10px 0 0;
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
        <h1>Tambah Produk</h1>

        <div class="breadcrumb">
            <a href="{{ url('/admin/dashboard') }}" style="color: inherit; text-decoration: none;">
                Dashboard
            </a>
            &nbsp; › &nbsp;
            <a href="{{ url('/admin/produk') }}" style="color: inherit; text-decoration: none;">
                Produk
            </a>
            &nbsp; › &nbsp; Tambah Produk
        </div>
        <form action="{{ route('admin.produk.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-produk-layout">

                <!-- INFORMASI PRODUK -->
                <div class="form-produk-card">

                    <h3>Informasi Produk</h3>

                    <!-- Nama Produk -->
                    <div class="form-group">
                        <label>Nama Produk <span style="color:red">*</span></label>
                        <input type="text" name="nama_produk" placeholder="Masukkan nama produk">
                    </div>

                    <!-- Kategori dan Harga -->
                    <div class="form-row">

                        <div class="form-group">
                            <label>Kategori <span style="color:red">*</span></label>
                            <select name="kategori_id" id="kategori_id">
                                <option value="">Pilih kategori</option>

                                @foreach($kategori as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->nama_kategori }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Harga <span style="color:red">*</span></label>
                            <input type="number" name="harga" min="0" placeholder="Masukkan harga">
                        </div>

                    </div>

                    <!-- Ukuran dan Material -->
                    <div class="form-material-box">

                        <div class="form-row">

                            <div class="form-group">
                                <label>Ukuran</label>
                                <input type="text" name="ukuran" id="ukuran" placeholder="Masukkan ukuran" disabled>
                            </div>

                            <div class="form-group">
                                <label>Material</label>
                                <select name="material_id" id="material_id" disabled>
                                    <option value="">Pilih material</option>
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
                            <input type="number" name="stok" min="0" placeholder="Masukkan stok">
                        </div>

                        <div class="form-group">
                            <label>Satuan <span style="color:red">*</span></label>
                            <select name="satuan_id" id="satuan_id" disabled>
                                <option value="">Pilih satuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Berat (gram) <span style="color:red">*</span></label>
                            <input type="number" name="berat" min="0.01" step="0.01" placeholder="Masukkan berat">
                        </div>

                    </div>

                    <!-- GAMBAR PRODUK -->
                    <div class="gambar-produk-section">
                        <h4>Gambar Produk</h4>
                        <div id="gambar-preview-list" class="gambar-preview-list" aria-live="polite"></div>
                        <input type="file" id="gambar-produk-input" name="gambar[]"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple>
                        <p class="gambar-produk-info">
                            Format: JPG, PNG (maksimal 2 MB per gambar). Anda dapat memilih lebih dari satu gambar.
                        </p>
                        <p id="gambar-produk-error" style="color:#B91C1C;font-size:11px;margin:8px 0 0;" role="alert"
                            hidden></p>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label>Deskripsi Produk <span style="color:red">*</span></label>
                        <textarea name="deskripsi" placeholder="Masukkan deskripsi produk di sini..."></textarea>
                    </div>

                </div>

            </div>

            <!-- TOMBOL AKSI -->
            <div class="form-produk-actions">

                <a href="{{ route('admin.produk.index') }}" class="btn-batal">
                    Batal
                </a>

                <button type="submit" class="btn-simpan">
                    Simpan Produk
                </button>

            </div>
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

            const gambarInput = document.getElementById('gambar-produk-input');
            const gambarPreview = document.getElementById('gambar-preview-list');
            const gambarError = document.getElementById('gambar-produk-error');
            const selectedImages = [];
            const maxImageSize = 2 * 1024 * 1024;

            function syncImageInput() {
                const transfer = new DataTransfer();
                selectedImages.forEach(file => transfer.items.add(file));
                gambarInput.files = transfer.files;
            }

            function renderImagePreviews() {
                gambarPreview.replaceChildren();
                selectedImages.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'gambar-preview-item';

                    const img = document.createElement('img');
                    img.alt = 'Preview ' + file.name;
                    const imageUrl = URL.createObjectURL(file);
                    img.src = imageUrl;
                    img.onload = () => URL.revokeObjectURL(imageUrl);

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'gambar-preview-hapus';
                    remove.textContent = '×';
                    remove.setAttribute('aria-label', 'Hapus gambar ' + file.name);
                    remove.addEventListener('click', () => {
                        selectedImages.splice(index, 1);
                        syncImageInput();
                        renderImagePreviews();
                    });

                    item.append(img, remove);
                    gambarPreview.append(item);
                });
            }

            gambarInput.addEventListener('change', function () {
                const incoming = Array.from(this.files);
                const invalid = [];
                let melebihiBatas = false;

                incoming.forEach(file => {
                    const validType = ['image/jpeg', 'image/png'].includes(file.type);

                    if (!validType || file.size > maxImageSize) {
                        invalid.push(file.name);
                    } else if (selectedImages.length >= 4) {
                        melebihiBatas = true;
                    } else {
                        selectedImages.push(file);
                    }
                });

                const pesanError = [];

                if (invalid.length > 0) {
                    pesanError.push(
                        'File berikut harus berupa JPG/PNG dan maksimal 2 MB: '
                        + invalid.join(', ')
                    );
                }

                if (melebihiBatas) {
                    pesanError.push('Maksimal hanya 4 gambar per produk.');
                }

                gambarError.hidden = pesanError.length === 0;
                gambarError.textContent = pesanError.join(' ');

                syncImageInput();
                renderImagePreviews();
            });


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