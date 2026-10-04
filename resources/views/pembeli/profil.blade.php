@extends('layouts.app')
@section('title','Profil Saya - Palugada Bandar')
@section('content')
<section class="account-page">
    <div class="section-heading"><span class="kicker">AKUN SAYA</span><h1>Profil</h1><p>Pastikan informasi kontakmu benar agar nanti proses pemesanan lebih mudah.</p></div>
    <div class="account-layout">
        <aside class="account-menu">
            <a href="{{ route('pembeli.akun') }}">Beranda Akun</a>
            <a class="active" href="{{ route('pembeli.profil') }}">Profil</a>
            <a href="{{ route('pembeli.pesanan') }}">Pesanan Saya</a>
            <a href="{{ route('pembeli.keranjang') }}">Keranjang</a>
        </aside>
        <section class="form-card account-form-card">
            <h2>Data pribadi</h2>
            <p class="form-help">Informasi ini hanya digunakan untuk kebutuhan akun dan transaksi.</p>
            <form method="POST" action="{{ route('pembeli.profil.perbarui') }}">@csrf @method('PUT')
                <div class="form-grid">
                    <label>Nama lengkap<input name="nama" value="{{ old('nama',$pengguna->nama) }}" required></label>
                    <label>Email<input type="email" name="email" value="{{ old('email',$pengguna->email) }}" required></label>
                    <label>No. HP<input name="no_hp" value="{{ old('no_hp',$pengguna->no_hp) }}" placeholder="Contoh: 081234567890"></label>
                    <label class="span-2">Alamat<textarea name="alamat" placeholder="Alamat untuk kebutuhan pengiriman nanti">{{ old('alamat',$pengguna->alamat) }}</textarea></label>
                </div>
                @if($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
                <div class="form-actions"><a href="{{ route('pembeli.akun') }}" class="btn btn-outline">Batal</a><button class="btn btn-primary">Simpan Perubahan</button></div>
            </form>
        </section>
    </div>
</section>
@endsection
