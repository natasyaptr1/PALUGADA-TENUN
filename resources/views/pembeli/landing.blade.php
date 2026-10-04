@extends('layouts.app')
@section('title','Palugada Bandar - Tenun Ikat dari Bandar')
@section('content')
<section class="landing-hero landing-hero-v4">
    <div class="landing-hero-copy">
        <span class="kicker">TENUN IKAT PALUGADA BANDAR</span>
        <h1>Tenun ikat Bandar untuk cerita yang ingin kamu bawa pulang.</h1>
        <p>Lihat koleksi, kenali motif dan bahannya, lalu pilih tenun yang paling sesuai denganmu.</p>
        <div class="landing-actions">
            <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary btn-lg">Lihat Koleksi</a>
            <a href="{{ route('masuk') }}" class="btn btn-outline btn-lg">Masuk</a>
        </div>
        <div class="landing-trust-row">
            <div><span class="trust-icon">✦</span><p><strong>Tenun Bandar</strong><small>Karya pengrajin lokal Kediri yang terus dijaga</small></p></div>
            <div><span class="trust-icon">◇</span><p><strong>Motif Berkarakter</strong><small>Setiap tenun membawa warna dan identitas yang khas</small></p></div>
            <div><span class="trust-icon">✓</span><p><strong>Mudah Dipilih</strong><small>Detail produk dan ketersediaan dibuat lebih jelas</small></p></div>
        </div>
    </div>
    <div class="landing-hero-art" aria-label="Ilustrasi motif tenun Palugada Bandar">
        <div class="landing-weave weave-main"><span>PALUGADA</span><small>TENUN IKAT</small></div>
        <div class="landing-weave weave-mini-one"></div>
        <div class="landing-weave weave-mini-two"></div>
        <p>“Lebih dari kain,<br><em>ini cerita budaya kita.</em>”</p>
    </div>
</section>

<section class="landing-section landing-featured">
    <div class="section-heading inline landing-section-head-v4">
        <div>
            <span class="kicker">KOLEKSI PILIHAN</span>
            <h2>Temukan motif yang menarik perhatianmu</h2>
            <p>Beberapa koleksi terbaru dari Palugada Bandar.</p>
        </div>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-outline">Lihat Semua Koleksi</a>
    </div>
    <div class="store-product-grid landing-product-grid">
        @forelse($produkPilihan as $produk)
            @include('pembeli.partials.product-card', ['produk' => $produk])
        @empty
            <div class="landing-empty-products">
                <strong>Koleksi sedang disiapkan.</strong>
                <p>Produk terbaru Palugada Bandar akan tampil di sini.</p>
            </div>
        @endforelse
    </div>
</section>

<section class="landing-story" id="tentang">
    <article class="landing-story-copy">
        <span class="kicker">TENTANG PALUGADA</span>
        <h2>Karya tangan pengrajin lokal, disajikan lebih dekat untuk pelanggan.</h2>
        <p>Palugada Bandar menghadirkan tenun ikat dan produk turunannya dengan informasi yang mudah dipahami, sehingga pelanggan dapat mengenal setiap produk sebelum memilih.</p>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">Jelajahi Koleksi</a>
    </article>
    <div class="landing-story-visual">
        <div class="story-pattern pattern-one"></div>
        <div class="story-pattern pattern-two"></div>
        <div class="story-caption"><span>Bandar · Kediri</span><strong>Warisan yang terus hidup.</strong></div>
    </div>
</section>

<section class="landing-proof" id="keunggulan">
    <div class="landing-proof-heading">
        <span class="kicker">CERITA YANG MEMBUATNYA ISTIMEWA</span>
        <h2>Tenun Ikat Bandar bukan sekadar kain.</h2>
        <p>Tenun ikat khas Bandar Kidul telah menjadi bagian dari identitas Kota Kediri dan terus hadir dalam berbagai agenda budaya serta busana resmi.</p>
    </div>
    <div class="landing-proof-grid">
        <article class="landing-proof-card">
            <span class="proof-number">01</span>
            <h3>Warisan sejak awal 1900-an</h3>
            <p>Jejak tenun ikat tradisional Kota Kediri telah ditelusuri sejak awal 1900-an, dengan sentra pengrajin yang berkembang di Bandar Kidul.</p>
        </article>
        <article class="landing-proof-card">
            <span class="proof-number">02</span>
            <h3>Tampil di panggung Kota Kediri</h3>
            <p>Tenun ikat khas Kediri rutin diangkat melalui Dhoho Street Fashion, agenda yang menampilkan karya pengrajin dan desainer.</p>
        </article>
        <article class="landing-proof-card">
            <span class="proof-number">03</span>
            <h3>Dipakai dalam agenda kota</h3>
            <p>Pada Dhoho Night Carnival 2026, Wali Kota Kediri tampil mengenakan busana bernuansa Tenun Ikat Bandar Kediri.</p>
        </article>
    </div>
    <p class="landing-proof-source">Informasi diringkas dari publikasi resmi Pemerintah Kota Kediri tentang Tenun Ikat Bandar/Kediri.</p>
</section>

<section class="landing-cta landing-cta-v4">
    <div>
        <span class="kicker">SIAP MELIHAT KOLEKSI?</span>
        <h2>Mulai dari produk yang kamu suka.</h2>
        <p>Kamu tetap bisa melihat koleksi tanpa membuat akun terlebih dahulu.</p>
    </div>
    <div class="landing-cta-actions">
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-cream btn-lg">Lihat Koleksi</a>
        <a href="{{ route('daftar') }}" class="btn btn-soft btn-lg">Buat Akun</a>
    </div>
</section>

@include('pembeli.partials.product-modal')
@endsection
