@extends('layouts.app')
@section('title', 'Ubah Jenis Produk')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">DATA JENIS PRODUK</span>
        <h1>Ubah Jenis Produk</h1>
        <p>Perbarui nama jenis produk atau keterangan kategori.</p>
    </div>
</div>
<form method="POST" action="{{ route('admin.jenis-produk.update', $jenisProduk) }}" class="form-card">
    @method('PUT')
    @include('admin.jenis_produk._form')
</form>
@endsection
