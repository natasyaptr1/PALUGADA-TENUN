@extends('layouts.app')
@section('title','Beranda Admin - Palugada Bandar')
@section('content')
<div class="page-head">
    <div><span class="eyebrow">RINGKASAN USAHA</span><h1>Beranda Admin</h1><p>Lihat kondisi data utama Palugada Bandar dan masuk ke pekerjaan yang paling sering digunakan.</p></div>
    <div class="user-pill"><strong>{{ auth()->user()->nama }}</strong><span>Admin Palugada</span></div>
</div>

<div class="stats">
    <div class="stat"><span>Total Produk</span><strong>{{ $jumlahProduk }}</strong><small>produk tersimpan</small></div>
    <div class="stat"><span>Produk Ditampilkan</span><strong>{{ $jumlahProdukAktif }}</strong><small>muncul di katalog pembeli</small></div>
    <div class="stat"><span>Stok Tersedia</span><strong>{{ $stokTersedia }}</strong><small>unit siap dijual</small></div>
    <div class="stat"><span>Penenun</span><strong>{{ $jumlahPenenun }}</strong><small>akun penenun aktif/terdaftar</small></div>
</div>

<div class="admin-home-grid">
    <section class="panel admin-primary-panel">
        <div class="panel-title"><div><span class="eyebrow">AKSES CEPAT</span><h3>Pekerjaan utama</h3></div></div>
        <div class="admin-action-grid">
            <a class="admin-action-card" href="{{ route('admin.produk.index') }}"><span>◇</span><strong>Produk & Stok</strong><small>Kelola informasi produk dan lihat stok yang tersedia.</small><b>Kelola →</b></a>
            <a class="admin-action-card" href="{{ route('admin.penenun.index') }}"><span>◎</span><strong>Data Penenun</strong><small>Tambah, ubah, dan lihat akun penenun.</small><b>Kelola →</b></a>
            <a class="admin-action-card muted-card" href="{{ route('admin.target-produksi') }}"><span>▤</span><strong>Target Produksi</strong><small>Menu sudah disiapkan untuk pengembangan berikutnya.</small><b>Buka →</b></a>
            <a class="admin-action-card muted-card" href="{{ route('admin.pesanan') }}"><span>▣</span><strong>Pesanan</strong><small>Tempat memantau transaksi pembeli nanti.</small><b>Buka →</b></a>
        </div>
    </section>

    <aside class="panel admin-note-panel">
        <div class="panel-title"><div><span class="eyebrow">PERHATIAN</span><h3>Kondisi data</h3></div></div>
        <div class="admin-note-list">
            <div><span class="status-dot {{ $stokHabis > 0 ? 'warning' : 'ok' }}"></span><p><strong>{{ $stokHabis }} produk stok kosong</strong><small>Produk dengan stok 0 tetap bisa dikelola dari menu Produk & Stok.</small></p></div>
            <div><span class="status-dot ok"></span><p><strong>{{ $jumlahProdukAktif }} produk tampil di katalog</strong><small>Pembeli hanya melihat produk dengan status aktif.</small></p></div>
            <div><span class="status-dot ok"></span><p><strong>{{ $jumlahPenenun }} akun penenun tersedia</strong><small>Akun ini akan digunakan untuk aplikasi Android penenun.</small></p></div>
        </div>
    </aside>
</div>

<section class="panel admin-flow-panel">
    <div class="panel-title"><div><span class="eyebrow">ALUR KERJA</span><h3>Satu sistem, satu alur</h3></div><a href="{{ route('pembeli.katalog') }}" class="text-link">Lihat website pembeli →</a></div>
    <div class="business-flow">
        <div class="flow-step active"><span>1</span><strong>Produk</strong><small>Data dan stok</small></div><i>→</i>
        <div class="flow-step"><span>2</span><strong>Target Produksi</strong><small>Produk & penenun</small></div><i>→</i>
        <div class="flow-step"><span>3</span><strong>Hasil Produksi</strong><small>Produk selesai</small></div><i>→</i>
        <div class="flow-step"><span>4</span><strong>Pesanan</strong><small>Transaksi pembeli</small></div><i>→</i>
        <div class="flow-step"><span>5</span><strong>Pembayaran</strong><small>Status transaksi</small></div>
    </div>
</section>
@endsection
