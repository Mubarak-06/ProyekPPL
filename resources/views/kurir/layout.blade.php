<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - CV Karunia Abadi</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Dasar halaman */
        :root {
            --kurir-primary: #008A50;
            --kurir-hover: #007344;
            --kurir-light: #E8F8EF;
            --kurir-bg: #F5F7FA;
            --kurir-text: #111827;
            --kurir-muted: #64748B;
            --kurir-border: #E2E8F0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--kurir-bg);
            color: var(--kurir-text);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            line-height: 1.6;
        }

        button,
        input,
        textarea {
            font-family: inherit;
        }

        [hidden] {
            display: none !important;
        }

        .kurir-wadah {
            width: min(calc(100% - 48px), 1100px);
            margin-inline: auto;
        }

        main.kurir-wadah {
            padding-block: 28px 40px;
            overflow-wrap: anywhere;
        }

        /* Header dan navigasi */
        .kurir-header {
            background: #FFFFFF;
            border-bottom: 1px solid var(--kurir-border);
        }

        .kurir-header-inner {
            display: flex;
            align-items: center;
            gap: 24px;
            min-height: 68px;
        }

        .kurir-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--kurir-primary);
            text-decoration: none;
            white-space: nowrap;
        }

        .kurir-brand-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: var(--kurir-primary);
            color: #FFFFFF;
            font-size: 19px;
        }

        .kurir-brand strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
        }

        .kurir-brand small {
            display: block;
            color: var(--kurir-muted);
            font-size: 10px;
            font-weight: 400;
        }

        .kurir-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: auto;
        }

        .kurir-nav a,
        .kurir-keluar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 12px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #475569;
            font: inherit;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .kurir-nav i,
        .kurir-keluar i {
            font-size: 17px;
        }

        .kurir-nav a[aria-current],
        .kurir-nav a:hover {
            background: var(--kurir-light);
            color: var(--kurir-primary);
        }

        .kurir-keluar {
            color: #DC2626;
        }

        .kurir-keluar:hover {
            background: #FEF2F2;
        }

        .kurir-label-mobile {
            display: none;
        }

        /* Judul halaman */
        .kurir-page-heading {
            margin-bottom: 22px;
        }

        .kurir-page-heading h1 {
            margin: 0 0 4px;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.4;
        }

        .kurir-page-heading p {
            margin: 0;
            color: var(--kurir-muted);
            font-size: 12px;
        }

        .kurir-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 44px;
            margin-bottom: 10px;
            color: var(--kurir-muted);
            font-size: 12px;
            text-decoration: none;
        }

        .kurir-back:hover {
            color: var(--kurir-primary);
        }

        /* Panel umum */
        .kirim-panel {
            min-width: 0;
            margin: 0 0 16px;
            padding: 20px;
            border: 1px solid var(--kurir-border);
            border-radius: 12px;
            background: #FFFFFF;
        }

        .operasional-kirim h2 {
            margin: 0 0 14px;
            font-size: 15px;
            font-weight: 600;
        }

        .operasional-kirim h3 {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .operasional-kirim p {
            margin: 0 0 10px;
            line-height: 1.7;
        }

        .operasional-kirim strong {
            font-weight: 600;
        }

        .kurir-panel-title {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .kurir-panel-title i {
            color: var(--kurir-primary);
            font-size: 18px;
        }

        /* Dashboard */
        .kurir-ringkasan {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .kurir-stat {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
            padding: 18px;
            border: 1px solid var(--kurir-border);
            border-radius: 12px;
            background: #FFFFFF;
        }

        .kurir-stat-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font-size: 21px;
        }

        .kurir-stat strong {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .kurir-stat-label {
            color: var(--kurir-muted);
            font-size: 12px;
        }

        .kurir-stat.biru .kurir-stat-icon {
            background: #EFF6FF;
            color: #2563EB;
        }

        .kurir-stat.kuning .kurir-stat-icon {
            background: #FFFBEB;
            color: #D97706;
        }

        .kurir-stat.merah .kurir-stat-icon {
            background: #FEF2F2;
            color: #DC2626;
        }

        .kurir-dashboard-aksi {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .kurir-dashboard-aksi h2 {
            margin-bottom: 4px;
        }

        .kurir-dashboard-aksi p {
            margin: 0;
            color: var(--kurir-muted);
            font-size: 12px;
        }

        /* Daftar pengiriman */
        .kurir-daftar {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .kurir-daftar>.kirim-panel {
            margin: 0;
        }

        .kurir-order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--kurir-border);
        }

        .kurir-order-header h2 {
            margin: 0;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .kurir-order-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 8px;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font-size: 18px;
        }

        .kurir-order-identitas {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .kurir-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font-size: 10px;
            font-weight: 600;
        }

        .kurir-status.biru {
            background: #DBEAFE;
            color: #2563EB;
        }

        .kurir-status.kuning {
            background: #FEF3C7;
            color: #B45309;
        }

        /* Empty state */
        .kurir-empty {
            padding: 48px 20px;
            text-align: center;
        }

        .kurir-empty-icon {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font-size: 32px;
        }

        .kurir-empty h2 {
            margin-bottom: 6px;
            font-size: 16px;
        }

        .kurir-empty p {
            max-width: 360px;
            margin: 0 auto;
            color: var(--kurir-muted);
            font-size: 12px;
        }

        /* Informasi tujuan */
        .kurir-tujuan {
            display: grid;
            gap: 12px;
        }

        .kurir-info-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
        }

        .kurir-info-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 8px;
            background: #F1F5F9;
            color: var(--kurir-muted);
            font-size: 16px;
        }

        .kurir-info-content {
            flex: 1;
            min-width: 0;
        }

        .kurir-info-content small {
            display: block;
            margin-bottom: 2px;
            color: var(--kurir-muted);
            font-size: 11px;
        }

        .kurir-info-content strong {
            display: block;
            font-size: 12px;
        }

        .kurir-info-content p {
            margin: 0;
            font-size: 12px;
        }

        .kurir-barang {
            padding: 12px;
            border: 1px solid var(--kurir-border);
            border-radius: 8px;
            background: #F8FAFC;
        }

        .kurir-barang h3 {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kurir-barang ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .kurir-barang li {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid var(--kurir-border);
            font-size: 12px;
        }

        .kurir-barang li:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .kurir-barang-jumlah {
            flex-shrink: 0;
            color: var(--kurir-primary);
            font-weight: 600;
        }

        /* Notifikasi dan kendala */
        .kurir-info-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #BFDBFE;
            border-radius: 8px;
            background: #EFF6FF;
            color: #1E40AF;
            font-size: 12px;
        }

        .kurir-info-box i {
            flex-shrink: 0;
            font-size: 16px;
        }

        .kurir-info-box p {
            margin: 0;
        }

        .kurir-info-box.warning,
        .kirim-panel .kirim-panel {
            border-color: #FDE68A;
            background: #FFFBEB;
            color: #92400E;
        }

        .kirim-panel .kirim-panel {
            padding: 14px;
            margin: 14px 0;
        }

        /* Detail pengiriman */
        .kurir-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
            gap: 16px;
            align-items: start;
        }

        /* Tombol tindakan */
        .kurir-aksi {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
        }

        .kurir-aksi form {
            margin: 0;
        }

        .kirim-tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: fit-content;
            min-height: 44px;
            padding: 10px 16px;
            border: 1px solid var(--kurir-primary);
            border-radius: 8px;
            background: var(--kurir-primary);
            color: #FFFFFF;
            font: inherit;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .kirim-tombol:hover {
            background: var(--kurir-hover);
            border-color: var(--kurir-hover);
        }

        .kirim-tombol:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .kirim-tombol-outline {
            background: #FFFFFF;
            color: var(--kurir-primary);
        }

        .kirim-tombol-outline:hover {
            background: var(--kurir-light);
            border-color: var(--kurir-primary);
        }

        /* Form pengiriman */
        .kirim-form {
            display: grid;
            gap: 14px;
            min-width: 0;
            margin: 16px 0 0;
            padding: 16px;
            border: 1px solid var(--kurir-border);
            border-radius: 10px;
            background: #F8FAFC;
        }

        .kirim-form label {
            display: grid;
            gap: 6px;
            min-width: 0;
            color: #334155;
            font-size: 12px;
            font-weight: 500;
        }

        .kirim-form input:not([type="checkbox"]),
        .kirim-form textarea {
            width: 100%;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #FFFFFF;
            color: var(--kurir-text);
            font: inherit;
        }

        .kirim-form input:not([type="checkbox"]) {
            min-height: 44px;
        }

        .kirim-form textarea {
            min-height: 90px;
            resize: vertical;
        }

        .kirim-form label:has(input[type="checkbox"]) {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .kirim-form input[type="checkbox"] {
            accent-color: var(--kurir-primary);
        }

        .kirim-form input[type="file"]::file-selector-button {
            margin-right: 8px;
            padding: 8px 10px;
            border: 0;
            border-radius: 5px;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font: inherit;
            cursor: pointer;
        }

        .kirim-form .kurir-form-help {
            margin: 0;
            color: var(--kurir-muted);
            font-size: 11px;
        }

        /* Timeline */
        .kirim-timeline {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .kirim-timeline li {
            position: relative;
            margin-left: 7px;
            padding: 0 0 20px 23px;
            border-left: 2px solid #BBE5CF;
            line-height: 1.6;
            font-weight: 500;
        }

        .kirim-timeline li::before {
            content: '';
            position: absolute;
            top: 4px;
            left: -6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--kurir-primary);
            box-shadow: 0 0 0 3px #DCFCE7;
        }

        .kirim-timeline li:last-child {
            border-left-color: transparent;
            padding-bottom: 0;
        }

        .kirim-timeline time,
        .kirim-timeline small {
            display: block;
            margin-top: 3px;
            color: var(--kurir-muted);
            font-size: 11px;
            font-weight: 400;
        }

        /* Foto penyerahan */
        .kirim-foto {
            display: block;
            width: min(100%, 320px);
            max-height: 280px;
            margin: 10px 0 16px;
            border: 1px solid var(--kurir-border);
            border-radius: 8px;
            object-fit: contain;
            object-position: left;
        }

        /* Profil */
        .kurir-profil {
            max-width: 650px;
        }

        .kurir-profil-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--kurir-border);
        }

        .kurir-avatar {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--kurir-light);
            color: var(--kurir-primary);
            font-size: 30px;
        }

        .kurir-profil-header h2 {
            margin: 0 0 4px;
            font-size: 16px;
        }

        .kurir-profil-header p {
            margin: 0;
            color: var(--kurir-muted);
            font-size: 12px;
        }

        .kurir-profil-data {
            display: grid;
            gap: 18px;
        }

        .kurir-profil-keluar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 44px;
            margin-top: 24px;
            padding: 10px;
            border: 1px solid #FECACA;
            border-radius: 8px;
            background: #FFFFFF;
            color: #DC2626;
            font: inherit;
            font-weight: 500;
            cursor: pointer;
        }

        .kurir-profil-keluar:hover {
            background: #FEF2F2;
        }

        /* Pagination */
        .kurir-pagination {
            margin-top: 20px;
        }

        .kurir-pagination .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .kurir-pagination .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 8px 14px;
            border: 1px solid var(--kurir-border);
            border-radius: 8px;
            background: #FFFFFF;
            color: var(--kurir-primary);
            text-decoration: none;
        }

        .kurir-pagination .disabled .page-link {
            opacity: .5;
        }

        /* Dialog konfirmasi */
        .kirim-dialog {
            width: min(400px, calc(100% - 32px));
            max-height: calc(100dvh - 32px);
            overflow-y: auto;
            padding: 20px;
            border: 1px solid var(--kurir-border);
            border-radius: 12px;
            background: #FFFFFF;
            color: var(--kurir-text);
            font-family: inherit;
            box-shadow: 0 16px 40px rgb(15 23 42 / 15%);
        }

        .kirim-dialog::backdrop {
            background: rgb(15 23 42 / 40%);
        }

        .kirim-dialog form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .kirim-dialog .kirim-batal {
            background: #FFFFFF;
            color: #475569;
            border-color: #CBD5E1;
        }

        .kirim-dialog .kirim-batal:hover {
            background: #F1F5F9;
        }

        /* Aksesibilitas */
        :focus-visible {
            outline: 2px solid var(--kurir-primary);
            outline-offset: 3px;
        }

        /* Tablet */
        @media (max-width: 900px) {
            .kurir-wadah {
                width: calc(100% - 32px);
            }

            .kurir-header-inner {
                gap: 12px;
            }

            .kurir-nav a {
                padding-inline: 8px;
                font-size: 11px;
            }

            .kurir-detail-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 0;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .kurir-header-inner {
                min-height: 64px;
                justify-content: space-between;
            }

            .kurir-brand strong {
                font-size: 13px;
            }

            .kurir-brand-icon {
                width: 34px;
                height: 34px;
            }

            .kurir-keluar {
                padding: 8px;
                font-size: 11px;
            }

            .kurir-nav {
                position: fixed;
                z-index: 100;
                bottom: 0;
                left: 0;
                right: 0;
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 4px;
                margin: 0;
                padding: 6px 12px calc(6px + env(safe-area-inset-bottom, 0px));
                border-top: 1px solid var(--kurir-border);
                background: #FFFFFF;
                box-shadow: 0 -4px 18px rgb(15 23 42 / 5%);
            }

            .kurir-nav a {
                flex-direction: column;
                gap: 3px;
                min-height: 54px;
                padding: 6px 4px;
                font-size: 10px;
            }

            .kurir-nav i {
                font-size: 20px;
            }

            .kurir-label-desktop {
                display: none;
            }

            .kurir-label-mobile {
                display: inline;
            }

            main.kurir-wadah {
                padding-top: 20px;
                padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px));
            }

            .kurir-page-heading {
                margin-bottom: 18px;
            }

            .kurir-page-heading h1 {
                font-size: 20px;
            }

            .kurir-page-heading p {
                font-size: 11px;
            }

            .kurir-ringkasan {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }

            .kurir-stat {
                padding: 14px;
                gap: 8px;
            }

            .kurir-stat strong {
                font-size: 25px;
            }

            .kurir-stat-label {
                font-size: 11px;
            }

            .kurir-stat-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .kirim-panel {
                padding: 16px;
                margin-bottom: 12px;
            }

            .kurir-dashboard-aksi {
                flex-direction: column;
                align-items: stretch;
            }

            .kurir-daftar {
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
            }

            .kurir-aksi {
                flex-direction: column;
                align-items: stretch;
            }

            .kurir-aksi form,
            .operasional-kirim .kirim-tombol {
                width: 100%;
            }

            .kirim-form {
                padding: 12px;
            }

            .kirim-form input:not([type="checkbox"]),
            .kirim-form textarea {
                font-size: 16px;
            }

            .kurir-empty {
                padding: 40px 16px;
            }

            .kirim-dialog form>button {
                flex: 1;
            }
        }

        @media (max-width: 360px) {
            .kurir-wadah {
                width: calc(100% - 24px);
            }

            .kurir-brand {
                gap: 7px;
            }

            .kurir-brand strong {
                font-size: 12px;
            }

            .kurir-stat {
                padding: 12px;
            }

            .kirim-panel {
                padding: 14px;
            }

            .kurir-nav {
                padding-inline: 6px;
            }
        }
    </style>
</head>

<body>
    <header class="kurir-header">
        <div class="kurir-wadah kurir-header-inner">

            <a href="{{ route('kurir.dashboard') }}" class="kurir-brand">
                <span class="kurir-brand-icon">
                    <i class="bi bi-truck" aria-hidden="true"></i>
                </span>
                <span>
                    <strong>CV Karunia Abadi</strong>
                    <small>Portal Kurir</small>
                </span>
            </a>

            <nav class="kurir-nav" aria-label="Menu kurir">
                @foreach([
                        'dashboard' => ['speedometer2', 'Dashboard', 'Beranda'],
                        'tugas' => ['truck', 'Tugas Pengiriman', 'Tugas'],
                        'riwayat' => ['clock-history', 'Riwayat Pengiriman', 'Riwayat'],
                        'profil' => ['person', 'Profil', 'Profil']
                    ] as $menu => [$ikon, $label, $labelMobile])
                    <a href="{{ route('kurir.' . $menu) }}" @if(request()->routeIs('kurir.' . $menu)) aria-current="page"
                    @endif>
                        <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                        <span class="kurir-label-desktop">{{ $label }}</span>
                        <span class="kurir-label-mobile">{{ $labelMobile }}</span>
                    </a>
                @endforeach
            </nav>

            <button class="kurir-keluar" type="button" data-buka-konfirmasi-keluar aria-haspopup="dialog">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                Keluar
            </button>

        </div>
    </header>

    <main class="kurir-wadah operasional-kirim">
        @include('pelanggan.partials.pesan')
        @yield('content')
    </main>

    <x-pengiriman.konfirmasi />
    <x-konfirmasi-keluar />
</body>

</html>