@extends('layouts.app')

@section('title', 'Laporan Produksi - Palugada Bandar')

@section('content')

<style>
    .laporan-produksi {
        max-width: 1300px;
        margin: 0 auto;
        padding: 30px;
        color: #234638;
    }

    .laporan-produksi * {
        box-sizing: border-box;
    }

    .laporan-produksi .back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: #163f32;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .laporan-produksi .back-link:hover {
        text-decoration: underline;
    }

    .laporan-produksi h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 34px;
        font-weight: 400;
        color: #163f32;
        margin: 0 0 10px;
    }

    .laporan-produksi .subtitle {
        font-size: 15px;
        color: #68776f;
        margin-bottom: 26px;
    }

    /* CARD FILTER TANGGAL */
    .laporan-produksi .filter-card {
        background: #fffdf9;
        border: 1px solid #e6e0d5;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .laporan-produksi .filter-card h2 {
        font-size: 19px;
        color: #163f32;
        margin: 0 0 20px;
    }

    .laporan-produksi .filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .laporan-produksi .filter-group {
        min-width: 0;
    }

    .laporan-produksi .filter-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #163f32;
        margin-bottom: 9px;
    }

    .laporan-produksi .filter-group input {
        display: block;
        width: 100%;
        padding: 12px 14px;
        background: #ffffff;
        border: 1px solid #d8ded7;
        border-radius: 8px;
        color: #234638;
        font-size: 14px;
    }

    .laporan-produksi .filter-group input:focus {
        outline: none;
        border-color: #163f32;
        box-shadow: 0 0 0 3px rgba(22, 63, 50, 0.10);
    }

    .laporan-produksi .filter-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
    }

    .laporan-produksi .btn-filter {
        display: inline-block;
        padding: 11px 24px;
        background: #163f32;
        border: 1px solid #163f32;
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }

    .laporan-produksi .btn-filter:hover {
        background: #225744;
    }

    .laporan-produksi .btn-reset {
        display: inline-block;
        padding: 11px 24px;
        background: #e7efe9;
        border: 1px solid #d8e5dc;
        border-radius: 8px;
        color: #163f32;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .laporan-produksi .btn-reset:hover {
        background: #dce9df;
    }

    .laporan-produksi .error-box {
        background: #fff0f0;
        border: 1px solid #e6b9b9;
        border-radius: 10px;
        padding: 14px 18px;
        color: #a52a2a;
        margin-bottom: 24px;
    }

    /* CARD RINGKASAN PRODUKSI */
    .laporan-produksi .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 26px;
    }

    .laporan-produksi .summary-card {
        background: #fffdf9;
        border: 1px solid #e6e0d5;
        border-radius: 14px;
        padding: 24px;
        min-height: 155px;
    }

    .laporan-produksi .summary-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #66746d;
        margin-bottom: 16px;
    }

    .laporan-produksi .summary-value {
        display: block;
        font-family: 'DM Serif Display', serif;
        font-size: 34px;
        color: #163f32;
        margin-bottom: 10px;
        line-height: 1.2;
    }

    .laporan-produksi .summary-description {
        font-size: 13px;
        color: #77847b;
    }

    /* CARD TABEL PRODUKSI */
    .laporan-produksi .table-card {
        background: #fffdf9;
        border: 1px solid #e6e0d5;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .laporan-produksi .table-card h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 24px;
        font-weight: 400;
        color: #163f32;
        margin: 0 0 20px;
    }

    .laporan-produksi .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .laporan-produksi .production-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #cbd7cf;
        font-size: 13px;
        background: #ffffff;
    }

    .laporan-produksi .production-table thead {
        background: #e7efe9;
    }

    .laporan-produksi .production-table th {
        padding: 14px 12px;
        border: 1px solid #cbd7cf;
        color: #163f32;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }

    .laporan-produksi .production-table td {
        padding: 13px 12px;
        border: 1px solid #d8e0da;
        color: #354b40;
        vertical-align: middle;
    }

    .laporan-produksi .production-table tbody tr:nth-child(even) {
        background: #f7faf7;
    }

    .laporan-produksi .production-table tbody tr:hover {
        background: #edf4ee;
    }

    .laporan-produksi .production-table .text-center {
        text-align: center;
    }

    .laporan-produksi .production-table .text-nowrap {
        white-space: nowrap;
    }

    .laporan-produksi .empty-data {
        text-align: center;
        padding: 28px !important;
        color: #77847b;
    }

    .laporan-produksi .pagination-wrap {
        margin-top: 20px;
    }

    @media (max-width: 900px) {
        .laporan-produksi .summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .laporan-produksi {
            padding: 18px;
        }

        .laporan-produksi .filter-grid {
            grid-template-columns: 1fr;
        }

        .laporan-produksi .filter-card,
        .laporan-produksi .summary-card,
        .laporan-produksi .table-card {
            padding: 18px;
        }

        .laporan-produksi h1 {
            font-size: 28px;
        }
    }
</style>

<div class="laporan-produksi">

    {{-- TOMBOL KEMBALI --}}
    <a href="{{ route('admin.laporan') }}" class="back-link">
        &larr; Kembali ke Laporan
    </a>

    {{-- JUDUL HALAMAN --}}
    <h1>Laporan Produksi</h1>

    <p class="subtitle">
        Rekapitulasi target dan hasil produksi tenun PALUGADA.
    </p>

    {{-- FILTER PERIODE PRODUKSI --}}
    <div class="filter-card">

        <h2>Filter Periode Produksi</h2>

        <form method="GET" action="{{ route('admin.laporan.produksi') }}">

            <div class="filter-grid">

                {{-- TANGGAL MULAI --}}
                <div class="filter-group">
                    <label for="tanggal_mulai">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        id="tanggal_mulai"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                    >
                </div>

                {{-- TANGGAL AKHIR --}}
                <div class="filter-group">
                    <label for="tanggal_akhir">
                        Tanggal Akhir
                    </label>

                    <input
                        type="date"
                        id="tanggal_akhir"
                        name="tanggal_akhir"
                        value="{{ request('tanggal_akhir') }}"
                    >
                </div>

            </div>

            <div class="filter-actions">

                <button type="submit" class="btn-filter">
                    Filter
                </button>

                <a href="{{ route('admin.laporan.produksi') }}"
                   class="btn-reset">
                    Reset
                </a>

            </div>

        </form>
    </div>

    {{-- PESAN ERROR FILTER --}}
    @if ($errors->any())
        <div class="error-box">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- RINGKASAN PRODUKSI --}}
    <div class="summary-grid">

        {{-- TOTAL TARGET PRODUKSI --}}
        <div class="summary-card">

            <span class="summary-label">
                Total Target Produksi
            </span>

            <strong class="summary-value">
                {{ number_format($totalTarget, 0, ',', '.') }}
            </strong>

            <span class="summary-description">
                Jumlah produk ditargetkan
            </span>

        </div>

        {{-- TOTAL HASIL PRODUKSI --}}
        <div class="summary-card">

            <span class="summary-label">
                Total Hasil Produksi
            </span>

            <strong class="summary-value">
                {{ number_format($totalSelesai, 0, ',', '.') }}
            </strong>

            <span class="summary-description">
                Jumlah produk selesai
            </span>

        </div>

        {{-- PENCAPAIAN PRODUKSI --}}
        <div class="summary-card">

            <span class="summary-label">
                Pencapaian Produksi
            </span>

            <strong class="summary-value">
                {{ number_format($persentase, 1, ',', '.') }}%
            </strong>

            <span class="summary-description">
                Persentase realisasi target
            </span>

        </div>

    </div>

    {{-- TABEL DETAIL PRODUKSI --}}
    <div class="table-card">

        <h2>Detail Produksi</h2>

        <div class="table-responsive">

            <table class="production-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Produk</th>
                        <th>Penenun</th>
                        <th>Target</th>
                        <th>Hasil</th>
                        <th>Sisa</th>
                        <th>Pencapaian</th>
                        <th>Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($laporan as $item)

                        @php
                            $target = (int) $item->jumlah_target;
                            $hasil = (int) ($item->total_selesai ?? 0);
                            $sisa = max(0, $target - $hasil);

                            $capaian = $target > 0
                                ? round(($hasil / $target) * 100, 1)
                                : 0;
                        @endphp

                        <tr>

                            <td>
                                {{ $laporan->firstItem() + $loop->index }}
                            </td>

                            <td>
                                {{ $item->produk?->nama_produk ?? '-' }}
                            </td>

                            <td>
                                {{ $item->penenun?->nama ?? '-' }}
                            </td>

                            <td>
                                {{ number_format($target, 0, ',', '.') }}
                            </td>

                            <td>
                                {{ number_format($hasil, 0, ',', '.') }}
                            </td>

                            <td>
                                {{ number_format($sisa, 0, ',', '.') }}
                            </td>

                            <td class="text-nowrap">
                                {{ number_format($capaian, 1, ',', '.') }}%
                            </td>

                            <td class="text-nowrap">
                                {{ $item->tanggal_deadline?->format('d-m-Y') ?? '-' }}
                            </td>

                            <td>
                                {{ ucfirst($item->status ?? '-') }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="9" class="empty-data">
                                Belum ada data produksi.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- PAGINATION --}}
        <div class="pagination-wrap">
            {{ $laporan->links() }}
        </div>

    </div>

</div>

@endsection