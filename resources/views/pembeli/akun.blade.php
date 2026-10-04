@extends('layouts.app')
@section('title','Akun Saya - Palugada Bandar')
@section('content')
<section class="member-simple-page">
    <div class="member-flat-empty member-flat-empty-large">
        <span>PB</span>
        <div><strong>Akun Saya</strong><p>Kelola profilmu dari halaman profil.</p></div>
        <a href="{{ route('pembeli.profil') }}" class="btn btn-primary">Buka Profil</a>
    </div>
</section>
@endsection
