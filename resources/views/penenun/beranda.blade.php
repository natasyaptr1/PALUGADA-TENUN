@extends('layouts.app')
@section('title','Ruang Penenun - Palugada Bandar')
@section('content')
<div class="worker-page">
    <section class="worker-welcome-card">
        <div>
            <span class="kicker">RUANG PENENUN</span>
            <h1>Halo, {{ $pengguna->nama }}</h1>
            <p>Akunmu sudah aktif. Pekerjaan produksi nantinya akan lebih nyaman dilakukan lewat aplikasi Android Palugada Penenun.</p>
        </div>
        <div class="worker-id-card">
            <small>Kode Penenun</small>
            <strong>{{ $pengguna->profilPenenun?->kode_penenun ?? '-' }}</strong>
            <span>{{ $pengguna->profilPenenun?->keahlian ?? 'Penenun Palugada' }}</span>
        </div>
    </section>

    <section class="worker-mobile-callout">
        <div class="phone-preview">
            <div class="phone-top"></div>
            <div class="phone-screen">
                <span class="mini-brand">PB</span>
                <small>Selamat bekerja,</small>
                <h3>{{ explode(' ', $pengguna->nama)[0] }}</h3>
                <div class="mini-task"><span>▤</span><p><strong>Target Produksi</strong><small>Lihat pekerjaan yang diberikan admin</small></p></div>
                <div class="mini-task"><span>＋</span><p><strong>Catat Hasil</strong><small>Masukkan jumlah produk yang selesai</small></p></div>
                <div class="mini-task"><span>↺</span><p><strong>Riwayat</strong><small>Lihat catatan pekerjaan sebelumnya</small></p></div>
            </div>
        </div>
        <div class="worker-mobile-copy">
            <span class="eyebrow">APLIKASI ANDROID PENENUN</span>
            <h2>Dibuat sederhana untuk pekerjaan lapangan</h2>
            <p>Tampilan mobile fokus pada tiga kebutuhan utama: melihat target, mencatat hasil produksi, dan melihat riwayat. Tidak ada menu teknis yang tidak dibutuhkan penenun.</p>
            <div class="worker-checks"><span>✓ Masuk dengan akun penenun</span><span>✓ Data terhubung ke sistem utama</span><span>✓ Siap ditambah fitur target produksi berikutnya</span></div>
        </div>
    </section>

    <div class="worker-feature-grid">
        <article><span>▤</span><h3>Target Produksi</h3><p>Melihat produk, jumlah target, deadline, dan progres pekerjaan.</p><small>Siap ditambahkan</small></article>
        <article><span>＋</span><h3>Catat Hasil Produksi</h3><p>Mencatat jumlah produk yang sudah benar-benar selesai.</p><small>Siap ditambahkan</small></article>
        <article><span>↺</span><h3>Riwayat Produksi</h3><p>Melihat catatan hasil dan pekerjaan yang pernah dikerjakan.</p><small>Siap ditambahkan</small></article>
    </div>
</div>
@endsection
