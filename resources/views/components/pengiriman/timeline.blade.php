@props(['pengiriman', 'ringkas' => false, 'tampilkanPelaku' => false])
@php
    $pesanan = $pengiriman->pesanan;
    $waktu = fn ($nilai) => $nilai->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i').' WITA';
    $riwayat = $pengiriman->tahap_kurir ? $pengiriman->riwayat : collect();
@endphp
<p>Metode <strong>Kurir CV Karunia Abadi</strong></p>
<p>Status <strong>{{ $pengiriman->tahap() }}</strong></p>
@if($pengiriman->estimasi_tanggal)
    <p>Perkiraan pengiriman <strong>{{ \Illuminate\Support\Carbon::parse($pengiriman->estimasi_tanggal)->format('d/m/Y') }}{{ $pengiriman->estimasi_jam ? ' pukul '.substr($pengiriman->estimasi_jam, 0, 5).' WITA' : '' }}</strong></p>
@endif
@unless($ringkas)
    <ol class="kirim-timeline">
        @if($pesanan->created_at)
            <li>Pesanan dibuat <time datetime="{{ $pesanan->created_at->toAtomString() }}">{{ $waktu($pesanan->created_at) }}</time></li>
        @endif
        @if($pesanan->pembayaran?->status_pembayaran === 'Berhasil' && $pesanan->pembayaran->waktu_pembayaran)
            <li>Pembayaran berhasil <time datetime="{{ $pesanan->pembayaran->waktu_pembayaran->toAtomString() }}">{{ $waktu($pesanan->pembayaran->waktu_pembayaran) }}</time></li>
        @endif
        @if($pengiriman->waktu_dikirim && ! $riwayat->contains('status_baru', \App\Models\Pengiriman::PERJALANAN))
            <li>Dikirim <time datetime="{{ $pengiriman->waktu_dikirim->toAtomString() }}">{{ $waktu($pengiriman->waktu_dikirim) }}</time></li>
        @endif
        @foreach($riwayat as $entri)
            <li>{{ match($entri->status_baru) {
                'Disiapkan' => 'Pesanan diproses',
                'Dalam Perjalanan' => 'Barang dalam perjalanan',
                'Diterima' => 'Pesanan diterima',
                default => $entri->status_baru,
            } }}
                <time datetime="{{ $entri->terjadi_at->toAtomString() }}">{{ $waktu($entri->terjadi_at) }}</time>
                @if($tampilkanPelaku)
                    <small>{{ $entri->status_lama }} → {{ $entri->status_baru }} · {{ $entri->pelaku?->name ?? ($entri->pelaku_id ? 'Akun tidak tersedia' : 'Sistem') }}</small>
                @endif
                @if($entri->catatan) {{ $entri->catatan }} @endif
            </li>
        @endforeach
        @if($riwayat->isEmpty() && $pengiriman->waktu_selesai)
            <li>Selesai <time datetime="{{ $pengiriman->waktu_selesai->toAtomString() }}">{{ $waktu($pengiriman->waktu_selesai) }}</time></li>
        @endif
    </ol>
    @if($pengiriman->penerima_aktual)
        <p>Diserahkan kepada <strong>{{ $pengiriman->penerima_aktual }}</strong></p>
        @if($pengiriman->catatan_penyerahan)<p>{{ $pengiriman->catatan_penyerahan }}</p>@endif
        @if($pengiriman->foto_penyerahan)
            <a href="{{ route('pengiriman.bukti', $pengiriman->id) }}"><img class="kirim-foto" src="{{ route('pengiriman.bukti', $pengiriman->id) }}" alt="Foto bukti penyerahan barang" loading="lazy"></a>
        @endif
    @endif
@endunless
