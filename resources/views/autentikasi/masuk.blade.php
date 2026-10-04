@extends('layouts.app')
@section('title','Masuk - Palugada Bandar')
@section('content')
<div class="auth-wrap">
    <section class="auth-hero">
        <div class="eyebrow">TENUN IKAT PALUGADA BANDAR</div>
        <h1>Masuk dengan tenang, lanjutkan pekerjaan tanpa bingung.</h1>
        <p>Gunakan email dan kata sandi yang sudah terdaftar. Setelah masuk, kamu akan langsung diarahkan ke halaman yang sesuai.</p>
        <div class="motif-card"><span></span><span></span><span></span><span></span></div>
    </section>
    <section class="auth-card">
        <div class="brand" style="margin-bottom:24px"><div class="brand-mark">PB</div><div><strong>Palugada Bandar</strong><span>Masuk ke sistem</span></div></div>
        <h2>Selamat datang</h2>
        <form method="POST" action="{{ route('masuk.proses') }}">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus></label>
            <label>Kata sandi<input type="password" name="kata_sandi" placeholder="Masukkan kata sandi" required></label>
            @error('email')<p class="error">{{ $message }}</p>@enderror
            <label class="check"><input type="checkbox" name="ingat"> Ingat saya di perangkat ini</label>
            <button class="btn btn-primary btn-block">Masuk</button>
        </form>
        <p class="auth-foot">Belum punya akun pembeli? <a href="{{ route('daftar') }}">Daftar sekarang</a></p>
        <p class="auth-foot"><a href="{{ route('pembeli.katalog') }}">← Kembali ke katalog</a></p>
    </section>
</div>
@endsection
