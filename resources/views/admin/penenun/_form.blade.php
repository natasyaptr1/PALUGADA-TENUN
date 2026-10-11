@csrf
<div class="form-grid">
<label>Kode Penenun<input name="kode_penenun" value="{{ old('kode_penenun', $penenun->profilPenenun->kode_penenun ?? $kodeOtomatis ?? '') }}" readonly class="bg-readonly" title="Kode penenun dibuat otomatis oleh sistem"></label>
<label>Nama<input name="nama" value="{{ old('nama',$penenun->nama??'') }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email',$penenun->email??'') }}" required></label>
<label>No. HP<input name="no_hp" value="{{ old('no_hp',$penenun->no_hp??'') }}"></label>
<div class="span-2 keahlian-container">
    <div style="font-weight:800;font-size:11px;color:#405049;margin-bottom:8px;">Keahlian Penenun (Dapat memilih lebih dari satu):</div>
    @php
        $daftarKeahlianSemua = $daftarKeahlian ?? \App\Models\Keahlian::orderBy('nama_keahlian')->get();
        $terpilih = old('keahlian_ids', isset($penenun) && $penenun->profilPenenun ? $penenun->profilPenenun->daftarKeahlian->pluck('id')->toArray() : []);
    @endphp
    <div class="keahlian-checkbox-grid">
        @foreach($daftarKeahlianSemua as $itemKeahlian)
            <label class="keahlian-checkbox-item">
                <input type="checkbox" name="keahlian_ids[]" value="{{ $itemKeahlian->id }}" @checked(in_array($itemKeahlian->id, (array)$terpilih))>
                <span>{{ $itemKeahlian->nama_keahlian }}</span>
            </label>
        @endforeach
    </div>
    <small style="color:var(--muted);font-size:11px;display:block;margin-top:4px;">
        Centang satu atau beberapa keahlian yang dimiliki oleh penenun.
    </small>
</div>
<label>Status<select name="status"><option value="aktif">Aktif</option><option value="nonaktif" @selected(old('status',$penenun->status??'')==='nonaktif')>Nonaktif</option></select></label>
<label class="span-2">Alamat<textarea name="alamat">{{ old('alamat',$penenun->alamat??'') }}</textarea></label>
<label>Kata sandi<input type="password" name="kata_sandi" {{ isset($penenun)?'':'required' }}></label>
<label>Konfirmasi kata sandi<input type="password" name="kata_sandi_confirmation" {{ isset($penenun)?'':'required' }}></label>
<label class="span-2">Catatan<textarea name="catatan">{{ old('catatan',$penenun->profilPenenun->catatan??'') }}</textarea></label>
</div>@if($errors->any())<div class="error-list">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="form-actions"><a class="btn btn-ghost" href="{{ route('admin.penenun.index') }}">Batal</a><button type="reset" class="btn btn-outline js-btn-reset" data-mode="{{ isset($penenun) ? 'edit' : 'create' }}">Bersihkan Form</button><button type="submit" class="btn btn-primary">Simpan Penenun</button></div>
