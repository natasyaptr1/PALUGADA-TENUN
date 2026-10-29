@extends('layouts.app')
@section('title', 'Detail Pesanan ' . $pesanan->kode_pesanan . ' - Palugada Bandar')
@section('content')
<div class="pesanan-detail-page">
    <div class="page-head admin-page-head-v4">
        <div>
            <span class="eyebrow">PENJUALAN</span>
            <h1>Detail Pesanan</h1>
            <p>{{ $pesanan->kode_pesanan }}</p>
        </div>
        <a href="{{ route('admin.pesanan.index') }}" class="btn btn-outline">Kembali</a>
    </div>

    {{-- Panel Ringkasan Pesanan --}}
    <section class="pesanan-panel">
        <div class="pesanan-panel-head">
            <h2>Ringkasan Pesanan</h2>
        </div>
        <div class="pesanan-info-grid">
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Kode Pesanan</span>
                <strong>{{ $pesanan->kode_pesanan }}</strong>
            </div>
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Tanggal Pesanan</span>
                <strong>{{ $pesanan->tanggal_pesanan?->translatedFormat('d M Y, H:i') ?? '—' }}</strong>
            </div>
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Status Pesanan</span>
                <div>
                    <span class="{{ \App\Support\LabelPesanan::classStatusPesanan($pesanan->status_pesanan) }}">
                        {{ \App\Support\LabelPesanan::labelStatusPesanan($pesanan->status_pesanan) }}
                    </span>
                </div>
            </div>
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Total Pesanan</span>
                <strong style="color: var(--green-950);">{{ \App\Support\LabelPesanan::formatRupiah($pesanan->total) }}</strong>
            </div>
        </div>
    </section>

    {{-- Panel Pembeli dan Pengiriman --}}
    <section class="pesanan-panel">
        <div class="pesanan-panel-head">
            <h2>Pembeli dan Pengiriman</h2>
        </div>
        <div class="pesanan-info-grid-2">
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Akun Pembeli</span>
                <strong>{{ $pesanan->pembeli?->nama ?? '—' }}</strong>
                <small>{{ $pesanan->pembeli?->email ?? '—' }}</small>
            </div>
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Penerima & Kontak</span>
                <strong>{{ $pesanan->nama_penerima ?? '—' }}</strong>
                <small>No. HP: {{ $pesanan->no_hp ?? '—' }}</small>
            </div>
        </div>
        <div style="margin-top: 14px;">
            <div class="pesanan-info-box">
                <span class="pesanan-info-label">Alamat Pengiriman</span>
                <p class="pesanan-address-text">{!! nl2br(e($pesanan->alamat_pengiriman)) !!}</p>
            </div>
        </div>
    </section>

    {{-- Panel Item Pesanan --}}
    <section class="pesanan-panel">
        <div class="pesanan-panel-head">
            <h2>Item Pesanan</h2>
        </div>
        <div class="stok-table-scroll">
            <table class="stok-table">
                <thead>
                    <tr>
                        <th scope="col">Produk</th>
                        <th scope="col" style="text-align: right; width: 150px;">Harga</th>
                        <th scope="col" style="text-align: center; width: 100px;">Jumlah</th>
                        <th scope="col" style="text-align: right; width: 160px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pesanan->detail as $item)
                    @php
                        $produk = $item->produk;
                        $semuaFoto = $produk?->semuaFoto() ?? [];
                    @endphp
                    <tr>
                        <td>
                            <div class="product-cell">
                                <div class="thumb" style="position:relative;">
                                    @if(!empty($semuaFoto))
                                        <img src="{{ asset('storage/' . $semuaFoto[0]) }}" alt="{{ $produk?->nama_produk }}">
                                    @else
                                        {{ strtoupper(substr($produk?->nama_produk ?? 'PR', 0, 2)) }}
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $produk?->nama_produk ?? 'Produk tidak ditemukan' }}</strong>
                                    @if($produk?->trashed())
                                        <span class="produk-dihapus-label">(produk dihapus)</span>
                                    @endif
                                    <br>
                                    <small style="color: #728078;">{{ $produk?->kode_produk ?? '—' }}</small>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right;">{{ \App\Support\LabelPesanan::formatRupiah($item->harga) }}</td>
                        <td style="text-align: center;">{{ $item->jumlah }}</td>
                        <td style="text-align: right;">
                            <strong>{{ \App\Support\LabelPesanan::formatRupiah($item->subtotal) }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="stok-empty">Tidak ada item pesanan.</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" style="text-align: right; font-weight: 700;">Total:</th>
                        <th style="text-align: right; font-weight: 800; font-size: 16px; color: var(--green-950);">
                            {{ \App\Support\LabelPesanan::formatRupiah($pesanan->total) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Panel Pembayaran --}}
    <section class="pesanan-panel">
        <div class="pesanan-panel-head">
            <h2>Pembayaran</h2>
        </div>
        @if($pesanan->pembayaran->isEmpty())
            <p class="pesanan-empty-text">Belum ada data pembayaran.</p>
        @else
            <div class="pesanan-payment-list">
                @foreach($pesanan->pembayaran as $bayar)
                    <article class="pesanan-payment-item">
                        <div class="pesanan-payment-item-head">
                            <div>
                                <strong>Metode: {{ \App\Support\LabelPesanan::labelMetodePembayaran($bayar->metode) }}</strong>
                            </div>
                            <div>
                                <span class="{{ \App\Support\LabelPesanan::classStatusPembayaran($bayar->status_pembayaran) }}">
                                    {{ \App\Support\LabelPesanan::labelStatusPembayaran($bayar->status_pembayaran) }}
                                </span>
                            </div>
                        </div>
                        <div class="pesanan-payment-item-grid">
                            <div class="pesanan-payment-item-field">
                                <span>Jumlah Bayar</span>
                                <strong>{{ \App\Support\LabelPesanan::formatRupiah($bayar->jumlah_bayar) }}</strong>
                            </div>
                            @if($bayar->tipe_pembayaran)
                                <div class="pesanan-payment-item-field">
                                    <span>Tipe Pembayaran</span>
                                    <strong>{{ ucfirst($bayar->tipe_pembayaran) }}</strong>
                                </div>
                            @endif
                            @if($bayar->id_transaksi)
                                <div class="pesanan-payment-item-field">
                                    <span>ID Transaksi</span>
                                    <strong>{{ $bayar->id_transaksi }}</strong>
                                </div>
                            @endif
                            <div class="pesanan-payment-item-field">
                                <span>Tanggal Bayar</span>
                                <strong>{{ $bayar->tanggal_bayar?->translatedFormat('d M Y, H:i') ?? '-' }}</strong>
                            </div>
                            <div class="pesanan-payment-item-field">
                                <span>Dikonfirmasi oleh</span>
                                <strong>{{ $bayar->pengonfirmasi?->nama ?? '-' }}</strong>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
