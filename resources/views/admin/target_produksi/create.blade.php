@extends('layouts.app')
@section('title', 'Buat Target Produksi - Palugada Bandar')
@section('content')
<div class="page-head admin-page-head-v4">
    <div>
        <span class="eyebrow">PRODUKSI</span>
        <h1>Buat Target Produksi</h1>
        <p>Tugaskan pembuatan kain tenun kepada penenun dengan jumlah target dan batas waktu penyelesaian.</p>
    </div>
</div>
<form method="POST" action="{{ route('admin.target-produksi.store') }}" class="form-card">
    @include('admin.target_produksi._form')
</form>
@endsection
