@extends('layouts.app')
@section('title', 'Detail Target Produksi - Palugada Bandar')
@section('content')
<div class="tp-page tp-detail-page">
    <div class="tp-head">
        <div><h1>Detail Target Produksi</h1><p>Informasi lengkap target produksi dan riwayat pencatatan hasil tenun.</p></div>
        <div class="tp-head-actions"><a href="{{ route('admin.target-produksi') }}" class="tp-button tp-secondary">Kembali</a>@if($target->status==='berjalan')<button type="button" class="tp-button tp-secondary" onclick="document.getElementById('tp-detail-edit').hidden = !document.getElementById('tp-detail-edit').hidden">Ubah</button>@if($target->hasilProduksi->isEmpty())<form method="POST" action="{{ route('admin.target-produksi.cancel',$target) }}" onsubmit="return confirm('Batalkan target ini?')">@csrf @method('PATCH')<button type="submit" class="tp-button tp-outline-danger">Batalkan</button></form>@endif @endif</div>
    </div>
    @if(session('success'))<div class="tp-alert tp-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="tp-alert tp-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="tp-alert tp-danger">{{ $errors->first() }}</div>@endif
    <section class="tp-panel tp-detail-card">
        <div class="tp-section-header"><div><span class="tp-overline">RINGKASAN TARGET</span><h2>{{ $target->produk?->nama_produk ?? 'Produk tidak ditemukan' }}</h2></div><span class="tp-status tp-status-{{ $target->status }}">{{ ucfirst($target->status) }}</span></div>
        <div class="tp-info-grid">
            <div class="tp-info-item"><span>PRODUK</span><strong>{{ $target->produk?->nama_produk ?? '—' }}</strong><small>Kode: {{ $target->produk?->kode_produk ?? '—' }}</small></div>
            <div class="tp-info-item"><span>PENENUN</span><strong>{{ $target->penenun?->nama ?? '—' }}</strong></div>
            <div class="tp-info-item"><span>TANGGAL MULAI</span><strong>{{ $target->tanggal_mulai?->format('d M Y') ?? '—' }}</strong></div>
            <div class="tp-info-item"><span>BATAS WAKTU PENYELESAIAN</span><strong>{{ $target->tanggal_deadline?->format('d M Y') ?? '—' }}</strong></div>
        </div>
        <div class="tp-main-progress"><div><strong>Monitoring Progres Penyelesaian</strong><strong>{{ $selesai }} dari {{ $target->jumlah_target }} unit ({{ $persen }}%)</strong></div><div class="tp-main-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $persen }}"><span style="width:{{ $persen }}%"></span></div></div>
    </section>
    @if($target->status==='berjalan')
    <section id="tp-detail-edit" class="tp-panel tp-form-panel" hidden><h2>Ubah Target Produksi</h2><form method="POST" action="{{ route('admin.target-produksi.update',$target) }}">@csrf @method('PUT')<div class="tp-form-grid"><label>Jumlah Target <input type="number" min="{{ max(1,$selesai) }}" name="jumlah_target" value="{{ $target->jumlah_target }}" required></label><label>Batas Waktu <input type="date" name="tanggal_deadline" value="{{ $target->tanggal_deadline?->format('Y-m-d') }}"></label></div><button class="tp-button tp-primary" type="submit">Simpan Perubahan</button></form></section>
    @endif
    <section class="tp-panel tp-detail-card"><div class="tp-section-header"><div><span class="tp-overline">MONITORING</span><h2>Riwayat Hasil Produksi</h2></div></div>
        <div class="tp-overflow"><table class="tp-table"><thead><tr><th>No</th><th>Tanggal Input</th><th>Jumlah Selesai</th><th>Catatan</th></tr></thead><tbody>
            @forelse($target->hasilProduksi as $hasil)<tr><td>{{ $loop->iteration }}</td><td>{{ $hasil->tanggal_input?->format('d M Y H:i') ?? '—' }}</td><td>{{ $hasil->jumlah_selesai }} pcs</td><td>{{ $hasil->catatan ?: '—' }}</td></tr>
            @empty<tr><td colspan="4" class="tp-empty">Belum ada hasil produksi yang dicatat.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
