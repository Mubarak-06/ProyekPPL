@extends('kurir.layout')
@section('title', 'Detail Tugas Pengiriman')
@section('content')
    <h1>{{ $pengiriman->pesanan->kode_pesanan }}</h1>
    <section class="kirim-panel">
        <h2>Tujuan dan Barang</h2>
        @include('kurir.tujuan')
        <div class="kurir-aksi">
            @if($nomor = preg_replace('/[^0-9+]/', '', $pengiriman->nomor_telepon))
                <a class="kirim-tombol" href="tel:{{ $nomor }}">Hubungi Pelanggan</a>
            @endif
            @if($pengiriman->alamat_pengiriman)
                <a class="kirim-tombol" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($pengiriman->alamat_pengiriman) }}">Buka Petunjuk Arah</a>
            @endif
        </div>
    </section>
    <section class="kirim-panel">
        <h2>Perkembangan Pengiriman</h2>
        <x-pengiriman.timeline :pengiriman="$pengiriman" />
        <x-pengiriman.kendala :pengiriman="$pengiriman" />
        @if($pengiriman->tahap() === \App\Models\Pengiriman::SIAP)
            @include('kurir.mulai')
        @elseif($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN && ! $pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))
            <p>Perbarui status hanya saat kendaraan berhenti dengan aman.</p>
            <form class="kirim-form" method="POST" enctype="multipart/form-data" action="{{ route('kurir.sampai', $pengiriman) }}"
                data-judul="Konfirmasi Barang Sampai" data-konfirmasi-pengiriman="Apakah Anda yakin barang telah diserahkan kepada penerima?" data-setuju="Ya, Barang Sampai">
                @csrf
                <p>Waktu penyerahan dicatat otomatis oleh sistem.</p>
                <label>Nama penerima <input name="penerima_aktual" value="{{ old('penerima_aktual') }}" maxlength="100" required></label>
                <label>Foto bukti penyerahan <input id="foto-penyerahan" type="file" name="foto" accept="image/*" required></label>
                <p>Ambil atau pilih foto barang dengan persetujuan penerima. Gunakan JPEG, PNG, atau WebP hingga 5 MB. Hindari foto wajah dan dokumen pribadi.</p>
                <img id="preview-penyerahan" class="kirim-foto" alt="Pratinjau foto bukti" hidden>
                <label>Catatan penyerahan <textarea name="catatan_penyerahan" rows="3" maxlength="1000">{{ old('catatan_penyerahan') }}</textarea></label>
                <button class="kirim-tombol" type="submit">Konfirmasi Barang Sampai</button>
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
@endsection
