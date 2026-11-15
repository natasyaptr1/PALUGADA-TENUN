<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TargetProduksi;
use Illuminate\Http\Request;

class LaporanProduksiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $query = TargetProduksi::query()
            ->with(['produk', 'penenun'])
            ->withSum('hasilProduksi as total_selesai', 'jumlah_selesai');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal_mulai',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate(
                'tanggal_mulai',
                '<=',
                $request->tanggal_akhir
            );
        }

        // Ringkasan dihitung dari seluruh data yang difilter.
        $ringkasan = (clone $query)->get();

        $totalTarget = $ringkasan->sum('jumlah_target');

        $totalSelesai = $ringkasan->sum(
            fn ($item) => $item->total_selesai ?? 0
        );

        $persentase = $totalTarget > 0
            ? round(($totalSelesai / $totalTarget) * 100, 1)
            : 0;

        // Tabel ditampilkan per halaman.
        $laporan = $query
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.laporan.produksi', compact(
            'laporan',
            'totalTarget',
            'totalSelesai',
            'persentase'
        ));
    }
}
