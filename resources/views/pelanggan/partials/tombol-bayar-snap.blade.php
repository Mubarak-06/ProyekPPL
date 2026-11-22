<form method="POST" action="{{ route('pelanggan.pembayaran.snap', $order->id) }}">
    @csrf
    <button type="submit" class="tombol tombol-bayar-snap">{{ $order->pembayaran->midtrans_status === 'pending' ? 'Lanjutkan Pembayaran' : 'Bayar Sekarang' }}</button>
</form>
