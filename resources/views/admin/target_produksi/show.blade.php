@extends('layouts.app')
@section('title', 'Detail Target Produksi - Palugada Bandar')
@section('content')
@php
    $keahlian = $targetProduksi->penenun?->profilPenenun?->daftarKeahlian?->isNotEmpty()
        ? $targetProduksi->penenun->profilPenenun->daftarKeahlian->pluck('nama_keahlian')->join(', ')
        : ($targetProduksi->penenun?->profilPenenun?->keahlian ?: '-');
@endphp
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">PRODUKSI</span>
        <h1>Detail Target Produksi</h1>
        <p>Informasi lengkap target produksi dan riwayat pencatatan hasil tenun.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a class="btn btn-outline" href="{{ route('admin.target-produksi.index') }}">Kembali</a>
        @if($targetProduksi->status === 'berjalan')
            <a class="btn btn-secondary" href="{{ route('admin.target-produksi.edit', $targetProduksi) }}">Ubah</a>
            @php
                $selesaiCount = (int) $targetProduksi->total_selesai;
                $pesanKonfirmasi = "Batalkan target produksi {$targetProduksi->produk?->nama_produk} untuk {$targetProduksi->penenun?->nama}?";
                if ($selesaiCount > 0) {
                    $pesanKonfirmasi .= " Hasil produksi yang sudah tercatat ({$selesaiCount}) tetap masuk stok.";
                }
            @endphp
            <form method="POST" action="{{ route('admin.target-produksi.batalkan', $targetProduksi) }}" style="display:inline;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);" onclick="return confirm('{{ addslashes($pesanKonfirmasi) }}')">
                    Batalkan
                </button>
            </form>
        @endif
    </div>
</div>

<div class="panel" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 16px; flex-wrap: wrap;">
        <div>
            <span class="eyebrow">RINGKASAN TARGET</span>
            <h2 style="font-family: var(--serif); font-size: 24px; margin: 4px 0 0;">
                {{ $targetProduksi->produk?->nama_produk ?? '-' }}
            </h2>
        </div>
        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
            <span class="badge status-{{ $targetProduksi->status }}" style="font-size: 11px; padding: 6px 12px;">
                {{ ucfirst($targetProduksi->status) }}
            </span>
            @if($targetProduksi->sedang_terlambat)
                <span class="badge status-terlambat" style="font-size: 11px; padding: 6px 12px;">
                    Terlambat {{ $targetProduksi->hari_terlambat }} hari
                </span>
            @endif
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px;">
        <div style="background: #fbfaf7; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Produk</span>
            <strong style="font-size: 15px; display: block; margin-top: 4px; color: var(--ink);">
                {{ $targetProduksi->produk?->nama_produk ?? '-' }}
            </strong>
            <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 2px;">
                Kode: {{ $targetProduksi->produk?->kode_produk ?? '-' }} &bull; Jenis: {{ $targetProduksi->produk?->jenis ?? '-' }}
            </span>
        </div>

        <div style="background: #fbfaf7; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Penenun</span>
            <strong style="font-size: 15px; display: block; margin-top: 4px; color: var(--ink);">
                {{ $targetProduksi->penenun?->nama ?? '-' }}
            </strong>
            <span style="font-size: 12px; color: var(--muted); display: block; margin-top: 2px;">
                Kode: {{ $targetProduksi->penenun?->profilPenenun?->kode_penenun ?? '-' }} &bull; Keahlian: {{ $keahlian }}
            </span>
        </div>

        <div style="background: #fbfaf7; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Tanggal Mulai</span>
            <strong style="font-size: 15px; display: block; margin-top: 4px; color: var(--ink);">
                {{ $targetProduksi->tanggal_mulai ? $targetProduksi->tanggal_mulai->translatedFormat('d M Y') : '-' }}
            </strong>
        </div>

        <div style="background: #fbfaf7; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Batas Waktu Penyelesaian</span>
            <strong style="font-size: 15px; display: block; margin-top: 4px; color: var(--ink);">
                {{ $targetProduksi->tanggal_deadline ? $targetProduksi->tanggal_deadline->translatedFormat('d M Y') : '-' }}
            </strong>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div style="padding: 16px; background: #fff; border: 1px solid var(--line); border-radius: 12px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Jumlah Target</span>
            <strong style="font-family: var(--serif); font-size: 28px; color: var(--green-900); display: block; margin-top: 6px;">
                {{ number_format($targetProduksi->jumlah_target, 0, ',', '.') }}
            </strong>
            <span style="font-size: 11px; color: var(--muted);">unit</span>
        </div>

        <div style="padding: 16px; background: #fff; border: 1px solid var(--line); border-radius: 12px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Total Selesai</span>
            <strong style="font-family: var(--serif); font-size: 28px; color: var(--green-900); display: block; margin-top: 6px;">
                {{ number_format($targetProduksi->total_selesai, 0, ',', '.') }}
            </strong>
            <span style="font-size: 11px; color: var(--muted);">unit</span>
        </div>

        <div style="padding: 16px; background: #fff; border: 1px solid var(--line); border-radius: 12px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Sisa Target</span>
            <strong style="font-family: var(--serif); font-size: 28px; color: var(--green-900); display: block; margin-top: 6px;">
                {{ number_format($targetProduksi->sisa_target, 0, ',', '.') }}
            </strong>
            <span style="font-size: 11px; color: var(--muted);">unit</span>
        </div>

        <div style="padding: 16px; background: #fff; border: 1px solid var(--line); border-radius: 12px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 700; display: block;">Persen Progres</span>
            <strong style="font-family: var(--serif); font-size: 28px; color: {{ $targetProduksi->sedang_terlambat ? 'var(--danger)' : 'var(--green-900)' }}; display: block; margin-top: 6px;">
                {{ $targetProduksi->persen_progres }}%
            </strong>
            <span style="font-size: 11px; color: var(--muted);">selesai</span>
        </div>
    </div>

    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 12px; font-weight: 700; color: #405049;">Monitoring Progres Penyelesaian</span>
            <strong style="font-size: 13px; color: {{ $targetProduksi->sedang_terlambat ? 'var(--danger)' : 'var(--green-900)' }};">
                {{ $targetProduksi->total_selesai }} dari {{ $targetProduksi->jumlah_target }} unit ({{ $targetProduksi->persen_progres }}%)
            </strong>
        </div>
        <div class="progres-target-lg {{ $targetProduksi->sedang_terlambat ? 'is-terlambat' : '' }}">
            <div class="progres-target-isi" style="width: {{ $targetProduksi->persen_progres }}%;"></div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-title" style="margin-bottom: 16px;">
        <div>
            <span class="eyebrow">MONITORING</span>
            <h2 style="font-family: var(--serif); font-size: 22px; margin: 4px 0 0;">Riwayat Hasil Produksi</h2>
        </div>
    </div>

    <div class="table-card" style="box-shadow: none; border-radius: 12px; margin-top: 12px;">
        <table>
            <thead>
                <tr>
                    <th style="width: 220px;">Tanggal Input</th>
                    <th style="width: 140px; text-align: center;">Jumlah Selesai</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
            @forelse($riwayatHasil as $hasil)
                <tr>
                    <td>{{ $hasil->tanggal_input ? $hasil->tanggal_input->translatedFormat('d M Y, H:i') : '-' }}</td>
                    <td style="text-align: center;">
                        <span class="stock-pill" style="min-width: 50px; font-weight: 700;">
                            +{{ number_format($hasil->jumlah_selesai, 0, ',', '.') }}
                        </span>
                    </td>
                    <td>{{ $hasil->catatan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty">Belum ada hasil produksi yang dicatat.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($riwayatHasil->hasPages())
        <div class="pagination">{{ $riwayatHasil->links() }}</div>
    @endif
</div>
@endsection
