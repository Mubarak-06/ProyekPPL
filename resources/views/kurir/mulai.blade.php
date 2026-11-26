<form method="POST" action="{{ route('kurir.mulai', $pengiriman) }}" data-judul="Mulai Pengiriman" data-konfirmasi-pengiriman="Apakah Anda siap berangkat untuk mengantar pesanan ini?" data-setuju="Ya, Mulai">
    @csrf
    <button class="kirim-tombol" type="submit">Mulai Pengiriman</button>
</form>
