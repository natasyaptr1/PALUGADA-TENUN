@extends('layouts.app')
@section('title', 'Target Produksi - Palugada Bandar')
@section('content')
<div class="tp-page">
    <div class="tp-head">
        <div><span class="tp-overline">PRODUKSI</span><h1>Target Produksi</h1><p>Kelola target produksi dan penugasan penenun untuk setiap produk.</p></div>
        <a class="tp-button tp-primary" href="{{ route('admin.target-produksi.create') }}">Buat Target Produksi</a>
    </div>

    @if(session('success'))<div class="tp-alert tp-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="tp-alert tp-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="tp-alert tp-danger"><strong>Periksa formulir:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('admin.target-produksi') }}" class="tp-filters">
        <input type="search" name="cari" aria-label="Cari produk atau penenun" placeholder="Cari produk atau penenun..." value="{{ $pencarian }}">
        <select name="status" aria-label="Filter status">
            <option value="">Semua Status</option>
            <option value="berjalan" @selected($status==='berjalan')>Berjalan</option>
            <option value="selesai" @selected($status==='selesai')>Selesai</option>
            <option value="dibatalkan" @selected($status==='dibatalkan')>Dibatalkan</option>
        </select>
        <button type="submit" class="tp-button tp-primary">Cari</button>
        @if($pencarian!=='' || $status!=='')<a class="tp-button tp-secondary" href="{{ route('admin.target-produksi') }}">Reset Filter</a>@endif
    </form>

    <section class="tp-panel tp-table-panel">
        <div class="tp-overflow"><table class="tp-table">
            <thead><tr><th>No</th><th>Produk</th><th>Penenun</th><th>Target</th><th>Selesai</th><th>Batas Waktu</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($targets as $t)
                @php
                    $selesai = (int) ($t->hasil_produksi_sum_jumlah_selesai ?? 0);
                    $persen = $t->jumlah_target > 0 ? min(100, round($selesai * 100 / $t->jumlah_target)) : 0;
                @endphp
                <tr>
                    <td>{{ $targets->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $t->produk?->nama_produk ?? '—' }}</strong><span class="tp-subline">{{ $t->produk?->kode_produk ?? '—' }}</span></td>
                    <td><strong>{{ $t->penenun?->nama ?? '—' }}</strong></td>
                    <td><strong>{{ $t->jumlah_target }}</strong></td>
                    <td><span class="tp-progress-count">{{ $selesai }} / {{ $t->jumlah_target }}</span><div class="tp-mini-progress"><span style="width:{{ $persen }}%"></span></div><span class="tp-progress-text">{{ $persen }}%</span></td>
                    <td>{{ $t->tanggal_deadline?->format('d M Y') ?? '—' }}</td>
                    <td><span class="tp-status tp-status-{{ $t->status }}">{{ ucfirst($t->status) }}</span></td>
                    <td><div class="tp-actions">
                        <a href="{{ route('admin.target-produksi.show', $t) }}">Detail</a>
                        @if($t->status==='berjalan')
                            <button type="button" class="tp-action" onclick="document.getElementById('ubah-target-{{ $t->id }}').hidden = !document.getElementById('ubah-target-{{ $t->id }}').hidden">Ubah</button>
                            @if($selesai===0)<form method="POST" action="{{ route('admin.target-produksi.cancel',$t) }}" onsubmit="return confirm('Batalkan target ini?')">@csrf @method('PATCH')<button type="submit" class="tp-action tp-danger-text">Batalkan</button></form>@endif
                        @endif
                        @if($selesai===0)<form method="POST" action="{{ route('admin.target-produksi.destroy',$t) }}" onsubmit="return confirm('Hapus target ini secara permanen?')">@csrf @method('DELETE')<button type="submit" class="tp-action tp-danger-text">Hapus</button></form>@endif
                    </div></td>
                </tr>
                @if($t->status==='berjalan')
                <tr id="ubah-target-{{ $t->id }}" hidden><td colspan="8" class="tp-edit-row"><form class="tp-edit-form" method="POST" action="{{ route('admin.target-produksi.update', $t) }}">@csrf @method('PUT')<strong>Ubah target: {{ $t->produk?->nama_produk }}</strong><label>Jumlah Target <input type="number" min="{{ max(1,$selesai) }}" name="jumlah_target" value="{{ $t->jumlah_target }}" required></label><label>Batas Waktu <input type="date" name="tanggal_deadline" value="{{ $t->tanggal_deadline?->format('Y-m-d') }}"></label><button type="submit" class="tp-button tp-primary">Simpan</button></form></td></tr>
                @endif
            @empty
                <tr><td colspan="8" class="tp-empty">Belum ada target produksi. Klik tombol Buat Target Produksi untuk menambahkan data.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="tp-pagination">{{ $targets->links('vendor.pagination.palugada') }}</div>
    </section>

</div>
@endsection
