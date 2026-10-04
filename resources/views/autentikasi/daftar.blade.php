@extends('layouts.app')
@section('title','Daftar Pembeli - Palugada Bandar')
@section('content')
<div class="auth-wrap auth-v3 auth-v4 compact-page">
    <section class="auth-hero auth-hero-v3 auth-register-hero">
        <a href="{{ route('landing') }}" class="auth-back-link">Kembali ke Beranda</a>
        <div class="auth-hero-copy-v3">
            <span class="eyebrow">AKUN PEMBELI</span>
            <h1>Buat akun untuk belanja lebih praktis.</h1>
            <p>Simpan data akunmu agar keranjang dan pesanan lebih mudah dikelola.</p>
        </div>
        <div class="auth-weave-visual" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    </section>

    <section class="auth-panel-v3">
        <div class="auth-card auth-card-v3 auth-register-card">
            <div class="brand auth-brand"><div class="brand-mark">PB</div><div><strong>Palugada Bandar</strong><span>Daftar Pembeli</span></div></div>
            <div class="auth-card-heading"><h2>Buat akun</h2><p>Isi data berikut untuk membuat akun pembeli.</p></div>
            <form method="POST" action="{{ route('daftar.proses') }}">
                @csrf
                <label>Nama lengkap<input name="nama" value="{{ old('nama') }}" placeholder="Nama Anda" required></label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required></label>
                <label>No. HP <small class="field-note">opsional</small><input name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"></label>
                <label>Kata sandi
                    <span class="password-field">
                        <input type="password" name="kata_sandi" placeholder="Minimal 8 karakter" required data-password-input>
                        <button type="button" class="password-toggle password-toggle-icon" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.5 10.5 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.3 3.1"></path><path d="M6.1 6.1C3.8 7.8 2.5 12 2.5 12s3.5 6 9.5 6c1.5 0 2.8-.4 4-1"></path></svg>
                        </button>
                    </span>
                </label>
                <label>Konfirmasi kata sandi
                    <span class="password-field">
                        <input type="password" name="kata_sandi_confirmation" placeholder="Ulangi kata sandi" required data-password-input>
                        <button type="button" class="password-toggle password-toggle-icon" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.5 10.5 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.3 3.1"></path><path d="M6.1 6.1C3.8 7.8 2.5 12 2.5 12s3.5 6 9.5 6c1.5 0 2.8-.4 4-1"></path></svg>
                        </button>
                    </span>
                </label>
                @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
                <button class="btn btn-primary btn-block btn-lg">Daftar</button>
            </form>
            <div class="auth-links-v3">
                <p>Sudah punya akun? <a href="{{ route('masuk') }}">Masuk sekarang</a></p>
                <a href="{{ route('pembeli.katalog') }}" class="auth-secondary-link">Lihat koleksi terlebih dahulu</a>
            </div>
        </div>
    </section>
</div>
@endsection
