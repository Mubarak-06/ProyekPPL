<section class="operasional-kirim" id="operasional-pengiriman">

    <h2 class="kirim-operasional-title">
        <i class="bi bi-truck"></i>
        Pengiriman Toko
    </h2>

    @if(session('success'))
        <div class="kirim-alert kirim-alert-success" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @foreach($errors->all() as $pesan)
        <div class="kirim-alert kirim-alert-danger" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ $pesan }}</span>
        </div>
    @endforeach

    <p>
        Pelanggan:
        <strong>{{ $pengiriman->pesanan->user?->name }}</strong>
    </p>

    <x-pengiriman.timeline
        :pengiriman="$pengiriman"
        :tampilkan-pelaku="true"
    />

    @if($alurTersedia)

        <x-pengiriman.kendala :pengiriman="$pengiriman" />

        @if($pengiriman->tahap() === \App\Models\Pengiriman::DISIAPKAN)

            @if(! $kurirTersedia)

                <div class="kirim-alert kirim-alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>
                        Akun kurir aktif belum tersedia.
                        Pengiriman belum dapat disiapkan.
                    </span>
                </div>

            @elseif($siapDitugaskan)

                <form class="kirim-form"
                    method="POST"
                    action="{{ route('admin.pengiriman.siapkan', $pengiriman) }}">

                    @csrf

                    <h3 class="kirim-section-title">
                        Persiapan Pengiriman
                    </h3>

                    <label>
                        Estimasi tanggal pengiriman

                        <input type="date"
                            name="estimasi_tanggal"
                            value="{{ old('estimasi_tanggal') }}"
                            required>
                    </label>

                    <label>
                        Estimasi waktu tiba (WITA)

                        <input type="time"
                            name="estimasi_jam"
                            value="{{ old('estimasi_jam') }}">
                    </label>

                    <label class="kirim-full">
                        Catatan untuk kurir (opsional)

                        <textarea
                            name="catatan_driver"
                            maxlength="1000"
                            placeholder="Tambahkan catatan untuk kurir...">{{ old('catatan_driver') }}</textarea>
                    </label>

                    <div class="kirim-full">
                        <button type="submit" class="kirim-tombol">
                            <i class="bi bi-truck"></i>
                            Siapkan Pengiriman
                        </button>
                    </div>

                </form>

            @else

                <div class="kirim-alert kirim-alert-warning">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>
                        Periksa verifikasi pembayaran dan
                        rekonsiliasi sebelum menugaskan pengiriman.
                    </span>
                </div>

            @endif

        @endif

        @if($aktif = $pengiriman->kendala->firstWhere('diselesaikan_at', null))

            <form class="kirim-form"
                method="POST"
                action="{{ route('admin.pengiriman.tindak-lanjut', $pengiriman) }}">

                @csrf

                <input type="hidden"
                    name="kendala_id"
                    value="{{ $aktif->id }}">

                <h3 class="kirim-section-title">
                    Tindak Lanjut Kendala
                </h3>

                <label class="kirim-full">
                    Tindak lanjut

                    <textarea
                        name="penyelesaian"
                        maxlength="1000"
                        placeholder="Jelaskan tindak lanjut kendala..."
                        required>{{ old('penyelesaian') }}</textarea>
                </label>

                <label>
                    Estimasi tanggal baru

                    <input type="date"
                        name="estimasi_tanggal"
                        value="{{ old('estimasi_tanggal') }}">
                </label>

                <label>
                    Estimasi waktu baru (WITA)

                    <input type="time"
                        name="estimasi_jam"
                        value="{{ old('estimasi_jam') }}">
                </label>

                @if($pengiriman->tahap() === \App\Models\Pengiriman::PERJALANAN)

                    <label class="kirim-full">
                        <input type="checkbox"
                            name="jadwalkan_ulang"
                            value="1"
                            @checked(old('jadwalkan_ulang'))>

                        Jadwalkan ulang dan kembalikan
                        ke tugas siap dikirim
                    </label>

                @endif

                <div class="kirim-full">
                    <button type="submit" class="kirim-tombol">
                        <i class="bi bi-check-circle"></i>
                        Simpan Tindak Lanjut
                    </button>
                </div>

            </form>

        @endif

    @else

        <div class="kirim-alert kirim-alert-warning">
            <i class="bi bi-info-circle"></i>
            <span>
                Alur kurir toko belum diaktifkan
                pada database ini.
            </span>
        </div>

    @endif

</section>
