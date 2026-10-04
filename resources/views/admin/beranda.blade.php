@extends('layouts.app')
@section('title','Dashboard Admin - Palugada Bandar')
@section('content')
<section class="admin-page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">DASHBOARD ADMIN</span>
        <h1>Selamat datang, {{ auth()->user()->nama }}</h1>
        <p>Ringkasan kondisi usaha yang perlu dilihat hari ini.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.produk.create') }}">Tambah Produk</a>
</section>

<section class="admin-stat-grid admin-stat-grid-v4" aria-label="Ringkasan usaha">
    <article class="admin-stat-card admin-stat-card-v4">
        <div><span class="admin-stat-label">Total Pesanan</span><strong>{{ $jumlahPesanan }}</strong><p>Pesanan yang tercatat</p></div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div><span class="admin-stat-label">Pemasukan Bulan Ini</span><strong class="admin-money">Rp {{ number_format($pemasukanBulanIni,0,',','.') }}</strong><p>Tahun ini Rp {{ number_format($pemasukanTahunIni,0,',','.') }}</p></div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div><span class="admin-stat-label">Target Belum Selesai</span><strong>{{ $targetBelumSelesai }}</strong><p>Target produksi masih berjalan</p></div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4 {{ $stokMenipis > 0 ? 'needs-attention' : '' }}">
        <div><span class="admin-stat-label">Stok Menipis</span><strong>{{ $stokMenipis }}</strong><p>Produk dengan stok 3 atau kurang</p></div>
    </article>
</section>

<section class="admin-panel admin-orders-v4 admin-orders-v4-full">
    <div class="admin-panel-head admin-panel-head-inline">
        <div><span class="eyebrow">PESANAN TERBARU</span><h2>Aktivitas penjualan</h2></div>
        <a class="text-link" href="{{ route('admin.pesanan') }}">Lihat Pesanan</a>
    </div>

    @if($pesananTerbaru->isEmpty())
        <div class="admin-empty-v4">
            <span>◇</span>
            <div><strong>Belum ada pesanan</strong><p>Pesanan terbaru akan muncul di sini ketika transaksi pembeli mulai berjalan.</p></div>
        </div>
    @else
        <div class="admin-order-list-v4">
            @foreach($pesananTerbaru as $pesanan)
                <div class="admin-order-row-v4">
                    <div><small>Kode Pesanan</small><strong>{{ $pesanan->kode_pesanan }}</strong></div>
                    <div><small>Status</small><strong>{{ ucfirst(str_replace('_',' ',$pesanan->status_pesanan)) }}</strong></div>
                    <div><small>Total</small><strong>Rp {{ number_format($pesanan->total,0,',','.') }}</strong></div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
