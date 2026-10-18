@csrf
<div class="form-grid">
    <label class="span-2">Produk <span style="color:var(--danger)">*</span>
        <select name="produk_id" required>
            <option value="">-- Pilih Produk --</option>
            @foreach($daftarProduk as $produk)
                <option value="{{ $produk->id }}" @selected(old('produk_id') == $produk->id)>
                    {{ $produk->nama_produk }} ({{ $produk->kode_produk }})
                </option>
            @endforeach
        </select>
        @error('produk_id')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label class="span-2">Penenun <span style="color:var(--danger)">*</span>
        <select name="penenun_id" required>
            <option value="">-- Pilih Penenun --</option>
            @foreach($daftarPenenun as $penenun)
                @php
                    $keahlianText = '-';
                    if ($penenun->profilPenenun) {
                        if ($penenun->profilPenenun->daftarKeahlian && $penenun->profilPenenun->daftarKeahlian->isNotEmpty()) {
                            $keahlianText = $penenun->profilPenenun->daftarKeahlian->pluck('nama_keahlian')->join(', ');
                        } elseif (!empty($penenun->profilPenenun->keahlian)) {
                            $keahlianText = $penenun->profilPenenun->keahlian;
                        }
                    }
                    $kodePenenun = $penenun->profilPenenun?->kode_penenun ?? '-';
                @endphp
                <option value="{{ $penenun->id }}" @selected(old('penenun_id') == $penenun->id)>
                    {{ $penenun->nama }} - {{ $kodePenenun }} - {{ $keahlianText }}
                </option>
            @endforeach
        </select>
        @error('penenun_id')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label class="span-2">Jumlah Target <span style="color:var(--danger)">*</span>
        <input type="number" name="jumlah_target" value="{{ old('jumlah_target') }}" min="1" max="10000" required placeholder="Contoh: 20">
        @error('jumlah_target')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label>Tanggal Mulai <span style="color:var(--danger)">*</span>
        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
        <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
            Tidak boleh sebelum hari ini.
        </small>
        @error('tanggal_mulai')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label>Batas Waktu Penyelesaian
        <input type="date" name="tanggal_deadline" value="{{ old('tanggal_deadline') }}">
        <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
            Opsional, kosongkan bila tidak ada batas waktu khusus.
        </small>
        @error('tanggal_deadline')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
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
    <a class="btn btn-outline" href="{{ route('admin.target-produksi.index') }}">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan Target</button>
</div>
