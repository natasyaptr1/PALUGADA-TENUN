@extends('layouts.app')
@section('title','Keranjang - Palugada Bandar')
@section('content')
<section class="account-page">
    <div class="section-heading"><span class="kicker">BELANJA</span><h1>Keranjang</h1><p>Tempat produk pilihanmu sebelum melanjutkan ke pemesanan.</p></div>
    <div class="account-layout">
        <aside class="account-menu">
            <a href="{{ route('pembeli.akun') }}">Beranda Akun</a>
            <a href="{{ route('pembeli.profil') }}">Profil</a>
            <a href="{{ route('pembeli.pesanan') }}">Pesanan Saya</a>
            <a class="active" href="{{ route('pembeli.keranjang') }}">Keranjang</a>
        </aside>
        <section class="customer-panel">
            <div class="friendly-empty large"><span>⌑</span><h2>Keranjangmu masih kosong</h2><p>Struktur halaman sudah disiapkan agar nanti fitur keranjang bisa ditambahkan tanpa mengubah alur navigasi pembeli.</p><a href="{{ route('pembeli.katalog') }}#produk" class="btn btn-primary">Pilih Produk</a></div>
        </section>
    </div>
</section>
@endsection
