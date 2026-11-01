@extends('layouts.app')

@section('title', 'Pesanan Saya - Palugada Bandar')

@section('content')
<style>
    .pg-orders {
        max-width: 1180px;
        margin: 0 auto;
        padding: 35px 20px 80px;
        color: #173d32;
    }

    .pg-orders * {
        box-sizing: border-box;
    }

    .pg-orders-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 30px;
    }

    .pg-orders-eyebrow {
        display: block;
        color: #b77249;
        letter-spacing: 2px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 9px;
    }

    .pg-orders-head h1 {
        margin: 0 0 8px;
        font-size: clamp(28px, 4vw, 40px);
        color: #133d31;
    }

    .pg-orders-head p {
        margin: 0;
        color: #7c8781;
        font-size: 14px;
    }

    .pg-orders-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border: 1px solid #d5ded7;
        background: white;
        border-radius: 10px;
        color: #164536;
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
    }

    .pg-orders-card {
        background: #fff;
        border: 1px solid #e1e7df;
        border-radius: 17px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(20, 55, 44, .035);
    }

    .pg-orders-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        padding: 22px 25px;
        border-bottom: 1px solid #edf0ea;
    }

    .pg-orders-label {
        display: block;
        font-size: 12px;
        color: #87928a;
        margin-bottom: 7px;
    }

    .pg-orders-code {
        display: block;
        font-size: 16px;
        color: #163f32;
        overflow-wrap: anywhere;
    }

    .pg-orders-date {
        display: block;
        color: #8b958e;
        font-size: 12px;
        margin-top: 7px;
    }

    .pg-orders-badge {
        padding: 8px 13px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
    }

    .pg-orders-badge.pending {
        background: #fff2d9;
        color: #94651d;
    }

    .pg-orders-badge.success {
        background: #e5f4e9;
        color: #267145;
    }

    .pg-orders-badge.failed {
        background: #fce9e7;
        color: #aa4843;
    }

    .pg-orders-badge.neutral {
        background: #edf1ef;
        color: #55675e;
    }

    .pg-orders-content {
        padding: 22px 25px;
    }

    .pg-orders-products {
        margin-bottom: 20px;
    }

    .pg-orders-product {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 11px 0;
        border-bottom: 1px solid #f0f1ed;
    }

    .pg-orders-product-info {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .pg-orders-product-image {
        width: 104px;
        height: 104px;
        flex: 0 0 104px;
        border-radius: 12px;
        overflow: hidden;
        background: #f4f1e9;
        border: 1px solid #e2e7df;
    }

    .pg-orders-product-image img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .pg-orders-no-image {
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        color: #87928a;
        text-align: center;
        font-size: 11px;
    }

    .pg-orders-product-name {
        font-size: 14px;
        font-weight: 600;
        color: #254b3d;
    }

    .pg-orders-product-qty {
        font-size: 12px;
        color: #919a93;
        margin-top: 5px;
    }

    .pg-orders-product-price {
        font-size: 14px;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    .pg-orders-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 20px;
        padding-top: 15px;
    }

    .pg-orders-method {
        font-size: 13px;
        font-weight: 600;
        color: #416254;
    }

    .pg-orders-total {
        text-align: right;
    }

    .pg-orders-total strong {
        display: block;
        font-size: 23px;
        color: #174b3b;
    }

    .pg-orders-alert {
        margin-top: 20px;
        padding: 13px 16px;
        background: #f6f7f2;
        border-radius: 9px;
        color: #67756c;
        font-size: 12px;
        line-height: 1.7;
    }

    .pg-orders-empty {
        padding: 65px 25px;
        background: #fff;
        border: 1px solid #e4e8e1;
        border-radius: 18px;
        text-align: center;
    }

    .pg-orders-empty h2 {
        margin: 0 0 12px;
        font-size: 22px;
    }

    .pg-orders-empty p {
        margin-bottom: 22px;
        color: #808b82;
    }

    /* Tombol pembayaran dan pembatalan */

    .pg-payment-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 12px;
        padding: 16px 25px 22px;
        border-top: 1px solid #edf0ea;
    }

    .pg-payment-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 22px;
        border-radius: 10px;
        background: #174b3b;
        color: #fff !important;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        min-height: 43px;
    }

    .pg-payment-button:hover {
        background: #10392d;
    }

    .pg-payment-expired {
        border-radius: 10px;
        padding: 11px 14px;
        background: #fff2df;
        color: #95611b;
        font-size: 12px;
        font-weight: 600;
    }

    .pg-payment-actions form {
        margin: 0;
    }

    .pg-cancel-button {
        padding: 12px 20px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #d9a6a0;
        color: #a4423b;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        min-height: 43px;
    }

    .pg-cancel-button:hover {
        background: #fff0ee;
        border-color: #ba5a50;
    }

    /* Pagination */

    .pg-orders-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        padding: 16px 20px;
        border: 1px solid #e1e7df;
        border-radius: 13px;
        background: #fff;
        margin-top: 28px;
    }

    .pg-pagination-info {
        color: #76847a;
        font-size: 12px;
    }

    .pg-pagination-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    .pg-page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 35px;
        padding: 9px 11px;
        border: 1px solid #dfe7df;
        border-radius: 9px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        color: #174b3b;
        background: #fff;
    }

    .pg-page-current {
        background: #174b3b;
        color: #fff;
        border-color: #174b3b;
    }

    .pg-page-disabled {
        color: #a1aba4;
        background: #f8faf7;
    }

    @media (max-width: 600px) {
        .pg-orders {
            padding: 25px 15px 50px;
        }

        .pg-orders-card-head,
        .pg-orders-content {
            padding: 19px;
        }

        .pg-orders-total {
            text-align: left;
        }

        .pg-orders-total strong {
            font-size: 21px;
        }

        .pg-orders-product-info {
            gap: 11px;
        }

        .pg-orders-product-image {
            width: 74px;
            height: 74px;
            flex-basis: 74px;
        }

        .pg-orders-product-price {
            font-size: 12px;
        }

        .pg-payment-actions {
            padding: 16px 19px 20px;
        }

        .pg-payment-button,
        .pg-cancel-button {
            font-size: 12px;
        }
    }
</style>

<section class="pg-orders">

    <header class="pg-orders-head">
        <div>
            <span class="pg-orders-eyebrow">
                PALUGADA BANDAR / PESANAN
            </span>

            <h1>Pesanan Saya</h1>

            <p>
                Pantau status pembayaran dan riwayat pembelianmu.
            </p>
        </div>
    </header>

    @if(session('success'))
        <div class="pg-orders-alert">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="pg-orders-alert"
             style="background:#fff0ee;color:#a13c32;">
            {{ $errors->first() }}
        </div>
    @endif

    @if($daftarPesanan->isEmpty())

        <div class="pg-orders-empty">
            <h2>Belum Ada Pesanan</h2>

            <p>
                Produk yang kamu pesan akan tampil di halaman ini.
            </p>

            <a href="{{ route('pembeli.katalog') }}"
               class="pg-orders-link">
                Mulai Belanja
            </a>
        </div>

    @else

        @foreach($daftarPesanan as $pesanan)

            @php
                $pembayaran = $pesanan->pembayaran
                    ->sortByDesc('id')
                    ->first();

                $status = $pesanan->status_pesanan;

                $statusClass = match ($status) {
                    'dibayar', 'selesai' => 'success',
                    'menunggu_pembayaran' => 'pending',
                    'batal', 'dibatalkan' => 'failed',
                    default => 'neutral',
                };

                $statusLabel = match ($status) {
                    'menunggu_pembayaran' => 'Menunggu Pembayaran',
                    'dibayar' => 'Sudah Dibayar',
                    'selesai' => 'Selesai',
                    'batal', 'dibatalkan' => 'Dibatalkan',
                    default => ucwords(str_replace('_', ' ', $status)),
                };

                $metode = match ($pembayaran?->metode) {
                    'midtrans' => 'Midtrans (Online)',
                    'cash' => 'Cash (Konfirmasi Admin)',
                    default => 'Belum tersedia',
                };

                $bolehBatalkan =
                    $status === 'menunggu_pembayaran' &&
                    $pembayaran?->status_pembayaran === 'menunggu' &&
                    \Illuminate\Support\Facades\Route::has(
                        'pembeli.pesanan.batalkan'
                    );

                $sesiAktif =
                    !empty($pembayaran?->snap_redirect_url) &&
                    !empty($pembayaran?->snap_expires_at) &&
                    \Illuminate\Support\Carbon::parse(
                        $pembayaran->snap_expires_at
                    )->isFuture();
            @endphp

            <article class="pg-orders-card">

                <div class="pg-orders-card-head">

                    <div>
                        <span class="pg-orders-label">
                            Kode Pesanan
                        </span>

                        <strong class="pg-orders-code">
                            {{ $pesanan->kode_pesanan }}
                        </strong>

                        <span class="pg-orders-date">
                            {{ optional($pesanan->tanggal_pesanan)->format('d M Y, H:i') }}
                        </span>
                    </div>

                    <span class="pg-orders-badge {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                </div>

                <div class="pg-orders-content">

                    <div class="pg-orders-products">

                        @foreach($pesanan->detail as $item)

                            @php
                                $produk = $item->produk;

                                $foto = $produk?->produkFoto?->first()?->foto
                                    ?: $produk?->foto;

                                $fotoUrl = null;

                                if ($foto) {
                                    if (\Illuminate\Support\Str::startsWith(
                                        $foto,
                                        ['http://', 'https://']
                                    )) {
                                        $fotoUrl = $foto;
                                    } else {
                                        $path = ltrim($foto, '/');

                                        $fotoUrl = \Illuminate\Support\Str::startsWith(
                                            $path,
                                            'storage/'
                                        )
                                            ? asset($path)
                                            : \Illuminate\Support\Facades\Storage::url($path);
                                    }
                                }
                            @endphp

                            <div class="pg-orders-product">

                                <div class="pg-orders-product-info">

                                    <div class="pg-orders-product-image">

                                        @if($fotoUrl)
                                            <img
                                                src="{{ $fotoUrl }}"
                                                alt="{{ $produk?->nama_produk ?? 'Produk PALUGADA' }}"
                                                loading="lazy">
                                        @else
                                            <div class="pg-orders-no-image">
                                                Foto belum tersedia
                                            </div>
                                        @endif

                                    </div>

                                    <div>

                                        <div class="pg-orders-product-name">
                                            {{ $produk?->nama_produk ?? 'Produk tidak tersedia' }}
                                        </div>

                                        <div class="pg-orders-product-qty">
                                            {{ $item->jumlah }} &times;
                                            Rp{{ number_format((float) $item->harga, 0, ',', '.') }}
                                        </div>

                                        @if($produk?->motif)
                                            <div class="pg-orders-product-qty">
                                                Motif: {{ $produk->motif }}
                                            </div>
                                        @endif

                                    </div>

                                </div>

                                <div class="pg-orders-product-price">
                                    Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                </div>

                            </div>

                        @endforeach

                    </div>

                    <div class="pg-orders-bottom">

                        <div>
                            <span class="pg-orders-label">
                                Metode Pembayaran
                            </span>

                            <div class="pg-orders-method">
                                {{ $metode }}
                            </div>

                            <span class="pg-orders-date">
                                Status:
                                {{ ucfirst(str_replace('_', ' ', $pembayaran?->status_pembayaran ?? 'belum tersedia')) }}
                            </span>
                        </div>

                        <div class="pg-orders-total">
                            <span class="pg-orders-label">
                                Total Pembayaran
                            </span>

                            <strong>
                                Rp{{ number_format((float) $pesanan->total, 0, ',', '.') }}
                            </strong>
                        </div>

                    </div>

                    @if(
                        $status === 'menunggu_pembayaran' &&
                        $pembayaran?->metode === 'midtrans'
                    )
                        <div class="pg-orders-alert">
                            Pembayaran belum selesai.
                            Kamu dapat membuka kembali sesi pembayaran
                            yang masih aktif melalui tombol di bawah.
                            Jika sesi sudah kedaluwarsa, transaksi perlu
                            diperiksa sebelum pembayaran baru dibuat.
                        </div>
                    @endif

                </div>

                @if($status === 'menunggu_pembayaran' && $pembayaran)

                    <div class="pg-payment-actions">

                        @if($pembayaran->metode === 'midtrans')

                            @if(
                                $sesiAktif &&
                                $pembayaran->status_pembayaran === 'menunggu' &&
                                \Illuminate\Support\Facades\Route::has('pembeli.pesanan.bayar')
                            )
                                <a
                                    href="{{ route('pembeli.pesanan.bayar', $pesanan->id) }}"
                                    class="pg-payment-button">
                                    Bayar Sekarang
                                </a>
                            @else
                                <span class="pg-payment-expired">
                                    Sesi pembayaran tidak tersedia / kedaluwarsa
                                </span>
                            @endif

                        @endif

                        @if($bolehBatalkan)
                            <form
                                method="POST"
                                action="{{ route('pembeli.pesanan.batalkan', $pesanan->id) }}"
                                onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="pg-cancel-button">
                                    Batalkan Pesanan
                                </button>
                            </form>
                        @endif

                    </div>

                @endif

            </article>

        @endforeach

    @endif

    <div class="pg-orders-pagination">

            <span class="pg-pagination-info">
                Menampilkan
                {{ $daftarPesanan->firstItem() ?? 0 }}–{{ $daftarPesanan->lastItem() ?? 0 }}
                dari {{ $daftarPesanan->total() }} pesanan
            </span>

            <nav class="pg-pagination-controls"
                     aria-label="Navigasi halaman pesanan">

                    @if($daftarPesanan->onFirstPage())
                        <span class="pg-page-link pg-page-disabled">
                            Sebelumnya
                        </span>
                    @else
                        <a class="pg-page-link"
                           href="{{ $daftarPesanan->previousPageUrl() }}">
                            Sebelumnya
                        </a>
                    @endif

                    @php
                        $halamanAwal = max(
                            1,
                            $daftarPesanan->currentPage() - 2
                        );

                        $halamanAkhir = min(
                            $daftarPesanan->lastPage(),
                            $daftarPesanan->currentPage() + 2
                        );
                    @endphp

                    @for($nomor = $halamanAwal; $nomor <= $halamanAkhir; $nomor++)

                        @if($nomor === $daftarPesanan->currentPage())
                            <span class="pg-page-link pg-page-current"
                                  aria-current="page">
                                {{ $nomor }}
                            </span>
                        @else
                            <a class="pg-page-link"
                               href="{{ $daftarPesanan->url($nomor) }}">
                                {{ $nomor }}
                            </a>
                        @endif

                    @endfor

                    @if($daftarPesanan->hasMorePages())
                        <a class="pg-page-link"
                           href="{{ $daftarPesanan->nextPageUrl() }}">
                            Berikutnya
                        </a>
                    @else
                        <span class="pg-page-link pg-page-disabled">
                            Berikutnya
                        </span>
                    @endif

            </nav>

    </div>

</section>
@endsection
