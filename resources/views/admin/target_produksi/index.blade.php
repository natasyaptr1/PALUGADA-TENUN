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

<section class="admin-stat-grid admin-stat-grid-v4" aria-label="Ringkasan target produksi">
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Berjalan</span>
            <strong>{{ $ringkasan->total_berjalan ?? 0 }}</strong>
            <p>Target sedang dikerjakan</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Selesai</span>
            <strong>{{ $ringkasan->total_selesai ?? 0 }}</strong>
            <p>Target telah diselesaikan</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4">
        <div>
            <span class="admin-stat-label">Dibatalkan</span>
            <strong>{{ $ringkasan->total_dibatalkan ?? 0 }}</strong>
            <p>Target yang dibatalkan</p>
        </div>
    </article>
    <article class="admin-stat-card admin-stat-card-v4 {{ ($ringkasan->total_terlambat ?? 0) > 0 ? 'needs-attention' : '' }}">
        <div>
            <span class="admin-stat-label">Terlambat</span>
            <strong>{{ $ringkasan->total_terlambat ?? 0 }}</strong>
            <p>Melewati batas waktu</p>
        </div>
    </article>
</section>

<form class="toolbar admin-toolbar-v4" method="GET" action="{{ route('admin.target-produksi.index') }}">
    <input name="cari" value="{{ request('cari') }}" placeholder="Cari produk atau penenun...">
    <select name="status" style="width: auto; min-width: 160px;">
        <option value="">Semua Status</option>
        <option value="berjalan" @selected(request('status') === 'berjalan')>Berjalan</option>
        <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
        <option value="dibatalkan" @selected(request('status') === 'dibatalkan')>Dibatalkan</option>
        <option value="terlambat" @selected(request('status') === 'terlambat')>Terlambat</option>
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
                <th style="text-align: center; width: 140px;">Status</th>
                <th style="width: 170px; text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse($daftarTarget as $index => $item)
            <tr>
                <td>{{ $daftarTarget->firstItem() ? $daftarTarget->firstItem() + $index : $index + 1 }}</td>
                <td>
                    <a href="{{ route('admin.target-produksi.show', $item) }}" style="color: inherit; text-decoration: none;">
                        <strong>{{ $item->produk?->nama_produk ?? '-' }}</strong>
                    </a>
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
                <td style="text-align: center; min-width: 120px;">
                    <div>
                        <span class="stock-pill" style="min-width: 60px; padding: 0 8px;">
                            {{ $item->total_selesai }} / {{ $item->jumlah_target }}
                        </span>
                        <div class="progres-target-wrap" style="margin-top: 6px;">
                            <div class="progres-target {{ $item->sedang_terlambat ? 'is-terlambat' : '' }}">
                                <div class="progres-target-isi" style="width: {{ $item->persen_progres }}%;"></div>
                            </div>
                            <span class="progres-target-persen {{ $item->sedang_terlambat ? 'is-terlambat' : '' }}">{{ $item->persen_progres }}%</span>
                        </div>
                    </div>
                </td>
                <td>
                    {{ $item->tanggal_deadline ? $item->tanggal_deadline->translatedFormat('d M Y') : '-' }}
                </td>
                <td style="text-align: center;">
                    <div style="display: inline-flex; align-items: center; gap: 4px; justify-content: center; flex-wrap: wrap;">
                        <span class="badge status-{{ $item->status }}">
                            {{ ucfirst($item->status) }}
                        </span>
                        @if($item->sedang_terlambat)
                            <span class="badge status-terlambat">Terlambat</span>
                        @endif
                    </div>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div class="actions actions-kanan">
                        <a href="{{ route('admin.target-produksi.show', $item) }}">Detail</a>
                        @if($item->status === 'berjalan')
                            <a href="{{ route('admin.target-produksi.edit', $item) }}">Ubah</a>
                            @php
                                $selesaiCount = (int) $item->total_selesai;
                                $pesanKonfirmasi = "Batalkan target produksi {$item->produk?->nama_produk} untuk {$item->penenun?->nama}?";
                                if ($selesaiCount > 0) {
                                    $pesanKonfirmasi .= " Hasil produksi yang sudah tercatat ({$selesaiCount}) tetap masuk stok.";
                                }
                            @endphp
                            <form method="POST" action="{{ route('admin.target-produksi.batalkan', $item) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('{{ addslashes($pesanKonfirmasi) }}')">Batalkan</button>
                            </form>
                        @else
                            <span style="color: var(--muted); font-size: 11px;">Terkunci</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="empty">Belum ada target produksi.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($daftarTarget->hasPages())
    <div class="pagination">{{ $daftarTarget->links() }}</div>
@endif
@endsection
