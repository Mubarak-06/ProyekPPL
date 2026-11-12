@extends('pelanggan.layouts.akun')
@section('title', $alamat->exists ? 'Edit Alamat' : 'Tambah Alamat')
@section('akun-content')
    <h1>{{ $alamat->exists ? 'Edit Alamat' : 'Tambah Alamat' }}</h1>
    <form class="form-alamat" action="{{ $alamat->exists ? route('pelanggan.alamat.update', $alamat->id) : route('pelanggan.alamat.store') }}" method="POST">
        @csrf
        @if($alamat->exists) @method('PUT') @endif
        <p><label for="nama_penerima">Nama penerima</label><input id="nama_penerima" name="nama_penerima" value="{{ old('nama_penerima', $alamat->nama_penerima) }}" maxlength="100" required></p>
        <p><label for="nomor_telepon">Nomor telepon</label><input id="nomor_telepon" type="tel" name="nomor_telepon" value="{{ old('nomor_telepon', $alamat->nomor_telepon) }}" maxlength="20" required></p>
        <p class="lebar-penuh"><label for="alamat_lengkap">Alamat lengkap</label><textarea id="alamat_lengkap" name="alamat_lengkap" maxlength="2000" required>{{ old('alamat_lengkap', $alamat->alamat_lengkap) }}</textarea></p>
        <div class="lebar-penuh wilayah-form" data-wilayah="{{ route('pelanggan.wilayah') }}" data-provinsi="{{ $wilayah?->provinsi_id }}" data-kota="{{ $wilayah?->kota_id }}" data-kecamatan="{{ $wilayah?->kecamatan_id }}" data-kelurahan="{{ old('kelurahan_id', $alamat->kelurahan_id) }}">
            <p><label for="provinsi">Provinsi</label><select id="provinsi" required><option value="">Pilih Provinsi</option>@foreach($provinsi as $item)<option value="{{ $item->id }}" @selected($wilayah?->provinsi_id == $item->id)>{{ $item->nama_provinsi }}</option>@endforeach</select></p>
            <p><label for="kota">Kota / Kabupaten</label><select id="kota" required><option value="">Pilih Kota</option></select></p>
            <p><label for="kecamatan">Kecamatan</label><select id="kecamatan" required><option value="">Pilih Kecamatan</option></select></p>
            <p class="kolom-setengah"><label for="kelurahan">Kelurahan / Desa</label><select id="kelurahan" name="kelurahan_id" required><option value="">Pilih Kelurahan</option></select></p>
            <p class="kolom-setengah"><label for="kode_pos">Kode pos</label><input id="kode_pos" name="kode_pos" value="{{ old('kode_pos', $alamat->kode_pos) }}" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required></p>
            <p class="lebar-penuh" role="status" data-wilayah-pesan></p>
        </div>
        <div class="aksi-form lebar-penuh">
            <button type="submit">Simpan Alamat</button>
            <a class="tombol tombol-outline" href="{{ route('pelanggan.alamat') }}">Kembali</a>
        </div>
    </form>
@endsection
