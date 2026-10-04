<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\TargetProduksi;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        $jumlahPesanan = Pesanan::count();

        $pemasukanBulanIni = (float) Pembayaran::query()
            ->where('status_pembayaran', 'berhasil')
            ->whereYear('tanggal_bayar', now()->year)
            ->whereMonth('tanggal_bayar', now()->month)
            ->sum('jumlah_bayar');

        $pemasukanTahunIni = (float) Pembayaran::query()
            ->where('status_pembayaran', 'berhasil')
            ->whereYear('tanggal_bayar', now()->year)
            ->sum('jumlah_bayar');

        $targetBelumSelesai = TargetProduksi::where('status', 'berjalan')->count();

        $stokMenipis = Produk::query()
            ->where('status', 'aktif')
            ->where(function ($query) {
                $query->whereDoesntHave('stok')
                    ->orWhereHas('stok', fn ($stok) => $stok->where('jumlah_stok', '<=', 3));
            })
            ->count();

        $jumlahPenenunAktif = Pengguna::where('peran', 'penenun')
            ->where('status', 'aktif')
            ->count();

        $pesananTerbaru = Pesanan::query()
            ->latest('tanggal_pesanan')
            ->take(5)
            ->get();

        return view('admin.beranda', compact(
            'jumlahPesanan',
            'pemasukanBulanIni',
            'pemasukanTahunIni',
            'targetBelumSelesai',
            'stokMenipis',
            'jumlahPenenunAktif',
            'pesananTerbaru',
        ));
    }
}
