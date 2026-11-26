@php($pengiriman = $pesanan->pengiriman)

<section class="informasi-pengiriman operasional-kirim" id="lacak-pengiriman">

    <h2>
        <i class="bi bi-truck"></i>
        Lacak Pengiriman
    </h2>

    <div class="kirim-penerima">
        <h3>
            <i class="bi bi-geo-alt"></i>
            Informasi Penerima
        </h3>

        <p>
            <strong>{{ $pengiriman->nama_penerima }}</strong>
            · {{ $pengiriman->nomor_telepon }}
        </p>

        <p>{{ $pengiriman->alamat_pengiriman }}</p>
    </div>

    <x-pengiriman.timeline :pengiriman="$pengiriman" />

    @if($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN)

        @unless($pengiriman->kendala->contains(fn($kendala) => !$kendala->diselesaikan_at))

            <div class="kirim-informasi" role="status">
                <i class="bi bi-truck"></i>

                <div>
                    <strong>Pesanan Dalam Perjalanan</strong>
                    <p>Kurir toko sedang mengantarkan pesanan Anda.</p>
                </div>
            </div>

        @endunless

    @endif

    @if($pengiriman->tahap_kurir)
        <x-pengiriman.kendala :pengiriman="$pengiriman" />
    @endif

    @php($barangSampai = $pengiriman->tahap() === \App\Models\Pengiriman::SAMPAI)

    @if($barangSampai || $bisaTerimaHistoris)

        @if($barangSampai)

            <div class="kirim-informasi berhasil">
                <i class="bi bi-check-circle-fill"></i>

                <div>
                    <strong>Barang Telah Sampai</strong>
                    <p>
                        Pastikan jumlah dan kondisi barang sudah sesuai
                        sebelum mengonfirmasi penerimaan pesanan.
                    </p>
                </div>
            </div>

        @else

            <div class="kirim-informasi peringatan">
                <i class="bi bi-exclamation-circle"></i>

                <div>
                    <strong>Konfirmasi Penerimaan</strong>
                    <p>
                        Pesanan lama ini tercatat dikirim.
                        Konfirmasi penerimaan hanya jika barang
                        sudah Anda terima dan periksa.
                    </p>
                </div>
            </div>

        @endif

        @if($bisaTerimaHistoris || !$pengiriman->kendala->contains(fn($kendala) => !$kendala->diselesaikan_at))

            <div class="kirim-tindakan">

                <form method="POST" action="{{ route('pelanggan.pengiriman.terima', $pengiriman) }}"
                    data-judul="Konfirmasi Penerimaan"
                    data-konfirmasi-pengiriman="Pastikan jumlah dan kondisi barang telah sesuai sebelum mengonfirmasi penerimaan pesanan."
                    data-setuju="Ya, Pesanan Diterima">

                    @csrf

                    <button class="kirim-tombol kirim-terima" type="submit">
                        <i class="bi bi-check-circle"></i>
                        Pesanan Diterima
                    </button>

                </form>

                @if($barangSampai)

                    <x-pengiriman.form-kendala :action="route('pelanggan.pengiriman.kendala', $pengiriman)" />

                @endif

            </div>

        @endif

        <x-pengiriman.konfirmasi />

    @endif

</section>