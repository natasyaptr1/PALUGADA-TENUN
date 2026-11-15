<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stok;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class LaporanStokController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jenis_mutasi' => 'nullable|in:masuk,keluar,penyesuaian',
        ]);

        // Ringkasan stok saat ini
        $totalJenisProduk = Stok::whereHas('produk')->count();

        $totalStok = Stok::whereHas('produk')
            ->sum('jumlah_stok');

        $stokHabis = Stok::whereHas('produk')
            ->where('jumlah_stok', '<=', 0)
            ->count();

        // Daftar stok per produk
        $daftarStok = Stok::with('produk')
            ->whereHas('produk')
            ->orderBy('produk_id')
            ->paginate(10, ['*'], 'stok_page')
            ->withQueryString();

        // Riwayat mutasi stok
        $queryMutasi = MutasiStok::with('stok.produk');

        if ($request->filled('tanggal_mulai')) {
            $queryMutasi->whereDate(
                'tanggal_mutasi',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_akhir')) {
            $queryMutasi->whereDate(
                'tanggal_mutasi',
                '<=',
                $request->tanggal_akhir
            );
        }

        if ($request->filled('jenis_mutasi')) {
            $queryMutasi->where(
                'jenis_mutasi',
                $request->jenis_mutasi
            );
        }

        // Ringkasan mutasi sesuai filter
        $totalMasuk = (clone $queryMutasi)
            ->where('jenis_mutasi', 'masuk')
            ->sum('jumlah');

        $totalKeluar = (clone $queryMutasi)
            ->where('jenis_mutasi', 'keluar')
            ->sum('jumlah');

        $riwayatMutasi = $queryMutasi
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'mutasi_page')
            ->withQueryString();

        return view('admin.laporan.stok', compact(
            'totalJenisProduk',
            'totalStok',
            'stokHabis',
            'daftarStok',
            'totalMasuk',
            'totalKeluar',
            'riwayatMutasi'
        ));
    }
}