@extends('kurir.layout')
@section('title', $riwayat ? 'Riwayat Pengiriman' : 'Tugas Pengiriman')
@section('content')
    <div class="kurir-page-heading">
        <h1>{{ $riwayat ? 'Riwayat Pengiriman' : 'Tugas Pengiriman' }}</h1>

        <p>
            {{ $riwayat
                ? 'Lihat kembali riwayat pengiriman yang telah ditangani.'
                : 'Kelola tugas pengiriman yang ditugaskan kepada Anda.' }}
        </p>
    </div>

    @if($daftar->isNotEmpty())
        <div class="kurir-daftar">
            @foreach($daftar as $pengiriman)
                <article class="kirim-panel">

                    <div class="kurir-order-header">
                        <div class="kurir-order-identitas">
                            <span class="kurir-order-icon">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                            </span>

                            <h2>{{ $pengiriman->pesanan->kode_pesanan }}</h2>
                        </div>

                        <span class="kurir-status {{ match($pengiriman->tahap()) {
                            \App\Models\Pengiriman::SIAP => 'biru',
                            \App\Models\Pengiriman::PERJALANAN => 'kuning',
                            default => ''
                        } }}">
                            {{ $pengiriman->tahap() }}
                        </span>
                    </div>

                    <x-pengiriman.timeline :pengiriman="$pengiriman" ringkas />

                    @include('kurir.tujuan')

                    @if($pengiriman->kendala->contains(fn ($kendala) => ! $kendala->diselesaikan_at))
                        <div class="kurir-info-box warning">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <strong>Ada kendala aktif.</strong>
                        </div>
                    @endif

                    <div class="kurir-aksi">
                        <a class="kirim-tombol kirim-tombol-outline"
                            href="{{ route('kurir.detail', $pengiriman) }}">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                            Lihat Detail
                        </a>

                        @if($pengiriman->tahap() === \App\Models\Pengiriman::SIAP)
                            @include('kurir.mulai')
                        @endif
                    </div>

                </article>
            @endforeach
        </div>
    @else
        <div class="kirim-panel kurir-empty">
            <div class="kurir-empty-icon">
                <i class="bi {{ $riwayat ? 'bi-clock-history' : 'bi-truck' }}"
                    aria-hidden="true"></i>
            </div>

            <h2>
                {{ $riwayat
                    ? 'Belum Ada Riwayat Pengiriman'
                    : 'Belum Ada Tugas Pengiriman' }}
            </h2>

            <p>
                {{ $riwayat
                    ? 'Riwayat pengiriman akan ditampilkan di halaman ini.'
                    : 'Tugas pengiriman baru akan muncul setelah ditugaskan oleh admin.' }}
            </p>
        </div>
    @endif

    <div class="kurir-pagination">
        {{ $daftar->links('pagination::simple-bootstrap-5') }}
    </div>
@endsection