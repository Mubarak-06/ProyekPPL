
@extends('admin.layouts.app')

@section('title', 'Kelola Data Master')

@push('styles')
<style>
    .master-title {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
    }

    .master-breadcrumb {
        font-size: 11px;
        color: #64748B;
        margin: 5px 0 20px;
    }

    .master-breadcrumb a {
        color: inherit;
        text-decoration: none;
    }

    .master-breadcrumb a:hover {
        color: #008A50;
    }

    .master-tabs {
        display: flex;
        margin-bottom: 20px;
    }

    .master-tab {
        padding: 10px 18px;
        border: 1px solid #E2E8F0;
        background: white;
        color: #64748B;
        font-size: 11px;
        text-decoration: none;
    }

    .master-tab:first-child {
        border-radius: 6px 0 0 6px;
    }

    .master-tab:last-child {
        border-radius: 0 6px 6px 0;
    }

    .master-tab.active {
        background: #008A50;
        color: white;
        border-color: #008A50;
    }

    .master-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 20px;
    }

    .master-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .master-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }

    .master-card-header p {
        margin: 3px 0 0;
        font-size: 11px;
        color: #94A3B8;
    }

    .btn-tambah-master {
        background: #008A50;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 15px;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        cursor: pointer;
    }

    .master-subtabs {
        display: flex;
        gap: 6px;
        margin: 20px 0;
    }

    .master-subtab {
        padding: 8px 14px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        color: #64748B;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        cursor: pointer;
    }

    .master-subtab.active {
        background: #008A50;
        color: white;
        border-color: #008A50;
    }

    .master-table-wrapper {
        overflow-x: auto;
    }

    .master-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .master-table th,
    .master-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #E2E8F0;
    }

    .master-table th {
        font-weight: 600;
        color: #111827;
    }

    .btn-edit-master,
    .btn-hapus-master {
        border: 1px solid;
        border-radius: 5px;
        padding: 6px 8px;
        cursor: pointer;
        background: white;
    }

    .btn-edit-master {
        color: #008A50;
        border-color: #A7F3D0;
    }

    .btn-hapus-master {
        color: #F43F5E;
        border-color: #FECDD3;
    }

    .master-aksi {
        display: flex;
        gap: 6px;
    }

    .master-panel {
        display: none;
    }

    .master-panel.active {
        display: block;
    }

    /* Popup Data Master */
    .master-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 9999;
    }

    .master-modal-overlay.show {
        display: flex;
    }

    .master-modal {
        width: 100%;
        max-width: 440px;
        max-height: 90vh;
        overflow-y: auto;
        background: #FFFFFF;
        border-radius: 10px;
        padding: 24px;
        font-family: 'Poppins', sans-serif;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .master-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
    }

    .master-modal-header h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .master-modal-close {
        border: none;
        background: transparent;
        color: #64748B;
        font-size: 17px;
        cursor: pointer;
    }

    .master-form-group {
        margin-bottom: 20px;
    }

    .master-form-group > label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #111827;
        margin-bottom: 8px;
    }

    .master-form-group input[type="text"] {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        outline: none;
    }

    .master-form-group input[type="text"]:focus {
        border-color: #008A50;
    }

    .master-checkbox-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        padding: 12px;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        max-height: 150px;
        overflow-y: auto;
    }

    .master-checkbox-item {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 11px;
        color: #334155;
        cursor: pointer;
    }

    .master-checkbox-item input {
        accent-color: #008A50;
        cursor: pointer;
    }

    .master-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    .master-btn-batal,
    .master-btn-simpan {
        padding: 10px 15px;
        border-radius: 6px;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
    }

    .master-btn-batal {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        color: #64748B;
    }

    .master-btn-simpan {
        background: #008A50;
        border: 1px solid #008A50;
        color: #FFFFFF;
    }

    .master-alert-success,
    .master-alert-error {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        margin-bottom: 18px;
        border-radius: 6px;
        font-size: 12px;
    }

    .master-alert-success {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #047857;
    }

    .master-alert-error {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #B91C1C;
    }

    .master-aksi {
        align-items: center;
    }

    .master-aksi form {
        margin: 0;
    }
    @media (max-width: 768px) {
        .master-card-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="master-alert-success">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="master-alert-error">
        <i class="bi bi-exclamation-circle-fill"></i>
        {{ $errors->first() }}
    </div>
@endif
<h1 class="master-title">Produk</h1>

<div class="master-breadcrumb">
    <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
    &nbsp; › &nbsp; Produk
</div>

<div class="master-tabs">
    <a href="{{ route('admin.produk.index') }}"
       class="master-tab">
        <i class="bi bi-box"></i>
        Kelola Produk
    </a>

    <a href="{{ route('admin.data-master.index') }}"
       class="master-tab active">
        <i class="bi bi-database"></i>
        Kelola Data Master
    </a>
</div>

<div class="master-card">

    <div class="master-card-header">
        <div>
            <h3>Data Master</h3>
            <p>Kelola kategori, material, dan satuan produk.</p>
        </div>

        <button type="button"
                class="btn-tambah-master"
                id="btnTambahMaster">
            <i class="bi bi-plus-lg"></i>
            Tambah Kategori
        </button>
    </div>

    <div class="master-subtabs">
        <button class="master-subtab active"
                data-target="kategori">
            Kategori
        </button>

        <button class="master-subtab"
                data-target="material">
            Material
        </button>

        <button class="master-subtab"
                data-target="satuan">
            Satuan
        </button>
    </div>

    <div id="panel-kategori" class="master-panel active">
        <div class="master-table-wrapper">
            <table class="master-table" id="tabelKategori">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategori as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_kategori }}</td>
                            <td>
                                <div class="master-aksi">
                                    <button class="btn-edit-master btn-buka-edit-kategori"
                                            type="button"
                                            title="Edit Kategori"
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ $item->nama_kategori }}"
                                            data-material='@json(($kategoriMaterial[$item->id] ?? collect())->pluck("material_id")->values())'
                                            data-satuan='@json(($kategoriSatuan[$item->id] ?? collect())->pluck("satuan_id")->values())'>
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form action="{{ route('admin.data-master.kategori.destroy', $item->id) }}"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori {{ $item->nama_kategori }}?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn-hapus-master"
                                                title="Hapus Kategori">
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

    <div id="panel-material" class="master-panel">
        <div class="master-table-wrapper">
            <table class="master-table" id="tabelMaterial">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Material</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($material as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_material }}</td>
                            <td>                               
                                <form action="{{ route('admin.data-master.material.destroy', $item->id) }}"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus material {{ $item->nama_material }}?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn-hapus-master"
                                            title="Hapus Material">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="panel-satuan" class="master-panel">
        <div class="master-table-wrapper">
            <table class="master-table" id="tabelSatuan">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Satuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satuan as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_satuan }}</td>
                            <td>
                                <form action="{{ route('admin.data-master.satuan.destroy', $item->id) }}"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus satuan {{ $item->nama_satuan }}?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn-hapus-master"
                                            title="Hapus Satuan">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Popup Tambah Kategori -->
<div class="master-modal-overlay" id="modalTambahKategori">

    <div class="master-modal">

        <div class="master-modal-header">
            <h3>Tambah Kategori</h3>

            <button type="button"
                    class="master-modal-close"
                    id="btnTutupKategori">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="formTambahKategori"
                action="{{ route('admin.data-master.kategori.store') }}"
                method="POST">

            @csrf

            <div class="master-form-group">
                <label for="namaKategori">Nama Kategori</label>

                <input type="text"
                       id="namaKategori"
                       name="nama_kategori"
                       placeholder="Masukkan nama kategori"
                       maxlength="100"
                       required>
            </div>

            <div class="master-form-group">
                <label>Material yang Tersedia</label>

                <div class="master-checkbox-list">
                    @forelse($material as $item)
                        <label class="master-checkbox-item">
                            <input type="checkbox"
                                   name="material_id[]"
                                   value="{{ $item->id }}">

                            <span>{{ $item->nama_material }}</span>
                        </label>
                    @empty
                        <p>Belum ada material.</p>
                    @endforelse
                </div>
            </div>

            <div class="master-form-group">
                <label>Satuan yang Tersedia</label>

                <div class="master-checkbox-list">
                    @forelse($satuan as $item)
                        <label class="master-checkbox-item">
                            <input type="checkbox"
                                   name="satuan_id[]"
                                   value="{{ $item->id }}">

                            <span>{{ $item->nama_satuan }}</span>
                        </label>
                    @empty
                        <p>Belum ada satuan.</p>
                    @endforelse
                </div>
            </div>

            <div class="master-modal-footer">
                <button type="button"
                        class="master-btn-batal"
                        id="btnBatalKategori">
                    Batal
                </button>

                <button type="submit"
                        class="master-btn-simpan">
                    Simpan Kategori
                </button>
            </div>

        </form>

    </div>

</div>

<!-- Popup Edit Kategori -->
<div class="master-modal-overlay" id="modalEditKategori">

    <div class="master-modal">

        <div class="master-modal-header">
            <h3>Edit Kategori</h3>

            <button type="button"
                    class="master-modal-close"
                    id="btnTutupEditKategori">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="formEditKategori" method="POST">
            @csrf
            @method('PUT')

            <div class="master-form-group">
                <label for="editNamaKategori">Nama Kategori</label>

                <input type="text"
                       id="editNamaKategori"
                       name="nama_kategori"
                       maxlength="100"
                       required>
            </div>

            <div class="master-form-group">
                <label>Material yang Tersedia</label>

                <div class="master-checkbox-list">
                    @foreach($material as $item)
                        <label class="master-checkbox-item">
                            <input type="checkbox"
                                   name="material_id[]"
                                   value="{{ $item->id }}"
                                   class="edit-material">

                            <span>{{ $item->nama_material }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="master-form-group">
                <label>Satuan yang Tersedia</label>

                <div class="master-checkbox-list">
                    @foreach($satuan as $item)
                        <label class="master-checkbox-item">
                            <input type="checkbox"
                                   name="satuan_id[]"
                                   value="{{ $item->id }}"
                                   class="edit-satuan">

                            <span>{{ $item->nama_satuan }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="master-modal-footer">
                <button type="button"
                        class="master-btn-batal"
                        id="btnBatalEditKategori">
                    Batal
                </button>

                <button type="submit"
                        class="master-btn-simpan">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

</div>

<!-- Popup Tambah Material -->
<div class="master-modal-overlay" id="modalTambahMaterial">

    <div class="master-modal">

        <div class="master-modal-header">
            <h3>Tambah Material</h3>

            <button type="button"
                    class="master-modal-close"
                    id="btnTutupMaterial">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <<form id="formTambahMaterial"
                action="{{ route('admin.data-master.material.store') }}"
                method="POST">
            @csrf

            <div class="master-form-group">
                <label for="namaMaterial">Nama Material</label>

                <input type="text"
                       id="namaMaterial"
                       name="nama_material"
                       placeholder="Masukkan nama material"
                       maxlength="100"
                       required>
            </div>

            <div class="master-modal-footer">

                <button type="button"
                        class="master-btn-batal"
                        id="btnBatalMaterial">
                    Batal
                </button>

                <button type="submit"
                        class="master-btn-simpan">
                    Simpan Material
                </button>

            </div>

        </form>

    </div>

</div>

<!-- Popup Tambah Satuan -->
<div class="master-modal-overlay" id="modalTambahSatuan">

    <div class="master-modal">

        <div class="master-modal-header">
            <h3>Tambah Satuan</h3>

            <button type="button"
                    class="master-modal-close"
                    id="btnTutupSatuan">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="formTambahSatuan"
                action="{{ route('admin.data-master.satuan.store') }}"
                method="POST">
            @csrf

            <div class="master-form-group">
                <label for="namaSatuan">Nama Satuan</label>

                <input type="text"
                       id="namaSatuan"
                       name="nama_satuan"
                       placeholder="Masukkan nama satuan"
                       maxlength="100"
                       required>
            </div>

            <div class="master-modal-footer">

                <button type="button"
                        class="master-btn-batal"
                        id="btnBatalSatuan">
                    Batal
                </button>

                <button type="submit"
                        class="master-btn-simpan">
                    Simpan Satuan
                </button>

            </div>

        </form>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.master-subtab');
    const panels = document.querySelectorAll('.master-panel');
    const tambahButton = document.getElementById('btnTambahMaster');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = this.dataset.target;

            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');

            document.getElementById('panel-' + target)
                .classList.add('active');

            const nama = target.charAt(0).toUpperCase()
                       + target.slice(1);

            tambahButton.innerHTML =
                '<i class="bi bi-plus-lg"></i> Tambah ' + nama;
        });
    });
    // Popup Tambah Kategori
    const modalKategori = document.getElementById('modalTambahKategori');
    const btnTutupKategori = document.getElementById('btnTutupKategori');
    const btnBatalKategori = document.getElementById('btnBatalKategori');
    const formTambahKategori = document.getElementById('formTambahKategori');

    tambahButton.addEventListener('click', function () {
        const tabAktif = document.querySelector('.master-subtab.active');

        if (tabAktif.dataset.target === 'kategori') {
            modalKategori.classList.add('show');
            document.getElementById('namaKategori').focus();
        }

        if (tabAktif.dataset.target === 'material') {
            modalMaterial.classList.add('show');
            document.getElementById('namaMaterial').focus();
        }

        if (tabAktif.dataset.target === 'satuan') {
            modalSatuan.classList.add('show');
            document.getElementById('namaSatuan').focus();
        }
    });

    function tutupModalKategori() {
        modalKategori.classList.remove('show');
        formTambahKategori.reset();
    }

    btnTutupKategori.addEventListener('click', tutupModalKategori);
    btnBatalKategori.addEventListener('click', tutupModalKategori);

    modalKategori.addEventListener('click', function (event) {
        if (event.target === modalKategori) {
            tutupModalKategori();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupModalKategori();
        }
    });
    
    // Popup Edit Kategori
    const modalEditKategori = document.getElementById('modalEditKategori');
    const formEditKategori = document.getElementById('formEditKategori');
    const editNamaKategori = document.getElementById('editNamaKategori');

    const urlUpdateKategori = @json(
        route('admin.data-master.kategori.update', ['id' => '__ID__'])
    );

    document.querySelectorAll('.btn-buka-edit-kategori')
        .forEach(function (button) {

            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const materialDipilih = JSON.parse(this.dataset.material);
                const satuanDipilih = JSON.parse(this.dataset.satuan);

                formEditKategori.action =
                    urlUpdateKategori.replace('__ID__', id);

                editNamaKategori.value = this.dataset.nama;

                document.querySelectorAll('.edit-material')
                    .forEach(function (checkbox) {
                        checkbox.checked = materialDipilih
                            .map(String)
                            .includes(checkbox.value);
                    });

                document.querySelectorAll('.edit-satuan')
                    .forEach(function (checkbox) {
                        checkbox.checked = satuanDipilih
                            .map(String)
                            .includes(checkbox.value);
                    });

                modalEditKategori.classList.add('show');
                editNamaKategori.focus();
            });
        });

    function tutupEditKategori() {
        modalEditKategori.classList.remove('show');
        formEditKategori.reset();
    }

    document.getElementById('btnTutupEditKategori')
        .addEventListener('click', tutupEditKategori);

    document.getElementById('btnBatalEditKategori')
        .addEventListener('click', tutupEditKategori);

    modalEditKategori.addEventListener('click', function (event) {
        if (event.target === modalEditKategori) {
            tutupEditKategori();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupEditKategori();
        }
    });
    
    // Popup Tambah Material
    const modalMaterial = document.getElementById('modalTambahMaterial');
    const formTambahMaterial = document.getElementById('formTambahMaterial');

    function tutupModalMaterial() {
        modalMaterial.classList.remove('show');
        formTambahMaterial.reset();
    }

    document.getElementById('btnTutupMaterial')
        .addEventListener('click', tutupModalMaterial);

    document.getElementById('btnBatalMaterial')
        .addEventListener('click', tutupModalMaterial);

    modalMaterial.addEventListener('click', function (event) {
        if (event.target === modalMaterial) {
            tutupModalMaterial();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupModalMaterial();
        }
    });
    
    // Popup Tambah Satuan
    const modalSatuan = document.getElementById('modalTambahSatuan');
    const formTambahSatuan = document.getElementById('formTambahSatuan');

    function tutupModalSatuan() {
        modalSatuan.classList.remove('show');
        formTambahSatuan.reset();
    }

    document.getElementById('btnTutupSatuan')
        .addEventListener('click', tutupModalSatuan);

    document.getElementById('btnBatalSatuan')
        .addEventListener('click', tutupModalSatuan);

    modalSatuan.addEventListener('click', function (event) {
        if (event.target === modalSatuan) {
            tutupModalSatuan();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupModalSatuan();
        }
    });
});
</script>
@endpush
