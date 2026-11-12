@extends('pelanggan.layouts.akun')
@section('title', 'Alamat Saya')
@section('akun-content')
    <h1>Alamat Saya</h1>
    <a class="tombol" href="{{ route('pelanggan.alamat.create') }}">Tambah Alamat</a>
    @forelse($alamat as $item)
        <article class="kartu-alamat">
            <h2>{{ $item->nama_penerima }}</h2>
            <p>{{ $item->nomor_telepon }}</p>
            <p>{{ $item->alamat_lengkap }}, {{ $item->kelurahan?->nama_kelurahan }} {{ $item->kode_pos }}</p>
            <p>{{ $item->kelurahan?->ongkir ? 'Ongkir: Rp '.number_format($item->kelurahan->ongkir->biaya_ongkir, 0, ',', '.') : 'Tarif ongkir belum tersedia.' }}</p>
            <a class="tombol tombol-outline" href="{{ route('pelanggan.alamat.edit', $item->id) }}">Edit Alamat</a>
            <form class="form-inline" action="{{ route('pelanggan.alamat.destroy', $item->id) }}" method="POST" data-konfirmasi="Hapus alamat ini?">
                @csrf
                @method('DELETE')
                <button class="tombol-hapus" type="submit">Hapus</button>
            </form>
        </article>
    @empty
        <p>Anda belum memiliki alamat.</p>
    @endforelse
@endsection
