@extends('layouts.app')

@section('title', 'Koleksi Tenun - Palugada Bandar')

@section('content')
<section class="collection-page collection-page-v4">

    {{-- FILTER KATALOG --}}
    <form
        class="collection-toolbar-v4 filter-katalog-putri"
        method="GET"
        action="{{ route('pembeli.katalog') }}"
        role="search"
    >

        {{-- Pencarian produk --}}
        <label class="collection-search-v4">
            <span class="sr-only">Cari produk</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
                type="search"
                name="cari"
                value="{{ $cari }}"
                placeholder="Cari nama, motif, warna, atau jenis..."
                autocomplete="off"
            >
        </label>

        {{-- Filter jenis produk --}}
        <label class="collection-filter-v4">
            <span class="sr-only">Filter jenis produk</span>
            <select name="jenis">
                <option value="">Semua jenis</option>
                @foreach($jenisProduk as $namaJenis)
                    <option
                        value="{{ $namaJenis }}"
                        @selected($jenis === $namaJenis)
                    >
                        {{ $namaJenis }}
                    </option>
                @endforeach
            </select>
        </label>

        {{-- Filter rentang harga --}}
        <div class="filter-harga-putri">

            <label class="filter-input-harga-putri">
                <span class="sr-only">Harga minimum</span>
                <input
                    type="number"
                    name="harga_min"
                    min="0"
                    step="1000"
                    placeholder="Harga minimum"
                    value="{{ $hargaMin }}"
                >
            </label>

            <label class="filter-input-harga-putri">
                <span class="sr-only">Harga maksimum</span>
                <input
                    type="number"
                    name="harga_max"
                    min="0"
                    step="1000"
                    placeholder="Harga maksimum"
                    value="{{ $hargaMax }}"
                >
            </label>

        </div>

        {{-- Filter stok tersedia --}}
        <label class="filter-stok-putri">
            <input
                type="checkbox"
                name="stok_tersedia"
                value="1"
                @checked($stokTersedia)
            >
            <span>Stok tersedia</span>
        </label>

        {{-- Tombol pencarian --}}
        <button
            class="btn btn-primary collection-search-button"
            type="submit"
        >
            Cari
        </button>

        {{-- Bersihkan filter --}}
        @if(
            $cari ||
            $jenis ||
            $hargaMin !== null ||
            $hargaMax !== null ||
            $stokTersedia
        )
            <a
                href="{{ route('pembeli.katalog') }}"
                class="collection-reset-link"
            >
                Bersihkan
            </a>
        @endif

    </form>

    {{-- JUDUL KOLEKSI --}}
    <div class="collection-titlebar-v4">
        <div>
            <span class="eyebrow">KOLEKSI</span>

            <h1>
                {{ $cari || $jenis || $hargaMin !== null || $hargaMax !== null || $stokTersedia
                    ? 'Hasil pencarian'
                    : 'Koleksi Palugada' }}
            </h1>

            @if($cari)
                <p>
                    Hasil untuk "{{ $cari }}"
                    {{ $jenis ? ' pada jenis '.$jenis : '' }}.
                </p>
            @elseif($jenis)
                <p>Menampilkan koleksi jenis {{ $jenis }}.</p>
            @elseif(!auth()->check())
                <p>
                    Pilih tenun yang kamu suka dan lihat
                    informasi produk dengan mudah.
                </p>
            @endif
        </div>

        <span class="count-chip count-chip-v4">
            {{ $daftarProduk->count() }} produk
        </span>
    </div>

    {{-- DAFTAR PRODUK --}}
    <div class="store-product-grid collection-grid collection-grid-v4">

        @forelse($daftarProduk as $produk)

            @include('pembeli.partials.product-card', [
                'produk' => $produk
            ])

        @empty

            <div class="collection-empty collection-empty-v4">
                <span class="collection-empty-icon">◇</span>
                <h3>Produk belum ditemukan</h3>
                <p>
                    Coba kata kunci lain atau tampilkan
                    seluruh koleksi.
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

@include('pembeli.partials.product-modal')
@endsection
