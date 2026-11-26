@php($pengiriman = $pesanan->pengiriman)
<section class="informasi-pengiriman operasional-kirim" id="lacak-pengiriman">
    <x-pengiriman.gaya />
    <h2>Lacak Pengiriman</h2>
    <p><strong>{{ $pengiriman->nama_penerima }}</strong> · {{ $pengiriman->nomor_telepon }}</p>
    <p>{{ $pengiriman->alamat_pengiriman }}</p>
    <x-pengiriman.timeline :pengiriman="$pengiriman" />
    @if($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN)
        @unless($pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))
            <p>Kurir toko sedang mengantarkan pesanan Anda.</p>
        @endunless
    @endif
    @if($pengiriman->tahap_kurir)
        <x-pengiriman.kendala :pengiriman="$pengiriman" />
    @endif
    @php($barangSampai = $pengiriman->tahap() === \App\Models\Pengiriman::SAMPAI)
    @if($barangSampai || $bisaTerimaHistoris)
        @if($barangSampai)
            <p><strong>Barang Telah Sampai</strong></p>
        @else
            <p>Pesanan lama ini tercatat dikirim. Konfirmasi penerimaan hanya jika barang sudah Anda terima dan periksa.</p>
        @endif
        @if($bisaTerimaHistoris || ! $pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))
            <form method="POST" action="{{ route('pelanggan.pengiriman.terima', $pengiriman) }}" data-judul="Konfirmasi Penerimaan"
                data-konfirmasi-pengiriman="Pastikan jumlah dan kondisi barang telah sesuai sebelum mengonfirmasi penerimaan pesanan." data-setuju="Ya, Pesanan Diterima">
                @csrf
                <button class="kirim-tombol" type="submit">Pesanan Diterima</button>
            </form>
            @if($barangSampai)
                <x-pengiriman.form-kendala :action="route('pelanggan.pengiriman.kendala', $pengiriman)" />
            @endif
        @endif
        <x-pengiriman.konfirmasi />
    @endif
</section>
