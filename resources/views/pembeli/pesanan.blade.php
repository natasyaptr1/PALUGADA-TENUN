@extends('layouts.app')
@section('title','Pesanan Saya - Palugada Bandar')
@section('content')
<section class="account-page">
    <div class="section-heading"><span class="kicker">AKUN SAYA</span><h1>Pesanan Saya</h1><p>Semua transaksi yang pernah kamu buat akan tersimpan di halaman ini.</p></div>
    <div class="account-layout">
        <aside class="account-menu">
            <a href="{{ route('pembeli.akun') }}">Beranda Akun</a>
            <a href="{{ route('pembeli.profil') }}">Profil</a>
            <a class="active" href="{{ route('pembeli.pesanan') }}">Pesanan Saya</a>
            <a href="{{ route('pembeli.keranjang') }}">Keranjang</a>
        </aside>
        <section class="customer-panel">
            @if($daftarPesanan->isEmpty())
                <div class="friendly-empty large"><span>▤</span><h2>Belum ada pesanan</h2><p>Kamu tetap bisa melihat koleksi dan detail produk. Saat fitur transaksi online diaktifkan, riwayat pesanan akan langsung tersedia di halaman ini.</p><a href="{{ route('pembeli.katalog') }}#produk" class="btn btn-primary">Lihat Koleksi</a></div>
            @else
                <div class="order-list">
                    @foreach($daftarPesanan as $pesanan)
                        <article class="order-card">
                            <div><small>Kode Pesanan</small><strong>{{ $pesanan->kode_pesanan }}</strong></div>
                            <div><small>Tanggal</small><strong>{{ optional($pesanan->tanggal_pesanan)->format('d M Y') }}</strong></div>
                            <div><small>Status</small><strong>{{ ucfirst(str_replace('_',' ',$pesanan->status_pesanan)) }}</strong></div>
                            <div><small>Total</small><strong>Rp {{ number_format($pesanan->total,0,',','.') }}</strong></div>
                        </article>
                    @endforeach
                </div>
                <div class="pagination">{{ $daftarPesanan->links() }}</div>
            @endif
        </section>
    </div>
</section>
@endsection
