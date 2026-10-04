@extends('layouts.app')
@section('title','Keranjang - Palugada Bandar')
@section('content')
<section class="member-simple-page member-list-page-v4">
    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">KERANJANG</span>
            <h1>Keranjang Saya</h1>
            <p>Produk yang kamu pilih akan terkumpul di sini sebelum dibuat menjadi pesanan.</p>
        </div>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-outline">Lihat Koleksi</a>
    </div>

    <div class="member-flat-empty member-flat-empty-large member-empty-v4">
        <span>◇</span>
        <div>
            <strong>Keranjangmu masih kosong</strong>
            <p>Pilih produk yang kamu suka dari koleksi Palugada.</p>
        </div>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">Mulai Belanja</a>
    </div>
</section>
@endsection
