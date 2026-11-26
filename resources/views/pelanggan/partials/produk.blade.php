<article class="kartu-produk">
    @if(!empty($labelProduk))
        <span class="badge-produk"><i class="bi bi-fire" aria-hidden="true"></i> {{ $labelProduk }}</span>
    @endif
    <a class="tautan-produk" href="{{ route('pelanggan.produk.show', $item) }}">
        <div class="foto-produk">
            @include('pelanggan.partials.gambar', ['path' => $item->gambar[0] ?? null, 'nama' => $item->nama_produk])
        </div>
        <h3>{{ $item->nama_produk }} @if($item->tingkatanGrosir()) <small class="label-grosir">Grosir</small> @endif</h3>
    </a>
    <p class="harga">Rp {{ number_format($item->harga, 0, ',', '.') }}</p>
    <form id="beli-produk-{{ $item->id }}" action="{{ route('pelanggan.produk.show', $item) }}" method="GET" hidden></form>
    <form class="aksi-produk" action="{{ route('pelanggan.keranjang.store', $item) }}" method="POST">
        @csrf
        <input type="hidden" name="jumlah" value="1">
        <button type="submit" form="beli-produk-{{ $item->id }}">@if(!empty($labelProduk))<i class="bi bi-cart3" aria-hidden="true"></i>@endif Beli Sekarang</button>
        <button class="tombol-outline" type="submit" @disabled($item->stok < 1)><i class="bi bi-cart3" aria-hidden="true"></i> + Keranjang</button>
    </form>
</article>
