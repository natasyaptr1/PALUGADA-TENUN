@extends('layouts.app')

@section('title', 'Laporan Penjualan - Palugada Bandar')

@section('content')
@include('admin.laporan.partials.styles')
<div class="pl-report">
    <header class="pl-report-head">
        <a class="pl-report-back" href="{{ route('admin.laporan') }}">← Kembali ke Laporan</a>
        <p class="pl-report-eyebrow">Laporan / Penjualan</p>
        <h1>Laporan Penjualan</h1>
        <p class="pl-report-subtitle">Daftar pesanan dan pembayaran. Omzet hanya menghitung pesanan dengan status pembayaran berhasil.</p>
    </header>

    <form class="pl-report-toolbar" method="GET" action="{{ route('admin.laporan.penjualan') }}">
        <label class="pl-report-search"><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode pesanan, penerima, atau produk..." aria-label="Cari penjualan"></label>
        <details class="pl-report-filter">
            <summary><span aria-hidden="true">☷</span> Filter</summary>
            <div class="pl-report-filter-panel">
                <div class="pl-report-filter-grid">
                    <div class="pl-report-field"><label for="tanggal_mulai">Tanggal dari</label><input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"></div>
                    <div class="pl-report-field"><label for="tanggal_akhir">Tanggal sampai</label><input id="tanggal_akhir" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></div>
                    <div class="pl-report-field"><label for="status_pesanan">Status pesanan</label><select id="status_pesanan" name="status_pesanan"><option value="">Semua status</option>@foreach (['menunggu_pembayaran'=>'Menunggu Pembayaran','dibayar'=>'Dibayar','diproses'=>'Diproses','selesai'=>'Selesai','batal'=>'Batal'] as $value => $label)<option value="{{ $value }}" @selected(request('status_pesanan') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="pl-report-field"><label for="metode">Metode pembayaran</label><select id="metode" name="metode"><option value="">Semua metode</option><option value="cash" @selected(request('metode') === 'cash')>Cash</option><option value="midtrans" @selected(request('metode') === 'midtrans')>Midtrans</option></select></div>
                    <div class="pl-report-field"><label for="status_pembayaran">Status pembayaran</label><select id="status_pembayaran" name="status_pembayaran"><option value="">Semua status pembayaran</option><option value="menunggu" @selected(request('status_pembayaran') === 'menunggu')>Menunggu</option><option value="berhasil" @selected(request('status_pembayaran') === 'berhasil')>Berhasil</option><option value="gagal" @selected(request('status_pembayaran') === 'gagal')>Gagal</option><option value="kedaluwarsa" @selected(request('status_pembayaran') === 'kedaluwarsa')>Kedaluwarsa</option></select></div>
                </div>
                <div class="pl-report-filter-actions"><a class="pl-report-btn pl-report-btn-light" href="{{ route('admin.laporan.penjualan') }}">Reset</a><button class="pl-report-btn pl-report-btn-primary" type="submit">Terapkan filter</button></div>
            </div>
        </details>
        <span class="pl-report-toolbar-spacer"></span>
        <a class="pl-report-btn pl-report-btn-pdf" href="{{ route('admin.laporan.penjualan.pdf', request()->query()) }}">↓ PDF</a>
        <a class="pl-report-btn pl-report-btn-excel" href="{{ route('admin.laporan.penjualan.excel', request()->query()) }}">↓ Excel</a>
    </form>

    @if ($errors->any())<div class="pl-report-error">{{ $errors->first() }}</div>@endif

    <div class="pl-report-summary">
        <span>Pesanan ditemukan: <strong>{{ number_format($ringkasan['jumlahPesanan'], 0, ',', '.') }}</strong></span>
        <span>Transaksi berhasil: <strong>{{ number_format($ringkasan['jumlahTransaksiBerhasil'], 0, ',', '.') }}</strong></span>
        <span>Omzet pembayaran berhasil: <strong>Rp {{ number_format((float) $ringkasan['totalOmzet'], 0, ',', '.') }}</strong></span>
    </div>

    <section class="pl-report-card">
        <div class="pl-report-card-head"><div><h2>Detail Transaksi Penjualan</h2><p>Pesanan yang belum dibayar dan dibatalkan tetap ditampilkan sebagai informasi transaksi, bukan omzet.</p></div></div>
        <div class="pl-report-table-wrap">
            <table class="pl-report-table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Kode Pesanan</th><th>Penerima</th><th>Produk</th><th>Qty</th><th>Subtotal</th><th>Ongkir</th><th>Total</th><th>Metode</th><th>Pembayaran</th><th>Pesanan</th></tr></thead>
                <tbody>
                @forelse ($laporan as $pesanan)
                    @php($pembayaran = $pesanan->pembayaran->first())
                    <tr>
                        <td>{{ $laporan->firstItem() + $loop->index }}</td>
                        <td>{{ $pesanan->tanggal_pesanan?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td><strong>{{ $pesanan->kode_pesanan }}</strong></td>
                        <td>{{ $pesanan->nama_penerima }}</td>
                        <td>{{ $pesanan->detail->pluck('produk.nama_produk')->filter()->join(', ') ?: '-' }}</td>
                        <td>{{ number_format((int) $pesanan->detail->sum('jumlah'), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) ($pesanan->subtotal ?? $pesanan->total), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) ($pesanan->ongkir ?? 0), 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format((float) $pesanan->total, 0, ',', '.') }}</strong></td>
                        <td>{{ strtoupper($pembayaran?->metode ?? '-') }}</td>
                        <td><span class="pl-report-badge {{ $pembayaran?->status_pembayaran === 'berhasil' ? 'success' : (in_array($pembayaran?->status_pembayaran, ['gagal', 'kedaluwarsa'], true) ? 'danger' : 'pending') }}">{{ ucfirst($pembayaran?->status_pembayaran ?? 'Belum ada') }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $pesanan->status_pesanan)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="pl-report-empty">Tidak ada transaksi yang sesuai dengan pencarian atau filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($laporan->hasPages())<div class="pl-report-pagination">{{ $laporan->links() }}</div>@endif
    </section>
</div>
@endsection
