<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - CV Karunia Abadi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: #111827; font: 400 14px/1.6 Poppins, sans-serif; }
        .kurir-header { background: white; border-bottom: 1px solid #dce4ef; }
        .kurir-wadah { width: min(100% - 32px, 860px); margin-inline: auto; padding-block: 14px; }
        .kurir-header strong { color: #008a50; font-size: 16px; }
        .kurir-nav { display: flex; flex-wrap: wrap; gap: 4px 8px; margin-top: 8px; }
        .kurir-nav a, .kurir-nav button { background: transparent; color: #111827; font: inherit; text-decoration: none; padding: 8px 10px; min-height: 44px; border: 0; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
        .kurir-nav a[aria-current], .kurir-nav :is(a, button):hover { color: #00683d; background: #ecfdf5; }
        .kurir-ringkasan { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px; }
        .kurir-ringkasan .kirim-panel { margin: 0; }
        .kurir-ringkasan strong { display: block; font-size: 22px; }
        .kurir-aksi { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        h1 { font-size: clamp(20px, 5vw, 28px); }
    </style>
</head>
<body>
    <header class="kurir-header">
        <div class="kurir-wadah">
            <strong>CV Karunia Abadi</strong>
            <nav class="kurir-nav" aria-label="Menu kurir">
                @foreach(['dashboard' => ['speedometer2', 'Dashboard'], 'tugas' => ['truck', 'Tugas Pengiriman'], 'riwayat' => ['clock-history', 'Riwayat Pengiriman'], 'profil' => ['person', 'Profil']] as $menu => [$ikon, $label])
                    <a href="{{ route('kurir.'.$menu) }}" @if(request()->routeIs('kurir.'.$menu)) aria-current="page" @endif><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i> {{ $label }}</a>
                @endforeach
                <button type="button" data-buka-konfirmasi-keluar aria-haspopup="dialog"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button>
            </nav>
        </div>
    </header>
    <main class="kurir-wadah operasional-kirim">
        <x-pengiriman.gaya />
        @include('pelanggan.partials.pesan')
        @yield('content')
    </main>
    <x-pengiriman.konfirmasi />
    <x-konfirmasi-keluar />
</body>
</html>
