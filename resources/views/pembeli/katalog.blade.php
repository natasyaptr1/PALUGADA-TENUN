@extends('layouts.app')
@section('title','Palugada Bandar - Tenun Ikat')
@section('content')
<section class="store-hero" id="beranda">
    <div class="hero-copy">
        @if(auth()->check())
            @if(auth()->user()->peran === 'pembeli')<span class="kicker">SELAMAT DATANG, {{ strtoupper(explode(' ', auth()->user()->nama)[0]) }}</span>@else<span class="kicker">TENUN IKAT PALUGADA BANDAR</span>@endif
        @else<span class="kicker">WARISAN BUDAYA DARI BANDAR</span>@endif
        <h1>Tenun ikat dengan cerita di setiap motif.</h1>
        <p>Jelajahi koleksi Palugada Bandar dengan informasi produk dan stok yang mudah dipahami.</p>
        <div class="hero-actions">
            <a href="#produk" class="btn btn-primary btn-lg">Lihat Koleksi <span>→</span></a>
            @if(auth()->user()?->peran === 'pembeli')<a href="{{ route('pembeli.akun') }}" class="btn btn-outline btn-lg">Buka Akun Saya</a>@else<a href="#tentang" class="btn btn-outline btn-lg">Kenali Palugada</a>@endif
        </div>
        <div class="hero-benefits">
            <div><span>✦</span><p><strong>Karya Lokal</strong><small>Tenun dari pengrajin Bandar</small></p></div>
            <div><span>◇</span><p><strong>Informasi Jelas</strong><small>Motif, ukuran, bahan, dan harga</small></p></div>
            <div><span>✓</span><p><strong>Stok Terlihat</strong><small>Ketersediaan produk mudah dicek</small></p></div>
        </div>
    </div>
    <div class="hero-visual" aria-label="Ilustrasi motif tenun Palugada">
        <div class="weave-card weave-a"><span>PALUGADA</span></div>
        <div class="weave-card weave-b"></div>
        <div class="weave-card weave-c"></div>
        <div class="hero-note">Lebih dari kain,<br><em>ini cerita budaya kita.</em></div>
    </div>
</section>

<section class="category-panel" id="kategori">
    <div class="section-heading inline">
        <div><span class="kicker">TEMUKAN YANG KAMU SUKA</span><h2>Jelajahi berdasarkan jenis</h2><p>Pilih kategori untuk mempersempit koleksi.</p></div>
        @if(request('jenis') || request('cari'))<a href="{{ route('pembeli.katalog') }}" class="text-link">Lihat semua →</a>@endif
    </div>
    <div class="category-scroll">
        <a class="category-item {{ !$jenis ? 'active' : '' }}" href="{{ route('pembeli.katalog') }}#produk"><span class="category-orb orb-all">✦</span><strong>Semua</strong><small>Seluruh koleksi</small></a>
        @foreach($jenisProduk as $namaJenis)
            <a class="category-item {{ $jenis === $namaJenis ? 'active' : '' }}" href="{{ route('pembeli.katalog',['jenis'=>$namaJenis]) }}#produk"><span class="category-orb">{{ strtoupper(substr($namaJenis,0,2)) }}</span><strong>{{ $namaJenis }}</strong><small>Lihat produk</small></a>
        @endforeach
    </div>
</section>

<section class="product-section" id="produk">
    <div class="section-heading inline">
        <div><span class="kicker">KOLEKSI PALUGADA</span><h2>{{ $cari || $jenis ? 'Hasil pencarian' : 'Pilihan tenun terbaru' }}</h2><p>@if($cari)Menampilkan hasil untuk “{{ $cari }}”.@elseif($jenis)Menampilkan jenis {{ $jenis }}.@else Lihat produk yang tersedia saat ini.@endif</p></div>
        <span class="count-chip">{{ $daftarProduk->count() }} produk</span>
    </div>

    <div class="store-product-grid">
        @forelse($daftarProduk as $produk)
            <article class="store-product-card">
                <div class="store-product-image">
                    @if($produk->foto)<img src="{{ asset('storage/'.$produk->foto) }}" alt="{{ $produk->nama_produk }}">@else<div class="fabric-placeholder"><span>{{ strtoupper(substr($produk->nama_produk,0,2)) }}</span></div>@endif
                    @if(($produk->stok?->jumlah_stok ?? 0) <= 0)<span class="sold-badge">Stok habis</span>@endif
                </div>
                <div class="product-body">
                    <span class="product-type">{{ $produk->jenis ?: 'Tenun Ikat' }}</span>
                    <h3>{{ $produk->nama_produk }}</h3>
                    <p class="product-desc">{{ $produk->motif ?: 'Motif Palugada' }} · {{ $produk->warna ?: 'Warna khas tenun' }}</p>
                    <div class="product-price-row"><strong>Rp {{ number_format($produk->harga,0,',','.') }}</strong><span>{{ ($produk->stok?->jumlah_stok ?? 0) > 0 ? 'Tersedia '.$produk->stok->jumlah_stok : 'Belum tersedia' }}</span></div>
                    <div class="product-card-actions">
                        <button type="button" class="btn btn-outline btn-sm js-product-detail" data-name="{{ $produk->nama_produk }}" data-code="{{ $produk->kode_produk }}" data-type="{{ $produk->jenis }}" data-motif="{{ $produk->motif }}" data-color="{{ $produk->warna }}" data-size="{{ $produk->ukuran }}" data-material="{{ $produk->material }}" data-price="Rp {{ number_format($produk->harga,0,',','.') }}" data-stock="{{ $produk->stok?->jumlah_stok ?? 0 }}" data-image="{{ $produk->foto ? asset('storage/'.$produk->foto) : '' }}">Lihat Detail</button>
                        @if(auth()->user()?->peran === 'pembeli')<a href="{{ route('pembeli.keranjang') }}" class="btn btn-primary btn-sm">Keranjang</a>@elseif(auth()->check())<button type="button" class="btn btn-secondary btn-sm" disabled>Khusus Pembeli</button>@else<a href="{{ route('masuk') }}" class="btn btn-primary btn-sm">Masuk untuk Belanja</a>@endif
                    </div>
                </div>
            </article>
        @empty
            <div class="empty-store"><span>◇</span><h3>Produk belum ditemukan</h3><p>Coba kata kunci lain atau kembali ke semua koleksi.</p><a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">Lihat Semua Produk</a></div>
        @endforelse
    </div>
</section>

<section class="story-grid" id="tentang">
    <article class="story-card story-light"><span class="kicker">CERITA PALUGADA</span><h2>Motif yang lahir dari tangan pengrajin lokal</h2><p>Palugada Bandar menghadirkan tenun ikat dan produk turunannya dengan identitas motif, warna, bahan, ukuran, serta harga yang ditampilkan secara jelas.</p><a href="#produk" class="text-link">Lihat koleksi →</a></article>
    <article class="story-card story-green"><span class="kicker">BELANJA LEBIH MUDAH</span><h2>Satu akun untuk pengalaman yang lebih rapi</h2><p>Pembeli yang sudah masuk memiliki halaman akun sendiri untuk melihat profil, keranjang, dan riwayat pesanan di satu tempat.</p>@if(auth()->user()?->peran === 'pembeli')<a href="{{ route('pembeli.akun') }}" class="btn btn-cream">Buka Akun Saya →</a>@elseif(!auth()->check())<a href="{{ route('daftar') }}" class="btn btn-cream">Buat Akun →</a>@endif</article>
</section>

<section class="service-strip" id="layanan">
    <div><span>◇</span><p><strong>Informasi Lengkap</strong><small>Detail produk mudah dibaca</small></p></div>
    <div><span>▣</span><p><strong>Stok Terlihat</strong><small>Ketersediaan ada di setiap produk</small></p></div>
    <div><span>✓</span><p><strong>Akun Pribadi</strong><small>Data dan aktivitas tersimpan rapi</small></p></div>
    <div><span>⌘</span><p><strong>Mudah Dipakai</strong><small>Navigasi sederhana dan konsisten</small></p></div>
</section>

<section class="join-banner" id="bantuan-info">
    <div class="join-art"><div class="loom-lines"></div></div>
    <div class="join-copy"><span class="kicker">BUTUH BANTUAN?</span><h2>Mulai dari koleksi, lalu lihat detail produk.</h2><p>Kamu bisa mencari berdasarkan nama, motif, warna, atau jenis. Jika sudah punya akun, semua menu pribadi tersedia dari tombol “Akun Saya”.</p><div class="join-actions">@if(auth()->user()?->peran === 'pembeli')<a class="btn btn-primary" href="{{ route('pembeli.akun') }}">Buka Akun Saya</a>@elseif(!auth()->check())<a class="btn btn-primary" href="{{ route('daftar') }}">Daftar Sekarang</a><a class="btn btn-outline" href="{{ route('masuk') }}">Masuk</a>@endif</div></div>
</section>

<div class="product-modal" id="productModal" aria-hidden="true">
    <div class="modal-backdrop js-modal-close"></div>
    <div class="product-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalProductName">
        <button class="modal-close js-modal-close" type="button" aria-label="Tutup">×</button>
        <div class="modal-image" id="modalProductImage"><div class="fabric-placeholder"><span>PB</span></div></div>
        <div class="modal-content"><span class="kicker" id="modalProductType">TENUN IKAT</span><h2 id="modalProductName">Detail Produk</h2><p id="modalProductCode" class="modal-code"></p><div class="modal-price" id="modalProductPrice"></div><dl class="detail-list"><div><dt>Motif</dt><dd id="modalMotif">-</dd></div><div><dt>Warna</dt><dd id="modalColor">-</dd></div><div><dt>Ukuran</dt><dd id="modalSize">-</dd></div><div><dt>Bahan</dt><dd id="modalMaterial">-</dd></div><div><dt>Stok</dt><dd id="modalStock">0</dd></div></dl>@if(auth()->user()?->peran === 'pembeli')<a href="{{ route('pembeli.keranjang') }}" class="btn btn-primary btn-block">Lihat Keranjang</a>@elseif(!auth()->check())<a href="{{ route('masuk') }}" class="btn btn-primary btn-block">Masuk untuk Belanja</a>@endif</div>
    </div>
</div>
@endsection
