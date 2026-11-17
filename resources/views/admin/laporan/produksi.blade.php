@extends('layouts.app')

@section('title', 'Laporan Produksi - Palugada Bandar')

@section('content')
@include('admin.laporan.partials.styles')
<div class="pl-report">
    <header class="pl-report-head">
        <a class="pl-report-back" href="{{ route('admin.laporan') }}">← Kembali ke Laporan</a>
        <p class="pl-report-eyebrow">Laporan / Produksi</p>
        <h1>Laporan Produksi</h1>
        <p class="pl-report-subtitle">Rekap target dan hasil produksi tenun berdasarkan produk, penenun, dan periode target.</p>
    </header>

    <form class="pl-report-toolbar" method="GET" action="{{ route('admin.laporan.produksi') }}">
        <label class="pl-report-search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk, kode, atau nama penenun..." aria-label="Cari laporan produksi">
        </label>
        <details class="pl-report-filter">
            <summary><span aria-hidden="true">☷</span> Filter</summary>
            <div class="pl-report-filter-panel">
                <div class="pl-report-filter-grid">
                    <div class="pl-report-field"><label for="tanggal_mulai">Mulai target dari</label><input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"></div>
                    <div class="pl-report-field"><label for="tanggal_akhir">Mulai target sampai</label><input id="tanggal_akhir" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></div>
                    <div class="pl-report-field"><label for="status">Status produksi</label><select id="status" name="status"><option value="">Semua status</option><option value="berjalan" @selected(request('status') === 'berjalan')>Berjalan</option><option value="selesai" @selected(request('status') === 'selesai')>Selesai</option><option value="dibatalkan" @selected(request('status') === 'dibatalkan')>Dibatalkan</option></select></div>
                </div>
                <div class="pl-report-filter-actions"><a class="pl-report-btn pl-report-btn-light" href="{{ route('admin.laporan.produksi') }}">Reset</a><button class="pl-report-btn pl-report-btn-primary" type="submit">Terapkan filter</button></div>
            </div>
        </details>
        <span class="pl-report-toolbar-spacer"></span>
        <a class="pl-report-btn pl-report-btn-pdf" href="{{ route('admin.laporan.produksi.pdf', request()->query()) }}">↓ PDF</a>
        <a class="pl-report-btn pl-report-btn-excel" href="{{ route('admin.laporan.produksi.excel', request()->query()) }}">↓ Excel</a>
    </form>

    @if ($errors->any())<div class="pl-report-error">{{ $errors->first() }}</div>@endif

    <div class="pl-report-summary">
        <span>Data ditemukan: <strong>{{ $laporan->total() }} target</strong></span>
        <span>Per halaman: <strong>{{ $laporan->count() }}</strong></span>
        <span>Tanggal penyelesaian aktual diambil dari catatan hasil produksi terakhir pada target yang selesai.</span>
    </div>

    <section class="pl-report-card">
        <div class="pl-report-card-head"><div><h2>Detail Target dan Realisasi Produksi</h2><p>Hasil produksi merupakan akumulasi seluruh catatan hasil pada setiap target.</p></div></div>
        <div class="pl-report-table-wrap">
            <table class="pl-report-table">
                <thead><tr><th>No.</th><th>Produk</th><th>Penenun</th><th>Tanggal Mulai</th><th>Target Selesai</th><th>Selesai Aktual</th><th>Target</th><th>Hasil</th><th>Sisa</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($laporan as $item)
                    @php($hasil = (int) ($item->total_selesai ?? 0))
                    <tr>
                        <td>{{ $laporan->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $item->produk?->nama_produk ?? '-' }}</strong><br><small class="pl-report-muted">{{ $item->produk?->kode_produk ?? '-' }}</small></td>
                        <td>{{ $item->penenun?->nama ?? '-' }}</td>
                        <td>{{ $item->tanggal_mulai?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $item->tanggal_deadline?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $item->status === 'selesai' && $item->tanggal_selesai_aktual ? \Illuminate\Support\Carbon::parse($item->tanggal_selesai_aktual)->format('d/m/Y') : '-' }}</td>
                        <td>{{ number_format($item->jumlah_target, 0, ',', '.') }}</td>
                        <td>{{ number_format($hasil, 0, ',', '.') }}</td>
                        <td>{{ number_format(max(0, (int) $item->jumlah_target - $hasil), 0, ',', '.') }}</td>
                        <td><span class="pl-report-badge {{ $item->status === 'selesai' ? 'success' : ($item->status === 'dibatalkan' ? 'danger' : 'pending') }}">{{ ucfirst($item->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="pl-report-empty">Tidak ada data produksi yang sesuai dengan pencarian atau filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($laporan->hasPages())<div class="pl-report-pagination">{{ $laporan->links() }}</div>@endif
    </section>
</div>
@endsection
