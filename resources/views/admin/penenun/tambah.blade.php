@extends('layouts.app')
@section('title','Tambah Penenun')
@section('content')<div class="page-head"><div><span class="eyebrow">DATA PENENUN</span><h1>Tambah Penenun</h1><p>Buat data dan akun mobile penenun sekaligus.</p></div></div><form method="POST" action="{{ route('admin.penenun.store') }}" class="form-card">@include('admin.penenun._form')</form>@endsection