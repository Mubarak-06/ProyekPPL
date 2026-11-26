@extends('kurir.layout')
@section('title', 'Dashboard Kurir')
@section('content')
    <div class="kurir-page-heading">
        <h1>Dashboard Kurir</h1>
        <p>Ringkasan aktivitas dan status pengiriman Anda.</p>
    </div>

    <div class="kurir-ringkasan">
        @foreach($ringkasan as $label => $jumlah)
            @php
                [$ikon, $warna] = match($label) {
                    'Siap Dikirim' => ['box-seam', 'biru'],
                    'Dalam Perjalanan' => ['truck', 'kuning'],
                    'Kendala Aktif' => ['exclamation-circle', 'merah'],
                    'Telah Diserahkan' => ['check-circle', 'hijau'],
                    default => ['box', 'hijau']
                };
            @endphp

            <div class="kurir-stat {{ $warna }}">
                <div class="kurir-stat-icon">
                    <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                </div>

                <strong>{{ $jumlah }}</strong>

                <span class="kurir-stat-label">
                    {{ $label }}
                </span>
            </div>
        @endforeach
    </div>

    <section class="kirim-panel kurir-dashboard-aksi">
        <div>
            <h2>Tugas Pengiriman</h2>
            <p>Lihat dan kelola pengiriman yang ditugaskan kepada Anda.</p>
        </div>

        <a class="kirim-tombol" href="{{ route('kurir.tugas') }}">
            <i class="bi bi-truck" aria-hidden="true"></i>
            Lihat Tugas Pengiriman
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </section>
@endsection