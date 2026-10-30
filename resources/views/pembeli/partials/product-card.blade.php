
@php
    $daftarFoto = $produk->semuaFoto();
    $fotoUtama = !empty($daftarFoto)
        ? asset('storage/'.$daftarFoto[0])
        : '';

    $jsonFoto = json_encode(
        array_map(fn($f) => asset('storage/'.$f), $daftarFoto)
    );

    $jumlahStok = (int) ($produk->stok?->jumlah_stok ?? 0);
@endphp

<article class="store-product-card">

    <div class="store-product-image">

        @if(!empty($daftarFoto))
            <img src="{{ $fotoUtama }}"
                 alt="{{ $produk->nama_produk }}">

            @if(count($daftarFoto) > 1)
                <span class="multi-photo-badge">
                    📷 {{ count($daftarFoto) }} Foto
                </span>
            @endif
        @else
            <div class="fabric-placeholder">
                <span>
                    {{ strtoupper(substr($produk->nama_produk, 0, 2)) }}
                </span>
            </div>
        @endif

        @if($jumlahStok <= 0)
            <span class="sold-badge">Stok habis</span>
        @endif

    </div>

    <div class="product-body">

        <span class="product-type">
            {{ $produk->jenis ?: 'Tenun Ikat' }}
        </span>

        <h3>{{ $produk->nama_produk }}</h3>

        <p class="product-desc">
            {{ $produk->motif ?: 'Motif Palugada' }}
            ·
            {{ $produk->warna ?: 'Warna khas tenun' }}
        </p>

        <div class="product-price-row">
            <strong>{{ $produk->harga_format }}</strong>

            <span>
                {{ $jumlahStok > 0
                    ? 'Tersedia '.$jumlahStok
                    : 'Belum tersedia' }}
            </span>
        </div>

        <div class="product-card-actions"
             style="display:flex;gap:8px;flex-wrap:wrap;">

            <button type="button"
                    class="btn btn-outline js-product-detail"
                    data-name="{{ $produk->nama_produk }}"
                    data-code="{{ $produk->kode_produk }}"
                    data-type="{{ $produk->jenis }}"
                    data-motif="{{ $produk->motif }}"
                    data-color="{{ $produk->warna }}"
                    data-size="{{ $produk->ukuran }}"
                    data-material="{{ $produk->material }}"
                    data-price="{{ $produk->harga_format }}"
                    data-stock="{{ $jumlahStok }}"
                    data-image="{{ $fotoUtama }}"
                    data-images="{{ $jsonFoto }}">
                Lihat Detail
            </button>

            @if($jumlahStok > 0)

                @auth
                    @if(auth()->user()->peran === 'pembeli')

                        <form method="POST"
                              action="{{ route('pembeli.keranjang.tambah') }}"
                              style="margin:0;">

                            @csrf

                            <input type="hidden"
                                   name="produk_id"
                                   value="{{ $produk->id }}">

                            <input type="hidden"
                                   name="jumlah"
                                   value="1">

                            <button type="submit"
                                    class="btn btn-primary">
                                + Keranjang
                            </button>

                        </form>

                    @endif
                @else

                    <a href="{{ route('masuk') }}"
                       class="btn btn-primary">
                        + Keranjang
                    </a>

                @endauth

            @else

                <button type="button"
                        class="btn btn-outline"
                        disabled>
                    Stok Habis
                </button>

            @endif

        </div>

    </div>

</article>
