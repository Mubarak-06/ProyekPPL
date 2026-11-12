@extends('pelanggan.layouts.akun')
@section('title', 'Profil Saya')
@section('akun-content')
    <div class="judul-bagian">
        <div><h2>Profil Saya</h2><p>Informasi dasar akun Anda.</p></div>
        <a class="tombol tombol-outline" href="{{ route('pelanggan.profil.edit') }}"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Profil</a>
    </div>
    <dl class="data-profil">
        <dt>Nama Lengkap</dt><dd>{{ $user->name }}</dd>
        <dt>Email</dt><dd>{{ $user->email }}</dd>
        <dt>Nomor Telepon</dt><dd>{{ $user->nomor_telepon ?: 'Belum diisi' }}</dd>
    </dl>
@endsection
