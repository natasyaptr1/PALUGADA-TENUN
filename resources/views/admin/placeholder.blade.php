@extends('layouts.app')
@section('title', $judul.' - Palugada Bandar')
@section('content')
<div class="page-head">
    <div><span class="eyebrow">PENGELOLAAN PALUGADA</span><h1>{{ $judul }}</h1><p>{{ $deskripsi }}</p></div>
</div>
<section class="panel placeholder-panel">
    <div class="placeholder-icon">◇</div>
    <h2>Halaman sudah disiapkan</h2>
    <p>Menu ini sudah menjadi bagian dari alur sistem. Fungsinya akan ditambahkan pada pengembangan berikutnya tanpa mengubah susunan menu utama.</p>
    <a href="{{ route('admin.beranda') }}" class="btn btn-primary">Kembali ke Beranda</a>
</section>
@endsection
