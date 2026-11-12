@if($user->foto_profil && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto_profil))
    <img class="avatar" src="{{ asset('storage/'.$user->foto_profil) }}" alt="Foto profil {{ $user->name }}" width="48" height="48">
@else
    <i class="bi bi-person-circle avatar-kosong" aria-hidden="true"></i>
@endif
