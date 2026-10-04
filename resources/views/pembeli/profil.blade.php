@extends('layouts.app')
@section('title','Akun Saya - Palugada Bandar')
@section('content')
<section class="member-simple-page profile-simple-page member-account-v4">
    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">AKUN SAYA</span>
            <h1>Profil</h1>
            <p>Kelola data yang digunakan untuk akun dan proses belanja.</p>
        </div>
    </div>

    <section class="profile-form-clean profile-form-v4">
        <form method="POST" action="{{ route('pembeli.profil.perbarui') }}">
            @csrf
            @method('PUT')
            <div class="form-grid profile-form-grid">
                <label>Nama lengkap<input name="nama" value="{{ old('nama',$pengguna->nama) }}" required></label>
                <label>Email<input type="email" name="email" value="{{ old('email',$pengguna->email) }}" required></label>
                <label>No. HP<input name="no_hp" value="{{ old('no_hp',$pengguna->no_hp) }}" placeholder="Contoh: 081234567890"></label>
                <label class="span-2">Alamat<textarea name="alamat" placeholder="Masukkan alamat lengkap">{{ old('alamat',$pengguna->alamat) }}</textarea></label>
            </div>
            @if($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
            <div class="form-actions profile-actions-v4">
                <a href="{{ route('pembeli.katalog') }}" class="btn btn-outline">Lihat Koleksi</a>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </section>
</section>
@endsection
