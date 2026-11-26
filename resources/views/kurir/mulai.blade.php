<form method="POST"
    action="{{ route('kurir.mulai', $pengiriman) }}"
    data-judul="Mulai Pengiriman"
    data-konfirmasi-pengiriman="Apakah Anda siap berangkat untuk mengantar pesanan ini?"
    data-setuju="Ya, Mulai">

    @csrf

    <button class="kirim-tombol" type="submit">
        <i class="bi bi-truck" aria-hidden="true"></i>
        Mulai Pengiriman
    </button>
</form>