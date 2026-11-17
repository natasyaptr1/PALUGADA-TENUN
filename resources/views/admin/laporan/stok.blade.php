@extends('layouts.app')

@section('title', 'Laporan Stok - Palugada Bandar')

@section('content')
@include('admin.laporan.partials.styles')
<div class="pl-report">
    <header class="pl-report-head">
        <a class="pl-report-back" href="{{ route('admin.laporan') }}">← Kembali ke Laporan</a>
        <p class="pl-report-eyebrow">Laporan / Stok</p>
        <h1>Laporan Mutasi Stok</h1>
        <p class="pl-report-subtitle">Riwayat perubahan persediaan akibat produksi, penjualan, dan penyesuaian. Untuk melihat atau mengelola stok terkini, gunakan menu Stok.</p>
    </header>

    <form class="pl-report-toolbar" method="GET" action="{{ route('admin.laporan.stok') }}">
        <label class="pl-report-search"><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk, kode, catatan, atau kode pesanan..." aria-label="Cari mutasi stok"></label>
        <details class="pl-report-filter">
            <summary><span aria-hidden="true">☷</span> Filter</summary>
            <div class="pl-report-filter-panel">
                <div class="pl-report-filter-grid">
                    <div class="pl-report-field"><label for="tanggal_mulai">Tanggal dari</label><input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"></div>
                    <div class="pl-report-field"><label for="tanggal_akhir">Tanggal sampai</label><input id="tanggal_akhir" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></div>
                    <div class="pl-report-field"><label for="jenis_mutasi">Jenis mutasi</label><select id="jenis_mutasi" name="jenis_mutasi"><option value="">Semua jenis</option><option value="masuk" @selected(request('jenis_mutasi') === 'masuk')>Masuk</option><option value="keluar" @selected(request('jenis_mutasi') === 'keluar')>Keluar</option><option value="penyesuaian" @selected(request('jenis_mutasi') === 'penyesuaian')>Penyesuaian</option></select></div>
                    <div class="pl-report-field"><label for="sumber">Sumber mutasi</label><select id="sumber" name="sumber"><option value="">Semua sumber</option><option value="produksi" @selected(request('sumber') === 'produksi')>Produksi</option><option value="penjualan" @selected(request('sumber') === 'penjualan')>Penjualan</option><option value="non_penjualan" @selected(request('sumber') === 'non_penjualan')>Non-penjualan</option></select></div>
                </div>
                <div class="pl-report-filter-actions"><a class="pl-report-btn pl-report-btn-light" href="{{ route('admin.laporan.stok') }}">Reset</a><button class="pl-report-btn pl-report-btn-primary" type="submit">Terapkan filter</button></div>
            </div>
        </details>
        <span class="pl-report-toolbar-spacer"></span>
        <a class="pl-report-btn pl-report-btn-pdf" href="{{ route('admin.laporan.stok.pdf', request()->query()) }}">↓ PDF</a>
        <a class="pl-report-btn pl-report-btn-excel" href="{{ route('admin.laporan.stok.excel', request()->query()) }}">↓ Excel</a>
    </form>

    @if ($errors->any())<div class="pl-report-error">{{ $errors->first() }}</div>@endif

    <div class="pl-report-summary">
        <span>Total mutasi: <strong>{{ $riwayatMutasi->total() }}</strong></span>
        <span>Jumlah masuk: <strong>{{ number_format($totalMasuk, 0, ',', '.') }} unit</strong></span>
        <span>Jumlah keluar: <strong>{{ number_format($totalKeluar, 0, ',', '.') }} unit</strong></span>
        <span>Penyesuaian: <strong>{{ number_format($totalPenyesuaian, 0, ',', '.') }} unit</strong></span>
    </div>

    <section class="pl-report-card">
        <div class="pl-report-card-head"><div><h2>Riwayat Mutasi Stok</h2><p>Setiap baris mewakili satu pencatatan perubahan stok.</p></div></div>
        <div class="pl-report-table-wrap">
            <table class="pl-report-table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Kode Produk</th><th>Produk</th><th>Jenis Mutasi</th><th>Jumlah</th><th>Sumber</th><th>Referensi</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse ($riwayatMutasi as $item)
                    @php($referensi = $item->detailPesanan?->pesanan?->kode_pesanan ?? ($item->hasilProduksi?->targetProduksi ? 'Target #' . $item->hasilProduksi->targetProduksi->id : '-'))
                    <tr>
                        <td>{{ $riwayatMutasi->firstItem() + $loop->index }}</td>
                        <td>{{ $item->tanggal_mutasi?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $item->stok?->produk?->kode_produk ?? '-' }}</td>
                        <td><strong>{{ $item->stok?->produk?->nama_produk ?? '-' }}</strong></td>
                        <td><span class="pl-report-badge {{ $item->jenis_mutasi === 'masuk' ? 'success' : ($item->jenis_mutasi === 'keluar' ? 'pending' : '') }}">{{ ucfirst($item->jenis_mutasi) }}</span></td>
                        <td>{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->sumber)) }}</td>
                        <td>{{ $referensi }}</td>
                        <td>{{ $item->catatan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="pl-report-empty">Belum ada riwayat mutasi yang sesuai dengan filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayatMutasi->hasPages())<div class="pl-report-pagination">{{ $riwayatMutasi->links() }}</div>@endif
    </section>
</div>
@endsection
