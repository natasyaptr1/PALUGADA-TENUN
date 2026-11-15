@extends('layouts.app')

@section('title', 'Laporan Stok - Palugada Bandar')

@section('content')
<style>
    .laporan-stok {
        max-width: 1250px;
        margin: 0 auto;
        padding: 28px;
        color: #234638;
    }

    .laporan-stok h1 {
        font-size: 30px;
        color: #163f32;
        margin: 10px 0;
    }

    .laporan-stok h2 {
        font-size: 21px;
        color: #163f32;
        margin: 0 0 20px;
    }

    .laporan-stok .subtitle {
        color: #68776f;
        margin-bottom: 25px;
    }

    .laporan-stok .back-link {
        color: #163f32;
        text-decoration: none;
        font-weight: 600;
    }

    .laporan-stok .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 25px;
    }

    .laporan-stok .summary-card,
    .laporan-stok .report-card {
        background: #fffdf9;
        border: 1px solid #e3e4da;
        border-radius: 12px;
        padding: 22px;
    }

    .laporan-stok .summary-card span {
        display: block;
        color: #69766f;
        margin-bottom: 12px;
    }

    .laporan-stok .summary-card strong {
        font-size: 30px;
        color: #163f32;
    }

    .laporan-stok .report-card {
        margin-bottom: 22px;
    }

    .laporan-stok .table-wrap {
        overflow-x: auto;
    }

    .laporan-stok table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .laporan-stok th {
        background: #e7efe9;
        color: #163f32;
        text-align: left;
        padding: 12px;
        white-space: nowrap;
    }

    .laporan-stok td {
        padding: 12px;
        border-bottom: 1px solid #e8e3d9;
    }

    .laporan-stok .empty {
        text-align: center;
        padding: 25px;
        color: #69766f;
    }

    .laporan-stok .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 16px;
    }

    .laporan-stok label {
        display: block;
        font-weight: 600;
        margin-bottom: 7px;
    }

    .laporan-stok input,
    .laporan-stok select {
        width: 100%;
        padding: 10px;
        border: 1px solid #d9d6cc;
        border-radius: 7px;
        box-sizing: border-box;
    }

    .laporan-stok .btn-filter {
        background: #163f32;
        color: white;
        padding: 11px 18px;
        border: none;
        border-radius: 7px;
        cursor: pointer;
    }

    .laporan-stok .btn-reset {
        color: #163f32;
        margin-left: 12px;
        text-decoration: none;
    }

    .laporan-stok .mutasi-summary {
        display: flex;
        gap: 25px;
        margin: 22px 0;
        flex-wrap: wrap;
    }

    .laporan-stok .pagination-wrap {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .laporan-stok {
            padding: 15px;
        }

        .laporan-stok .summary-grid,
        .laporan-stok .filter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="laporan-stok">

    <a href="{{ route('admin.laporan') }}" class="back-link">
        &larr; Kembali ke Laporan
    </a>

    <h1>Laporan Stok</h1>

    <p class="subtitle">
        Ringkasan persediaan produk dan riwayat mutasi stok Palugada Bandar.
    </p>

    <div class="summary-grid">
        <div class="summary-card">
            <span>Jenis Produk Tercatat</span>
            <strong>{{ number_format($totalJenisProduk, 0, ',', '.') }}</strong>
        </div>

        <div class="summary-card">
            <span>Total Stok Saat Ini</span>
            <strong>{{ number_format($totalStok, 0, ',', '.') }}</strong>
        </div>

        <div class="summary-card">
            <span>Produk Stok Habis</span>
            <strong>{{ number_format($stokHabis, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="report-card">
        <h2>Daftar Stok Produk</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th>Jumlah Stok</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarStok as $item)
                        <tr>
                            <td>{{ $daftarStok->firstItem() + $loop->index }}</td>
                            <td>{{ $item->produk?->kode_produk ?? '-' }}</td>
                            <td>{{ $item->produk?->nama_produk ?? '-' }}</td>
                            <td>{{ number_format($item->jumlah_stok, 0, ',', '.') }}</td>
                            <td>
                                {{ $item->jumlah_stok > 0 ? 'Tersedia' : 'Habis' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                Belum ada data stok produk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $daftarStok->links() }}
        </div>
    </div>

    <div class="report-card">
        <h2>Riwayat Mutasi Stok</h2>

        <form method="GET" action="{{ route('admin.laporan.stok') }}">
            <div class="filter-grid">
                <div>
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai"
                           name="tanggal_mulai"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div>
                    <label for="tanggal_akhir">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir"
                           name="tanggal_akhir"
                           value="{{ request('tanggal_akhir') }}">
                </div>

                <div>
                    <label for="jenis_mutasi">Jenis Mutasi</label>
                    <select id="jenis_mutasi" name="jenis_mutasi">
                        <option value="">Semua Mutasi</option>
                        <option value="masuk" {{ request('jenis_mutasi') === 'masuk' ? 'selected' : '' }}>
                            Masuk
                        </option>
                        <option value="keluar" {{ request('jenis_mutasi') === 'keluar' ? 'selected' : '' }}>
                            Keluar
                        </option>
                        <option value="penyesuaian" {{ request('jenis_mutasi') === 'penyesuaian' ? 'selected' : '' }}>
                            Penyesuaian
                        </option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-filter">
                Terapkan Filter
            </button>

            <a href="{{ route('admin.laporan.stok') }}" class="btn-reset">
                Reset
            </a>
        </form>

        @if ($errors->any())
            <p style="color: #a52a2a;">
                {{ $errors->first() }}
            </p>
        @endif

        <div class="mutasi-summary">
            <div>
                Total Stok Masuk:
                <strong>{{ number_format($totalMasuk, 0, ',', '.') }}</strong>
            </div>

            <div>
                Total Stok Keluar:
                <strong>{{ number_format($totalKeluar, 0, ',', '.') }}</strong>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Jenis Mutasi</th>
                        <th>Jumlah</th>
                        <th>Sumber</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($riwayatMutasi as $item)
                        <tr>
                            <td>{{ $riwayatMutasi->firstItem() + $loop->index }}</td>
                            <td>{{ $item->tanggal_mutasi?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>{{ $item->stok?->produk?->nama_produk ?? '-' }}</td>
                            <td>{{ ucfirst($item->jenis_mutasi) }}</td>
                            <td>{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $item->sumber)) }}</td>
                            <td>{{ $item->catatan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                Belum ada riwayat mutasi stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $riwayatMutasi->links() }}
        </div>
    </div>
</div>
@endsection