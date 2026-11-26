@extends('kurir.layout')
@section('title', $riwayat ? 'Riwayat Pengiriman' : 'Tugas Pengiriman')
@section('content')
    <h1>{{ $riwayat ? 'Riwayat Pengiriman' : 'Tugas Pengiriman' }}</h1>
    @forelse($daftar as $pengiriman)
        <article class="kirim-panel">
            <h2>{{ $pengiriman->pesanan->kode_pesanan }}</h2>
            <x-pengiriman.timeline :pengiriman="$pengiriman" ringkas />
            @include('kurir.tujuan')
            @if($pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))
                <p><strong>Ada kendala aktif.</strong></p>
            @endif
            <div class="kurir-aksi">
                <a class="kirim-tombol" href="{{ route('kurir.detail', $pengiriman) }}">Lihat Detail</a>
                @if($pengiriman->tahap() === \App\Models\Pengiriman::SIAP)
                    @include('kurir.mulai')
                @endif
            </div>
        </article>
    @empty
        <p>Belum ada pengiriman pada daftar ini.</p>
    @endforelse
    {{ $daftar->links('pagination::simple-bootstrap-5') }}
@endsection
