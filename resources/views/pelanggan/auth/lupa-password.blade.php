@extends('pelanggan.layouts.auth')
@section('title', 'Lupa Kata Sandi')
@section('form-auth')
    <h1>Lupa kata sandi?</h1>
    <p>Masukkan email akun Anda untuk menerima tautan reset kata sandi.</p>
    <form action="{{ route('password.email') }}" method="POST">
        @csrf
        <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></p>
        <button type="submit">Kirim Tautan Reset</button>
    </form>
    <p class="aksi-form"><a href="{{ route('login') }}">Kembali ke Masuk</a></p>
@endsection
