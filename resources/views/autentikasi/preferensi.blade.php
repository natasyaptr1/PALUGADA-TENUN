@extends('layouts.app')

@section('title', 'Pilih Kategori Favorit - Palugada Bandar')

@section('content')

<div class="preferensi-page">

    <div class="preferensi-card">

        <div class="preferensi-brand">
            <div class="preferensi-logo">PB</div>
            <div>
                <strong>Palugada Bandar</strong>
                <span>Personalisasi Belanja</span>
            </div>
        </div>

        <div class="preferensi-step">
            LANGKAH 2 DARI 2
        </div>

        <h1>Pilih Kategori Favoritmu</h1>

        <p class="preferensi-description">
            Akunmu berhasil dibuat!
            Pilih kategori produk yang kamu sukai agar
            kami dapat menampilkan rekomendasi produk
            yang lebih sesuai untukmu.
        </p>

        <form
            method="POST"
            action="{{ route('daftar.preferensi.simpan') }}"
        >
            @csrf

            <div class="preferensi-grid">

                @forelse($kategoriProduk as $kategori)

                    <label class="preferensi-option">
                        <input
                            type="checkbox"
                            name="preferensi[]"
                            value="{{ $kategori }}"
                            @checked(in_array($kategori, old('preferensi', [])))
                        >

                        <span class="preferensi-check"></span>
                        <span class="preferensi-text">
                            {{ $kategori }}
                        </span>
                    </label>

                @empty

                    <p class="preferensi-empty">
                        Belum ada kategori produk tersedia.
                    </p>

                @endforelse

            </div>

            <p class="preferensi-count">
                <span id="jumlahKategori">0</span>
                kategori dipilih
            </p>

            @if($errors->any())
                <div class="preferensi-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <button
                type="submit"
                class="preferensi-button"
                @disabled($kategoriProduk->isEmpty())
            >
                Simpan Preferensi & Lanjutkan
            </button>

        </form>

        <form
            method="POST"
            action="{{ route('daftar.preferensi.lewati') }}"
        >
            @csrf

            <button type="submit" class="preferensi-skip">
                Lewati untuk Sekarang
            </button>
        </form>

        <p class="preferensi-footer">
            Preferensi digunakan untuk membantu sistem
            merekomendasikan produk yang sesuai.
        </p>

    </div>

</div>

<style>
.preferensi-page {
    min-height: 85vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 20px;
    background: #f5f7f2;
}

.preferensi-card {
    width: 100%;
    max-width: 600px;
    padding: 38px;
    background: #fff;
    border: 1px solid #e0e7df;
    border-radius: 22px;
    box-shadow: 0 18px 45px rgba(0,0,0,.06);
}

.preferensi-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 30px;
}

.preferensi-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #194d3a;
    color: #fff;
    font-weight: 700;
}

.preferensi-brand strong {
    display: block;
    color: #194d3a;
    font-size: 15px;
}

.preferensi-brand span {
    display: block;
    margin-top: 3px;
    font-size: 12px;
    color: #778679;
}

.preferensi-step {
    color: #50815e;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    margin-bottom: 15px;
}

.preferensi-card h1 {
    margin: 0 0 12px;
    color: #194d3a;
    font-size: 28px;
    line-height: 1.3;
}

.preferensi-description {
    margin-bottom: 28px;
    color: #718075;
    font-size: 14px;
    line-height: 1.8;
}

.preferensi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.preferensi-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    min-height: 55px;
    border: 1px solid #dce6dd;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    transition: .2s;
}

.preferensi-option:hover {
    border-color: #39845b;
    background: #f5faf6;
}

.preferensi-option input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.preferensi-check {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border: 2px solid #b1c7b6;
    border-radius: 6px;
    background: #fff;
}

.preferensi-option input:checked + .preferensi-check {
    background: #194d3a;
    border-color: #194d3a;
}

.preferensi-option input:checked + .preferensi-check::after {
    content: "✓";
    color: #fff;
    font-size: 13px;
}

.preferensi-option:has(input:checked) {
    border-color: #39845b;
    background: #edf7ef;
}

.preferensi-option:has(input:focus-visible) {
    outline: 2px solid #39845b;
    outline-offset: 3px;
}

.preferensi-text {
    color: #344c3b;
    font-size: 13px;
    font-weight: 500;
    overflow-wrap: anywhere;
}

.preferensi-count {
    margin: 20px 0;
    color: #738175;
    font-size: 12px;
}

.preferensi-button {
    width: 100%;
    padding: 15px;
    border: 0;
    border-radius: 12px;
    background: #194d3a;
    color: #fff;
    font-weight: 700;
    cursor: pointer;
}

.preferensi-button:hover {
    background: #123e2f;
}

.preferensi-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.preferensi-skip {
    width: 100%;
    padding: 14px;
    margin-top: 12px;
    border: 0;
    background: transparent;
    color: #607d69;
    cursor: pointer;
}

.preferensi-skip:hover {
    text-decoration: underline;
}

.preferensi-footer {
    margin-top: 20px;
    color: #8b978d;
    font-size: 11px;
    text-align: center;
    line-height: 1.6;
}

.preferensi-empty {
    grid-column: 1 / -1;
    color: #718075;
    font-size: 13px;
}

.preferensi-error {
    margin-bottom: 15px;
    padding: 12px;
    border-radius: 8px;
    background: #fff0f0;
    color: #b53131;
    font-size: 12px;
}

@media (max-width: 480px) {
    .preferensi-card {
        padding: 24px;
    }

    .preferensi-grid {
        grid-template-columns: 1fr;
    }

    .preferensi-card h1 {
        font-size: 24px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll(
        'input[name="preferensi[]"]'
    );

    const counter = document.getElementById('jumlahKategori');

    function updateCounter() {
        const jumlah = [...checkboxes].filter(
            checkbox => checkbox.checked
        ).length;

        counter.textContent = jumlah;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounter);
    });

    updateCounter();
});
</script>

@endsection
