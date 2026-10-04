@php
    $pengguna = auth()->user();
    $adminArea = $pengguna?->peran === 'admin' && request()->routeIs('admin.*');
    $penenunArea = $pengguna?->peran === 'penenun' && request()->routeIs('penenun.*');
    $halamanAutentikasi = request()->routeIs('masuk', 'daftar');
    $pembeliMasuk = $pengguna?->peran === 'pembeli';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#163f32">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Palugada Bandar')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="shell">
    @if($adminArea)
        <aside class="sidebar">
            <a href="{{ route('admin.beranda') }}" class="brand admin-brand">
                <div class="brand-mark" aria-hidden="true">PB</div>
                <div><strong>Palugada Bandar</strong><span>Panel Pengelola</span></div>
            </a>

            <div class="sidebar-label">PENGELOLAAN</div>
            <nav>
                <a href="{{ route('admin.beranda') }}" class="nav-link {{ request()->routeIs('admin.beranda') ? 'active' : '' }}"><span class="nav-icon">⌂</span> Beranda</a>
                <a href="{{ route('admin.produk.index') }}" class="nav-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}"><span class="nav-icon">◇</span> Produk & Stok</a>
                <a href="{{ route('admin.penenun.index') }}" class="nav-link {{ request()->routeIs('admin.penenun.*') ? 'active' : '' }}"><span class="nav-icon">◎</span> Penenun</a>
                <a href="{{ route('admin.target-produksi') }}" class="nav-link {{ request()->routeIs('admin.target-produksi') ? 'active' : '' }}"><span class="nav-icon">▤</span> Target Produksi <small>Segera</small></a>
                <a href="{{ route('admin.pesanan') }}" class="nav-link {{ request()->routeIs('admin.pesanan') ? 'active' : '' }}"><span class="nav-icon">▣</span> Pesanan <small>Segera</small></a>
                <a href="{{ route('admin.pembayaran') }}" class="nav-link {{ request()->routeIs('admin.pembayaran') ? 'active' : '' }}"><span class="nav-icon">◫</span> Pembayaran <small>Segera</small></a>
                <a href="{{ route('admin.laporan') }}" class="nav-link {{ request()->routeIs('admin.laporan') ? 'active' : '' }}"><span class="nav-icon">▥</span> Laporan <small>Segera</small></a>
            </nav>

            <div class="sidebar-bottom">
                <div class="profile-mini">
                    <div class="avatar">{{ strtoupper(substr($pengguna->nama, 0, 1)) }}</div>
                    <div><strong>{{ $pengguna->nama }}</strong><span>Admin</span></div>
                </div>
                <a href="{{ route('pembeli.katalog') }}" class="btn btn-soft btn-block">Lihat Website Pembeli</a>
                <form action="{{ route('keluar') }}" method="POST">@csrf<button class="btn btn-ghost btn-block">Keluar</button></form>
            </div>
        </aside>
    @elseif($penenunArea)
        <header class="worker-header">
            <a href="{{ route('penenun.beranda') }}" class="brand worker-brand"><div class="brand-mark">PB</div><div><strong>Palugada Penenun</strong><span>Ruang kerja pengrajin</span></div></a>
            <div class="worker-header-actions">
                <span class="worker-name">{{ $pengguna->nama }}</span>
                <form action="{{ route('keluar') }}" method="POST">@csrf<button class="btn btn-outline" type="submit">Keluar</button></form>
            </div>
        </header>
    @elseif(!$halamanAutentikasi)
        <header class="store-header">
            <div class="store-header-main">
                <a href="{{ route('pembeli.katalog') }}" class="brand store-brand">
                    <div class="brand-mark">PB</div>
                    <div><strong>Palugada Bandar</strong><span>Tenun Ikat</span></div>
                </a>

                <form class="store-search" method="GET" action="{{ route('pembeli.katalog') }}" role="search">
                    <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari kain, motif, warna, atau jenis..." aria-label="Cari produk">
                    @if(request('jenis'))<input type="hidden" name="jenis" value="{{ request('jenis') }}">@endif
                    <button type="submit" aria-label="Cari">⌕</button>
                </form>

                <div class="header-actions">
                    @if($pembeliMasuk)
                        <a class="header-action" href="{{ route('pembeli.akun') }}"><span>{{ strtoupper(substr($pengguna->nama,0,1)) }}</span><small>Akun Saya</small></a>
                        <a class="header-action" href="{{ route('pembeli.pesanan') }}"><span>▤</span><small>Pesanan</small></a>
                        <a class="header-action" href="{{ route('pembeli.keranjang') }}"><span>⌑</span><small>Keranjang</small><b>0</b></a>
                    @elseif($pengguna?->peran === 'admin')
                        <a class="header-action" href="{{ route('admin.beranda') }}"><span>⌂</span><small>Kembali ke Admin</small></a>
                    @elseif($pengguna?->peran === 'penenun')
                        <a class="header-action" href="{{ route('penenun.beranda') }}"><span>◎</span><small>Ruang Penenun</small></a>
                    @else
                        <a class="header-action" href="{{ route('masuk') }}"><span>♙</span><small>Masuk</small></a>
                        <a class="header-action" href="{{ route('daftar') }}"><span>＋</span><small>Daftar</small></a>
                    @endif
                </div>
            </div>

            <nav class="store-nav" aria-label="Navigasi utama">
                <a href="{{ route('pembeli.katalog') }}#beranda">Beranda</a>
                <a href="{{ route('pembeli.katalog') }}#kategori">Kategori</a>
                <a href="{{ route('pembeli.katalog') }}#produk">Koleksi</a>
                <a href="{{ route('pembeli.katalog') }}#tentang">Tentang</a>
                <a href="{{ route('pembeli.katalog') }}#bantuan">Bantuan</a>
                @if($pembeliMasuk)<a href="{{ route('pembeli.akun') }}">Akun Saya</a>@endif
            </nav>
        </header>
    @endif

    <main class="{{ $adminArea ? 'content' : ($penenunArea ? 'worker-content' : ($halamanAutentikasi ? 'auth-content' : 'store-content')) }}">
        @if(session('success'))<div class="toast success" role="status">{{ session('success') }}</div>@endif
        @yield('content')
    </main>

    @if(!$adminArea && !$penenunArea && !$halamanAutentikasi)
        <footer class="store-footer" id="bantuan">
            <div class="footer-grid">
                <div>
                    <a href="{{ route('pembeli.katalog') }}" class="brand footer-brand"><div class="brand-mark">PB</div><div><strong>Palugada Bandar</strong><span>Tenun Ikat</span></div></a>
                    <p>Tenun ikat dari Bandar yang kami tampilkan dengan informasi produk yang jelas dan pengalaman belanja yang sederhana.</p>
                </div>
                <div><h4>Belanja</h4><a href="{{ route('pembeli.katalog') }}#produk">Semua Produk</a><a href="{{ route('pembeli.katalog') }}#kategori">Kategori</a><a href="{{ route('pembeli.katalog') }}#tentang">Tentang Palugada</a></div>
                <div><h4>Akun</h4>@if($pembeliMasuk)<a href="{{ route('pembeli.akun') }}">Akun Saya</a><a href="{{ route('pembeli.pesanan') }}">Pesanan Saya</a><a href="{{ route('pembeli.profil') }}">Profil</a>@else<a href="{{ route('masuk') }}">Masuk</a><a href="{{ route('daftar') }}">Daftar</a>@endif</div>
                <div><h4>Bantuan</h4><span>Informasi produk</span><span>Stok tersedia</span><span>Kediri, Jawa Timur</span></div>
            </div>
            <div class="footer-bottom"><span>© {{ date('Y') }} Palugada Bandar</span><span>Dibuat agar mudah digunakan oleh pembeli, admin, dan penenun.</span></div>
        </footer>
    @endif
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
