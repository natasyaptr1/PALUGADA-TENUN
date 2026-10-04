@extends('layouts.app')
@section('title', $judul.' - Palugada Bandar')
@section('content')
<section class="admin-page-head admin-page-head-v4">
    <div><span class="eyebrow">PENGELOLAAN PALUGADA</span><h1>{{ $judul }}</h1></div>
</section>
<section class="admin-placeholder-clean" aria-label="{{ $judul }} belum memiliki data">
    <div class="admin-placeholder-symbol">◇</div>
    <strong>Belum ada data</strong>
    <p>{{ $deskripsi ?? 'Data '.strtolower($judul).' akan tampil di halaman ini.' }}</p>
</section>
@endsection
