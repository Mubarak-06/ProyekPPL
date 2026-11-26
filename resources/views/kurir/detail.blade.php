@extends('kurir.layout')

@section('title', 'Detail Tugas Pengiriman')

@section('content')

    <a href="{{ route('kurir.tugas') }}" class="kurir-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Kembali ke Tugas Pengiriman
    </a>

    <div class="kurir-page-heading">
        <h1>Detail Pengiriman</h1>
        <p>{{ $pengiriman->pesanan->kode_pesanan }}</p>
    </div>

    <div class="kurir-detail-grid">

        <section class="kirim-panel">
            <h2 class="kurir-panel-title">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                Tujuan dan Barang
            </h2>

            @include('kurir.tujuan')

            <div class="kurir-aksi">
                @if($nomor = preg_replace('/[^0-9+]/', '', $pengiriman->nomor_telepon))
                    <a class="kirim-tombol kirim-tombol-outline"
                        href="tel:{{ $nomor }}">
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        Hubungi Pelanggan
                    </a>
                @endif

                @if($pengiriman->alamat_pengiriman)
                    <a class="kirim-tombol kirim-tombol-outline"
                        target="_blank"
                        rel="noopener noreferrer"
                        href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($pengiriman->alamat_pengiriman) }}">
                        <i class="bi bi-map" aria-hidden="true"></i>
                        Buka Petunjuk Arah
                    </a>
                @endif
            </div>
        </section>

        <section class="kirim-panel">
            <h2 class="kurir-panel-title">
                <i class="bi bi-truck" aria-hidden="true"></i>
                Perkembangan Pengiriman
            </h2>

            <x-pengiriman.timeline :pengiriman="$pengiriman" />

            <x-pengiriman.kendala :pengiriman="$pengiriman" />

            @if($pengiriman->tahap() === \App\Models\Pengiriman::SIAP)

                <div class="kurir-aksi">
                    @include('kurir.mulai')
                </div>

            @elseif($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN
                && ! $pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))

                <div class="kurir-info-box warning">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <span>
                        Perbarui status hanya saat kendaraan berhenti dengan aman.
                    </span>
                </div>

                <form class="kirim-form"
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('kurir.sampai', $pengiriman) }}"
                    data-judul="Konfirmasi Barang Sampai"
                    data-konfirmasi-pengiriman="Apakah Anda yakin barang telah diserahkan kepada penerima?"
                    data-setuju="Ya, Barang Sampai">

                    @csrf

                    <h3 class="kurir-panel-title">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        Bukti Penyerahan Barang
                    </h3>

                    <p class="kurir-form-help">
                        Waktu penyerahan dicatat otomatis oleh sistem.
                    </p>

                    <label>
                        Nama Penerima
                        <input name="penerima_aktual"
                            value="{{ old('penerima_aktual') }}"
                            placeholder="Masukkan nama penerima"
                            maxlength="100"
                            required>
                    </label>

                    <label>
                        Foto Bukti Penyerahan
                        <input id="foto-penyerahan"
                            type="file"
                            name="foto"
                            accept="image/*"
                            required>
                    </label>

                    <p class="kurir-form-help">
                        Ambil atau pilih foto barang dengan persetujuan penerima.
                        Gunakan JPEG, PNG, atau WebP hingga 5 MB.
                        Hindari foto wajah dan dokumen pribadi.
                    </p>

                    <img id="preview-penyerahan"
                        class="kirim-foto"
                        alt="Pratinjau foto bukti"
                        hidden>

                    <label>
                        Catatan Penyerahan
                        <textarea name="catatan_penyerahan"
                            rows="3"
                            maxlength="1000"
                            placeholder="Tambahkan catatan jika diperlukan">{{ old('catatan_penyerahan') }}</textarea>
                    </label>

                    <button class="kirim-tombol" type="submit">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        Konfirmasi Barang Sampai
                    </button>
                </form>

                <script>
                    (() => {
                        const input = document.getElementById('foto-penyerahan');
                        const preview = document.getElementById('preview-penyerahan');
                        let url;

                        input.addEventListener('change', () => {
                            if (url) URL.revokeObjectURL(url);

                            const foto = input.files[0];
                            preview.hidden = !foto;

                            if (!foto) {
                                preview.removeAttribute('src');
                                return;
                            }

                            url = URL.createObjectURL(foto);
                            preview.src = url;
                        });
                    })();
                </script>

                <x-pengiriman.form-kendala :action="route('kurir.kendala', $pengiriman)" />

            @endif
        </section>

    </div>
@endsection