@csrf
<div class="form-grid">
<label>Kode Produk<input name="kode_produk" value="{{ old('kode_produk', $produk->kode_produk ?? '') }}" required></label>
<label>Nama Produk<input name="nama_produk" value="{{ old('nama_produk', $produk->nama_produk ?? '') }}" required></label>
<label>Jenis<input name="jenis" value="{{ old('jenis', $produk->jenis ?? '') }}" required></label>
<label>Motif<input name="motif" value="{{ old('motif', $produk->motif ?? '') }}"></label>
<label>Warna<input name="warna" value="{{ old('warna', $produk->warna ?? '') }}"></label>
<label>Ukuran<input name="ukuran" value="{{ old('ukuran', $produk->ukuran ?? '') }}"></label>
<label>Bahan<input name="material" value="{{ old('material', $produk->material ?? '') }}"></label>
<label>Harga<input type="number" name="harga" min="0" step="1000" value="{{ old('harga', $produk->harga ?? '') }}" required></label>
<label>Foto<input type="file" name="foto" accept="image/*"></label>
<label>Status<select name="status"><option value="aktif">Aktif</option><option value="nonaktif" @selected(old('status',$produk->status??'')==='nonaktif')>Nonaktif</option></select></label>
</div>@if($errors->any())<div class="error-list">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="form-actions"><a class="btn btn-ghost" href="{{ route('admin.produk.index') }}">Batal</a><button class="btn btn-primary">Simpan Produk</button></div>
