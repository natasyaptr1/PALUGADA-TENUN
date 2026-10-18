<?php

use App\Http\Controllers\Admin\BerandaController;
use App\Http\Controllers\Admin\JenisProdukController;
use App\Http\Controllers\Admin\PenenunController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\TargetProduksiController;
use App\Http\Controllers\AutentikasiController;
use App\Http\Controllers\Pembeli\AkunController;
use App\Models\Produk;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->peran) {
            'admin' => redirect()->route('admin.beranda'),
            'penenun' => redirect()->route('penenun.beranda'),
            default => redirect()->route('pembeli.katalog'),
        };
    }

    $produkPilihan = Produk::query()
        ->with('stok')
        ->where('status', 'aktif')
        ->latest()
        ->take(4)
        ->get();

    return view('pembeli.landing', compact('produkPilihan'));
})->name('landing');

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

    return view('pembeli.katalog', compact('daftarProduk', 'jenisProduk', 'cari', 'jenis'));
})->name('pembeli.katalog');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AutentikasiController::class, 'tampilMasuk'])->name('masuk');
    Route::post('/masuk', [AutentikasiController::class, 'masuk'])->name('masuk.proses');

    Route::get('/login', function () {
        return redirect('/masuk');
    })->name('login');

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
    Route::get('/beranda', BerandaController::class)->name('beranda');

    Route::resource('produk', ProdukController::class)->except(['show']);
    Route::delete('produk/{produk}/foto/{foto}', [ProdukController::class, 'destroyFoto'])->name('produk.foto.destroy');
    Route::resource('jenis-produk', JenisProdukController::class)->parameters(['jenis-produk' => 'jenisProduk'])->except(['show']);
    Route::resource('jenis_produk', JenisProdukController::class)->parameters(['jenis_produk' => 'jenisProduk'])->names('jenis_produk')->except(['show']);
    Route::resource('penenun', PenenunController::class)->except(['show']);

    Route::view('/stok', 'admin.placeholder', [
        'judul' => 'Stok',
        'deskripsi' => 'Jumlah barang yang siap dijual akan tampil di sini setelah alur stok mulai digunakan.',
    ])->name('stok');
    Route::resource('target-produksi', TargetProduksiController::class)
        ->parameters(['target-produksi' => 'targetProduksi'])
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('target-produksi/{targetProduksi}/batalkan', [TargetProduksiController::class, 'batalkan'])
        ->name('target-produksi.batalkan');
    Route::view('/pesanan', 'admin.placeholder', [
        'judul' => 'Pesanan',
        'deskripsi' => 'Pesanan pembeli akan tampil di sini setelah fitur pemesanan mulai digunakan.',
    ])->name('pesanan');
    Route::view('/pembayaran', 'admin.placeholder', [
        'judul' => 'Pembayaran',
        'deskripsi' => 'Transaksi pembayaran pembeli akan tampil di sini.',
    ])->name('pembayaran');
    Route::view('/laporan', 'admin.placeholder', [
        'judul' => 'Laporan',
        'deskripsi' => 'Ringkasan produksi, stok, penjualan, dan pembayaran akan tampil di sini ketika datanya tersedia.',
    ])->name('laporan');
});

Route::middleware(['auth', 'peran:penenun'])
    ->get('/penenun', fn () => view('penenun.beranda', ['pengguna' => auth()->user()->load('profilPenenun')]))
    ->name('penenun.beranda');
