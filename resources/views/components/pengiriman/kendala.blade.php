@props(['pengiriman'])
@foreach($pengiriman->kendala as $kendala)
    <section class="kirim-panel">
        <h3>{{ $kendala->diselesaikan_at ? 'Kendala diselesaikan' : 'Kendala aktif' }}</h3>
        <p>{{ $kendala->peran_pelapor }} · {{ $kendala->dilaporkan_at->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA</p>
        <p>{{ $kendala->catatan }}</p>
        @if($kendala->diselesaikan_at)
            <p>{{ $kendala->diselesaikan_at->copy()->timezone(config('toko.timezone'))->format('d/m/Y H:i') }} WITA · {{ $kendala->penyelesaian }}</p>
        @endif
    </section>
@endforeach
