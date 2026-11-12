@extends('pelanggan.layouts.auth')
@section('title', 'Reset Kata Sandi')
@section('form-auth')
    <h1>Buat kata sandi baru</h1>
    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', is_string(request('email')) ? request('email') : '') }}" required></p>
        <p><label for="password">Kata sandi baru</label><input id="password" type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password" required></p>
        <p><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></p>
        <button type="submit">Simpan Kata Sandi</button>
    </form>
@endsection
