@extends('layouts.app')

@section('title', 'Checkout - Palugada Bandar')

@section('content')
<section class="member-simple-page member-list-page-v4">

    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">CHECKOUT</span>
            <h1>Checkout Pesanan</h1>
            <p>Lengkapi informasi pengiriman dan pembayaran.</p>
        </div>

        <a href="{{ route('pembeli.keranjang') }}"
           class="btn btn-outline">
            Kembali ke Keranjang
        </a>
    </div>

    @if($errors->any())
        <div style="padding:15px;background:#ffe8e8;color:#a52222;border-radius:10px;margin:20px 0;">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,350px),1fr));gap:24px;margin-top:30px;align-items:start;">

        <div style="background:white;border:1px solid #e5e0d6;border-radius:16px;padding:28px;">

            <h2 style="margin-bottom:24px;">Informasi Pengiriman</h2>

            <form action="{{ route('pembeli.checkout.simpan') }}"
                  method="POST">

                @csrf

                <div style="margin-bottom:20px;">
                    <label for="nama_penerima">Nama Penerima</label>
                    <input type="text"
                           id="nama_penerima"
                           name="nama_penerima"
                           value="{{ old('nama_penerima', auth()->user()->nama) }}"
                           required
                           maxlength="100"
                           style="width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:8px;">
                </div>

                <div style="margin-bottom:20px;">
                    <label for="no_hp">Nomor HP</label>
                    <input type="tel"
                           id="no_hp"
                           name="no_hp"
                           value="{{ old('no_hp', auth()->user()->no_hp) }}"
                           placeholder="08xxxxxxxxxx"
                           required
                           style="width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:8px;">
                </div>

                <div style="margin-bottom:20px;">
                    <label for="alamat_pengiriman">Alamat Pengiriman</label>
                    <textarea id="alamat_pengiriman"
                              name="alamat_pengiriman"
                              rows="4"
                              maxlength="1000"
                              required
                              style="width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:8px;">{{ old('alamat_pengiriman', auth()->user()->alamat) }}</textarea>
                </div>

                <div style="margin-bottom:24px;">
                    <label for="metode">Metode Pembayaran</label>

                    <select name="metode"
                            id="metode"
                            required
                            style="width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:8px;">

                        <option value="cash" @selected(old('metode') === 'cash')>
                            Cash (Konfirmasi Admin)
                        </option>

                        <option value="midtrans" @selected(old('metode') === 'midtrans')>
                            Midtrans (Menunggu Integrasi)
                        </option>

                    </select>

                    <p style="font-size:13px;color:#777;margin-top:8px;">
                        Pembayaran Midtrans belum aktif.
                        Pemilihan Midtrans saat ini hanya mencatat metode pembayaran.
                    </p>
                </div>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;">
                    Buat Pesanan
                </button>

            </form>
        </div>

        <div style="background:white;border:1px solid #e5e0d6;border-radius:16px;padding:28px;">

            <h2 style="margin-bottom:24px;">Ringkasan Pesanan</h2>

            @foreach($keranjang->detail as $item)
                @php
                    $subtotal = (float) $item->produk->harga * $item->jumlah;
                @endphp

                <div style="padding:14px 0;border-bottom:1px solid #eee;">
                    <strong>{{ $item->produk->nama_produk }}</strong>

                    <p style="margin-top:6px;">
                        {{ $item->jumlah }} ×
                        Rp {{ number_format((float) $item->produk->harga, 0, ',', '.') }}
                    </p>

                    <strong>
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </strong>
                </div>
            @endforeach

            <div style="margin-top:24px;">
                <p>Total Belanja</p>

                <h2 style="color:#163f32;margin-top:8px;">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </h2>

                <p style="font-size:13px;color:#777;margin-top:10px;">
                    Ongkos kirim belum dihitung pada sistem ini.
                </p>
            </div>

        </div>
    </div>

</section>
@endsection
