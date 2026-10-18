@extends('layouts.app')
@section('title', 'Ubah Target Produksi - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">PRODUKSI</span>
        <h1>Ubah Target Produksi</h1>
        <p>Perbarui rencana target produksi dan batas waktu penyelesaian untuk penenun.</p>
    </div>
</div>
<form method="POST" action="{{ route('admin.target-produksi.update', $targetProduksi) }}" class="form-card">
    @method('PUT')
    @include('admin.target_produksi._form')
</form>
@endsection
