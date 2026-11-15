<?php

use App\Http\Controllers\Admin\BerandaController;
use App\Http\Controllers\Admin\JenisProdukController;
use App\Http\Controllers\Admin\TargetProduksiController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\PenenunController;
use App\Http\Controllers\Admin\ProdukController;

use App\Http\Controllers\AutentikasiController;

use App\Http\Controllers\Pembeli\AkunController;
use App\Http\Controllers\Pembeli\KeranjangController;
use App\Http\Controllers\Pembeli\CheckoutController;
use App\Http\Controllers\Pembeli\PembayaranController;
use App\Http\Controllers\Pembeli\PembatalanPesananController;
use App\Http\Controllers\Pembeli\OngkirController;

use App\Models\Produk;

use Illuminate\Support\Facades\Route;


// ==========================================
// LANDING PAGE
// ==========================================

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

    return view(
        'pembeli.landing',
        compact('produkPilihan')
    );

})->name('landing');


// ==========================================
// KATALOG PRODUK
// ==========================================

Route::get('/katalog', function () {

    $cari = trim((string) request('cari', ''));

    $jenis = trim((string) request('jenis', ''));

    $hargaMin = request()->filled('harga_min')
        ? max(0, (float) request('harga_min'))
        : null;

    $hargaMax = request()->filled('harga_max')
        ? max(0, (float) request('harga_max'))
        : null;

    $stokTersedia = request('stok_tersedia') === '1';

    $daftarProduk = Produk::query()
        ->with('stok')
        ->where('status', 'aktif')

        ->when($cari, function ($query) use ($cari) {

            $query->where(function ($sub) use ($cari) {

                $sub->where(
                    'nama_produk',
                    'like',
                    "%{$cari}%"
                )
                ->orWhere(
                    'kode_produk',
                    'like',
                    "%{$cari}%"
                )
                ->orWhere(
                    'jenis',
                    'like',
                    "%{$cari}%"
                )
                ->orWhere(
                    'motif',
                    'like',
                    "%{$cari}%"
                )
                ->orWhere(
                    'warna',
                    'like',
                    "%{$cari}%"
                );

            });

        })

        ->when($jenis, fn ($query) =>
            $query->where('jenis', $jenis)
        )

        ->when($hargaMin !== null, fn ($query) =>
            $query->where('harga', '>=', $hargaMin)
        )

        ->when($hargaMax !== null, fn ($query) =>
            $query->where('harga', '<=', $hargaMax)
        )

        ->when($stokTersedia, fn ($query) =>
            $query->whereHas('stok', function ($stok) {
                $stok->where('jumlah_stok', '>', 0);
            })
        )

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

    return view('pembeli.katalog', compact(
        'daftarProduk',
        'jenisProduk',
        'cari',
        'jenis',
        'hargaMin',
        'hargaMax',
        'stokTersedia'
    ));

})->name('pembeli.katalog');


// ==========================================
// AUTENTIKASI
// ==========================================

Route::middleware('guest')->group(function () {

    // LOGIN

    Route::get(
        '/masuk',
        [AutentikasiController::class, 'tampilMasuk']
    )->name('masuk');

    Route::post(
        '/masuk',
        [AutentikasiController::class, 'masuk']
    )->name('masuk.proses');

    Route::get(
        '/login',
        fn () => redirect('/masuk')
    )->name('login');


    // REGISTRASI

    Route::get(
        '/daftar',
        [AutentikasiController::class, 'tampilDaftar']
    )->name('daftar');

    Route::post(
        '/daftar',
        [AutentikasiController::class, 'daftar']
    )->name('daftar.proses');

});


// ==========================================
// LOGOUT
// ==========================================

Route::post(
    '/keluar',
    [AutentikasiController::class, 'keluar']
)
->middleware('auth')
->name('keluar');


// ==========================================
// AREA PEMBELI
// ==========================================

Route::middleware(['auth', 'peran:pembeli'])
    ->prefix('akun')
    ->name('pembeli.')
    ->group(function () {

        // AKUN

        Route::get(
            '/',
            [AkunController::class, 'beranda']
        )->name('akun');


        // PROFIL

        Route::get(
            '/profil',
            [AkunController::class, 'profil']
        )->name('profil');

        Route::put(
            '/profil',
            [AkunController::class, 'perbaruiProfil']
        )->name('profil.perbarui');


        // RIWAYAT PESANAN

        Route::get(
            '/pesanan',
            [AkunController::class, 'pesanan']
        )->name('pesanan');


        // LANJUTKAN PEMBAYARAN MIDTRANS
        // Route GET Sprint 3 dipertahankan.

        Route::get(
            '/pesanan/{pesanan}/bayar',
            [PembayaranController::class, 'lanjutkan']
        )->name('pesanan.bayar');


        // CEK STATUS PEMBAYARAN
        // Fitur tambahan dari branch Panjol.

        Route::post(
            '/pesanan/{pesanan}/cek-status',
            [AkunController::class, 'cekStatus']
        )->name('pesanan.cek-status');


        // PEMBATALAN PESANAN

        Route::patch(
            '/pesanan/{pesanan}/batalkan',
            [PembatalanPesananController::class, 'batalkan']
        )->name('pesanan.batalkan');


        // ==================================
        // KERANJANG - FIRJA
        // ==================================

        Route::get(
            '/keranjang',
            [KeranjangController::class, 'index']
        )->name('keranjang');

        Route::post(
            '/keranjang',
            [KeranjangController::class, 'tambah']
        )->name('keranjang.tambah');

        Route::put(
            '/keranjang/{detail}',
            [KeranjangController::class, 'ubah']
        )->name('keranjang.ubah');

        Route::delete(
            '/keranjang/{detail}',
            [KeranjangController::class, 'hapus']
        )->name('keranjang.hapus');


        // MEMILIH PRODUK UNTUK CHECKOUT

        Route::post(
            '/keranjang/pilih-checkout',
            [KeranjangController::class, 'pilihCheckout']
        )->name('keranjang.pilih-checkout');


        // ==================================
        // CHECKOUT
        // ==================================

        Route::get(
            '/checkout',
            [CheckoutController::class, 'index']
        )->name('checkout');

        Route::post(
            '/checkout',
            [CheckoutController::class, 'simpan']
        )->name('checkout.simpan');

    });


// ==========================================
// API ONGKIR UNTUK PEMBELI - PANJOL
// ==========================================

Route::middleware(['auth', 'peran:pembeli'])
    ->prefix('akun/ongkir')
    ->name('pembeli.ongkir.')
    ->group(function () {

        // CARI TUJUAN PENGIRIMAN

        Route::get(
            '/tujuan',
            [OngkirController::class, 'tujuan']
        )
        ->middleware('throttle:30,1')
        ->name('tujuan');


        // AMBIL OPSI KURIR DAN ONGKIR

        Route::get(
            '/opsi',
            [OngkirController::class, 'opsi']
        )
        ->middleware('throttle:20,1')
        ->name('opsi');

    });


// ==========================================
// AREA ADMIN
// ==========================================

Route::middleware(['auth', 'peran:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // DASHBOARD ADMIN

        Route::get(
            '/beranda',
            BerandaController::class
        )->name('beranda');


        // ==================================
        // PRODUK
        // ==================================

        Route::resource(
            'produk',
            ProdukController::class
        )->except(['show']);

        Route::delete(
            'produk/{produk}/foto/{foto}',
            [ProdukController::class, 'destroyFoto']
        )->name('produk.foto.destroy');


        // ==================================
        // JENIS PRODUK
        // ==================================

        Route::resource(
            'jenis-produk',
            JenisProdukController::class
        )
        ->parameters([
            'jenis-produk' => 'jenisProduk'
        ])
        ->except(['show']);

        Route::resource(
            'jenis_produk',
            JenisProdukController::class
        )
        ->parameters([
            'jenis_produk' => 'jenisProduk'
        ])
        ->names('jenis_produk')
        ->except(['show']);


        // ==================================
        // PENENUN
        // ==================================

        Route::resource(
            'penenun',
            PenenunController::class
        )->except(['show']);


        // ==================================
        // STOK
        // ==================================

        Route::get(
            '/stok',
            [StokController::class, 'index']
        )->name('stok');


        // ==================================
        // TARGET PRODUKSI
        // ==================================

        Route::get(
            '/target-produksi/create',
            [TargetProduksiController::class, 'create']
        )->name('target-produksi.create');

        Route::get(
            '/target-produksi',
            [TargetProduksiController::class, 'index']
        )->name('target-produksi');

        Route::post(
            '/target-produksi',
            [TargetProduksiController::class, 'store']
        )->name('target-produksi.store');

        Route::get(
            '/target-produksi/{target}',
            [TargetProduksiController::class, 'show']
        )->name('target-produksi.show');

        Route::put(
            '/target-produksi/{target}',
            [TargetProduksiController::class, 'update']
        )->name('target-produksi.update');

        Route::delete(
            '/target-produksi/{target}',
            [TargetProduksiController::class, 'destroy']
        )->name('target-produksi.destroy');

        Route::patch(
            '/target-produksi/{target}/batal',
            [TargetProduksiController::class, 'cancel']
        )->name('target-produksi.cancel');


        // ==================================
        // PESANAN ADMIN - PANJOL
        // ==================================

        Route::resource(
            'pesanan',
            PesananController::class
        )->only([
            'index',
            'show'
        ]);


        // UBAH STATUS PESANAN

        Route::patch(
            '/pesanan/{pesanan}/status',
            [PesananController::class, 'ubahStatus']
        )->name('pesanan.status');


        // KONFIRMASI PEMBAYARAN ADMIN

        Route::post(
            '/pesanan/{pesanan}/konfirmasi-pembayaran',
            [PesananController::class, 'konfirmasiPembayaran']
        )->name('pesanan.konfirmasi-pembayaran');


        // ==================================
        // HALAMAN PEMBAYARAN
        // ==================================

        Route::view(
            '/pembayaran',
            'admin.placeholder',
            [
                'judul' => 'Pembayaran',
                'deskripsi' => 'Transaksi pembayaran pembeli akan tampil di sini.',
            ]
        )->name('pembayaran');


        // ==================================
        // HALAMAN LAPORAN
        // ==================================

        Route::view(
            '/laporan',
            'admin.placeholder',
            [
                'judul' => 'Laporan',
                'deskripsi' => 'Ringkasan produksi, stok, penjualan, dan pembayaran akan tampil di sini ketika datanya tersedia.',
            ]
        )->name('laporan');

    });


// ==========================================
// AREA PENENUN
// ==========================================

Route::middleware(['auth', 'peran:penenun'])
    ->get(
        '/penenun',
        fn () => view('penenun.beranda', [
            'pengguna' => auth()->user()->load('profilPenenun'),
        ])
    )
    ->name('penenun.beranda');
