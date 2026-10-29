<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\LabelPesanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesananController extends Controller
{
    /**
     * Menampilkan daftar pesanan admin dengan pencarian, filter, kartu ringkasan, dan pagination.
     */
    public function index(Request $request): View
    {
        // 4 kartu ringkasan: dihitung dari SEMUA pesanan dengan satu query agregat (tidak terpengaruh filter)
        $ringkasan = Pesanan::toBase()
            ->selectRaw("
                COUNT(CASE WHEN status_pesanan = 'menunggu_pembayaran' THEN 1 END) as menunggu_pembayaran,
                COUNT(CASE WHEN status_pesanan = 'dibayar' THEN 1 END) as dibayar,
                COUNT(CASE WHEN status_pesanan = 'diproses' THEN 1 END) as diproses,
                COUNT(CASE WHEN status_pesanan = 'selesai' THEN 1 END) as selesai
            ")
            ->first();

        $cari = trim((string) $request->input('cari'));
        $statusInput = (string) $request->input('status');
        $statusValid = LabelPesanan::normalisasiStatusFilter($statusInput);

        $daftarPesanan = Pesanan::query()
            ->with([
                'pembeli',
                'pembayaran' => function ($q) {
                    $q->orderByDesc('id');
                },
            ])
            ->withCount('detail')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($sub) use ($cari) {
                    $sub->where('kode_pesanan', 'like', "%{$cari}%")
                        ->orWhere('nama_penerima', 'like', "%{$cari}%")
                        ->orWhereHas('pembeli', function ($pembeliQuery) use ($cari) {
                            $pembeliQuery->where('nama', 'like', "%{$cari}%");
                        });
                });
            })
            ->when($statusValid !== null, function ($query) use ($statusValid) {
                $query->where('status_pesanan', $statusValid);
            })
            ->orderByDesc('tanggal_pesanan')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pesanan.index', compact('daftarPesanan', 'ringkasan', 'cari', 'statusInput'));
    }
}
