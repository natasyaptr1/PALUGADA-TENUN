<?php

use App\Http\Controllers\Admin\PenenunController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\AutentikasiController;
use App\Http\Controllers\Pembeli\AkunController;
use App\Models\Pengguna;
use App\Models\Produk;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('pembeli.katalog'));

Route::get('/katalog', function () {
    $cari = trim((string) request('cari'));
    $jenis = trim((string) request('jenis'));

    $daftarProduk = Produk::query()
        ->with('stok')
        ->where('status', 'aktif')
        ->when($cari, function ($query) use ($cari) {
            $query->where(function ($sub) use ($cari) {
                $sub->where('nama_produk', 'like', "%{$cari}%")
                    ->orWhere('kode_produk', 'like', "%{$cari}%")
                    ->orWhere('jenis', 'like', "%{$cari}%")
                    ->orWhere('motif', 'like', "%{$cari}%")
                    ->orWhere('warna', 'like', "%{$cari}%");
            });
        })
        ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
        ->latest()
        ->get();

    $jenisProduk = Produk::query()
        ->where('status', 'aktif')
        ->whereNotNull('jenis')
        ->where('jenis', '!=', '')
        ->select('jenis')
        ->distinct()
        ->orderBy('jenis')
        ->pluck('jenis');

    $produkUnggulan = Produk::query()
        ->with('stok')
        ->where('status', 'aktif')
        ->latest()
        ->take(5)
        ->get();

    return view('pembeli.katalog', compact('daftarProduk', 'jenisProduk', 'produkUnggulan', 'cari', 'jenis'));
})->name('pembeli.katalog');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AutentikasiController::class, 'tampilMasuk'])->name('masuk');
    Route::post('/masuk', [AutentikasiController::class, 'masuk'])->name('masuk.proses');
    Route::get('/daftar', [AutentikasiController::class, 'tampilDaftar'])->name('daftar');
    Route::post('/daftar', [AutentikasiController::class, 'daftar'])->name('daftar.proses');
});

Route::post('/keluar', [AutentikasiController::class, 'keluar'])->middleware('auth')->name('keluar');

Route::middleware(['auth', 'peran:pembeli'])->prefix('akun')->name('pembeli.')->group(function () {
    Route::get('/', [AkunController::class, 'beranda'])->name('akun');
    Route::get('/profil', [AkunController::class, 'profil'])->name('profil');
    Route::put('/profil', [AkunController::class, 'perbaruiProfil'])->name('profil.perbarui');
    Route::get('/pesanan', [AkunController::class, 'pesanan'])->name('pesanan');
    Route::get('/keranjang', [AkunController::class, 'keranjang'])->name('keranjang');
});

Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/beranda', function () {
        return view('admin.beranda', [
            'jumlahProduk' => Produk::count(),
            'jumlahPenenun' => Pengguna::where('peran', 'penenun')->count(),
            'jumlahProdukAktif' => Produk::where('status', 'aktif')->count(),
            'stokTersedia' => Produk::with('stok')->get()->sum(fn ($produk) => (int) ($produk->stok?->jumlah_stok ?? 0)),
            'stokHabis' => Produk::with('stok')->get()->filter(fn ($produk) => (int) ($produk->stok?->jumlah_stok ?? 0) <= 0)->count(),
        ]);
    })->name('beranda');

    Route::resource('produk', ProdukController::class)->except(['show']);
    Route::resource('penenun', PenenunController::class)->except(['show']);

    Route::view('/target-produksi', 'admin.placeholder', [
        'judul' => 'Target Produksi',
        'deskripsi' => 'Halaman ini akan digunakan untuk membuat target berdasarkan produk dan penenun, lalu memantau progres penyelesaiannya.',
    ])->name('target-produksi');
    Route::view('/pesanan', 'admin.placeholder', [
        'judul' => 'Pesanan',
        'deskripsi' => 'Daftar pesanan pembeli akan tampil di sini agar admin mudah melihat transaksi yang perlu diperhatikan.',
    ])->name('pesanan');
    Route::view('/pembayaran', 'admin.placeholder', [
        'judul' => 'Pembayaran',
        'deskripsi' => 'Status pembayaran Cash dan pembayaran online akan dikelola dari halaman ini.',
    ])->name('pembayaran');
    Route::view('/laporan', 'admin.placeholder', [
        'judul' => 'Laporan',
        'deskripsi' => 'Ringkasan produksi, stok, penjualan, dan pembayaran akan tersedia di halaman ini.',
    ])->name('laporan');
});

Route::middleware(['auth', 'peran:penenun'])
    ->get('/penenun', fn () => view('penenun.beranda', ['pengguna' => auth()->user()->load('profilPenenun')]))
    ->name('penenun.beranda');
