@extends('layouts.app')
@section('title','Tambah Produk')
@section('content')<div class="page-head"><div><span class="eyebrow">PRODUK & STOK</span><h1>Tambah Produk</h1><p>Stok awal otomatis dibuat 0 saat produk disimpan.</p></div></div><form method="POST" action="{{ route('admin.produk.store') }}" enctype="multipart/form-data" class="form-card">@include('admin.produk._form')</form>@endsection