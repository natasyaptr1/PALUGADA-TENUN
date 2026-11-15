@extends('layouts.app')

@section('title', 'Laporan - Palugada Bandar')

@section('content')
<style>
    .laporan-page {
        padding: 36px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .laporan-heading {
        margin-bottom: 30px;
    }

    .laporan-heading h1 {
        font-family: 'DM Serif Display', serif;
        color: #163f32;
        font-size: 36px;
        margin-bottom: 10px;
    }

    .laporan-heading p {
        color: #66746d;
        font-size: 15px;
    }

    .laporan-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .laporan-card {
        background: #fffdf9;
        border: 1px solid #e6e0d5;
        border-radius: 16px;
        padding: 28px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        text-decoration: none;
        color: #163f32;
        transition: transform .2s, box-shadow .2s;
    }

    a.laporan-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(22, 63, 50, .10);
    }

    .laporan-icon {
        width: 50px;
        height: 50px;
        background: #e7efe9;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
    }

    .laporan-card h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 24px;
        margin: 0;
    }

    .laporan-card p {
        font-size: 14px;
        color: #66746d;
        margin: 0;
        line-height: 1.6;
    }

    .laporan-link {
        font-weight: 700;
        font-size: 14px;
        color: #163f32;
        margin-top: 10px;
    }

    .laporan-disabled {
        opacity: .65;
        cursor: default;
    }

    @media (max-width: 768px) {
        .laporan-page {
            padding: 20px;
        }

        .laporan-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="laporan-page">

    <div class="laporan-heading">
        <h1>Laporan</h1>
        <p>
            Pantau aktivitas produksi, persediaan stok,
            penjualan, dan pembayaran Palugada Bandar.
        </p>
    </div>

    <div class="laporan-grid">

        {{-- LAPORAN PRODUKSI - PUTRI --}}
        <a href="{{ route('admin.laporan.produksi') }}"
           class="laporan-card">

            <div class="laporan-icon">&#128202;</div>

            <h2>Laporan Produksi</h2>

            <p>
                Lihat target produksi, hasil produksi penenun,
                sisa target, dan persentase pencapaian.
            </p>

            <span class="laporan-link">
                Lihat Laporan &rarr;
            </span>
        </a>

        {{-- LAPORAN STOK - PUTRI --}}
        <a href="{{ route('admin.laporan.stok') }}"
           class="laporan-card">

            <div class="laporan-icon">&#128230;</div>

            <h2>Laporan Stok</h2>

            <p>
                Pantau jumlah stok produk dan riwayat
                mutasi stok masuk maupun keluar.
            </p>

            <span class="laporan-link">
                Lihat Laporan &rarr;
            </span>
        </a>

        {{-- LAPORAN PENJUALAN - PANJOL --}}
        <div class="laporan-card laporan-disabled">

            <div class="laporan-icon">&#128200;</div>

            <h2>Laporan Penjualan</h2>

            <p>
                Lihat ringkasan transaksi penjualan
                dan perkembangan penjualan produk.
            </p>

            <span class="laporan-link">
                Segera tersedia
            </span>
        </div>

        {{-- LAPORAN PEMBAYARAN - PANJOL --}}
        <div class="laporan-card laporan-disabled">

            <div class="laporan-icon">&#128179;</div>

            <h2>Laporan Pembayaran</h2>

            <p>
                Pantau transaksi pembayaran Cash
                dan Midtrans.
            </p>

            <span class="laporan-link">
                Segera tersedia
            </span>
        </div>

    </div>
</div>
@endsection