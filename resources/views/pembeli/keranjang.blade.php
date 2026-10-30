
@extends('layouts.app')

@section('title', 'Keranjang - Palugada Bandar')

@section('content')

<section class="member-simple-page member-list-page-v4">

    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">KERANJANG</span>
            <h1>Keranjang Saya</h1>
            <p>Periksa produk pilihanmu sebelum checkout.</p>
        </div>

        <a href="{{ route('pembeli.katalog') }}"
           class="btn btn-outline">
            Lihat Koleksi
        </a>
    </div>

    @if(session('success'))
        <div style="padding:15px;background:#e5f5e9;color:#176537;border-radius:10px;margin-bottom:20px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:15px;background:#ffe8e8;color:#a52222;border-radius:10px;margin-bottom:20px;">
            {{ $errors->first() }}
        </div>
    @endif

    @if(!$keranjang || $keranjang->detail->isEmpty())

        <div class="member-flat-empty member-flat-empty-large member-empty-v4">
            <div>
                <strong>Keranjangmu masih kosong</strong>
                <p>Yuk pilih produk tenun favoritmu!</p>
            </div>

            <a href="{{ route('pembeli.katalog') }}"
               class="btn btn-primary">
                Mulai Belanja
            </a>
        </div>

    @else

        <div style="display:grid;gap:20px;margin-top:25px;">

            @foreach($keranjang->detail as $item)

                @php
                    $produk = $item->produk;
                    $harga = (float) ($produk?->harga ?? 0);
                    $subtotal = $harga * $item->jumlah;
                    $stok = (int) ($produk?->stok?->jumlah_stok ?? 0);
                @endphp

                <div style="background:#fff;padding:24px;border:1px solid #e5e5e5;border-radius:16px;">

                    <h3 style="margin-bottom:12px;">
                        {{ $produk?->nama_produk ?? 'Produk tidak tersedia' }}
                    </h3>

                    <p>
                        Harga:
                        <strong>
                            Rp {{ number_format($harga, 0, ',', '.') }}
                        </strong>
                    </p>

                    <p>Stok tersedia: {{ $stok }}</p>

                    <p>
                        Subtotal:
                        <strong>
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </strong>
                    </p>

                    <form method="POST"
                          action="{{ route('pembeli.keranjang.ubah', $item->id) }}"
                          style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:15px;">

                        @csrf
                        @method('PUT')

                        <label for="jumlah-{{ $item->id }}">
                            Jumlah
                        </label>

                        <input type="number"
                               id="jumlah-{{ $item->id }}"
                               name="jumlah"
                               min="1"
                               max="{{ max(1, $stok) }}"
                               value="{{ $item->jumlah }}"
                               required
                               style="width:80px;padding:10px;border:1px solid #ddd;border-radius:8px;">

                        <button type="submit" class="btn btn-primary">
                            Perbarui
                        </button>

                    </form>

                    <form method="POST"
                          action="{{ route('pembeli.keranjang.hapus', $item->id) }}"
                          style="margin-top:12px;">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-outline"
                                onclick="return confirm('Yakin ingin menghapus produk ini?')">
                            Hapus
                        </button>

                    </form>

                </div>

            @endforeach

            <div style="background:#fff;padding:25px;border:1px solid #e5e5e5;border-radius:16px;">

                <h2>Ringkasan Belanja</h2>

                <h3 style="margin:18px 0;">
                    Total:
                    Rp {{ number_format($total, 0, ',', '.') }}
                </h3>

                <a href="{{ route('pembeli.checkout') }}"
                    class="btn btn-primary">
                        Lanjut ke Checkout
                </a>

            </div>

        </div>

    @endif

</section>

@endsection
