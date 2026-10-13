@extends('layouts.app')
@section('title', 'Tambah Jenis Produk')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">DATA JENIS PRODUK</span>
        <h1>Tambah Jenis Produk</h1>
        <p>Tambahkan jenis atau kategori baru untuk kelompok produk tenun.</p>
    </div>
</div>
<form method="POST" action="{{ route('admin.jenis-produk.store') }}" class="form-card">
    @include('admin.jenis_produk._form')
</form>
@endsection
