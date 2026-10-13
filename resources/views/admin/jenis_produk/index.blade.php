@extends('layouts.app')
@section('title', 'Jenis Produk - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">DATA PRODUK</span>
        <h1>Jenis Produk</h1>
        <p>Kelola kategori atau jenis kain tenun yang digunakan untuk pengelompokan produk.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.jenis-produk.create') }}">Tambah Jenis Produk</a>
</div>

<form class="toolbar admin-toolbar-v4" method="GET" action="{{ route('admin.jenis-produk.index') }}">
    <input name="cari" value="{{ request('cari') }}" placeholder="Cari nama jenis atau keterangan...">
    <button class="btn btn-secondary" type="submit">Cari</button>
    @if(request('cari'))
        <a class="btn btn-outline" href="{{ route('admin.jenis-produk.index') }}">Reset</a>
    @endif
</form>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Nama Jenis</th>
                <th>Keterangan</th>
                <th style="text-align: center; width: 140px;">Jumlah Produk</th>
                <th style="width: 140px; text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse($daftarJenisProduk as $index => $item)
            <tr>
                <td>{{ $daftarJenisProduk->firstItem() ? $daftarJenisProduk->firstItem() + $index : $index + 1 }}</td>
                <td><strong>{{ $item->nama_jenis }}</strong></td>
                <td>{{ $item->keterangan ?: '-' }}</td>
                <td style="text-align: center;">
                    <span class="stock-pill">{{ $item->produk_count ?? 0 }}</span>
                </td>
                <td class="actions">
                    <a href="{{ route('admin.jenis-produk.edit', $item) }}">Ubah</a>
                    @if(($item->produk_count ?? 0) > 0)
                        <button type="button" class="btn-locked-delete" title="Jenis produk tidak dapat dihapus karena masih digunakan oleh {{ $item->produk_count }} produk." onclick="alert('Jenis produk tidak dapat dihapus karena masih digunakan oleh {{ $item->produk_count }} produk.');">Hapus</button>
                    @else
                        <form method="POST" action="{{ route('admin.jenis-produk.destroy', $item) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus jenis produk \'{{ $item->nama_jenis }}\'?')">Hapus</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="empty">Belum ada jenis produk.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($daftarJenisProduk->hasPages())
    <div class="pagination">{{ $daftarJenisProduk->links() }}</div>
@endif
@endsection
