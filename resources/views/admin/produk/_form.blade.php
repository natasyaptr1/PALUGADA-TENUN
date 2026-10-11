@csrf
<div class="form-grid">
<label>Kode Produk<input name="kode_produk" value="{{ old('kode_produk', $produk->kode_produk ?? $kodeOtomatis ?? '') }}" readonly class="bg-readonly" title="Kode produk dibuat otomatis oleh sistem"></label>
<label>Nama Produk<input name="nama_produk" value="{{ old('nama_produk', $produk->nama_produk ?? '') }}" required></label>
@php $daftarJenis = $daftarJenis ?? \App\Models\JenisProduk::orderBy('nama_jenis')->get(); @endphp
<label>Jenis Produk
    <select name="jenis_produk_id" required>
        <option value="">-- Pilih Jenis Produk --</option>
        @foreach($daftarJenis as $itemJenis)
            <option value="{{ $itemJenis->id }}" @selected(old('jenis_produk_id', $produk->jenis_produk_id ?? '') == $itemJenis->id || (empty($produk->jenis_produk_id) && strcasecmp(old('jenis', $produk->jenis ?? ''), $itemJenis->nama_jenis) === 0))>
                {{ $itemJenis->nama_jenis }}
            </option>
        @endforeach
    </select>
</label>
<label>Motif<input name="motif" value="{{ old('motif', $produk->motif ?? '') }}"></label>
<label>Warna<input name="warna" value="{{ old('warna', $produk->warna ?? '') }}"></label>
<label>Ukuran<input name="ukuran" value="{{ old('ukuran', $produk->ukuran ?? '') }}"></label>
<label>Bahan<input name="material" value="{{ old('material', $produk->material ?? '') }}"></label>
<label>Harga (Rp)
    <input type="text" name="harga" class="js-currency-input" inputmode="numeric" placeholder="Contoh: 500.000" value="{{ old('harga') ? number_format((float)preg_replace('/[^0-9]/', '', (string)old('harga')), 0, ',', '.') : (isset($produk->harga) ? number_format((float)$produk->harga, 0, ',', '.') : '') }}" required>
    <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">Format ribuan otomatis dengan pemisah titik (contoh: 500.000).</small>
</label>
<label class="span-2">Foto Produk (Dapat Memilih Beberapa Foto Sekaligus)
    <input type="file" name="foto[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
    <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
        Pilih satu atau beberapa file foto sekaligus (tahan Ctrl atau Shift saat memilih). Format: JPG, JPEG, PNG, WEBP. Maks 2MB per foto.
    </small>
</label>
@if(isset($produk) && $produk->produkFoto && $produk->produkFoto->isNotEmpty())
<div class="span-2">
    <div style="font-weight:800;font-size:11px;color:#405049;margin-bottom:8px;">Foto Tersimpan Saat Ini ({{ $produk->produkFoto->count() }} foto):</div>
    <div class="existing-photos-grid">
        @foreach($produk->produkFoto as $fotoItem)
            <div class="existing-photo-card">
                <img src="{{ asset('storage/' . $fotoItem->foto) }}" alt="Foto Produk {{ $produk->nama_produk }}">
                <button type="submit" form="hapus-foto-{{ $fotoItem->id }}" class="btn-hapus-foto" onclick="return confirm('Hapus foto ini dari produk?')">&times; Hapus</button>
            </div>
        @endforeach
    </div>
    <small style="font-size:11px;color:var(--muted);display:block;margin-top:6px;">* Foto baru yang diunggah akan otomatis ditambahkan ke daftar foto di atas.</small>
</div>
@endif
<label>Status<select name="status"><option value="aktif">Aktif</option><option value="nonaktif" @selected(old('status',$produk->status??'')==='nonaktif')>Nonaktif</option></select></label>
</div>@if($errors->any())<div class="error-list">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="form-actions"><a class="btn btn-ghost" href="{{ route('admin.produk.index') }}">Batal</a><button type="reset" class="btn btn-outline js-btn-reset" data-mode="{{ isset($produk) ? 'edit' : 'create' }}">Bersihkan Form</button><button type="submit" class="btn btn-primary">Simpan Produk</button></div>
