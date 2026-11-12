@extends('pelanggan.layouts.auth')
@section('title', 'Masuk')
@section('form-auth')
    <h1>Masuk ke akun Anda</h1>
    <form action="{{ route('login') }}" method="POST">
        @csrf
        <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" maxlength="255" autocomplete="email" required></p>
        <p><label for="password">Kata sandi</label><input id="password" type="password" name="password" placeholder="Masukkan kata sandi Anda" autocomplete="current-password" required></p>
        <div class="opsi-login"><button class="tombol-teks" type="button" data-password="password">Tampilkan kata sandi</button><a href="{{ route('password.request') }}">Lupa password?</a></div>
        <p><label class="ingat-saya"><input type="checkbox" name="remember" value="1"> Ingat saya</label></p>
        <button type="submit">Masuk</button>
    </form>
    <div class="pemisah">atau</div>
    <a class="tombol tombol-outline" href="{{ route('pelanggan.daftar') }}">Daftar Akun Baru</a>
@endsection
