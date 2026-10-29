@extends('layouts.app')
@section('title', 'Pesanan - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">PENJUALAN</span>
        <h1>Pesanan</h1>
        <p>Pantau dan kelola pesanan pembeli.</p>
    </div>
</div>

<section class="admin-stat-grid admin-stat-grid-v4" aria-label="Ringkasan status pesanan">
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Menunggu Pembayaran</span>
            <strong>{{ $ringkasan->menunggu_pembayaran ?? 0 }}</strong>
            <p>Pesanan belum dibayar</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Dibayar</span>
            <strong>{{ $ringkasan->dibayar ?? 0 }}</strong>
            <p>Pembayaran telah diterima</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Diproses</span>
            <strong>{{ $ringkasan->diproses ?? 0 }}</strong>
            <p>Pesanan sedang disiapkan</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Selesai</span>
            <strong>{{ $ringkasan->selesai ?? 0 }}</strong>
            <p>Pesanan telah selesai</p>
        </div>
    </article>
</section>

<form class="toolbar admin-toolbar-v4 pesanan-toolbar" method="GET" action="{{ route('admin.pesanan.index') }}">
    <input name="cari" value="{{ request('cari') }}" placeholder="Cari kode pesanan, pembeli, atau penerima...">
    <select name="status" aria-label="Filter status pesanan">
        <option value="">Semua Status</option>
        <option value="menunggu_pembayaran" @selected(request('status') === 'menunggu_pembayaran')>Menunggu Pembayaran</option>
        <option value="dibayar" @selected(request('status') === 'dibayar')>Dibayar</option>
        <option value="diproses" @selected(request('status') === 'diproses')>Diproses</option>
        <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
        <option value="dibatalkan" @selected(request('status') === 'dibatalkan' || request('status') === 'batal')>Dibatalkan</option>
    </select>
    <button class="btn btn-secondary" type="submit">Cari</button>
    @if((request('cari') !== null && request('cari') !== '') || (request('status') !== null && request('status') !== ''))
        <a class="btn btn-outline" href="{{ route('admin.pesanan.index') }}">Reset</a>
    @endif
</form>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Kode Pesanan</th>
                <th>Pembeli</th>
                <th>Tanggal</th>
                <th style="text-align: center; width: 90px;">Item</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Status Pesanan</th>
            </tr>
        </thead>
        <tbody>
        @forelse($daftarPesanan as $index => $pesanan)
            @php
                $pembayaranTerbaru = $pesanan->pembayaran->first();
            @endphp
            <tr>
                <td>{{ $daftarPesanan->firstItem() ? $daftarPesanan->firstItem() + $index : $index + 1 }}</td>
                <td><strong>{{ $pesanan->kode_pesanan }}</strong></td>
                <td>
                    <div class="pesanan-buyer-info">
                        <strong>{{ $pesanan->pembeli?->nama ?? '—' }}</strong>
                        <small>{{ $pesanan->pembeli?->email ?? '—' }}</small>
                    </div>
                </td>
                <td>{{ $pesanan->tanggal_pesanan?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                <td style="text-align: center;">{{ $pesanan->detail_count ?? 0 }} item</td>
                <td><span class="pesanan-total">{{ \App\Support\LabelPesanan::formatRupiah($pesanan->total) }}</span></td>
                <td>
                    @if($pembayaranTerbaru)
                        <div class="pesanan-payment-cell">
                            <span class="pesanan-payment-method">{{ \App\Support\LabelPesanan::labelMetodePembayaran($pembayaranTerbaru->metode) }}</span>
                            <span class="{{ \App\Support\LabelPesanan::classStatusPembayaran($pembayaranTerbaru->status_pembayaran) }}">
                                {{ \App\Support\LabelPesanan::labelStatusPembayaran($pembayaranTerbaru->status_pembayaran) }}
                            </span>
                        </div>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="{{ \App\Support\LabelPesanan::classStatusPesanan($pesanan->status_pesanan) }}">
                        {{ \App\Support\LabelPesanan::labelStatusPesanan($pesanan->status_pesanan) }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="empty">Belum ada pesanan.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $daftarPesanan->links('vendor.pagination.palugada') }}
</div>
@endsection
