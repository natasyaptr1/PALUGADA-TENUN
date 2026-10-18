@csrf
@php
    $isEdit = isset($targetProduksi);
    $totalHasil = $totalHasil ?? ($isEdit ? (int) $targetProduksi->hasilProduksi()->sum('jumlah_selesai') : 0);
    $terkunciKarenaHasil = $isEdit && $totalHasil > 0;
@endphp

<div class="form-grid">
    <label class="span-2">Produk <span style="color:var(--danger)">*</span>
        <select name="produk_id" @disabled($terkunciKarenaHasil) required>
            <option value="">-- Pilih Produk --</option>
            @foreach($daftarProduk as $produk)
                <option value="{{ $produk->id }}" @selected(old('produk_id', $targetProduksi->produk_id ?? '') == $produk->id)>
                    {{ $produk->nama_produk }} ({{ $produk->kode_produk }})
                </option>
            @endforeach
        </select>
        @if($terkunciKarenaHasil)
            <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
                Tidak dapat diubah karena sudah ada hasil produksi.
            </small>
        @endif
        @error('produk_id')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label class="span-2">Penenun <span style="color:var(--danger)">*</span>
        <select name="penenun_id" @disabled($terkunciKarenaHasil) required>
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
                <option value="{{ $penenun->id }}" @selected(old('penenun_id', $targetProduksi->penenun_id ?? '') == $penenun->id)>
                    {{ $penenun->nama }} - {{ $kodePenenun }} - {{ $keahlianText }}
                </option>
            @endforeach
        </select>
        @if($terkunciKarenaHasil)
            <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
                Tidak dapat diubah karena sudah ada hasil produksi.
            </small>
        @endif
        @error('penenun_id')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label class="span-2">Jumlah Target <span style="color:var(--danger)">*</span>
        <input type="number" name="jumlah_target" value="{{ old('jumlah_target', $targetProduksi->jumlah_target ?? '') }}" min="{{ max(1, $totalHasil) }}" max="10000" required placeholder="Contoh: 20">
        @if($totalHasil > 0)
            <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
                Hasil yang sudah masuk: {{ $totalHasil }}
            </small>
        @endif
        @error('jumlah_target')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label>Tanggal Mulai <span style="color:var(--danger)">*</span>
        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', isset($targetProduksi) && $targetProduksi->tanggal_mulai ? $targetProduksi->tanggal_mulai->format('Y-m-d') : date('Y-m-d')) }}" @disabled($terkunciKarenaHasil) required>
        @if($terkunciKarenaHasil)
            <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
                Tidak dapat diubah karena sudah ada hasil produksi.
            </small>
        @else
            <small style="font-size:11px;color:var(--muted);font-weight:normal;margin-top:2px;">
                Tidak boleh sebelum hari ini.
            </small>
        @endif
        @error('tanggal_mulai')
            <small style="color:var(--danger);font-size:11px;font-weight:600;display:block;margin-top:2px;">
                {{ $message }}
            </small>
        @enderror
    </label>

    <label>Batas Waktu Penyelesaian
        <input type="date" name="tanggal_deadline" value="{{ old('tanggal_deadline', isset($targetProduksi) && $targetProduksi->tanggal_deadline ? $targetProduksi->tanggal_deadline->format('Y-m-d') : '') }}">
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
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Target' }}</button>
</div>
