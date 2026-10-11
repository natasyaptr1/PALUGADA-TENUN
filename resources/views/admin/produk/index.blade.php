@extends('layouts.app')
@section('title','Produk - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">DATA PRODUK</span>
        <h1>Produk</h1>
        <p>Kelola identitas barang yang tampil pada koleksi pembeli. Jumlah barang siap jual dipantau terpisah melalui menu Stok.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.produk.create') }}">Tambah Produk</a>
</div>
<form class="toolbar admin-toolbar-v4">
    <input name="cari" value="{{ request('cari') }}" placeholder="Cari kode, nama, atau motif...">
    <button class="btn btn-secondary">Cari</button>
</form>
<div class="table-card"><table><thead><tr><th>Produk</th><th>Detail</th><th>Harga</th><th>Stok Siap Jual</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($daftarProduk as $produk)
@php $semuaFoto = $produk->semuaFoto(); @endphp
<tr><td><div class="product-cell"><div class="thumb" style="position:relative;">@if(!empty($semuaFoto))<img src="{{ asset('storage/'.$semuaFoto[0]) }}" alt="{{ $produk->nama_produk }}">@if(count($semuaFoto) > 1)<span class="thumb-badge" title="{{ count($semuaFoto) }} foto">+{{ count($semuaFoto) }}</span>@endif @else{{ strtoupper(substr($produk->nama_produk,0,2)) }}@endif</div><div><strong>{{ $produk->nama_produk }}</strong><span>{{ $produk->kode_produk }}@if(count($semuaFoto) > 1) • {{ count($semuaFoto) }} Foto @endif</span></div></div></td><td>{{ $produk->jenisProduk?->nama_jenis ?? $produk->jenis }}<br><small>{{ $produk->motif ?: '-' }} / {{ $produk->warna ?: '-' }}</small></td><td>{{ $produk->harga_format }}</td><td><span class="stock-pill">{{ $produk->stok?->jumlah_stok ?? 0 }}</span></td><td><span class="badge">{{ ucfirst($produk->status) }}</span></td><td class="actions"><a href="{{ route('admin.produk.edit',$produk) }}">Ubah</a>@if(($produk->stok?->jumlah_stok ?? 0) > 0)<button type="button" class="btn-locked-delete" title="Produk tidak dapat dihapus karena stok masih tersedia." onclick="alert('Produk tidak dapat dihapus karena stok masih tersedia.');">Hapus</button>@else<form method="POST" action="{{ route('admin.produk.destroy',$produk) }}">@csrf @method('DELETE')<button onclick="return confirm('Hapus produk ini?')">Hapus</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="empty">Belum ada produk.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $daftarProduk->links() }}</div>
@endsection
