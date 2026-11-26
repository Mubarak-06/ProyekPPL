<section class="operasional-kirim kirim-panel" id="operasional-pengiriman">
    <x-pengiriman.gaya />
    <h2>Pengiriman Toko</h2>
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach($errors->all() as $pesan)<p role="alert">{{ $pesan }}</p>@endforeach
    <p>Pelanggan <strong>{{ $pengiriman->pesanan->user?->name }}</strong></p>
    <x-pengiriman.timeline :pengiriman="$pengiriman" :tampilkan-pelaku="true" />
    @if($alurTersedia)
        <x-pengiriman.kendala :pengiriman="$pengiriman" />
        @if($pengiriman->tahap() === \App\Models\Pengiriman::DISIAPKAN)
            @if(! $kurirTersedia)
                <p>Akun kurir aktif belum tersedia. Pengiriman belum dapat disiapkan.</p>
            @elseif($siapDitugaskan)
                <form class="kirim-form" method="POST" action="{{ route('admin.pengiriman.siapkan', $pengiriman) }}">
                    @csrf
                    <label>Estimasi tanggal pengiriman <input type="date" name="estimasi_tanggal" value="{{ old('estimasi_tanggal') }}" required></label>
                    <label>Estimasi waktu tiba (WITA) <input type="time" name="estimasi_jam" value="{{ old('estimasi_jam') }}"></label>
                    <label>Catatan untuk kurir <textarea name="catatan_driver" maxlength="1000">{{ old('catatan_driver') }}</textarea></label>
                    <button type="submit" class="kirim-tombol">Siapkan Pengiriman</button>
                </form>
            @else
                <p>Periksa verifikasi pembayaran dan rekonsiliasi sebelum menugaskan pengiriman.</p>
            @endif
        @endif
        @if($aktif = $pengiriman->kendala->firstWhere('diselesaikan_at', null))
            <form class="kirim-form" method="POST" action="{{ route('admin.pengiriman.tindak-lanjut', $pengiriman) }}">
                @csrf
                <input type="hidden" name="kendala_id" value="{{ $aktif->id }}">
                <label>Tindak lanjut <textarea name="penyelesaian" maxlength="1000" required>{{ old('penyelesaian') }}</textarea></label>
                <label>Estimasi tanggal baru <input type="date" name="estimasi_tanggal" value="{{ old('estimasi_tanggal') }}"></label>
                <label>Estimasi waktu baru (WITA) <input type="time" name="estimasi_jam" value="{{ old('estimasi_jam') }}"></label>
                @if($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN)
                    <label><input type="checkbox" name="jadwalkan_ulang" value="1" @checked(old('jadwalkan_ulang'))> Jadwalkan ulang dan kembalikan ke tugas siap dikirim</label>
                @endif
                <button type="submit" class="kirim-tombol">Simpan Tindak Lanjut</button>
            </form>
        @endif
    @else
        <p>Alur kurir toko belum diaktifkan pada database ini.</p>
    @endif
</section>
