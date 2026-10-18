@extends('layouts.app')
@section('title', 'Monitoring Stok - Palugada Bandar')
@section('content')
<div class="stok-page">
    <div class="stok-head"><span class="eyebrow">PERSEDIAAN</span><h1>Monitoring Stok</h1><p>Pantau persediaan produk dan riwayat perubahan stok.</p></div>
    <section class="stok-card">
        <div class="stok-card-head"><div><h2>Stok Produk</h2><p>Jumlah produk yang tersedia untuk dijual.</p></div></div>
        <div class="stok-table-scroll"><table class="stok-table">
            <thead><tr><th scope="col">Kode Produk</th><th scope="col">Nama Produk</th><th scope="col">Jumlah Stok</th><th scope="col">Kondisi</th></tr></thead>
            <tbody>@forelse($stok as $s)
                <tr><td>{{ $s->produk?->kode_produk ?? '—' }}</td><td><strong>{{ $s->produk?->nama_produk ?? '—' }}</strong></td><td><span class="stok-count">{{ $s->jumlah_stok }} pcs</span></td><td><span class="stok-status {{ $s->jumlah_stok <= 3 ? 'stok-status-low' : 'stok-status-ok' }}">{{ $s->jumlah_stok <= 3 ? 'Menipis' : 'Tersedia' }}</span></td></tr>
            @empty<tr><td colspan="4" class="stok-empty">Belum ada data stok.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="stok-pagination">{{ $stok->links('vendor.pagination.palugada') }}</div>
    </section>
    <section class="stok-card">
        <div class="stok-card-head"><div><h2>Riwayat Mutasi Stok</h2><p>Pencatatan penambahan maupun pengurangan stok produk.</p></div></div>
        <div class="stok-table-scroll"><table class="stok-table stok-history-table">
            <thead><tr><th scope="col">Tanggal</th><th scope="col">Produk</th><th scope="col">Jenis</th><th scope="col">Jumlah</th><th scope="col">Sumber</th><th scope="col">Catatan</th></tr></thead>
            <tbody>@forelse($mutasi as $m)
                <tr><td>{{ $m->tanggal_mutasi?->format('d/m/Y H:i') ?? '—' }}</td><td><strong>{{ $m->stok?->produk?->nama_produk ?? '—' }}</strong></td><td>{{ ucfirst($m->jenis_mutasi) }}</td><td><strong>{{ $m->jenis_mutasi === 'masuk' ? '+' : ($m->jenis_mutasi === 'keluar' ? '-' : '') }}{{ $m->jumlah }}</strong></td><td>{{ ucfirst(str_replace('_', ' ', $m->sumber ?? '-')) }}</td><td>{{ $m->catatan ?: '—' }}</td></tr>
            @empty<tr><td colspan="6" class="stok-empty">Belum ada riwayat mutasi stok.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="stok-pagination">{{ $mutasi->links('vendor.pagination.palugada') }}</div>
    </section>
</div>
@endsection
