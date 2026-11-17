@extends('layouts.app')

@section('title', 'Pesanan Saya - Palugada Bandar')

@section('content')
<style>
    .pb-orders-page {
        --green: #204b3b;
        --green-dark: #16392d;
        --green-soft: #edf4ef;
        --text: #34483d;
        --muted: #879188;
        --border: #e8ece7;

        width: min(1080px, calc(100% - 40px));
        margin: 0 auto;
        padding: 42px 0 70px;
        color: var(--text);
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        line-height: 1.6;
    }

    .pb-orders-page *,
    .pb-orders-page *::before,
    .pb-orders-page *::after {
        box-sizing: border-box;
    }

    /* PAGE HEADER */
    .pb-orders-heading {
        margin-bottom: 30px;
    }

    .pb-orders-eyebrow {
        display: inline-block;
        margin-bottom: 9px;
        color: #a8754e;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 2px;
    }

    .pb-orders-heading h1 {
        margin: 0 0 8px;
        color: var(--green);
        font-family: 'DM Serif Display', serif;
        font-size: 38px;
        font-weight: 400;
        line-height: 1.2;
    }

    .pb-orders-heading p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
    }

    /* ALERT */
    .pb-order-alert {
        padding: 12px 16px;
        margin-bottom: 15px;
        border: 1px solid #f0dfb9;
        border-radius: 9px;
        background: #fff8e9;
        color: #88601f;
        font-size: 13px;
    }

    /* ORDER CARD */
    .pb-order-card {
        display: block;
        margin-bottom: 22px;
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(25, 60, 45, .035);
    }

    /* HEADER */
    .pb-order-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 19px 23px;
        border-bottom: 1px solid var(--border);
    }

    .pb-order-header-info {
        min-width: 0;
    }

    .pb-order-header-label {
        display: block;
        margin-bottom: 3px;
        color: var(--muted);
        font-size: 11px;
    }

    .pb-order-code {
        display: block;
        color: var(--green);
        font-size: 15px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .pb-order-date {
        display: block;
        margin-top: 4px;
        color: var(--muted);
        font-size: 12px;
    }

    /* STATUS */
    .pb-order-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0;
        padding: 7px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .pb-order-badge::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .pb-order-badge.success {
        color: #24734f;
        background: #eaf5ee;
    }

    .pb-order-badge.pending {
        color: #9a6a19;
        background: #fff5df;
    }

    .pb-order-badge.failed {
        color: #a34444;
        background: #fceeee;
    }

    .pb-order-badge.neutral {
        color: #596c61;
        background: #eef2ef;
    }

    /* BODY */
    .pb-order-body {
        display: block;
        padding: 22px 23px;
    }

    .pb-order-section-title {
        margin: 0 0 14px;
        color: var(--green);
        font-size: 14px;
        font-weight: 700;
    }

    /* PROGRESS */
    .pb-order-progress {
        padding: 17px 19px;
        margin-bottom: 25px;
        border-radius: 9px;
        background: #f7faf7;
    }

    .pb-order-progress-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .pb-order-progress-heading .pb-order-section-title {
        margin: 0;
    }

    .pb-order-progress-caption {
        color: var(--muted);
        font-size: 11px;
    }

    .pb-order-steps {
        display: flex;
        gap: 8px;
        margin-top: 20px;
    }

    .pb-order-step {
        position: relative;
        flex: 1;
        color: #a0aaa2;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
    }

    .pb-order-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 15px;
        left: calc(50% + 19px);
        width: calc(100% - 30px);
        height: 2px;
        background: #dfe7e0;
    }

    .pb-order-step.complete:not(:last-child)::after {
        background: var(--green);
    }

    .pb-order-step-icon {
        display: flex;
        position: relative;
        z-index: 1;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        margin: 0 auto 7px;
        border: 2px solid #e0e8e1;
        border-radius: 50%;
        background: #fff;
        color: #8b978e;
        font-size: 12px;
    }

    .pb-order-step.complete {
        color: var(--green);
    }

    .pb-order-step.complete .pb-order-step-icon {
        border-color: var(--green);
        background: var(--green);
        color: #fff;
    }

    .pb-order-progress-note {
        padding-top: 12px;
        margin: 14px 0 0;
        border-top: 1px solid #e5ece5;
        color: #718176;
        font-size: 12px;
        text-align: center;
    }

    /* PRODUCT */
    .pb-order-product-list {
        margin-bottom: 24px;
    }

    .pb-order-product {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 13px 0;
        border-bottom: 1px solid #f0f2ef;
    }

    .pb-order-product:first-child {
        padding-top: 0;
    }

    .pb-order-product:last-child {
        border-bottom: 0;
    }

    .pb-order-product-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .pb-order-image {
        display: block;
        width: 72px;
        height: 72px;
        flex: 0 0 72px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #f5f6f2;
        object-fit: cover;
    }

    .pb-order-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        color: var(--muted);
        font-size: 10px;
        text-align: center;
    }

    .pb-order-product-info {
        min-width: 0;
    }

    .pb-order-product-name {
        display: block;
        margin-bottom: 4px;
        color: #304a3b;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.5;
    }

    .pb-order-product-meta {
        color: var(--muted);
        font-size: 11px;
        line-height: 1.7;
    }

    .pb-order-product-price {
        flex-shrink: 0;
        color: var(--green);
        font-size: 13px;
        font-weight: 700;
        text-align: right;
    }

    /* DETAIL SECTION (DAPAT DIBUKA / DITUTUP) */
    .pb-order-detail-section {
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }

    .pb-order-toggle-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        color: var(--green);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        list-style: none;
        user-select: none;
    }

    .pb-order-toggle-summary::-webkit-details-marker {
        display: none;
    }

    .pb-order-toggle-hint {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 600;
    }

    .pb-order-toggle-hint::before {
        content: 'Lihat detail';
    }

    .pb-order-toggle[open] .pb-order-toggle-hint::before {
        content: 'Sembunyikan';
    }

    .pb-order-toggle-hint::after {
        content: '▾';
        transition: transform .2s ease;
    }

    .pb-order-toggle[open] .pb-order-toggle-hint::after {
        transform: rotate(180deg);
    }

    .pb-order-details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px;
        margin-top: 18px;
    }

    .pb-order-detail-column {
        min-width: 0;
    }

    .pb-order-detail-column h3 {
        margin: 0 0 12px;
        color: var(--green);
        font-size: 13px;
        font-weight: 700;
    }

    .pb-order-info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f2ef;
        font-size: 12px;
    }

    .pb-order-info-row:last-child {
        border-bottom: 0;
    }

    .pb-order-info-row span {
        color: var(--muted);
    }

    .pb-order-info-row strong {
        display: block;
        max-width: 65%;
        color: #405448;
        font-weight: 600;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .pb-order-info-row.pb-order-details-total {
        padding-top: 12px;
        margin-top: 5px;
        border-top: 1px solid #dfe7df;
        border-bottom: 0;
    }

    .pb-order-info-row.pb-order-details-total span,
    .pb-order-info-row.pb-order-details-total strong {
        color: var(--green);
        font-weight: 700;
    }

    /* PAYMENT SUMMARY */
    .pb-order-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding: 16px 19px;
        margin-top: 22px;
        border-radius: 9px;
        background: #f7faf7;
    }

    .pb-order-payment-info {
        min-width: 0;
    }

    .pb-order-payment-label,
    .pb-order-total-label {
        display: block;
        color: var(--muted);
        font-size: 11px;
    }

    .pb-order-payment-method {
        margin-top: 3px;
        color: #405448;
        font-size: 13px;
        font-weight: 700;
    }

    .pb-order-payment-status {
        margin-top: 3px;
        color: var(--muted);
        font-size: 11px;
    }

    .pb-order-total {
        flex-shrink: 0;
        text-align: right;
    }

    .pb-order-total strong {
        display: block;
        margin-top: 3px;
        color: var(--green);
        font-size: 22px;
        font-weight: 700;
    }

    /* ACTIONS */
    .pb-order-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
        padding: 15px 23px;
        border-top: 1px solid var(--border);
        background: #fcfdfb;
    }

    .pb-order-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 9px 17px;
        border: 1px solid var(--green);
        border-radius: 7px;
        background: var(--green);
        color: #fff !important;
        font-family: inherit;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: .2s ease;
    }

    .pb-order-button:hover {
        background: var(--green-dark);
        border-color: var(--green-dark);
    }

    .pb-order-button-danger {
        border-color: #e4c9c5;
        background: #fff;
        color: #a34444 !important;
    }

    .pb-order-button-danger:hover {
        border-color: #d9aaa4;
        background: #fff5f3;
        color: #a34444 !important;
    }

    .pb-order-actions form {
        margin: 0;
    }

    .pb-order-actions .pb-order-alert {
        margin: 0;
    }

    /* EMPTY */
    .pb-order-empty {
        padding: 55px 20px;
        border: 1px solid var(--border);
        border-radius: 13px;
        background: #fff;
        text-align: center;
    }

    .pb-order-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 55px;
        height: 55px;
        margin: 0 auto 15px;
        border-radius: 50%;
        background: var(--green-soft);
        color: var(--green);
        font-size: 24px;
    }

    .pb-order-empty h2 {
        margin: 0 0 7px;
        color: var(--green);
        font-family: 'DM Serif Display', serif;
        font-size: 25px;
        font-weight: 400;
    }

    .pb-order-empty p {
        margin: 0 0 20px;
        color: var(--muted);
        font-size: 13px;
    }

    /* PAGINATION */
    .pb-order-pagination {
        display: flex;
        justify-content: center;
        margin-top: 28px;
    }

    /* RESPONSIVE */
    @media (max-width: 650px) {
        .pb-orders-page {
            width: calc(100% - 26px);
            padding: 28px 0 45px;
        }

        .pb-orders-heading h1 {
            font-size: 32px;
        }

        .pb-orders-heading p {
            font-size: 13px;
        }

        .pb-order-card-header {
            align-items: flex-start;
            padding: 16px;
        }

        .pb-order-code {
            font-size: 13px;
        }

        .pb-order-badge {
            padding: 6px 9px;
            font-size: 10px;
        }

        .pb-order-body {
            padding: 18px 15px;
        }

        .pb-order-progress {
            padding: 15px 10px;
        }

        .pb-order-progress-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 3px;
        }

        .pb-order-steps {
            gap: 2px;
        }

        .pb-order-step {
            font-size: 10px;
        }

        .pb-order-step-icon {
            width: 29px;
            height: 29px;
        }

        .pb-order-step:not(:last-child)::after {
            top: 13px;
            left: calc(50% + 17px);
            width: calc(100% - 23px);
        }

        .pb-order-product-left {
            gap: 10px;
        }

        .pb-order-image {
            width: 60px;
            height: 60px;
            flex-basis: 60px;
        }

        .pb-order-product-name {
            font-size: 12px;
        }

        .pb-order-product-meta {
            font-size: 10px;
        }

        .pb-order-product-price {
            font-size: 11px;
        }

        .pb-order-details-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .pb-order-summary {
            align-items: flex-start;
            flex-direction: column;
            gap: 13px;
            padding: 15px;
        }

        .pb-order-total {
            width: 100%;
            padding-top: 12px;
            border-top: 1px solid #e3eae3;
            text-align: left;
        }

        .pb-order-total strong {
            font-size: 21px;
        }

        .pb-order-actions {
            align-items: stretch;
            flex-direction: column;
            padding: 14px 15px;
        }

        .pb-order-actions form,
        .pb-order-actions form button,
        .pb-order-actions > a {
            width: 100%;
        }

        .pb-order-actions .pb-order-alert {
            width: 100%;
        }
    }

    @media (max-width: 380px) {
        .pb-order-card-header {
            flex-direction: column;
        }

        .pb-order-badge {
            align-self: flex-start;
        }

        .pb-order-product {
            align-items: flex-start;
        }

        .pb-order-product-price {
            max-width: 95px;
            white-space: normal;
        }
    }
</style>

<section class="pb-orders-page">

    <div class="pb-orders-heading">
        <span class="pb-orders-eyebrow">PALUGADA BANDAR / PESANAN</span>
        <h1>Pesanan Saya</h1>
        <p>Pantau pembayaran, proses pesanan, dan informasi pengiriman produk tenun yang telah kamu pesan.</p>
    </div>

    @if(session('success'))
        <div class="pb-order-alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pb-order-alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="pb-order-alert">{{ $errors->first() }}</div>
    @endif

    @forelse($daftarPesanan as $pesanan)

        @php
            $pembayaran = $pesanan->pembayaran->sortByDesc('id')->first();
            $status = $pesanan->status_pesanan;

            $statusLabel = match ($status) {
                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                'dibayar' => 'Sudah Dibayar',
                'diproses' => 'Sedang Diproses',
                'dikirim' => 'Sedang Dikirim',
                'selesai' => 'Selesai',
                'batal', 'dibatalkan' => 'Dibatalkan',
                default => ucwords(str_replace('_', ' ', $status ?? '')),
            };

            $statusClass = match ($status) {
                'dibayar', 'selesai' => 'success',
                'menunggu_pembayaran' => 'pending',
                'batal', 'dibatalkan' => 'failed',
                default => 'neutral',
            };

            $tahapan = [
                'dibayar' => 1,
                'diproses' => 2,
                'dikirim' => 3,
                'selesai' => 4,
            ];

            $tahapAktif = $tahapan[$status] ?? 0;

            $metodePembayaran = match ($pembayaran?->metode) {
                'midtrans' => 'Midtrans (Online)',
                'cash' => 'Cash (Bayar di Tempat)',
                default => 'Belum tersedia',
            };

            $metodePengiriman = match ($pesanan->metode_pengiriman) {
                'ekspedisi' => 'Ekspedisi',
                'ambil_di_tempat' => 'Ambil di Tempat',
                default => 'Belum tersedia',
            };

            $sesiAktif =
                !empty($pembayaran?->snap_redirect_url) &&
                !empty($pembayaran?->snap_expires_at) &&
                \Illuminate\Support\Carbon::parse($pembayaran->snap_expires_at)->isFuture();

            $bolehBatalkan =
                $status === 'menunggu_pembayaran' &&
                $pembayaran?->status_pembayaran === 'menunggu' &&
                \Illuminate\Support\Facades\Route::has('pembeli.pesanan.batalkan');
        @endphp

        <article class="pb-order-card">

            {{-- HEADER PESANAN --}}
            <div class="pb-order-card-header">
                <div class="pb-order-header-info">
                    <span class="pb-order-header-label">Kode Pesanan</span>
                    <strong class="pb-order-code">{{ $pesanan->kode_pesanan }}</strong>
                    <span class="pb-order-date">
                        {{ $pesanan->tanggal_pesanan?->format('d M Y, H:i') }}
                    </span>
                </div>

                <span class="pb-order-badge {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="pb-order-body">

                {{-- PROGRESS --}}
                @if($tahapAktif > 0)
                    <details class="pb-order-progress pb-order-toggle">
                        <summary class="pb-order-toggle-summary">
                            <span>Perkembangan Pesanan</span>
                            <span class="pb-order-toggle-hint"></span>
                        </summary>

                        <div class="pb-order-steps">
                            @foreach([
                                1 => 'Dibayar',
                                2 => 'Diproses',
                                3 => 'Dikirim',
                                4 => 'Selesai'
                            ] as $nomor => $label)
                                <div class="pb-order-step {{ $nomor <= $tahapAktif ? 'complete' : '' }}">
                                    <span class="pb-order-step-icon">
                                        {{ $nomor < $tahapAktif ? '✓' : $nomor }}
                                    </span>
                                    <span>{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        <p class="pb-order-progress-note">
                            @if($status === 'dibayar')
                                Pembayaran berhasil. Pesanan menunggu diproses admin.
                            @elseif($status === 'diproses')
                                Pesanan sedang diproses oleh pihak toko.
                            @elseif($status === 'dikirim')
                                Pesanan sudah dikirim dan sedang dalam perjalanan.
                            @elseif($status === 'selesai')
                                Pesanan telah diselesaikan.
                            @endif
                        </p>
                    </details>
                @endif

                {{-- PRODUK --}}
                <h3 class="pb-order-section-title">Produk yang Dipesan</h3>

                <div class="pb-order-product-list">
                    @foreach($pesanan->detail as $item)
                        @php
                            $produk = $item->produk;

                            $foto = $produk?->produkFoto?->first()?->foto ?: $produk?->foto;
                            $fotoUrl = null;

                            if ($foto) {
                                if (\Illuminate\Support\Str::startsWith($foto, ['http://', 'https://'])) {
                                    $fotoUrl = $foto;
                                } else {
                                    // URL relatif agar tetap jalan lewat ngrok / domain apa pun
                                    $path = ltrim($foto, '/');
                                    $path = \Illuminate\Support\Str::after($path, 'storage/');
                                    $fotoUrl = '/storage/' . $path;
                                }
                            }
                        @endphp

                        <div class="pb-order-product">
                            <div class="pb-order-product-left">
                                @if($fotoUrl)
                                    <img
                                        class="pb-order-image"
                                        src="{{ $fotoUrl }}"
                                        alt="{{ $produk?->nama_produk ?? 'Produk' }}"
                                        loading="lazy">
                                @else
                                    <div class="pb-order-image pb-order-no-image">
                                        Foto belum tersedia
                                    </div>
                                @endif

                                <div class="pb-order-product-info">
                                    <strong class="pb-order-product-name">
                                        {{ $produk?->nama_produk ?? 'Produk tidak tersedia' }}
                                    </strong>

                                    <div class="pb-order-product-meta">
                                        {{ $item->jumlah }} × Rp{{ number_format((float) $item->harga, 0, ',', '.') }}
                                    </div>

                                    @if($produk?->motif)
                                        <div class="pb-order-product-meta">
                                            Motif: {{ $produk->motif }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <strong class="pb-order-product-price">
                                Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}
                            </strong>
                        </div>
                    @endforeach
                </div>

                {{-- DETAIL PENGIRIMAN DAN PEMBAYARAN (KLIK UNTUK MEMBUKA) --}}
                <details class="pb-order-detail-section pb-order-toggle">
                    <summary class="pb-order-toggle-summary">
                        <span>Detail Pesanan</span>
                        <span class="pb-order-toggle-hint"></span>
                    </summary>

                    <div class="pb-order-details-grid">

                        <div class="pb-order-detail-column">
                            <h3>Informasi Pengiriman</h3>

                            <div class="pb-order-info-row">
                                <span>Metode Pengiriman</span>
                                <strong>{{ $metodePengiriman }}</strong>
                            </div>

                            @if($pesanan->metode_pengiriman === 'ekspedisi')
                                <div class="pb-order-info-row">
                                    <span>Kurir</span>
                                    <strong>{{ $pesanan->kurir_nama ?: '-' }}</strong>
                                </div>

                                <div class="pb-order-info-row">
                                    <span>Layanan</span>
                                    <strong>{{ $pesanan->layanan_kurir ?: '-' }}</strong>
                                </div>

                                <div class="pb-order-info-row">
                                    <span>Tujuan</span>
                                    <strong>{{ $pesanan->tujuan_label ?: '-' }}</strong>
                                </div>

                                <div class="pb-order-info-row">
                                    <span>Estimasi</span>
                                    <strong>{{ $pesanan->estimasi_kirim ?: '-' }}</strong>
                                </div>

                                <div class="pb-order-info-row">
                                    <span>Berat Total</span>
                                    <strong>
                                        {{ number_format((int) $pesanan->berat_total_gram, 0, ',', '.') }} gram
                                    </strong>
                                </div>
                            @endif
                        </div>

                        <div class="pb-order-detail-column">
                            <h3>Rincian Pembayaran</h3>

                            <div class="pb-order-info-row">
                                <span>Metode Pembayaran</span>
                                <strong>{{ $metodePembayaran }}</strong>
                            </div>

                            <div class="pb-order-info-row">
                                <span>Status Pembayaran</span>
                                <strong>
                                    {{ ucfirst(str_replace('_', ' ', $pembayaran?->status_pembayaran ?? 'Belum tersedia')) }}
                                </strong>
                            </div>

                            <div class="pb-order-info-row">
                                <span>Subtotal Produk</span>
                                <strong>
                                    Rp{{ number_format((float) ($pesanan->subtotal ?? $pesanan->total), 0, ',', '.') }}
                                </strong>
                            </div>

                            <div class="pb-order-info-row">
                                <span>Ongkos Kirim</span>
                                <strong>
                                    Rp{{ number_format((float) ($pesanan->ongkir ?? 0), 0, ',', '.') }}
                                </strong>
                            </div>

                            <div class="pb-order-info-row pb-order-details-total">
                                <span>Total Pembayaran</span>
                                <strong>
                                    Rp{{ number_format((float) $pesanan->total, 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>

                    </div>
                </details>

                {{-- RINGKASAN --}}
                <div class="pb-order-summary">
                    <div class="pb-order-payment-info">
                        <span class="pb-order-payment-label">Metode Pembayaran</span>
                        <div class="pb-order-payment-method">{{ $metodePembayaran }}</div>
                        <div class="pb-order-payment-status">
                            Status:
                            {{ ucfirst(str_replace('_', ' ', $pembayaran?->status_pembayaran ?? 'Belum tersedia')) }}
                        </div>
                    </div>

                    <div class="pb-order-total">
                        <span class="pb-order-total-label">Total Pembayaran</span>
                        <strong>
                            Rp{{ number_format((float) $pesanan->total, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>

                {{-- INFORMASI MIDTRANS --}}
                @if($status === 'menunggu_pembayaran' && $pembayaran?->metode === 'midtrans')
                    <div class="pb-order-alert" style="margin-top:15px;margin-bottom:0">
                        Pembayaran belum selesai. Sesi Midtrans yang masih aktif dapat dibuka kembali.
                    </div>
                @endif

            </div>

            {{-- TOMBOL AKSI --}}
            @if($status === 'menunggu_pembayaran' && $pembayaran)
                <div class="pb-order-actions">

                    @if($pembayaran->metode === 'midtrans')
                        @if(
                            $sesiAktif &&
                            $pembayaran->status_pembayaran === 'menunggu' &&
                            \Illuminate\Support\Facades\Route::has('pembeli.pesanan.bayar')
                        )
                            <a
                                href="{{ route('pembeli.pesanan.bayar', $pesanan->id) }}"
                                class="pb-order-button">
                                Bayar Sekarang →
                            </a>
                        @else
                            <span class="pb-order-alert">
                                Sesi pembayaran tidak tersedia atau kedaluwarsa.
                            </span>
                        @endif
                    @endif

                    @if($bolehBatalkan)
                        <form
                            method="POST"
                            action="{{ route('pembeli.pesanan.batalkan', $pesanan->id) }}"
                            onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">

                            @csrf
                            @method('PATCH')

                            <button type="submit" class="pb-order-button pb-order-button-danger">
                                Batalkan Pesanan
                            </button>
                        </form>
                    @endif

                </div>
            @endif

        </article>

    @empty

        <div class="pb-order-empty">
            <div class="pb-order-empty-icon">▤</div>
            <h2>Belum Ada Pesanan</h2>
            <p>
                Produk yang kamu pesan akan tampil di sini.
                Yuk, temukan produk tenun favoritmu.
            </p>

            <a class="pb-order-button" href="{{ route('pembeli.katalog') }}">
                Mulai Belanja →
            </a>
        </div>

    @endforelse

    {{-- PAGINATION --}}
    @if($daftarPesanan->hasPages())
        <div class="pb-order-pagination">
            {{ $daftarPesanan->links('vendor.pagination.palugada') }}
        </div>
    @endif

</section>
@endsection