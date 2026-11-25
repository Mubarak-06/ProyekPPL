@php
    $pembayaranTimer = $order->pembayaran;

    $tampilkanTimer = $pembayaranTimer
        && $order->status_pesanan === 'Menunggu'
        && $pembayaranTimer->gateway === 'midtrans'
        && $pembayaranTimer->status_pembayaran === 'Menunggu'
        && $pembayaranTimer->waktu_pembayaran === null
        && $pembayaranTimer->snap_token_expires_at !== null
        && $pembayaranTimer->rekonsiliasiAktif->isEmpty()
        && in_array(
            $pembayaranTimer->midtrans_status,
            [null, 'pending'],
            true
        );
@endphp

@if($tampilkanTimer)
    @php
        $waktuServer = now()->timestamp;
        $batasWaktu = $pembayaranTimer->snap_token_expires_at->timestamp;

        $sisaWaktu = max(0, $batasWaktu - $waktuServer);

        $formatWaktu = sprintf(
            '%02d:%02d',
            intdiv($sisaWaktu, 60),
            $sisaWaktu % 60
        );
    @endphp

    <div
        class="hitung-mundur-pembayaran"
        data-payment-countdown
        data-expires-at="{{ $batasWaktu }}"
        data-server-now="{{ $waktuServer }}"
    >
        <div class="hitung-mundur-label">
            <i class="bi bi-clock" aria-hidden="true"></i>
            <span>Sisa waktu sesi pembayaran</span>
        </div>

        <strong
            class="hitung-mundur-waktu"
            data-countdown-value
            role="timer"
            aria-live="off"
        >{{ $formatWaktu }}</strong>

        <p
            class="hitung-mundur-pesan"
            data-countdown-message
            role="status"
            @if($sisaWaktu > 0) hidden @endif
        >
            Batas waktu sesi tercapai. Silakan periksa
            status pembayaran.
        </p>

        <a
            class="hitung-mundur-tautan"
            href="{{ route('pelanggan.pembayaran', $order->id) }}"
            data-countdown-link
            @if($sisaWaktu > 0) hidden @endif
        >
            Lihat Status Pembayaran
        </a>

        <noscript>
            <small>
                Muat ulang halaman untuk memperbarui sisa waktu.
            </small>
        </noscript>
    </div>
@endif
