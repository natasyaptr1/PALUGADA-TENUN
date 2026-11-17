
@extends('layouts.app')

@section('title', 'Laporan - Palugada Bandar')

@section('content')
@include('admin.laporan.partials.styles')

<div class="pl-report">
    <header class="pl-report-head">
        <p class="pl-report-eyebrow">Pusat Laporan</p>
        <h1>Laporan</h1>
        <p class="pl-report-subtitle">
            Pilih laporan untuk menelusuri aktivitas produksi, perubahan stok,
            dan penjualan Palugada Bandar.
        </p>
    </header>

    <div class="pl-report-index-grid">

        <a class="pl-report-index-card" href="{{ route('admin.laporan.produksi') }}">
            <span class="pl-report-index-icon">▤</span>
            <span class="pl-report-index-title">Laporan Produksi</span>
            <span class="pl-report-index-desc">
                Target produksi, realisasi hasil tenun, penenun,
                dan tanggal penyelesaian.
            </span>
            <span class="pl-report-index-link">Buka laporan <span>→</span></span>
        </a>

        <a class="pl-report-index-card" href="{{ route('admin.laporan.stok') }}">
            <span class="pl-report-index-icon">▥</span>
            <span class="pl-report-index-title">Laporan Stok</span>
            <span class="pl-report-index-desc">
                Riwayat stok masuk, stok keluar, penyesuaian,
                dan sumber perubahan persediaan.
            </span>
            <span class="pl-report-index-link">Buka laporan <span>→</span></span>
        </a>

        <a class="pl-report-index-card" href="{{ route('admin.laporan.penjualan') }}">
            <span class="pl-report-index-icon">▧</span>
            <span class="pl-report-index-title">Laporan Penjualan</span>
            <span class="pl-report-index-desc">
                Daftar pesanan, jumlah produk terjual, omzet transaksi
                berhasil, dan informasi pembayaran.
            </span>
            <span class="pl-report-index-link">Buka laporan <span>→</span></span>
        </a>

    </div>
</div>
@endsection
