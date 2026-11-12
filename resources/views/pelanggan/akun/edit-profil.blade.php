@extends('pelanggan.layouts.akun')
@section('title', 'Edit Profil')
@section('akun-content')
    <h1>Edit Profil</h1>
    <form class="form-akun" action="{{ route('pelanggan.profil.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <p><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required></p>
        <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required></p>
        <p><label for="nomor_telepon">Nomor telepon</label><input id="nomor_telepon" name="nomor_telepon" type="tel" value="{{ old('nomor_telepon', $user->nomor_telepon) }}" maxlength="20"></p>
        <div class="foto-profil-form">
            @include('pelanggan.partials.avatar')
            <img id="preview-profil" class="avatar" alt="Pratinjau foto profil" hidden>
            <p><label for="foto_profil">Foto Profil</label><input id="foto_profil" type="file" name="foto_profil" accept="image/jpeg,image/png" data-preview="preview-profil"><small>Format JPG, PNG (maks. 2 MB)</small></p>
        </div>
        <div class="aksi-form"><button type="submit">Simpan Perubahan</button><a class="tombol tombol-outline" href="{{ route('pelanggan.profil') }}">Batal</a></div>
    </form>
@endsection
