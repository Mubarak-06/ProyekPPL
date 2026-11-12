@if($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
    <img src="{{ asset('storage/'.$path) }}" alt="{{ $nama }}" width="240" height="200" loading="lazy">
@else
    <div class="gambar-kosong" role="img" aria-label="Gambar {{ $nama }} belum tersedia"><i class="bi bi-image" aria-hidden="true"></i><span>Gambar belum tersedia</span></div>
@endif
