@extends('admin.layouts.app')

@section('title', 'Pembayaran')

@push('styles')
    <style>
        .pb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .pb-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .pb-breadcrumb {
            font-size: 11px;
            color: #64748B;
            margin: 5px 0 20px;
        }

        .pb-breadcrumb a {
            color: #64748B;
            text-decoration: none;
        }

        .pb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        .pb-btn:hover {
            background: #007344;
        }

        .pb-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .pb-filter {
            display: flex;
            align-items: end;
            gap: 12px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .pb-field {
            flex: 1;
            min-width: 170px;
        }

        .pb-field.search {
            flex: 1.5;
        }

        .pb-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .pb-input {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            background: #FFFFFF;
            color: #64748B;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            outline: none;
        }

        .pb-input:focus {
            border-color: #008A50;
        }

        .pb-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .pb-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .pb-table th {
            padding: 17px 12px;
            background: #FFFFFF;
            color: #111827;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }

        .pb-table td {
            padding: 15px 12px;
            color: #334155;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }

        .pb-table tbody tr:last-child td {
            border-bottom: none;
        }

        .pb-table tbody tr:hover {
            background: #F8FAFC;
        }

        .pb-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
        }

        .pb-status.success {
            background: #DCFCE7;
            color: #15803D;
        }

        .pb-status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .pb-status.failed {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pb-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 11px;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            color: #008A50;
            background: #FFFFFF;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
        }

        .pb-action:hover {
            background: #F0FDF4;
        }

        .pb-table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            font-size: 11px;
            color: #64748B;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .pb-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .pb-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .pb-field {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')

    <div class="pb-header">
        <div>
            <h1 class="pb-title">Pembayaran</h1>

            <div class="pb-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                &nbsp; › &nbsp; Pembayaran
            </div>
        </div>

        <a href="{{ route('admin.pembayaran.metode') }}" class="pb-btn">
            <i class="bi bi-gear"></i>
            Kelola Metode Pembayaran
        </a>
    </div>

    {{-- Filter Pembayaran --}}
    <div class="pb-card">
        <form class="pb-filter" id="formFilterPembayaran">

            <div class="pb-field">
                <label class="pb-label">Tanggal Pembayaran</label>

                <input type="date" class="pb-input" id="filterTanggal" aria-label="Tanggal Pembayaran">
            </div>

            <div class="pb-field">
                <label class="pb-label">Status Pembayaran</label>

                <select class="pb-input" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="Berhasil">Berhasil</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Gagal">Gagal</option>
                </select>
            </div>

            <div class="pb-field search">
                <label class="pb-label">Pencarian</label>

                <input type="text" class="pb-input" id="filterCari" placeholder="Cari kode pesanan atau nama pelanggan...">
            </div>

            <button type="submit" class="pb-btn" style="height:40px;">
                <i class="bi bi-search"></i>
                Tampilkan
            </button>

        </form>
    </div>

    {{-- Tabel Pembayaran --}}
    <div class="pb-card">

        <div class="pb-table-wrap">
            <table class="pb-table" id="tabelPembayaran">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Pesanan</th>
                        <th>Nama Pelanggan</th>
                        <th>Tanggal Pembayaran</th>
                        <th>Metode Pembayaran</th>
                        <th>Jumlah Pembayaran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($pembayaran as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    {{-- Kode Pesanan --}}
                                    <td>
                                        {{ $item->kode_pesanan }}
                                    </td>

                                    {{-- Nama Pelanggan --}}
                                    <td>
                                        {{ $item->nama_pelanggan }}
                                    </td>

                                    {{-- Tanggal Pembayaran --}}
                                    <td data-tanggal="{{ $item->waktu_pembayaran
                        ? \Carbon\Carbon::parse($item->waktu_pembayaran)->format('Y-m-d')
                        : ($item->created_at
                            ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d')
                            : '') }}">

                                        {{ $item->waktu_pembayaran
                        ? \Carbon\Carbon::parse($item->waktu_pembayaran)->format('d/m/Y H:i')
                        : '-' }}
                                    </td>

                                    {{-- Metode Pembayaran --}}
                                    <td>
                                        {{ $item->metode_pembayaran }}

                                        @if($item->nama_penyedia)
                                            <div style="font-size:10px; color:#64748B; margin-top:3px;">
                                                {{ $item->nama_penyedia }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Jumlah Pembayaran --}}
                                    <td>
                                        Rp {{ number_format($item->jumlah_pembayaran, 0, ',', '.') }}
                                    </td>

                                    {{-- Status Pembayaran --}}
                                    <td data-status="{{ $item->status_pembayaran }}">
                                        @if($item->status_pembayaran === 'Berhasil')
                                            <span class="pb-status success">Berhasil</span>
                                        @elseif($item->status_pembayaran === 'Menunggu')
                                            <span class="pb-status pending">Menunggu</span>
                                        @elseif($item->status_pembayaran === 'Gagal')
                                            <span class="pb-status failed">Gagal</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td>
                                        <a href="{{ route('admin.pembayaran.show', $item->id) }}" class="pb-action">
                                            <i class="bi bi-eye"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                    @empty
                        <tr id="barisKosong">
                            <td colspan="8" style="text-align:center; padding:30px; color:#64748B;">
                                Belum ada transaksi pembayaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="pb-table-footer">
            <span id="informasiTabel">Menampilkan {{ $pembayaran->count() }} transaksi pembayaran.</span>
        </div>

    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const formFilter = document.getElementById('formFilterPembayaran');
    const filterTanggal = document.getElementById('filterTanggal');
    const filterStatus = document.getElementById('filterStatus');
    const filterCari = document.getElementById('filterCari');

    const tabelPembayaran = document.getElementById('tabelPembayaran');
    const informasiTabel = document.getElementById('informasiTabel');

    const barisPembayaran = Array.from(
        tabelPembayaran.querySelectorAll('tbody tr')
    ).filter(baris => baris.id !== 'barisKosong');

    // Menjalankan filter pembayaran
    function filterPembayaran() {

        const tanggal = filterTanggal.value;
        const status = filterStatus.value;
        const pencarian = filterCari.value.toLowerCase().trim();

        let jumlahTampil = 0;

        barisPembayaran.forEach(function (baris) {

            const kolom = baris.querySelectorAll('td');

            const kodePesanan = kolom[1].textContent.toLowerCase();
            const namaPelanggan = kolom[2].textContent.toLowerCase();

            const tanggalPembayaran = kolom[3].dataset.tanggal;
            const statusPembayaran = kolom[6].dataset.status;

            const cocokTanggal =
                !tanggal || tanggalPembayaran === tanggal;

            const cocokStatus =
                !status || statusPembayaran === status;

            const cocokPencarian =
                !pencarian ||
                kodePesanan.includes(pencarian) ||
                namaPelanggan.includes(pencarian);

            const tampil =
                cocokTanggal && cocokStatus && cocokPencarian;

            baris.style.display = tampil ? '' : 'none';

            if (tampil) {
                jumlahTampil++;
            }
        });

        informasiTabel.textContent =
            'Menampilkan ' + jumlahTampil + ' dari ' +
            barisPembayaran.length + ' transaksi pembayaran.';
    }

    // Tombol Tampilkan
    formFilter.addEventListener('submit', function (event) {
        event.preventDefault();
        filterPembayaran();
    });

    // Menjalankan filter ketika pilihan berubah
    filterTanggal.addEventListener('change', filterPembayaran);
    filterStatus.addEventListener('change', filterPembayaran);

    // Pencarian langsung saat mengetik
    filterCari.addEventListener('input', filterPembayaran);

    // Menampilkan jumlah data awal
    filterPembayaran();

});
</script>
@endpush