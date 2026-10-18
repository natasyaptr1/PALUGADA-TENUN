@extends('layouts.app')
@section('title','Masuk - Palugada Bandar')
@section('content')
<div class="auth-wrap auth-v3 auth-v4">
    <section class="auth-hero auth-hero-v3">
        <a href="{{ route('landing') }}" class="auth-back-link">Kembali ke Beranda</a>
        <div class="auth-hero-copy-v3">
            <span class="eyebrow">TENUN IKAT PALUGADA BANDAR</span>
            <h1>Senang melihatmu kembali.</h1>
            <p>Masuk untuk melanjutkan aktivitas sesuai akunmu.</p>
        </div>
        <div class="auth-weave-visual" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    </section>

    <section class="auth-panel-v3">
        <div class="auth-card auth-card-v3">
            <div class="brand auth-brand"><div class="brand-mark">PB</div><div><strong>Palugada Bandar</strong><span>Masuk</span></div></div>
            <div class="auth-card-heading"><h2>Selamat datang</h2><p>Masukkan email dan kata sandi akunmu.</p></div>
            <form method="POST" action="{{ route('masuk.proses') }}" id="formMasukPalugada" autocomplete="off">
                @csrf
                <label>Email<input type="email" name="email" value="" autocomplete="off" placeholder="nama@email.com" required autofocus></label>
                <label>Kata sandi
                    <span class="password-field">
                        <input type="password" name="kata_sandi" autocomplete="new-password" placeholder="Masukkan kata sandi" required data-password-input>
                        <button type="button" class="password-toggle password-toggle-icon" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.5 10.5 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.3 3.1"></path><path d="M6.1 6.1C3.8 7.8 2.5 12 2.5 12s3.5 6 9.5 6c1.5 0 2.8-.4 4-1"></path></svg>
                        </button>
                    </span>
                </label>
                @error('email')<p class="error">{{ $message }}</p>@enderror
                <label class="check"><input type="checkbox" name="ingat"> Ingat saya di perangkat ini</label>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Masuk</button>
            </form>
            <div class="auth-links-v3">
                <p>Belum punya akun? <a href="{{ route('daftar') }}">Daftar sebagai pembeli</a></p>
                <a href="{{ route('pembeli.katalog') }}" class="auth-secondary-link">Lihat koleksi tanpa masuk</a>
            </div>
        </div>
    </section>
</div>

<script>
(() => {
    const form = document.getElementById('formMasukPalugada');
    if (!form) return;
    const fields = [form.querySelector('[name="email"]'), form.querySelector('[name="kata_sandi"]')].filter(Boolean);
    let typed = false;
    let submitted = false;
    const clear = () => {
        if (typed || submitted) return;
        fields.forEach(input => {
            input.value = '';
            input.defaultValue = '';
        });
    };
    fields.forEach(input => input.addEventListener('input', event => {
        if (event.isTrusted) typed = true;
    }));
    form.addEventListener('submit', () => { submitted = true; });
    window.addEventListener('pageshow', () => {
        typed = false;
        submitted = false;
        clear();
        [80, 250, 600, 1200].forEach(ms => setTimeout(clear, ms));
    });
    clear();
})();
</script>
@endsection
