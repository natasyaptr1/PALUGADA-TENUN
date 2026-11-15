@extends('layouts.app')

@section('title', 'Rekomendasi Untukmu - Palugada Bandar')

@section('content')

<section class="collection-page collection-page-v4">

    <div class="collection-titlebar-v4">
        <div>
            <span class="eyebrow">KHUSUS UNTUKMU</span>
            <h1>Rekomendasi Produk</h1>
            <p>
                Temukan produk tenun yang sesuai dengan
                kategori favorit dan minat belanjamu.
            </p>
        </div>
    </div>

    {{-- KATEGORI FAVORIT --}}
    @if($kategoriFavorit->isNotEmpty())
        <div class="rekomendasi-favorit">
            <strong>Kategori favoritmu:</strong>

            @foreach($kategoriFavorit as $kategori)
                <span class="rekomendasi-chip">
                    {{ $kategori }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- REKOMENDASI PERSONAL --}}
    <div class="rekomendasi-section">
        <div class="rekomendasi-header">
            <span class="eyebrow">PERSONALISASI</span>
            <h2>Rekomendasi Untukmu</h2>
            <p>
                Produk pilihan berdasarkan kemiripan atribut
                dengan kategori favoritmu.
            </p>
        </div>

        @if($rekomendasiProduk->isNotEmpty())
            <div class="store-product-grid collection-grid collection-grid-v4">
                @foreach($rekomendasiProduk as $produk)
                    @include('pembeli.partials.product-card', [
                        'produk' => $produk
                    ])
                @endforeach
            </div>
        @else
            <div class="rekomendasi-empty">
                Belum ada rekomendasi personal yang sesuai.
            </div>
        @endif
    </div>

    {{-- PRODUK TERLARIS --}}
    <div class="rekomendasi-section">
        <div class="rekomendasi-header">
            <span class="eyebrow">PALING DIMINATI</span>
            <h2>Produk Terlaris</h2>
            <p>
                Produk terpopuler berdasarkan jumlah unit
                yang terjual melalui pembayaran berhasil.
            </p>
        </div>

        @if($produkTerlaris->isNotEmpty())
            <div class="store-product-grid collection-grid collection-grid-v4">
                @foreach($produkTerlaris as $produk)
                    @include('pembeli.partials.product-card', [
                        'produk' => $produk
                    ])
                @endforeach
            </div>
        @else
            <div class="rekomendasi-empty">
                Belum tersedia data produk terlaris.
            </div>
        @endif
    </div>

    <div class="rekomendasi-footer">
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">
            Jelajahi Semua Produk
        </a>
    </div>

</section>

<style>
.rekomendasi-favorit {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin: 0 0 35px;
    font-size: 13px;
    color: #31543e;
}

.rekomendasi-chip {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 30px;
    background: #e9f3eb;
    color: #194d3a;
    font-weight: 600;
    font-size: 12px;
}

.rekomendasi-section {
    margin-bottom: 55px;
}

.rekomendasi-header {
    margin-bottom: 22px;
}

.rekomendasi-header h2 {
    margin: 8px 0;
    color: #194d3a;
    font-size: 25px;
}

.rekomendasi-header p {
    color: #728074;
    font-size: 13px;
}

.rekomendasi-empty {
    padding: 25px;
    border: 1px solid #dce7dd;
    border-radius: 14px;
    background: #f8fbf8;
    color: #728074;
}

.rekomendasi-footer {
    text-align: center;
    margin: 30px 0;
}
</style>

@include('pembeli.partials.product-modal')

@endsection
