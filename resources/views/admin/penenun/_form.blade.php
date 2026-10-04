@csrf
<div class="form-grid">
<label>Kode Penenun<input name="kode_penenun" value="{{ old('kode_penenun',$penenun->profilPenenun->kode_penenun??'') }}" required></label>
<label>Nama<input name="nama" value="{{ old('nama',$penenun->nama??'') }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email',$penenun->email??'') }}" required></label>
<label>No. HP<input name="no_hp" value="{{ old('no_hp',$penenun->no_hp??'') }}"></label>
<label>Keahlian<input name="keahlian" value="{{ old('keahlian',$penenun->profilPenenun->keahlian??'') }}"></label>
<label>Status<select name="status"><option value="aktif">Aktif</option><option value="nonaktif" @selected(old('status',$penenun->status??'')==='nonaktif')>Nonaktif</option></select></label>
<label class="span-2">Alamat<textarea name="alamat">{{ old('alamat',$penenun->alamat??'') }}</textarea></label>
<label>Kata sandi<input type="password" name="kata_sandi" {{ isset($penenun)?'':'required' }}></label>
<label>Konfirmasi kata sandi<input type="password" name="kata_sandi_confirmation" {{ isset($penenun)?'':'required' }}></label>
<label class="span-2">Catatan<textarea name="catatan">{{ old('catatan',$penenun->profilPenenun->catatan??'') }}</textarea></label>
</div>@if($errors->any())<div class="error-list">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="form-actions"><a class="btn btn-ghost" href="{{ route('admin.penenun.index') }}">Batal</a><button class="btn btn-primary">Simpan Penenun</button></div>
