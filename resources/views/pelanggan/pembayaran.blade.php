@extends('pelanggan.layouts.app')
@section('title', 'Pembayaran')
@section('main-class', 'halaman-pembayaran')
@section('content')
    <nav aria-label="Breadcrumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a> › <a href="{{ route('pelanggan.pesanan.show', $pesanan->id) }}">{{ $pesanan->kode_pesanan }}</a> › Pembayaran</nav>
    <div class="judul-halaman"><h1>Pembayaran</h1><p>Lakukan pembayaran sesuai petunjuk berikut, lalu unggah bukti pembayaran untuk memproses pesanan Anda.</p></div>
    <section class="panel">
        <h2><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Instruksi Pembayaran</h2>
        <p>Status pembayaran: <strong>{{ $pesanan->pembayaran?->status_pembayaran ?? 'Belum tersedia' }}</strong></p>
        @if($pesanan->status_pesanan === 'Ditolak')
            <p role="alert">Pesanan ini telah ditolak. Jangan melakukan pembayaran untuk pesanan ini.</p>
        @elseif($pesanan->pembayaran?->status_pembayaran === 'Berhasil')
            <p class="pesan pesan-sukses">Pembayaran sudah dikonfirmasi.</p>
        @elseif($metode)
            <div class="instruksi-bayar">
                <h3>{{ $pesanan->pembayaran->metode_pembayaran }} — {{ $metode->nama_penyedia }}</h3>
                <ol class="langkah-bayar">
                    <li>
                        {{ $metode->jenis_metode === 'qris' ? 'Pindai kode QRIS berikut:' : 'Lakukan pembayaran ke rekening atau akun berikut:' }}
                        <div class="rekening-bayar">
                            <dl class="data-profil">
                                <dt>Penyedia</dt><dd>{{ $metode->nama_penyedia }}</dd>
                                @if($metode->nomor_rekening)<dt>Nomor rekening/akun</dt><dd>{{ $metode->nomor_rekening }} <button class="tombol-outline" type="button" data-salin="{{ $metode->nomor_rekening }}">Salin</button></dd>@endif
                                @if($metode->nama_pemilik)<dt>Nama pemilik</dt><dd>{{ $metode->nama_pemilik }}</dd>@endif
                                <dt>Total Pembayaran</dt><dd><strong>Rp {{ number_format($pesanan->total_pembayaran, 0, ',', '.') }}</strong></dd>
                            </dl>
                            @if($metode->jenis_metode === 'qris')
                                @include('pelanggan.partials.gambar', ['path' => $metode->gambar_qris, 'nama' => 'QRIS '.$metode->nama_penyedia])
                            @endif
                        </div>
                    </li>
                    <li>Pastikan nominal pembayaran sesuai total yang tertera.</li>
                    <li>Setelah membayar, unggah bukti pembayaran di bawah ini.</li>
                </ol>
            </div>
        @else
            <p>Informasi metode pembayaran tidak tersedia. Periksa status pesanan Anda.</p>
        @endif
        @if($pesanan->pembayaran?->bukti_pembayaran)
            <h3>Bukti Pembayaran</h3>
            <div class="bukti-pembayaran"><img src="{{ route('pelanggan.pesanan.bukti.show', $pesanan->id) }}" alt="Bukti pembayaran" loading="lazy"></div>
            <p>Bukti sudah diterima. Status pembayaran mengikuti hasil pemeriksaan admin.</p>
        @elseif($pesanan->pembayaran?->status_pembayaran === 'Menunggu' && in_array($pesanan->status_pesanan, ['Menunggu', 'Diproses']))
            <form action="{{ route('pelanggan.pesanan.bukti', $pesanan->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="unggah-bukti" data-dropzone>
                    <i class="bi bi-image" aria-hidden="true"></i>
                    <label for="bukti">Pilih atau seret file bukti pembayaran di sini</label>
                    <small>Format JPG, JPEG, PNG (maks. 2 MB)</small>
                    <input id="bukti" name="bukti" type="file" accept="image/jpeg,image/png" data-bukti required>
                    <p data-nama-file role="status">Belum ada file dipilih</p>
                </div>
                <button type="submit">Kirim Bukti Pembayaran</button>
            </form>
        @endif
        <p class="aksi-form"><a class="tombol tombol-outline" href="{{ route('pelanggan.pesanan.show', $pesanan->id) }}">Kembali ke Detail Pesanan</a></p>
    </section>
@endsection
