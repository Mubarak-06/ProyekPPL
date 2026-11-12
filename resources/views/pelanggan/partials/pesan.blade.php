@if(session('keranjang_ditambah'))
    <dialog class="dialog-sukses" data-sukses aria-labelledby="judul-sukses">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <h2 id="judul-sukses">Berhasil Ditambahkan</h2>
        <p>{{ session('success') }}</p>
        <button type="button" data-tutup>Lanjut Belanja</button>
        <a class="tombol tombol-outline" href="{{ route('pelanggan.keranjang') }}">Lihat Keranjang</a>
    </dialog>
@elseif(session('success'))
    <p class="pesan pesan-sukses" role="status">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p class="pesan pesan-error" role="alert">{{ session('error') }}</p>
@endif
@if($errors->any())
    <ul class="pesan pesan-error" role="alert">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
