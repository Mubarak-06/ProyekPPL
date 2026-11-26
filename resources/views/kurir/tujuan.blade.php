<p><strong>{{ $pengiriman->pesanan->user?->name }}</strong></p>
<p>Penerima tujuan {{ $pengiriman->nama_penerima }} · {{ $pengiriman->nomor_telepon }}</p>
<p>{{ $pengiriman->alamat_pengiriman }}</p>
<ul>
    @foreach($pengiriman->pesanan->detailPesanan as $barang)
        <li>{{ $barang->nama_produk ?? $barang->produk?->nama_produk ?? 'Produk #'.$barang->produk_id }} × {{ $barang->jumlah }}</li>
    @endforeach
</ul>
@if($pengiriman->catatan_driver)<p>Catatan admin {{ $pengiriman->catatan_driver }}</p>@endif
