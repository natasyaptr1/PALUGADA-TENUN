@extends('layouts.app')
@section('title','Pesanan Saya - Palugada Bandar')
@section('content')
<section class="member-simple-page member-list-page-v4">
    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">PESANAN</span>
            <h1>Pesanan Saya</h1>
            <p>Pantau status dan riwayat pembelianmu di sini.</p>
        </div>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-outline">Lihat Koleksi</a>
    </div>

    @if($daftarPesanan->isEmpty())
        <div class="member-flat-empty member-flat-empty-large member-empty-v4">
            <span>◇</span>
            <div>
                <strong>Belum ada pesanan</strong>
                <p>Setelah kamu melakukan pembelian, pesanan akan tampil di halaman ini.</p>
            </div>
            <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">Mulai Belanja</a>
        </div>
    @else
        <div class="order-list clean-order-list-page">
            @foreach($daftarPesanan as $pesanan)
                <article class="order-row-clean">
                    <div class="order-main"><small>Kode Pesanan</small><strong>{{ $pesanan->kode_pesanan }}</strong></div>
                    <div><small>Tanggal</small><strong>{{ optional($pesanan->tanggal_pesanan)->format('d M Y') }}</strong></div>
                    <div><small>Status</small><strong>{{ ucfirst(str_replace('_',' ',$pesanan->status_pesanan)) }}</strong></div>
                    <div><small>Total</small><strong>Rp {{ number_format($pesanan->total,0,',','.') }}</strong></div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $daftarPesanan->links() }}</div>
    @endif
</section>
@endsection
