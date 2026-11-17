<?php

use App\Http\Controllers\Admin\BerandaController;
use App\Http\Controllers\Admin\JenisProdukController;
use App\Http\Controllers\Admin\TargetProduksiController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\PenenunController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\LaporanProduksiController;
use App\Http\Controllers\Admin\LaporanStokController;
use App\Http\Controllers\Admin\LaporanPenjualanController;

use App\Http\Controllers\AutentikasiController;

use App\Http\Controllers\Pembeli\AkunController;
use App\Http\Controllers\Pembeli\KeranjangController;
use App\Http\Controllers\Pembeli\CheckoutController;
use App\Http\Controllers\Pembeli\PembayaranController;
use App\Http\Controllers\Pembeli\PembatalanPesananController;
use App\Http\Controllers\Pembeli\RekomendasiController;
use App\Http\Controllers\Pembeli\OngkirController;

use App\Services\RekomendasiProdukService;
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

    return view('pembeli.landing', compact('produkPilihan'));
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
                $sub->where('nama_produk', 'like', "%{$cari}%")
                    ->orWhere('kode_produk', 'like', "%{$cari}%")
                    ->orWhere('jenis', 'like', "%{$cari}%")
                    ->orWhere('motif', 'like', "%{$cari}%")
                    ->orWhere('warna', 'like', "%{$cari}%");
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

    $rekomendasiProduk = collect();
    $produkTerlaris = collect();

    if (
        auth()->check() &&
        auth()->user()->peran === 'pembeli'
    ) {
        $service = app(RekomendasiProdukService::class);

        $rekomendasiProduk = $service->rekomendasiUntuk(
            auth()->user(),
            4
        );

        $produkTerlaris = $service->produkTerlaris(4);
    }

    return view('pembeli.katalog', compact(
        'daftarProduk',
        'jenisProduk',
        'cari',
        'jenis',
        'hargaMin',
        'hargaMax',
        'stokTersedia',
        'rekomendasiProduk',
        'produkTerlaris'
    ));
})->name('pembeli.katalog');


// ==========================================
// AUTENTIKASI DAN REGISTRASI
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

    Route::get('/login', fn () => redirect('/masuk'))
        ->name('login');


    // REGISTRASI AKUN
    Route::get(
        '/daftar',
        [AutentikasiController::class, 'tampilDaftar']
    )->name('daftar');

    Route::post(
        '/daftar',
        [AutentikasiController::class, 'daftar']
    )->name('daftar.proses');


    // ==========================================
    // PREFERENSI SETELAH REGISTRASI
    // ==========================================

    // Halaman pemilihan kategori favorit
    Route::get(
        '/daftar/preferensi',
        [AutentikasiController::class, 'tampilPreferensi']
    )->name('daftar.preferensi');

    // Simpan preferensi pengguna
    Route::post(
        '/daftar/preferensi',
        [AutentikasiController::class, 'simpanPreferensi']
    )->name('daftar.preferensi.simpan');

    // Lewati pemilihan preferensi
    Route::post(
        '/daftar/preferensi/lewati',
        [AutentikasiController::class, 'lewatiPreferensi']
    )->name('daftar.preferensi.lewati');

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
// PEMBELI
// ==========================================

Route::middleware(['auth', 'peran:pembeli'])
    ->prefix('akun')
    ->name('pembeli.')
    ->group(function () {

        // BERANDA AKUN
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


        // PESANAN
        Route::get(
            '/pesanan',
            [AkunController::class, 'pesanan']
        )->name('pesanan');

        Route::get(
            '/pesanan/{pesanan}/bayar',
            [PembayaranController::class, 'lanjutkan']
        )->name('pesanan.bayar');

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


        // CHECKOUT
        Route::get(
            '/checkout',
            [CheckoutController::class, 'index']
        )->name('checkout');

        Route::post(
            '/checkout',
            [CheckoutController::class, 'simpan']
        )->name('checkout.simpan');

        Route::post(
            '/keranjang/pilih-checkout',
            [KeranjangController::class, 'pilihCheckout']
        )->name('keranjang.pilih-checkout');


        // REKOMENDASI PRODUK
        Route::get(
            '/rekomendasi',
            [RekomendasiController::class, 'index']
        )->name('rekomendasi');

    });


// ==========================================
// ONGKIR PEMBELI - SPRINT 4 PANJOL
// ==========================================

Route::middleware(['auth', 'peran:pembeli'])
    ->prefix('akun/ongkir')
    ->name('pembeli.ongkir.')
    ->group(function () {

        // Mencari kota atau kecamatan tujuan pengiriman.
        Route::get(
            '/tujuan',
            [OngkirController::class, 'tujuan']
        )
            ->middleware('throttle:30,1')
            ->name('tujuan');

        // Mengambil pilihan layanan kurir dan ongkir.
        Route::get(
            '/opsi',
            [OngkirController::class, 'opsi']
        )
            ->middleware('throttle:20,1')
            ->name('opsi');

    });


// ==========================================
// ADMIN
// ==========================================

Route::middleware(['auth', 'peran:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // BERANDA ADMIN
        Route::get(
            '/beranda',
            BerandaController::class
        )->name('beranda');


        // ==========================================
        // PRODUK
        // ==========================================

        Route::resource('produk', ProdukController::class)
            ->except(['show']);

        Route::delete(
            'produk/{produk}/foto/{foto}',
            [ProdukController::class, 'destroyFoto']
        )->name('produk.foto.destroy');


        // ==========================================
        // JENIS PRODUK
        // ==========================================

        Route::resource(
            'jenis-produk',
            JenisProdukController::class
        )
            ->parameters(['jenis-produk' => 'jenisProduk'])
            ->except(['show']);

        Route::resource(
            'jenis_produk',
            JenisProdukController::class
        )
            ->parameters(['jenis_produk' => 'jenisProduk'])
            ->names('jenis_produk')
            ->except(['show']);


        // ==========================================
        // PENENUN
        // ==========================================

        Route::resource('penenun', PenenunController::class)
            ->except(['show']);


        // ==========================================
        // STOK
        // ==========================================

        Route::get(
            '/stok',
            [StokController::class, 'index']
        )->name('stok');


        // ==========================================
        // TARGET PRODUKSI
        // ==========================================

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


        // ==========================================
        // PESANAN ADMIN - PANJOL
        // ==========================================

        Route::resource(
            'pesanan',
            PesananController::class
        )->only(['index', 'show', 'destroy']);

        Route::patch(
            '/pesanan/{pesanan}/status',
            [PesananController::class, 'ubahStatus']
        )->name('pesanan.status');

        Route::post(
            '/pesanan/{pesanan}/konfirmasi-pembayaran',
            [PesananController::class, 'konfirmasiPembayaran']
        )->name('pesanan.konfirmasi-pembayaran');


        // ==========================================
        // HALAMAN PLACEHOLDER PEMBAYARAN
        // ==========================================

        Route::view('/pembayaran', 'admin.placeholder', [
            'judul' => 'Pembayaran',
            'deskripsi' => 'Transaksi pembayaran pembeli akan tampil di sini.',
        ])->name('pembayaran');


        // ==========================================
        // LAPORAN ADMIN
        // ==========================================

        // HALAMAN UTAMA LAPORAN
        Route::view(
            '/laporan',
            'admin.laporan.index'
        )->name('laporan');


        // ==========================================
        // LAPORAN PRODUKSI
        // ==========================================

        Route::get(
            '/laporan/produksi',
            [LaporanProduksiController::class, 'index']
        )->name('laporan.produksi');

        Route::get(
            '/laporan/produksi/export/pdf',
            [LaporanProduksiController::class, 'exportPdf']
        )->name('laporan.produksi.pdf');

        Route::get(
            '/laporan/produksi/export/excel',
            [LaporanProduksiController::class, 'exportExcel']
        )->name('laporan.produksi.excel');


        // ==========================================
        // LAPORAN STOK
        // ==========================================

        Route::get(
            '/laporan/stok',
            [LaporanStokController::class, 'index']
        )->name('laporan.stok');

        Route::get(
            '/laporan/stok/export/pdf',
            [LaporanStokController::class, 'exportPdf']
        )->name('laporan.stok.pdf');

        Route::get(
            '/laporan/stok/export/excel',
            [LaporanStokController::class, 'exportExcel']
        )->name('laporan.stok.excel');


        // ==========================================
        // LAPORAN PENJUALAN
        // ==========================================

        Route::get(
            '/laporan/penjualan',
            [LaporanPenjualanController::class, 'index']
        )->name('laporan.penjualan');

        Route::get(
            '/laporan/penjualan/export/pdf',
            [LaporanPenjualanController::class, 'exportPdf']
        )->name('laporan.penjualan.pdf');

        Route::get(
            '/laporan/penjualan/export/excel',
            [LaporanPenjualanController::class, 'exportExcel']
        )->name('laporan.penjualan.excel');

    });


// ==========================================
// PENENUN
// ==========================================

Route::middleware(['auth', 'peran:penenun'])
    ->get(
        '/penenun',
        fn () => view('penenun.beranda', [
            'pengguna' => auth()->user()->load('profilPenenun'),
        ])
    )
    ->name('penenun.beranda');
