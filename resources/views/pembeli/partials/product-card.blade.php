<article class="store-product-card">
    <div class="store-product-image">
        @if($produk->foto)
            <img src="{{ asset('storage/'.$produk->foto) }}" alt="{{ $produk->nama_produk }}">
        @else
            <div class="fabric-placeholder"><span>{{ strtoupper(substr($produk->nama_produk,0,2)) }}</span></div>
        @endif
        @if(($produk->stok?->jumlah_stok ?? 0) <= 0)<span class="sold-badge">Stok habis</span>@endif
    </div>
    <div class="product-body">
        <span class="product-type">{{ $produk->jenis ?: 'Tenun Ikat' }}</span>
        <h3>{{ $produk->nama_produk }}</h3>
        <p class="product-desc">{{ $produk->motif ?: 'Motif Palugada' }} · {{ $produk->warna ?: 'Warna khas tenun' }}</p>
        <div class="product-price-row">
            <strong>Rp {{ number_format($produk->harga,0,',','.') }}</strong>
            <span>{{ ($produk->stok?->jumlah_stok ?? 0) > 0 ? 'Tersedia '.$produk->stok->jumlah_stok : 'Belum tersedia' }}</span>
        </div>
        <div class="product-card-actions product-card-actions-single">
            <button type="button" class="btn btn-outline js-product-detail"
                data-name="{{ $produk->nama_produk }}"
                data-code="{{ $produk->kode_produk }}"
                data-type="{{ $produk->jenis }}"
                data-motif="{{ $produk->motif }}"
                data-color="{{ $produk->warna }}"
                data-size="{{ $produk->ukuran }}"
                data-material="{{ $produk->material }}"
                data-price="Rp {{ number_format($produk->harga,0,',','.') }}"
                data-stock="{{ $produk->stok?->jumlah_stok ?? 0 }}"
                data-image="{{ $produk->foto ? asset('storage/'.$produk->foto) : '' }}">Lihat Detail</button>
        </div>
    </div>
</article>
