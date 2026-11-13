@extends('layouts.app')

@section('title', 'Checkout - Palugada Bandar')

@section('content')
<link rel="stylesheet" href="{{ asset('css/checkout-ongkir.css') }}">

<section class="member-simple-page member-list-page-v4">

    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">CHECKOUT</span>
            <h1>Checkout Pesanan</h1>
            <p>Lengkapi informasi pengiriman dan metode pembayaran.</p>
        </div>

        <a href="{{ route('pembeli.keranjang') }}" class="btn btn-outline">
            Kembali ke Keranjang
        </a>
    </div>

    @if($errors->any())
        <div style="padding:15px;background:#ffe8e8;color:#a52222;border-radius:10px;margin:20px 0;">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,350px),1fr));gap:24px;margin-top:30px;align-items:start;">

        <div style="background:white;border:1px solid #e5e0d6;border-radius:16px;padding:28px;">

            <h2 style="margin-bottom:24px;">Informasi Pengiriman</h2>

            <form id="form-checkout"
                  action="{{ route('pembeli.checkout.simpan') }}"
                  method="POST"
                  data-url-tujuan="{{ route('pembeli.ongkir.tujuan') }}"
                  data-url-opsi="{{ route('pembeli.ongkir.opsi') }}"
                  data-subtotal="{{ $subtotal }}"
                  data-berat="{{ $beratTotalGram }}"
                  data-pembayaran-per-metode='@json($pembayaranPerMetode)'
                  data-label-ambil="{{ config('ongkir.label_ambil_di_tempat') }}"
                  data-old-metode-pengiriman="{{ old('metode_pengiriman', 'ambil_di_tempat') }}"
                  data-old-metode-pembayaran="{{ old('metode', 'cash') }}"
                  data-old-tujuan-id="{{ old('tujuan_id', '') }}"
                  data-old-tujuan-label="{{ old('tujuan_label', '') }}"
                  data-old-kurir-kode="{{ old('kurir_kode', '') }}"
                  data-old-layanan-kurir="{{ old('layanan_kurir', '') }}">

                @csrf

                <!-- Input Hidden untuk Data Pengiriman Ekspedisi -->
                <input type="hidden" name="tujuan_id" id="tujuan_id" value="{{ old('tujuan_id') }}">
                <input type="hidden" name="tujuan_label" id="tujuan_label" value="{{ old('tujuan_label') }}">
                <input type="hidden" name="kurir_kode" id="kurir_kode" value="{{ old('kurir_kode') }}">
                <input type="hidden" name="layanan_kurir" id="layanan_kurir" value="{{ old('layanan_kurir') }}">

                <!-- Data Penerima -->
                <div style="margin-bottom:20px;">
                    <label for="nama_penerima" style="font-weight:600;display:block;margin-bottom:6px;">Nama Penerima</label>
                    <input type="text"
                           id="nama_penerima"
                           name="nama_penerima"
                           value="{{ old('nama_penerima', auth()->user()->nama) }}"
                           required
                           maxlength="100"
                           class="ongkir-input-text">
                </div>

                <div style="margin-bottom:20px;">
                    <label for="no_hp" style="font-weight:600;display:block;margin-bottom:6px;">Nomor HP</label>
                    <input type="tel"
                           id="no_hp"
                           name="no_hp"
                           value="{{ old('no_hp', auth()->user()->no_hp) }}"
                           placeholder="08xxxxxxxxxx"
                           required
                           class="ongkir-input-text">
                </div>

                <!-- Pilihan Metode Pengiriman -->
                <div style="margin-bottom:24px;">
                    <label style="font-weight:600;display:block;margin-bottom:6px;">Metode Pengiriman</label>

                    <div class="ongkir-pilihan-grup">
                        <label class="ongkir-radio-card @if(old('metode_pengiriman', 'ambil_di_tempat') === 'ambil_di_tempat') active @endif">
                            <input type="radio"
                                   name="metode_pengiriman"
                                   value="ambil_di_tempat"
                                   @checked(old('metode_pengiriman', 'ambil_di_tempat') === 'ambil_di_tempat')>
                            <div>
                                <span class="ongkir-card-label">Ambil di Tempat</span>
                                <span class="ongkir-card-desc">{{ config('ongkir.label_ambil_di_tempat') }} (Bebas Ongkir)</span>
                            </div>
                        </label>

                        <label class="ongkir-radio-card @if(old('metode_pengiriman') === 'ekspedisi') active @endif @if(!$ongkirTersedia) disabled @endif">
                            <input type="radio"
                                   name="metode_pengiriman"
                                   value="ekspedisi"
                                   @checked(old('metode_pengiriman') === 'ekspedisi')
                                   @disabled(!$ongkirTersedia)>
                            <div>
                                <span class="ongkir-card-label">Ekspedisi (Kirim ke Alamat)</span>
                                <span class="ongkir-card-desc">
                                    @if($ongkirTersedia)
                                        Pengiriman via JNE, J&T, SiCepat
                                    @else
                                        Pengiriman ekspedisi belum tersedia
                                    @endif
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Blok Ekspedisi: Pencarian Wilayah & Pilihan Kurir -->
                <div id="blok-ekspedisi" style="display: @if(old('metode_pengiriman') === 'ekspedisi') block @else none @endif; margin-bottom:24px; padding:18px; background:#fbfaf8; border:1px solid #e7e2d9; border-radius:12px;">

                    <!-- 1. Pencarian Tujuan -->
                    <div style="margin-bottom:18px;">
                        <label style="font-weight:600;display:block;margin-bottom:6px;">Wilayah / Kota Tujuan</label>

                        <div id="wadah-cari-tujuan" class="ongkir-cari-wadah">
                            <input type="text"
                                   id="input-cari-tujuan"
                                   class="ongkir-input-text"
                                   placeholder="Ketik minimal 3 huruf nama kecamatan atau kota..."
                                   autocomplete="off">
                            <div id="loading-tujuan" class="ongkir-status-box ongkir-status-loading" style="display:none;">
                                Sedang mencari wilayah...
                            </div>
                            <div id="dropdown-hasil-tujuan" class="ongkir-dropdown-hasil" style="display:none;"></div>
                        </div>

                        <div id="wadah-tujuan-terpilih" class="ongkir-tujuan-terpilih-box" style="display:none;">
                            <div>
                                <small style="display:block;color:#666;font-size:11px;">Tujuan Pengiriman:</small>
                                <span id="teks-tujuan-terpilih" class="ongkir-tujuan-terpilih-teks"></span>
                            </div>
                            <button type="button" id="btn-ganti-tujuan" class="ongkir-btn-ganti">Ganti</button>
                        </div>
                    </div>

                    <!-- 2. Pilihan Layanan Kurir -->
                    <div id="wadah-opsi-kurir" style="margin-bottom:12px;">
                        <label style="font-weight:600;display:block;margin-bottom:6px;">Pilih Layanan Kurir</label>

                        <div id="loading-kurir" class="ongkir-status-box ongkir-status-loading" style="display:none;">
                            Menghitung tarif ongkos kirim...
                        </div>
                        <div id="pesan-error-kurir" class="ongkir-status-box ongkir-status-error" style="display:none;"></div>
                        <div id="pesan-kosong-kurir" class="ongkir-status-box ongkir-status-empty" style="display:none;"></div>
                        <div id="daftar-opsi-kurir" class="ongkir-opsi-list"></div>
                    </div>
                </div>

                <!-- Blok Alamat Pengiriman -->
                <div id="blok-alamat-pengiriman" style="display: @if(old('metode_pengiriman') === 'ekspedisi') block @else none @endif; margin-bottom:20px;">
                    <label for="alamat_pengiriman" style="font-weight:600;display:block;margin-bottom:6px;">Alamat Lengkap Pengiriman</label>
                    <textarea id="alamat_pengiriman"
                              name="alamat_pengiriman"
                              rows="3"
                              maxlength="1000"
                              placeholder="Nama jalan, nomor rumah, RT/RW, patokan..."
                              class="ongkir-input-text">{{ old('alamat_pengiriman', auth()->user()->alamat) }}</textarea>
                </div>

                <!-- Metode Pembayaran -->
                <div style="margin-bottom:28px;">
                    <label style="font-weight:600;display:block;margin-bottom:6px;">Metode Pembayaran</label>

                    <div class="ongkir-pilihan-grup">
                        <label class="ongkir-radio-card @if(old('metode', 'cash') === 'cash') active @endif">
                            <input type="radio"
                                   name="metode"
                                   id="metode_cash"
                                   value="cash"
                                   @checked(old('metode', 'cash') === 'cash')>
                            <div>
                                <span class="ongkir-card-label">Cash (Bayar di Tempat)</span>
                                <span class="ongkir-card-desc">Khusus metode Ambil di Tempat</span>
                            </div>
                        </label>

                        <label class="ongkir-radio-card @if(old('metode') === 'midtrans') active @endif">
                            <input type="radio"
                                   name="metode"
                                   id="metode_midtrans"
                                   value="midtrans"
                                   @checked(old('metode') === 'midtrans')>
                            <div>
                                <span class="ongkir-card-label">Midtrans (Pembayaran Online)</span>
                                <span class="ongkir-card-desc">Transfer Bank, QRIS, & E-Wallet</span>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit"
                        id="btn-submit-pesanan"
                        class="btn btn-primary"
                        style="width:100%;padding:14px;font-size:16px;">
                    Buat Pesanan
                </button>

            </form>
        </div>

        <!-- Kolom Ringkasan Pesanan -->
        <div style="background:white;border:1px solid #e5e0d6;border-radius:16px;padding:28px;">

            <h2 style="margin-bottom:24px;">Ringkasan Pesanan</h2>

            @foreach($keranjang->detail as $item)
                @php
                    $itemSubtotal = (float) $item->produk->harga * $item->jumlah;
                @endphp

                <div style="padding:14px 0;border-bottom:1px solid #eee;">
                    <strong>{{ $item->produk->nama_produk }}</strong>

                    <p style="margin:4px 0;color:#666;font-size:13px;">
                        {{ $item->jumlah }} × Rp {{ number_format((float) $item->produk->harga, 0, ',', '.') }}
                        @if(!empty($item->produk->berat_gram))
                            <span style="color:#888;">({{ $item->produk->berat_gram }}g)</span>
                        @endif
                    </p>

                    <strong>
                        Rp {{ number_format($itemSubtotal, 0, ',', '.') }}
                    </strong>
                </div>
            @endforeach

            <!-- Rincian Biaya -->
            <div style="margin-top:20px;">
                <div class="ongkir-ringkasan-baris">
                    <span>Subtotal Produk</span>
                    <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                </div>

                <div class="ongkir-ringkasan-baris">
                    <span>Total Berat</span>
                    <span>{{ number_format($beratTotalGram, 0, ',', '.') }} gram</span>
                </div>

                <div class="ongkir-ringkasan-baris">
                    <span>Ongkos Kirim</span>
                    <strong id="ringkasan-ongkir">Rp 0</strong>
                </div>

                <div class="ongkir-ringkasan-baris total-baris">
                    <div>
                        <span style="font-weight:600;display:block;">Estimasi Total</span>
                        <small style="color:#777;font-size:11px;">Termasuk ongkir</small>
                    </div>

                    <h2 id="ringkasan-total" style="color:#163f32;margin:0;">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </h2>
                </div>
            </div>

        </div>
    </div>

</section>

<script src="{{ asset('js/checkout-ongkir.js') }}" defer></script>
@endsection
