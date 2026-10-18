@extends('layouts.app')
@section('title', 'Buat Target Produksi - Palugada Bandar')
@section('content')
<div class="tp-page tp-create-page">
    <div class="tp-head">
        <div><span class="tp-overline">PRODUKSI / TARGET BARU</span><h1>Buat Target Produksi</h1><p>Isi penugasan produk dan jumlah yang harus diselesaikan penenun.</p></div>
        <a class="tp-button tp-secondary" href="{{ route('admin.target-produksi') }}">← Kembali ke Daftar</a>
    </div>
    @if ($errors->any())
        <div class="tp-alert tp-danger" role="alert"><strong>Periksa data berikut:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section class="tp-panel tp-form-panel">
        <div class="tp-form-heading"><div><h2>Informasi Penugasan</h2><p>Setelah disimpan, target akan tersedia untuk akun penenun yang dipilih.</p></div></div>
        <form action="{{ route('admin.target-produksi.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="tp-form-grid">
                <label>Produk <select name="produk_id" required><option value="">Pilih produk</option>@foreach ($produk as $p)<option value="{{ $p->id }}" @selected(old('produk_id') == $p->id)>{{ $p->nama_produk }}</option>@endforeach</select></label>
                <label>Penenun <select name="penenun_id" required><option value="">Pilih penenun</option>@foreach ($penenun as $p)<option value="{{ $p->id }}" @selected(old('penenun_id') == $p->id)>{{ $p->nama }}</option>@endforeach</select></label>
                <label>Jumlah Target (pcs) <input name="jumlah_target" type="number" min="1" value="{{ old('jumlah_target') }}" required></label>
                <label>Tanggal Mulai <input name="tanggal_mulai" type="date" value="{{ old('tanggal_mulai') }}" required></label>
                <label>Batas Waktu <input name="tanggal_deadline" type="date" value="{{ old('tanggal_deadline') }}"></label>
            </div>
            <div class="tp-form-actions"><button type="submit" class="tp-button tp-primary">Simpan Target</button><a href="{{ route('admin.target-produksi') }}" class="tp-button tp-secondary">Batal</a></div>
        </form>
    </section>
</div>
@endsection
