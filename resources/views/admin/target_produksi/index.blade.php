@extends('layouts.app')
@section('title', 'Target Produksi - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">PRODUKSI</span>
        <h1>Target Produksi</h1>
        <p>Kelola target produksi dan penugasan penenun untuk setiap produk.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.target-produksi.create') }}">Buat Target Produksi</a>
</div>

<form class="toolbar admin-toolbar-v4" method="GET" action="{{ route('admin.target-produksi.index') }}">
    <input name="cari" value="{{ request('cari') }}" placeholder="Cari produk atau penenun...">
    <select name="status" style="width: auto; min-width: 160px;">
        <option value="">Semua Status</option>
        <option value="berjalan" @selected(request('status') === 'berjalan')>Berjalan</option>
        <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
        <option value="dibatalkan" @selected(request('status') === 'dibatalkan')>Dibatalkan</option>
    </select>
    <button class="btn btn-secondary" type="submit">Cari</button>
    @if(request('cari') || request('status'))
        <a class="btn btn-outline" href="{{ route('admin.target-produksi.index') }}">Reset</a>
    @endif
</form>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Produk</th>
                <th>Penenun</th>
                <th style="text-align: center;">Target</th>
                <th style="text-align: center;">Selesai</th>
                <th>Batas Waktu</th>
                <th style="text-align: center; width: 120px;">Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($daftarTarget as $index => $item)
            <tr>
                <td>{{ $daftarTarget->firstItem() ? $daftarTarget->firstItem() + $index : $index + 1 }}</td>
                <td>
                    <strong>{{ $item->produk?->nama_produk ?? '-' }}</strong>
                    <span style="display: block; font-size: 10px; color: var(--muted); margin-top: 2px;">
                        {{ $item->produk?->kode_produk ?? '-' }}
                    </span>
                </td>
                <td>
                    <strong>{{ $item->penenun?->nama ?? '-' }}</strong>
                    <span style="display: block; font-size: 10px; color: var(--muted); margin-top: 2px;">
                        {{ $item->penenun?->profilPenenun?->kode_penenun ?? '-' }}
                    </span>
                </td>
                <td style="text-align: center;">
                    <strong>{{ number_format($item->jumlah_target, 0, ',', '.') }}</strong>
                </td>
                <td style="text-align: center;">
                    <span class="stock-pill" style="min-width: 60px; padding: 0 8px;">
                        {{ (int) ($item->hasil_produksi_sum_jumlah_selesai ?? 0) }} / {{ $item->jumlah_target }}
                    </span>
                </td>
                <td>
                    {{ $item->tanggal_deadline ? $item->tanggal_deadline->format('d M Y') : '-' }}
                </td>
                <td style="text-align: center;">
                    <span class="badge status-{{ $item->status }}">
                        {{ ucfirst($item->status) }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="empty">Belum ada target produksi.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($daftarTarget->hasPages())
    <div class="pagination">{{ $daftarTarget->links() }}</div>
@endif
@endsection
