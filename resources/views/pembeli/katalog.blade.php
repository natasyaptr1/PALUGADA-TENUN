@extends('layouts.app')

@section('title', 'Koleksi Tenun - Palugada Bandar')

@section('content')

<style>
    /* ==========================================
       KATALOG PALUGADA - FILTER COMPACT
       ========================================== */

    .pg-catalog {
        position: relative;
    }

    .pg-catalog *,
    .pg-catalog *::before,
    .pg-catalog *::after {
        box-sizing: border-box;
    }

    /* AREA PENCARIAN */

    .pg-catalog-toolbar {
        position: relative;
        z-index: 20;
        margin-bottom: 30px;
    }

    .pg-catalog-search-form {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 12px;
        background: #fffaf4;
        border: 1px solid #e7d7c4;
        border-radius: 15px;
        box-shadow: 0 5px 18px rgba(35, 64, 48, .035);
    }

    /* INPUT PENCARIAN */

    .pg-catalog-search {
        position: relative;
        flex: 1;
        min-width: 0;
    }

    .pg-catalog-search-icon {
        position: absolute;
        left: 17px;
        top: 50%;
        transform: translateY(-50%);
        width: 19px;
        height: 19px;
        color: #567267;
        pointer-events: none;
    }

    .pg-catalog-search-icon circle,
    .pg-catalog-search-icon path {
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        fill: none;
    }

    .pg-catalog-search input {
        display: block;
        width: 100%;
        height: 46px;
        padding: 0 16px 0 47px;

        background: #ffffff;
        border: 1px solid #dfe4dc;
        border-radius: 10px;

        color: #173e32;
        font-family: inherit;
        font-size: 13px;

        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .pg-catalog-search input:focus {
        border-color: #28664f;
        box-shadow: 0 0 0 3px rgba(40, 102, 79, .10);
    }

    .pg-catalog-search input::placeholder {
        color: #8b958d;
    }

    /* TOMBOL FILTER */

    .pg-catalog-filter {
        position: relative;
        flex: 0 0 auto;
    }

    .pg-catalog-filter-summary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;

        min-width: 110px;
        height: 46px;
        padding: 0 17px;

        background: #ffffff;
        border: 1px solid #d7e0d8;
        border-radius: 10px;

        color: #174b3b;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;

        cursor: pointer;
        user-select: none;
        list-style: none;
        transition: background .2s, border-color .2s;
    }

    .pg-catalog-filter-summary::-webkit-details-marker {
        display: none;
    }

    .pg-catalog-filter-summary::marker {
        content: "";
    }

    .pg-catalog-filter-summary:hover {
        background: #edf5ef;
    }

    .pg-catalog-filter[open] .pg-catalog-filter-summary {
        background: #e8f2eb;
        border-color: #316a53;
    }

    .pg-catalog-filter-summary svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        stroke: currentColor;
        stroke-width: 1.8;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .pg-catalog-filter-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-width: 19px;
        height: 19px;
        padding: 0 5px;

        border-radius: 20px;
        background: #174b3b;
        color: #ffffff;

        font-size: 10px;
        font-weight: 700;
    }

    /* PANEL FILTER */

    .pg-catalog-filter-panel {
        position: absolute;
        top: calc(100% + 13px);
        right: 0;

        width: min(420px, calc(100vw - 40px));
        padding: 22px;

        background: #ffffff;
        border: 1px solid #dfe6de;
        border-radius: 15px;
        box-shadow: 0 16px 40px rgba(20, 50, 38, .14);

        z-index: 50;
    }

    .pg-catalog-filter-panel::before {
        content: "";
        position: absolute;
        top: -7px;
        right: 42px;

        width: 13px;
        height: 13px;
        transform: rotate(45deg);

        background: #ffffff;
        border-left: 1px solid #dfe6de;
        border-top: 1px solid #dfe6de;
    }

    .pg-catalog-filter-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;

        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #edf0eb;
    }

    .pg-catalog-filter-head h3 {
        margin: 0 0 5px;
        color: #173e32;
        font-size: 16px;
        font-weight: 700;
    }

    .pg-catalog-filter-head p {
        margin: 0;
        color: #89958c;
        font-size: 12px;
        line-height: 1.5;
    }

    .pg-catalog-filter-close {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 28px;
        height: 28px;
        flex: 0 0 28px;

        padding: 0;
        border: none;
        border-radius: 7px;

        background: #f2f5f1;
        color: #496153;

        cursor: pointer;
        font-size: 20px;
    }

    .pg-catalog-filter-close:hover {
        background: #e4ebe4;
    }

    .pg-catalog-filter-field {
        margin-bottom: 17px;
    }

    .pg-catalog-filter-field > label {
        display: block;
        margin-bottom: 8px;

        color: #36594a;
        font-size: 12px;
        font-weight: 700;
    }

    .pg-catalog-filter-field select,
    .pg-catalog-filter-field input {
        display: block;
        width: 100%;
        height: 43px;
        padding: 0 13px;

        background: #ffffff;
        border: 1px solid #dce4db;
        border-radius: 9px;

        color: #254538;
        font-family: inherit;
        font-size: 13px;

        outline: none;
    }

    .pg-catalog-filter-field select:focus,
    .pg-catalog-filter-field input:focus {
        border-color: #28664f;
        box-shadow: 0 0 0 3px rgba(40, 102, 79, .09);
    }

    .pg-catalog-price-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    /* FILTER STOK */

    .pg-catalog-stock {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 13px 14px;
        margin: 4px 0 20px;

        border: 1px solid #e3e9e2;
        border-radius: 9px;
        background: #f9fbf8;

        color: #36594a;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .pg-catalog-stock input {
        width: 17px;
        height: 17px;
        margin: 0;
        flex-shrink: 0;
        accent-color: #174b3b;
    }

    /* TOMBOL PANEL */

    .pg-catalog-filter-actions {
        display: flex;
        gap: 10px;
    }

    .pg-catalog-apply {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-height: 44px;
        padding: 10px 15px;

        border: none;
        border-radius: 9px;

        background: #174b3b;
        color: white;

        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .pg-catalog-apply:hover {
        background: #10392d;
    }

    .pg-catalog-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-height: 44px;
        padding: 10px 16px;

        border: 1px solid #e5dcd0;
        border-radius: 9px;

        background: #fff;
        color: #a16e4f;

        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
    }

    .pg-catalog-reset:hover {
        background: #fff5eb;
    }

    /* TOMBOL CARI */

    .pg-catalog-search-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        height: 46px;
        min-width: 104px;
        padding: 0 23px;

        border: none;
        border-radius: 10px;
        background: #174b3b;
        color: #ffffff;

        font-family: inherit;
        font-size: 13px;
        font-weight: 700;

        cursor: pointer;
        transition: background .2s;
    }

    .pg-catalog-search-submit:hover {
        background: #10392d;
    }

    /* JUDUL KOLEKSI */

    .pg-catalog .collection-titlebar-v4 {
        margin-top: 14px;
    }

    .pg-catalog-active-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;

        margin: -8px 0 27px;
    }

    .pg-catalog-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;

        padding: 7px 11px;

        border: 1px solid #dbe7dc;
        border-radius: 20px;

        background: #eaf3ec;
        color: #315846;

        font-size: 11px;
        font-weight: 600;
    }

    .pg-catalog-clear-link {
        color: #ad7252;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        margin-left: 4px;
    }

    .pg-catalog-clear-link:hover {
        text-decoration: underline;
    }

    /* RESPONSIVE */

    @media (max-width: 768px) {
        .pg-catalog-search-form {
            gap: 8px;
            padding: 10px;
        }

        .pg-catalog-filter-summary {
            min-width: 95px;
            padding: 0 12px;
        }

        .pg-catalog-search-submit {
            min-width: 77px;
            padding: 0 15px;
        }
    }

    @media (max-width: 560px) {
        .pg-catalog-toolbar {
            margin-bottom: 23px;
        }

        .pg-catalog-search-form {
            flex-wrap: wrap;
        }

        .pg-catalog-search {
            flex: 0 0 100%;
        }

        .pg-catalog-filter {
            flex: 1;
        }

        .pg-catalog-filter-summary {
            width: 100%;
        }

        .pg-catalog-search-submit {
            flex: 1;
        }

        .pg-catalog-filter-panel {
            right: 0;
            width: calc(100vw - 40px);
            padding: 18px;
        }

        .pg-catalog-price-fields {
            gap: 8px;
        }

        .pg-catalog-filter-field input {
            font-size: 12px;
        }
    }
</style>

<section class="collection-page collection-page-v4 pg-catalog">

    @php
        // Menghitung jumlah filter yang sedang aktif.
        $jumlahFilterAktif = 0;

        if ($jenis) {
            $jumlahFilterAktif++;
        }

        if ($hargaMin !== null && $hargaMin !== '') {
            $jumlahFilterAktif++;
        }

        if ($hargaMax !== null && $hargaMax !== '') {
            $jumlahFilterAktif++;
        }

        if ($stokTersedia) {
            $jumlahFilterAktif++;
        }

        $adaPencarian =
            $cari ||
            $jumlahFilterAktif > 0;
    @endphp

    {{-- ==========================================
         PENCARIAN DAN FILTER KATALOG
         ========================================== --}}

    <div class="pg-catalog-toolbar">

        <form
            class="pg-catalog-search-form"
            method="GET"
            action="{{ route('pembeli.katalog') }}"
            role="search"
        >

            {{-- PENCARIAN PRODUK --}}

            <label class="pg-catalog-search">

                <span class="sr-only">
                    Cari produk
                </span>

                <svg
                    class="pg-catalog-search-icon"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>

                <input
                    type="search"
                    name="cari"
                    value="{{ $cari }}"
                    placeholder="Cari nama produk, motif, atau warna..."
                    autocomplete="off"
                >

            </label>


            {{-- ==========================================
                 TOMBOL FILTER DAN DROPDOWN
                 ========================================== --}}

            <details
                class="pg-catalog-filter"
                id="pgCatalogFilter"
                @if($jumlahFilterAktif > 0) open @endif
            >

                <summary class="pg-catalog-filter-summary">

                    {{-- ICON FILTER --}}

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M4 7h16"></path>
                        <path d="M7 12h10"></path>
                        <path d="M10 17h4"></path>
                    </svg>

                    <span>Filter</span>

                    @if($jumlahFilterAktif > 0)
                        <span class="pg-catalog-filter-count">
                            {{ $jumlahFilterAktif }}
                        </span>
                    @endif

                </summary>


                {{-- PANEL FILTER --}}

                <div class="pg-catalog-filter-panel">

                    <div class="pg-catalog-filter-head">

                        <div>
                            <h3>Filter Produk</h3>
                            <p>
                                Sesuaikan koleksi dengan
                                kebutuhanmu.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="pg-catalog-filter-close"
                            id="pgCloseFilter"
                            aria-label="Tutup filter"
                        >
                            &times;
                        </button>

                    </div>


                    {{-- FILTER JENIS --}}

                    <div class="pg-catalog-filter-field">

                        <label for="pgFilterJenis">
                            Jenis Produk
                        </label>

                        <select
                            name="jenis"
                            id="pgFilterJenis"
                        >

                            <option value="">
                                Semua jenis
                            </option>

                            @foreach($jenisProduk as $namaJenis)

                                <option
                                    value="{{ $namaJenis }}"
                                    @selected($jenis === $namaJenis)
                                >
                                    {{ $namaJenis }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- FILTER HARGA --}}

                    <div class="pg-catalog-price-fields">

                        <div class="pg-catalog-filter-field">

                            <label for="pgFilterHargaMin">
                                Harga Minimum
                            </label>

                            <input
                                type="number"
                                name="harga_min"
                                id="pgFilterHargaMin"
                                min="0"
                                step="1000"
                                placeholder="Rp 0"
                                value="{{ $hargaMin }}"
                            >

                        </div>

                        <div class="pg-catalog-filter-field">

                            <label for="pgFilterHargaMax">
                                Harga Maksimum
                            </label>

                            <input
                                type="number"
                                name="harga_max"
                                id="pgFilterHargaMax"
                                min="0"
                                step="1000"
                                placeholder="Tanpa batas"
                                value="{{ $hargaMax }}"
                            >

                        </div>

                    </div>


                    {{-- FILTER STOK --}}

                    <label class="pg-catalog-stock">

                        <input
                            type="checkbox"
                            name="stok_tersedia"
                            value="1"
                            @checked($stokTersedia)
                        >

                        <span>
                            Hanya tampilkan produk yang
                            stoknya tersedia
                        </span>

                    </label>


                    {{-- TOMBOL FILTER --}}

                    <div class="pg-catalog-filter-actions">

                        <button
                            class="pg-catalog-apply"
                            type="submit"
                        >
                            Terapkan Filter
                        </button>

                        <a
                            href="{{ route('pembeli.katalog') }}"
                            class="pg-catalog-reset"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </details>


            {{-- TOMBOL PENCARIAN --}}

            <button
                class="pg-catalog-search-submit"
                type="submit"
            >
                Cari
            </button>

        </form>

    </div>


    {{-- ==========================================
         JUDUL KOLEKSI
         ========================================== --}}

    <div class="collection-titlebar-v4">

        <div>

            <span class="eyebrow">
                KOLEKSI
            </span>

            <h1>
                {{ $adaPencarian
                    ? 'Hasil Pencarian'
                    : 'Koleksi Palugada' }}
            </h1>

            @if($cari)

                <p>
                    Hasil pencarian untuk
                    "{{ $cari }}"
                    {{ $jenis ? 'pada jenis '.$jenis : '' }}.
                </p>

            @elseif($jenis)

                <p>
                    Menampilkan koleksi jenis
                    {{ $jenis }}.
                </p>

            @elseif(!auth()->check())

                <p>
                    Temukan koleksi tenun ikat
                    khas Bandar yang sesuai
                    dengan pilihanmu.
                </p>

            @endif

        </div>

        <span class="count-chip count-chip-v4">
            {{ $daftarProduk->count() }} produk
        </span>

    </div>


    {{-- ==========================================
         INDIKATOR FILTER AKTIF
         ========================================== --}}

    @if($adaPencarian)

        <div class="pg-catalog-active-filter">

            @if($cari)

                <span class="pg-catalog-filter-chip">
                    Pencarian: {{ $cari }}
                </span>

            @endif

            @if($jenis)

                <span class="pg-catalog-filter-chip">
                    Jenis: {{ $jenis }}
                </span>

            @endif

            @if($hargaMin !== null && $hargaMin !== '')

                <span class="pg-catalog-filter-chip">
                    Min:
                    Rp{{ number_format(
                        (float) $hargaMin,
                        0,
                        ',',
                        '.'
                    ) }}
                </span>

            @endif

            @if($hargaMax !== null && $hargaMax !== '')

                <span class="pg-catalog-filter-chip">
                    Maks:
                    Rp{{ number_format(
                        (float) $hargaMax,
                        0,
                        ',',
                        '.'
                    ) }}
                </span>

            @endif

            @if($stokTersedia)

                <span class="pg-catalog-filter-chip">
                    Stok Tersedia
                </span>

            @endif

            <a
                href="{{ route('pembeli.katalog') }}"
                class="pg-catalog-clear-link"
            >
                Hapus Semua
            </a>

        </div>

    @endif


    {{-- ==========================================
         DAFTAR PRODUK
         ========================================== --}}

    <div class="store-product-grid collection-grid collection-grid-v4">

        @forelse($daftarProduk as $produk)

            @include('pembeli.partials.product-card', [
                'produk' => $produk
            ])

        @empty

            <div class="collection-empty collection-empty-v4">

                <span class="collection-empty-icon">
                    ◇
                </span>

                <h3>
                    Produk Belum Ditemukan
                </h3>

                <p>
                    Coba gunakan kata kunci
                    atau pilihan filter lainnya.
                </p>

                <a
                    href="{{ route('pembeli.katalog') }}"
                    class="btn btn-primary"
                >
                    Lihat Semua Koleksi
                </a>

            </div>

        @endforelse

    </div>

</section>


{{-- MODAL DETAIL PRODUK --}}
@include('pembeli.partials.product-modal')


{{-- JAVASCRIPT FILTER --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('pgCatalogFilter');
    const closeButton = document.getElementById('pgCloseFilter');

    if (!filter) {
        return;
    }

    // Menutup filter lewat tombol X.
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            filter.open = false;
        });
    }

    // Menutup filter jika klik di luar panel.
    document.addEventListener('click', function (event) {
        if (filter.open && !filter.contains(event.target)) {
            filter.open = false;
        }
    });

    // Menutup filter dengan tombol Escape.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filter.open) {
            filter.open = false;
        }
    });
});
</script>

@endsection
