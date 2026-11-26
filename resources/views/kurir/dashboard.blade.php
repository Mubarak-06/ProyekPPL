@extends('kurir.layout')
@section('title', 'Dashboard Kurir')
@section('content')
    <h1>Dashboard Kurir</h1>
    <div class="kurir-ringkasan">
        @foreach($ringkasan as $label => $jumlah)
            <div class="kirim-panel"><strong>{{ $jumlah }}</strong>{{ $label }}</div>
        @endforeach
    </div>
    <p><a class="kirim-tombol" href="{{ route('kurir.tugas') }}">Lihat Tugas Pengiriman</a></p>
@endsection
