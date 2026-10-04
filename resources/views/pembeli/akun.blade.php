@extends('layouts.app')
@section('title','Akun Saya - Palugada Bandar')
@section('content')
<section class="customer-dashboard">
    <div class="customer-hero-card">
        <div>
            <span class="kicker">AKUN PEMBELI</span>
            <h1>Selamat datang, {{ $pengguna->nama }}</h1>
            <p>Dari sini kamu bisa kembali belanja, mengecek data akun, dan nanti melihat pesanan tanpa harus mencari-cari menu.</p>
            <div class="customer-hero-actions">
                <a href="{{ route('pembeli.katalog') }}#produk" class="btn btn-primary">Lihat Koleksi</a>
                <a href="{{ route('pembeli.profil') }}" class="btn btn-outline">Ubah Profil</a>
            </div>
        </div>
        <div class="customer-profile-card">
            <div class="customer-avatar">{{ strtoupper(substr($pengguna->nama,0,1)) }}</div>
            <strong>{{ $pengguna->nama }}</strong>
            <span>{{ $pengguna->email }}</span>
            @if($pengguna->no_hp)<span>{{ $pengguna->no_hp }}</span>@endif
        </div>
    </div>

    <div class="customer-summary-grid">
        <a class="customer-summary-card" href="{{ route('pembeli.katalog') }}#produk"><span>◇</span><div><strong>Koleksi Tenun</strong><small>Temukan produk yang tersedia</small></div><b>→</b></a>
        <a class="customer-summary-card" href="{{ route('pembeli.pesanan') }}"><span>▤</span><div><strong>Pesanan Saya</strong><small>{{ $jumlahPesanan }} pesanan tersimpan</small></div><b>→</b></a>
        <a class="customer-summary-card" href="{{ route('pembeli.keranjang') }}"><span>⌑</span><div><strong>Keranjang</strong><small>Tempat produk pilihanmu</small></div><b>→</b></a>
        <a class="customer-summary-card" href="{{ route('pembeli.profil') }}"><span>◎</span><div><strong>Data Akun</strong><small>Nama, kontak, dan alamat</small></div><b>→</b></a>
    </div>

    <div class="customer-main-grid">
        <section class="customer-panel">
            <div class="section-heading inline"><div><span class="kicker">PESANAN</span><h2>Aktivitas terbaru</h2></div><a href="{{ route('pembeli.pesanan') }}" class="text-link">Lihat semua →</a></div>
            @if($pesananTerbaru->isEmpty())
                <div class="friendly-empty"><span>▤</span><h3>Belum ada pesanan</h3><p>Saat transaksi online sudah tersedia, pesananmu akan muncul di sini.</p><a href="{{ route('pembeli.katalog') }}#produk" class="btn btn-outline">Lihat Produk</a></div>
            @else
                <div class="simple-list">
                    @foreach($pesananTerbaru as $pesanan)
                        <div><strong>{{ $pesanan->kode_pesanan }}</strong><span>{{ ucfirst(str_replace('_',' ',$pesanan->status_pesanan)) }}</span><b>Rp {{ number_format($pesanan->total,0,',','.') }}</b></div>
                    @endforeach
                </div>
            @endif
        </section>
        <aside class="customer-panel soft-panel">
            <span class="kicker">TIPS</span>
            <h2>Belanja lebih nyaman</h2>
            <p>Gunakan pencarian di bagian atas untuk menemukan motif, warna, atau jenis produk. Informasi stok terlihat langsung pada setiap kartu produk.</p>
            <a href="{{ route('pembeli.katalog') }}#kategori" class="text-link">Jelajahi kategori →</a>
        </aside>
    </div>
</section>
@endsection
