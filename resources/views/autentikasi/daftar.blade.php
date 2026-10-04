@extends('layouts.app')
@section('title','Daftar Pembeli - Palugada Bandar')
@section('content')
<div class="auth-wrap compact-page">
    <section class="auth-hero">
        <div class="eyebrow">AKUN PEMBELI</div>
        <h1>Mulai dari katalog, lanjut ke transaksi yang terhubung.</h1>
        <p>Pendaftaran ini khusus untuk pembeli. Akun Admin dan Penenun dibuat langsung oleh pengelola Palugada.</p>
    </section>
    <section class="auth-card">
        <div class="brand" style="margin-bottom:24px"><div class="brand-mark">PB</div><div><strong>Palugada Bandar</strong><span>Registrasi pembeli</span></div></div>
        <h2>Buat akun</h2>
        <form method="POST" action="{{ route('daftar.proses') }}">
            @csrf
            <label>Nama lengkap<input name="nama" value="{{ old('nama') }}" placeholder="Nama Anda" required></label>
            <label>Email<input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required></label>
            <label>No. HP <small style="font-weight:400;color:#7a847f">(opsional)</small><input name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"></label>
            <label>Kata sandi<input type="password" name="kata_sandi" placeholder="Minimal 8 karakter" required></label>
            <label>Konfirmasi kata sandi<input type="password" name="kata_sandi_confirmation" placeholder="Ulangi kata sandi" required></label>
            @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
            <button class="btn btn-primary btn-block">Daftar</button>
        </form>
        <p class="auth-foot">Sudah punya akun? <a href="{{ route('masuk') }}">Masuk</a></p>
        <p class="auth-foot"><a href="{{ route('pembeli.katalog') }}">← Kembali ke katalog</a></p>
    </section>
</div>
@endsection
