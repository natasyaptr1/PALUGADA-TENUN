@extends('layouts.app')
@section('title','Tambah Produk')
@section('content')
<div class="page-head admin-page-head-v4">
    <div><span class="eyebrow">DATA PRODUK</span><h1>Tambah Produk</h1><p>Tambahkan identitas barang yang nantinya tampil pada koleksi pembeli.</p></div>
</div>
<form method="POST" action="{{ route('admin.produk.store') }}" enctype="multipart/form-data" class="form-card">@include('admin.produk._form')</form>
@endsection
