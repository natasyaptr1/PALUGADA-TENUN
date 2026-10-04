@extends('layouts.app')
@section('title','Ubah Produk')
@section('content')
<div class="page-head admin-page-head-v4">
    <div><span class="eyebrow">DATA PRODUK</span><h1>Ubah Produk</h1><p>Perbarui nama, motif, harga, foto, atau informasi produk lainnya.</p></div>
</div>
<form method="POST" action="{{ route('admin.produk.update',$produk) }}" enctype="multipart/form-data" class="form-card">@method('PUT') @include('admin.produk._form')</form>
@endsection
