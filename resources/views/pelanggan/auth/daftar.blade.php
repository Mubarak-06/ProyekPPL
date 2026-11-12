@extends('pelanggan.layouts.auth')
@section('title', 'Daftar')
@section('form-auth')
    <h1>Daftar Akun</h1>
    <form action="{{ route('pelanggan.daftar') }}" method="POST">
        @csrf
        <p><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required></p>
        <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required></p>
        <p><label for="password">Kata sandi (minimal 8 karakter)</label><input id="password" type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password" required></p>
        <p><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></p>
        <button type="submit">Daftar</button>
    </form>
    <div class="pemisah">atau</div>
    <a class="tombol tombol-outline" href="{{ route('login') }}">Masuk ke Akun</a>
@endsection
