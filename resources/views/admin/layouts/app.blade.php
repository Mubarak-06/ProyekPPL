<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - CV Karunia Abadi</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #F8FAFC;
            color: #111827;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 200px;
            height: 100vh;
            background: #1B2533;
            color: white;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            padding: 2px 8px 24px;
        }

        .brand h3 {
            font-size: 14px;
            margin: 0;
            font-weight: 700;
        }

        .brand p {
            font-size: 10px;
            color: #94A3B8;
            margin: 3px 0 0;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 13px;
            border-radius: 6px;
            color: #94A3B8;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
        }

        .menu a:hover,
        .menu a.active {
            background: #008A50;
            color: white;
        }

        .menu i {
            font-size: 15px;
        }

        .logout {
            margin-top: auto;
        }

        .logout a {
            color: #FF4D67;
        }

        .main {
            margin-left: 200px;
            min-height: 100vh;
        }

        .navbar {
            height: 54px;
            background: white;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 28px;
            border-bottom: 1px solid #E2E8F0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 500;
        }

        .admin-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1B2533;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .content {
            padding: 24px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 170px;
            }

            .main {
                margin-left: 170px;
            }

            .content {
                padding: 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <aside class="sidebar">
        <div class="brand">
            <h3>CV Karunia Abadi</h3>
            <p>Panel Administrator</p>
        </div>

        <nav class="menu">
            <a href="{{ route('admin.dashboard') }}"
                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-fill"></i>
                Dashboard
            </a>

            <a href="{{ route('admin.produk.index') }}"
                class="{{ request()->routeIs('admin.produk.*') ? 'active' : '' }}">
                <i class="bi bi-box"></i>
                Produk
            </a>

            <a href="{{ route('admin.pesanan.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.pesanan.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                Pesanan
            </a>

            <a href="#">
                <i class="bi bi-truck"></i>
                Pengiriman
            </a>

            <a href="#">
                <i class="bi bi-credit-card"></i>
                Pembayaran
            </a>

            <a href="#">
                <i class="bi bi-bar-chart"></i>
                Laporan
            </a>
        </nav>

        <div class="menu logout">
            <a href="#">
                <i class="bi bi-box-arrow-right"></i>
                Keluar
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="navbar">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span>Admin</span>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>

</html>