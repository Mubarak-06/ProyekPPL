@extends('kurir.layout')
@section('title', 'Profil Kurir')
@section('content')
    <h1>Profil Kurir</h1>
    <div class="kirim-panel">
        <p><strong>Nama</strong> {{ auth()->user()->name }}</p>
        <p><strong>Email</strong> {{ auth()->user()->email }}</p>
    </div>
@endsection
