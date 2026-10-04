@extends('layouts.app')
@section('title','Ubah Penenun')
@section('content')<div class="page-head"><div><span class="eyebrow">DATA PENENUN</span><h1>Ubah Penenun</h1><p>Kosongkan kata sandi jika tidak ingin mengubahnya.</p></div></div><form method="POST" action="{{ route('admin.penenun.update',$penenun) }}" class="form-card">@method('PUT') @include('admin.penenun._form')</form>@endsection