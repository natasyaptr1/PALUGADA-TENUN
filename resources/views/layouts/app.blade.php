@php
    $pengguna = auth()->user();
    $adminArea = $pengguna?->peran === 'admin' && request()->routeIs('admin.*');
    $penenunArea = $pengguna?->peran === 'penenun' && request()->routeIs('penenun.*');
    $halamanAutentikasi = request()->routeIs('masuk', 'daftar');
    $pembeliMasuk = $pengguna?->peran === 'pembeli';
    $pembeliArea = $pembeliMasuk && request()->routeIs('pembeli.*');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#163f32">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Palugada Bandar')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/target-produksi-panjol-revisi.css') }}">
    <link rel="stylesheet" href="{{ asset('css/palugada-ui-konsisten.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pesanan-admin.css') }}">
</head>
<body>
<div class="shell">
    @if($adminArea)
        <aside class="sidebar admin-sidebar">
            <a href="{{ route('admin.beranda') }}" class="brand admin-brand">
                <div class="brand-mark" aria-hidden="true">PB</div>
                <div><strong>Palugada Bandar</strong><span>Dashboard Admin</span></div>
            </a>

            <p class="sidebar-label">MENU UTAMA</p>
            <nav class="admin-nav" aria-label="Menu admin">
                <a href="{{ route('admin.beranda') }}" class="nav-link {{ request()->routeIs('admin.beranda') ? 'active' : '' }}"><span class="nav-dot"></span><span>Beranda</span></a>
                <a href="{{ route('admin.produk.index') }}" class="nav-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}"><span class="nav-dot"></span><span>Produk</span></a>
                <a href="{{ route('admin.jenis-produk.index') }}" class="nav-link {{ request()->routeIs('admin.jenis-produk.*', 'admin.jenis_produk.*') ? 'active' : '' }}"><span class="nav-dot"></span><span>Jenis Produk</span></a>
                <a href="{{ route('admin.stok') }}" class="nav-link {{ request()->routeIs('admin.stok') ? 'active' : '' }}"><span class="nav-dot"></span><span>Stok</span></a>
                <a href="{{ route('admin.penenun.index') }}" class="nav-link {{ request()->routeIs('admin.penenun.*') ? 'active' : '' }}"><span class="nav-dot"></span><span>Data Penenun</span></a>
                <a href="{{ route('admin.target-produksi') }}" class="nav-link {{ request()->routeIs('admin.target-produksi') ? 'active' : '' }}"><span class="nav-dot"></span><span>Target Produksi</span></a>
                <a href="{{ route('admin.pesanan.index') }}" class="nav-link {{ request()->routeIs('admin.pesanan.*') ? 'active' : '' }}"><span class="nav-dot"></span><span>Pesanan</span></a>
                <a href="{{ route('admin.pembayaran') }}" class="nav-link {{ request()->routeIs('admin.pembayaran') ? 'active' : '' }}"><span class="nav-dot"></span><span>Pembayaran</span></a>
                <a href="{{ route('admin.laporan') }}" class="nav-link {{ request()->routeIs('admin.laporan') ? 'active' : '' }}"><span class="nav-dot"></span><span>Laporan</span></a>
            </nav>
        </aside>

        <main class="content admin-content">
            <header class="admin-topbar">
                <div>
                    <span class="admin-topbar-label">PANEL PENGELOLA</span>
                    <strong>Palugada Bandar</strong>
                </div>
                <div class="admin-user-menu">
                    <div class="admin-avatar">{{ strtoupper(substr($pengguna->nama, 0, 1)) }}</div>
                    <div class="admin-user-copy">
                        <strong>{{ $pengguna->nama }}</strong>
                        <span>{{ $pengguna->email }}</span>
                    </div>
                    <form action="{{ route('keluar') }}" method="POST">
                        @csrf
                        <button class="btn btn-logout" type="submit">Keluar</button>
                    </form>
                </div>
            </header>
            @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="toast error" role="alert">{{ session('error') }}</div>@endif
            @yield('content')
        </main>

    @elseif($penenunArea)
        <header class="worker-header">
            <a href="{{ route('penenun.beranda') }}" class="brand worker-brand"><div class="brand-mark">PB</div><div><strong>Palugada Penenun</strong><span>Ruang kerja pengrajin</span></div></a>
            <div class="worker-header-actions">
                <span class="worker-name">{{ $pengguna->nama }}</span>
                <form action="{{ route('keluar') }}" method="POST">@csrf<button class="btn btn-outline" type="submit">Keluar</button></form>
            </div>
        </header>
        <main class="worker-content">
            @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="toast error" role="alert">{{ session('error') }}</div>@endif
            @yield('content')
        </main>

    @elseif($pembeliArea)
        <header class="member-header">
            <div class="member-header-inner">
                <a href="{{ route('pembeli.katalog') }}" class="brand member-brand" aria-label="Koleksi Palugada Bandar">
                    <div class="brand-mark">PB</div>
                    <div><strong>Palugada Bandar</strong><span>Tenun Ikat</span></div>
                </a>
                <nav class="member-nav" aria-label="Menu pembeli">
                    <a href="{{ route('pembeli.katalog') }}" class="{{ request()->routeIs('pembeli.katalog') ? 'active' : '' }}">Koleksi</a>
                    <a href="{{ route('pembeli.pesanan') }}" class="{{ request()->routeIs('pembeli.pesanan') ? 'active' : '' }}">Pesanan</a>
                    <a href="{{ route('pembeli.keranjang') }}" class="{{ request()->routeIs('pembeli.keranjang') ? 'active' : '' }}">Keranjang</a>
                </nav>
                <div class="member-user">
                    <a href="{{ route('pembeli.profil') }}" class="member-user-link {{ request()->routeIs('pembeli.profil') ? 'active' : '' }}">
                        <span class="member-avatar">{{ strtoupper(substr($pengguna->nama, 0, 1)) }}</span>
                        <span><strong>{{ $pengguna->nama }}</strong><small>Profil</small></span>
                    </a>
                    <form action="{{ route('keluar') }}" method="POST">@csrf<button class="member-logout" type="submit">Keluar</button></form>
                </div>
            </div>
        </header>
        <main class="member-content">
            @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="toast error" role="alert">{{ session('error') }}</div>@endif
            @yield('content')
        </main>
        <footer class="member-footer"><span>© {{ date('Y') }} Palugada Bandar</span><a href="{{ route('pembeli.katalog') }}">Lihat koleksi</a></footer>

    @elseif(!$halamanAutentikasi)
        <header class="store-header">
            <div class="store-header-main public-simple-header">
                <a href="{{ route('landing') }}" class="brand store-brand">
                    <div class="brand-mark">PB</div>
                    <div><strong>Palugada Bandar</strong><span>Tenun Ikat</span></div>
                </a>

                <nav class="public-nav" aria-label="Navigasi utama">
                    <a href="{{ route('landing') }}">Beranda</a>
                    <a href="{{ route('pembeli.katalog') }}">Koleksi</a>
                    <a href="{{ route('landing') }}#tentang">Tentang</a>
                    <a href="{{ route('landing') }}#keunggulan">Keunggulan</a>
                </nav>

                <div class="public-actions">
                    @if($pengguna?->peran === 'admin')
                        <a class="btn btn-outline" href="{{ route('admin.beranda') }}">Dashboard Admin</a>
                    @elseif($pengguna?->peran === 'penenun')
                        <a class="btn btn-outline" href="{{ route('penenun.beranda') }}">Ruang Penenun</a>
                    @else
                        <a class="btn btn-outline" href="{{ route('masuk') }}">Masuk</a>
                        <a class="btn btn-primary" href="{{ route('daftar') }}">Daftar</a>
                    @endif
                </div>
            </div>
        </header>

        <main class="store-content">
            @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="toast error" role="alert">{{ session('error') }}</div>@endif
            @yield('content')
        </main>

        <footer class="store-footer store-footer-final" id="bantuan">
            <div class="footer-final-main">
                <div class="footer-final-brand">
                    <a href="{{ route('landing') }}" class="brand footer-brand-final">
                        <div class="brand-mark">PB</div>
                        <div><strong>Palugada Bandar</strong><span>Tenun Ikat</span></div>
                    </a>
                    <p>Tenun ikat khas Bandar dengan koleksi yang mudah dilihat dan informasi produk yang jelas.</p>
                </div>

                <nav class="footer-final-group" aria-label="Jelajahi Palugada">
                    <strong>Jelajahi</strong>
                    <a href="{{ route('pembeli.katalog') }}">Koleksi</a>
                    <a href="{{ route('landing') }}#tentang">Tentang Palugada</a>
                    <a href="{{ route('landing') }}#keunggulan">Keunggulan</a>
                </nav>

                <div class="footer-final-group footer-final-location">
                    <strong>Kunjungi</strong>
                    <span>Bandar, Kediri</span>
                    <span>Jawa Timur</span>
                </div>
            </div>

            <div class="footer-final-bottom">
                <span>© {{ date('Y') }} Palugada Bandar</span>
            </div>
        </footer>

    @else
        <main class="auth-content">
            @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="toast error" role="alert">{{ session('error') }}</div>@endif
            @yield('content')
        </main>
    @endif
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
