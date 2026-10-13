@csrf
<div class="form-grid">
    <label class="span-2">Nama Jenis Produk <span style="color:var(--danger)">*</span>
        <input type="text" name="nama_jenis" value="{{ old('nama_jenis', $jenisProduk->nama_jenis ?? '') }}" required maxlength="50" placeholder="Contoh: Kain Tenun, Sarung Tenun, Songket">
        <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
            Nama jenis produk harus unik dan maksimal 50 karakter.
        </small>
    </label>

    <label class="span-2">Keterangan
        <textarea name="keterangan" rows="4" maxlength="500" placeholder="Deskripsi atau keterangan singkat mengenai jenis produk ini (opsional)">{{ old('keterangan', $jenisProduk->keterangan ?? '') }}</textarea>
        <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
            Maksimal 500 karakter.
        </small>
    </label>
</div>

@if($errors->any())
    <div class="error-list">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="form-actions">
    <a class="btn btn-ghost" href="{{ route('admin.jenis-produk.index') }}">Batal</a>
    <button type="reset" class="btn btn-outline js-btn-reset" data-mode="{{ isset($jenisProduk) ? 'edit' : 'create' }}">Bersihkan Form</button>
    <button type="submit" class="btn btn-primary">Simpan Jenis Produk</button>
</div>
