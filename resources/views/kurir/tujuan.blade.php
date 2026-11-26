<div class="kurir-tujuan">

    <div class="kurir-info-row">
        <div class="kurir-info-icon">
            <i class="bi bi-person" aria-hidden="true"></i>
        </div>

        <div class="kurir-info-content">
            <small>Pelanggan</small>
            <strong>{{ $pengiriman->pesanan->user?->name ?? '-' }}</strong>
        </div>
    </div>

    <div class="kurir-info-row">
        <div class="kurir-info-icon">
            <i class="bi bi-person-check" aria-hidden="true"></i>
        </div>

        <div class="kurir-info-content">
            <small>Penerima Tujuan</small>
            <strong>{{ $pengiriman->nama_penerima }}</strong>
            <p>{{ $pengiriman->nomor_telepon ?: '-' }}</p>
        </div>
    </div>

    <div class="kurir-info-row">
        <div class="kurir-info-icon">
            <i class="bi bi-geo-alt" aria-hidden="true"></i>
        </div>

        <div class="kurir-info-content">
            <small>Alamat Pengiriman</small>
            <p>{{ $pengiriman->alamat_pengiriman }}</p>
        </div>
    </div>

    <div class="kurir-barang">
        <h3>
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            Daftar Barang
        </h3>

        <ul>
            @foreach($pengiriman->pesanan->detailPesanan as $barang)
                <li>
                    <span>
                        {{ $barang->nama_produk
                            ?? $barang->produk?->nama_produk
                            ?? 'Produk #'.$barang->produk_id }}
                    </span>

                    <span class="kurir-barang-jumlah">
                        × {{ $barang->jumlah }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    @if($pengiriman->catatan_driver)
        <div class="kurir-info-box warning">
            <i class="bi bi-sticky" aria-hidden="true"></i>

            <div>
                <strong>Catatan Admin</strong>
                <p>{{ $pengiriman->catatan_driver }}</p>
            </div>
        </div>
    @endif

</div>