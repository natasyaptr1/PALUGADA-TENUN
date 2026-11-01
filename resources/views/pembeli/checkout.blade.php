@extends('layouts.app')

@section('title', 'Checkout - Palugada Bandar')

@section('content')
<style>
    .pg-checkout {
        max-width: 1180px;
        margin: 0 auto;
        padding: 35px 20px 75px;
        color: #173d32;
    }

    .pg-checkout * {
        box-sizing: border-box;
    }

    .pg-checkout-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 32px;
        flex-wrap: wrap;
    }

    .pg-checkout-label {
        font-size: 12px;
        letter-spacing: 2px;
        font-weight: 700;
        color: #b06a42;
        margin-bottom: 8px;
    }

    .pg-checkout-header h1 {
        font-size: clamp(28px, 4vw, 40px);
        margin: 0 0 8px;
        color: #12392e;
    }

    .pg-checkout-subtitle {
        color: #718079;
        font-size: 14px;
        margin: 0;
    }

    .pg-checkout-back {
        color: #173d32;
        border: 1px solid #d7dcd7;
        background: #fff;
        text-decoration: none;
        padding: 12px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
    }

    .pg-checkout-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(300px, 1fr);
        gap: 24px;
        align-items: start;
    }

    .pg-checkout-card {
        background: #fff;
        border: 1px solid #e5e9e4;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 5px 20px rgba(20, 55, 44, .035);
        min-width: 0;
    }

    .pg-checkout-card h2 {
        margin: 0 0 8px;
        font-size: 21px;
        color: #12392e;
    }

    .pg-checkout-hint {
        margin: 0 0 24px;
        color: #7a877f;
        font-size: 13px;
    }

    .pg-checkout-field {
        margin-bottom: 20px;
    }

    .pg-checkout-field label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 13px;
    }

    .pg-checkout-field input,
    .pg-checkout-field textarea,
    .pg-checkout-field select {
        display: block;
        width: 100%;
        padding: 13px 14px;
        border: 1px solid #d9e1da;
        background: #fff;
        border-radius: 10px;
        font: inherit;
        font-size: 14px;
        color: #203d33;
        outline: none;
    }

    .pg-checkout-field input:focus,
    .pg-checkout-field textarea:focus,
    .pg-checkout-field select:focus {
        border-color: #397561;
        box-shadow: 0 0 0 3px rgba(57, 117, 97, .10);
    }

    .pg-checkout-field textarea {
        min-height: 110px;
        resize: vertical;
    }

    .pg-checkout-info {
        background: #f2f7f3;
        border: 1px solid #dce9de;
        border-radius: 10px;
        padding: 13px 15px;
        color: #496a58;
        font-size: 12px;
        line-height: 1.7;
        margin: 8px 0 22px;
    }

    .pg-checkout-submit {
        width: 100%;
        padding: 15px 20px;
        border: none;
        border-radius: 11px;
        background: #174b3b;
        color: white;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }

    .pg-checkout-submit:hover {
        background: #103b2f;
    }

    .pg-checkout-item {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        padding: 17px 0;
        border-bottom: 1px solid #edf0eb;
    }

    .pg-checkout-item strong {
        display: block;
        font-size: 14px;
        line-height: 1.5;
    }

    .pg-checkout-item small {
        display: block;
        color: #88918b;
        margin-top: 5px;
        font-size: 12px;
    }

    .pg-checkout-item-price {
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
        text-align: right;
    }

    .pg-checkout-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 22px;
        gap: 12px;
    }

    .pg-checkout-total span {
        font-size: 14px;
        font-weight: 600;
    }

    .pg-checkout-total strong {
        font-size: 24px;
        color: #174b3b;
        white-space: nowrap;
    }

    .pg-checkout-note {
        margin-top: 18px;
        font-size: 12px;
        line-height: 1.6;
        color: #89928b;
    }

    .pg-checkout-error {
        padding: 14px 16px;
        margin-bottom: 24px;
        border-radius: 10px;
        background: #fff0ee;
        color: #a13c32;
        border: 1px solid #f1d3ce;
        font-size: 13px;
    }

    @media (max-width: 800px) {
        .pg-checkout-layout {
            grid-template-columns: 1fr;
        }

        .pg-checkout-card {
            padding: 22px;
        }

        .pg-checkout {
            padding: 25px 15px 50px;
        }

        .pg-checkout-total strong {
            font-size: 21px;
        }
    }
</style>

<section class="pg-checkout">

    <header class="pg-checkout-header">
        <div>
            <div class="pg-checkout-label">PALUGADA BANDAR / CHECKOUT</div>
            <h1>Checkout Pesanan</h1>
            <p class="pg-checkout-subtitle">
                Lengkapi informasi pengiriman dan pilih metode pembayaran.
            </p>
        </div>

        <a href="{{ route('pembeli.keranjang') }}"
           class="pg-checkout-back">
            &larr; Kembali ke Keranjang
        </a>
    </header>

    @if($errors->any())
        <div class="pg-checkout-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="pg-checkout-layout">

        <div class="pg-checkout-card">
            <h2>Informasi Pengiriman</h2>
            <p class="pg-checkout-hint">
                Pastikan informasi penerima sesuai sebelum membuat pesanan.
            </p>

            <form action="{{ route('pembeli.checkout.simpan') }}"
                  method="POST">
                @csrf

                <div class="pg-checkout-field">
                    <label for="nama_penerima">Nama Penerima</label>
                    <input
                        id="nama_penerima"
                        name="nama_penerima"
                        type="text"
                        maxlength="100"
                        required
                        autocomplete="name"
                        value="{{ old('nama_penerima', auth()->user()->nama) }}"
                        placeholder="Masukkan nama penerima">
                </div>

                <div class="pg-checkout-field">
                    <label for="no_hp">Nomor Telepon</label>
                    <input
                        id="no_hp"
                        name="no_hp"
                        type="tel"
                        required
                        autocomplete="tel"
                        value="{{ old('no_hp', auth()->user()->no_hp) }}"
                        placeholder="08xxxxxxxxxx">
                </div>

                <div class="pg-checkout-field">
                    <label for="alamat_pengiriman">Alamat Pengiriman</label>
                    <textarea
                        id="alamat_pengiriman"
                        name="alamat_pengiriman"
                        maxlength="1000"
                        rows="4"
                        required
                        placeholder="Masukkan alamat lengkap">{{ old('alamat_pengiriman', auth()->user()->alamat) }}</textarea>
                </div>

                <div class="pg-checkout-field">
                    <label for="metode">Metode Pembayaran</label>
                    <select id="metode" name="metode" required>
                        <option value="midtrans"
                            @selected(old('metode', 'midtrans') === 'midtrans')>
                            Midtrans - Pembayaran Online
                        </option>
                        <option value="cash"
                            @selected(old('metode') === 'cash')>
                            Cash - Konfirmasi Admin
                        </option>
                    </select>
                </div>

                <div class="pg-checkout-info">
                    Pembayaran online diproses melalui Midtrans.
                    Status pesanan akan diperbarui setelah
                    pembayaran berhasil diverifikasi.
                </div>

                <button type="submit" class="pg-checkout-submit">
                    Buat Pesanan &amp; Lanjutkan
                </button>
            </form>
        </div>

        <aside class="pg-checkout-card">
            <h2>Ringkasan Pesanan</h2>
            <p class="pg-checkout-hint">
                {{ $keranjang->detail->count() }} jenis produk dalam keranjang
            </p>

            @foreach($keranjang->detail as $item)
                @php
                    $subtotal = (float) $item->produk->harga * $item->jumlah;
                @endphp

                <div class="pg-checkout-item">
                    <div>
                        <strong>{{ $item->produk->nama_produk }}</strong>
                        <small>
                            {{ $item->jumlah }} &times;
                            Rp{{ number_format((float) $item->produk->harga, 0, ',', '.') }}
                        </small>
                    </div>

                    <div class="pg-checkout-item-price">
                        Rp{{ number_format($subtotal, 0, ',', '.') }}
                    </div>
                </div>
            @endforeach

            <div class="pg-checkout-total">
                <span>Total Belanja</span>
                <strong>
                    Rp{{ number_format($total, 0, ',', '.') }}
                </strong>
            </div>

            <p class="pg-checkout-note">
                Ongkos pengiriman belum dihitung oleh sistem.
                Periksa kembali rincian pesanan sebelum melanjutkan.
            </p>
        </aside>

    </div>
</section>
@endsection
