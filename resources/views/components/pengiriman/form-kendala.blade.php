@props(['action'])
<form class="kirim-form" method="POST" action="{{ $action }}">
    @csrf
    <label>Jelaskan kendala
        <textarea name="catatan" rows="3" maxlength="1000" required>{{ old('catatan') }}</textarea>
    </label>
    <button class="kirim-tombol" type="submit">Laporkan Kendala</button>
</form>
