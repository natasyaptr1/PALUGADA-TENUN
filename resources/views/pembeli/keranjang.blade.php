@extends('layouts.app')
@section('title', 'Keranjang - Palugada Bandar')
@section('content')
<style>
.pg-cart{max-width:1180px;margin:auto;padding:32px 20px 75px;color:#163f32}
.pg-cart *{box-sizing:border-box}
.pg-cart-header{display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:28px}
.pg-cart-header h1{font-size:clamp(28px,4vw,40px);margin:6px 0 8px;color:#143c30}
.pg-cart-muted{color:#77857a;font-size:13px}
.pg-cart-kicker{font-size:12px;letter-spacing:2px;color:#aa704c;font-weight:700}
.pg-cart-link{padding:12px 17px;border:1px solid #dce2da;border-radius:10px;text-decoration:none;color:#184739;background:white;font-weight:600}
.pg-cart-grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:22px;align-items:start}
.pg-cart-list,.pg-cart-summary{background:#fff;border:1px solid #e3e8e2;border-radius:18px;overflow:hidden}
.pg-cart-list-head{padding:20px 22px;border-bottom:1px solid #edf0ec;display:flex;justify-content:space-between;align-items:center}
.pg-cart-item{display:flex;gap:16px;padding:22px;border-bottom:1px solid #edf0ec;align-items:flex-start}
.pg-cart-item:last-child{border-bottom:0}
.pg-cart-select{width:18px;height:18px;accent-color:#184b3a;margin-top:37px;flex-shrink:0}
.pg-cart-image{width:116px;height:116px;flex-shrink:0;background:#f2f2ec;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;color:#8c998e;font-size:12px;text-align:center}
.pg-cart-image img{width:100%;height:100%;object-fit:cover}
.pg-cart-details{flex:1;min-width:0}
.pg-cart-name{font-size:17px;font-weight:700;margin:0 0 9px}
.pg-cart-stock{font-size:12px;color:#708277;margin:0 0 12px}
.pg-cart-price{font-weight:700;font-size:16px;margin-bottom:16px}
.pg-cart-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.pg-cart-qty{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pg-cart-number{width:72px;padding:10px;border:1px solid #d7dfd6;border-radius:9px}
.pg-cart-btn{border:0;border-radius:9px;background:#174a39;color:white;padding:11px 14px;font-weight:700;cursor:pointer}
.pg-cart-delete{background:#fff;color:#a34c3f;border:1px solid #ead4d0}
.pg-cart-subtotal{text-align:right;font-weight:700;white-space:nowrap;color:#1d503e}
.pg-cart-summary{padding:24px;position:sticky;top:115px}
.pg-cart-summary h2{font-size:20px;margin:0 0 20px}
.pg-cart-summary-line{display:flex;justify-content:space-between;gap:12px;margin:14px 0;font-size:14px}
.pg-cart-divider{border-top:1px solid #e6ebe6;margin:20px 0}
.pg-cart-total{font-size:23px;font-weight:800;color:#174b39}
.pg-cart-checkout{display:block;text-align:center;width:100%;background:#174b39;color:white;border:0;border-radius:11px;padding:15px;font-size:14px;font-weight:700;cursor:pointer}
.pg-cart-checkout:disabled{opacity:.45;cursor:not-allowed}
.pg-cart-notice{margin:0 0 20px;border-radius:10px;padding:14px 16px;background:#e9f6ed;color:#216f40}
.pg-cart-error{background:#fff0ef;color:#a84036}
.pg-cart-empty{border:1px solid #e1e9e1;background:white;border-radius:18px;padding:60px 24px;text-align:center}
@media(max-width:900px){.pg-cart-grid{grid-template-columns:1fr}.pg-cart-summary{position:static}}
@media(max-width:600px){.pg-cart{padding:22px 14px 50px}.pg-cart-item{padding:16px;gap:10px;flex-wrap:wrap}.pg-cart-image{width:85px;height:85px}.pg-cart-select{margin-top:30px}.pg-cart-subtotal{width:100%;text-align:right}.pg-cart-name{font-size:15px}}
</style>
<section class="pg-cart">
    <header class="pg-cart-header">
        <div>
            <span class="pg-cart-kicker">PALUGADA BANDAR / KERANJANG</span>
            <h1>Keranjang Saya</h1>
            <div class="pg-cart-muted">Centang produk yang ingin kamu checkout.</div>
        </div>
        <a class="pg-cart-link" href="{{ route('pembeli.katalog') }}">Lihat Koleksi</a>
    </header>

    @if(session('success')) <div class="pg-cart-notice">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="pg-cart-notice pg-cart-error">{{ $errors->first() }}</div> @endif

    @if(!$keranjang || $keranjang->detail->isEmpty())
        <div class="pg-cart-empty">
            <h2>Keranjangmu masih kosong</h2>
            <p class="pg-cart-muted">Yuk pilih produk tenun favoritmu!</p>
            <a class="pg-cart-link" href="{{ route('pembeli.katalog') }}">Mulai Belanja</a>
        </div>
    @else
        <form id="pg-selected-form" method="POST" action="{{ route('pembeli.keranjang.pilih-checkout') }}">
            @csrf
        </form>
        <div class="pg-cart-grid">
            <div class="pg-cart-list">
                <div class="pg-cart-list-head">
                    <label><input type="checkbox" id="pg-select-all"> <strong>Pilih Semua</strong></label>
                    <span class="pg-cart-muted">{{ $keranjang->detail->count() }} produk di keranjang</span>
                </div>
                @foreach($keranjang->detail as $item)
                    @php
                        $produk = $item->produk;
                        $harga = (float) ($produk?->harga ?? 0);
                        $stok = (int) ($produk?->stok?->jumlah_stok ?? 0);
                        $subtotal = $harga * $item->jumlah;
                        $foto = $produk?->produkFoto?->first()?->foto ?: $produk?->foto;
                        $foto = $foto ? ltrim(str_replace('\\', '/', $foto), '/') : null;
                        $fotoUrl = $foto
                            ? (filter_var($foto, FILTER_VALIDATE_URL)
                                ? $foto
                                : (str_starts_with($foto, 'storage/') || str_starts_with($foto, 'images/') || str_starts_with($foto, 'uploads/')
                                    ? asset($foto) : asset('storage/'.$foto)))
                            : null;
                    @endphp
                    <article class="pg-cart-item">
                        <input class="pg-cart-select pg-item-check" form="pg-selected-form"
                               type="checkbox" name="detail_ids[]" value="{{ $item->id }}"
                               data-total="{{ $subtotal }}" {{ (!$produk || $stok < $item->jumlah) ? 'disabled' : '' }}
                               aria-label="Pilih {{ $produk?->nama_produk ?? 'produk' }}">
                        <div class="pg-cart-image">
                            @if($fotoUrl)
                                <img src="{{ $fotoUrl }}" alt="Foto {{ $produk?->nama_produk }}"
                                     onerror="this.style.display='none';this.parentElement.append('Foto tidak tersedia')">
                            @else
                                Foto belum tersedia
                            @endif
                        </div>
                        <div class="pg-cart-details">
                            <h3 class="pg-cart-name">{{ $produk?->nama_produk ?? 'Produk tidak tersedia' }}</h3>
                            <p class="pg-cart-stock">Stok tersedia: {{ $stok }}</p>
                            <div class="pg-cart-price">Rp{{ number_format($harga, 0, ',', '.') }}</div>
                            <div class="pg-cart-actions">
                                <form class="pg-cart-qty" method="POST" action="{{ route('pembeli.keranjang.ubah', $item->id) }}">
                                    @csrf @method('PUT')
                                    <label for="qty-{{ $item->id }}" class="pg-cart-muted">Jumlah</label>
                                    <input class="pg-cart-number" id="qty-{{ $item->id }}" type="number"
                                           name="jumlah" min="1" max="{{ max(1, $stok) }}"
                                           value="{{ $item->jumlah }}" required>
                                    <button type="submit" class="pg-cart-btn">Perbarui</button>
                                </form>
                                <form method="POST" action="{{ route('pembeli.keranjang.hapus', $item->id) }}"
                                      onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="pg-cart-btn pg-cart-delete">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="pg-cart-subtotal">Rp{{ number_format($subtotal, 0, ',', '.') }}</div>
                    </article>
                @endforeach
            </div>
            <aside class="pg-cart-summary">
                <h2>Ringkasan Belanja</h2>
                <div class="pg-cart-summary-line"><span>Produk dipilih</span><strong id="pg-count">0 produk</strong></div>
                <div class="pg-cart-divider"></div>
                <div class="pg-cart-summary-line"><span>Total checkout</span><strong class="pg-cart-total" id="pg-total">Rp0</strong></div>
                <p class="pg-cart-muted">Hanya produk yang dicentang yang masuk ke checkout. Produk lainnya tetap di keranjang.</p>
                <button id="pg-checkout-button" form="pg-selected-form" type="submit" class="pg-cart-checkout" disabled>
                    Checkout Produk Terpilih
                </button>
            </aside>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const boxes = Array.from(document.querySelectorAll('.pg-item-check'));
                const all = document.getElementById('pg-select-all');
                const count = document.getElementById('pg-count');
                const total = document.getElementById('pg-total');
                const button = document.getElementById('pg-checkout-button');
                const format = new Intl.NumberFormat('id-ID');
                function update() {
                    const selected = boxes.filter(el => el.checked);
                    const sum = selected.reduce((s, el) => s + Number(el.dataset.total || 0), 0);
                    count.textContent = selected.length + ' produk';
                    total.textContent = 'Rp' + format.format(sum);
                    button.disabled = selected.length === 0;
                    const eligible = boxes.filter(el => !el.disabled);
                    all.checked = eligible.length > 0 && eligible.every(el => el.checked);
                    all.indeterminate = selected.length > 0 && !all.checked;
                }
                all.addEventListener('change', () => { boxes.forEach(el => {
                    if (!el.disabled) el.checked = all.checked;
                }); update(); });
                boxes.forEach(el => el.addEventListener('change', update));
                update();
            });
        </script>
    @endif
</section>
@endsection
