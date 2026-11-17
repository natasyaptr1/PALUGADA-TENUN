<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanStokExport;
use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanStokController extends Controller
{
    private function query(Request $request)
    {
        return MutasiStok::query()
            ->with([
                'stok.produk',
                'hasilProduksi.targetProduksi',
                'detailPesanan.pesanan',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $cari = trim((string) $request->query('q'));

                $query->where(function ($sub) use ($cari) {
                    $sub->where('catatan', 'like', "%{$cari}%")
                        ->orWhereHas('stok.produk', function ($produk) use ($cari) {
                            $produk->where('nama_produk', 'like', "%{$cari}%")
                                ->orWhere('kode_produk', 'like', "%{$cari}%");
                        })
                        ->orWhereHas('detailPesanan.pesanan', function ($pesanan) use ($cari) {
                            $pesanan->where('kode_pesanan', 'like', "%{$cari}%");
                        });
                });
            })
            ->when($request->filled('tanggal_mulai'), fn ($query) =>
                $query->whereDate('tanggal_mutasi', '>=', $request->query('tanggal_mulai'))
            )
            ->when($request->filled('tanggal_akhir'), fn ($query) =>
                $query->whereDate('tanggal_mutasi', '<=', $request->query('tanggal_akhir'))
            )
            ->when($request->filled('jenis_mutasi'), fn ($query) =>
                $query->where('jenis_mutasi', $request->query('jenis_mutasi'))
            )
            ->when($request->filled('sumber'), fn ($query) =>
                $query->where('sumber', $request->query('sumber'))
            );
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'jenis_mutasi' => ['nullable', 'in:masuk,keluar,penyesuaian'],
            'sumber' => ['nullable', 'in:produksi,penjualan,non_penjualan'],
        ]);
    }

    public function index(Request $request)
    {
        $this->validateFilters($request);

        $query = $this->query($request);
        $totalMasuk = (clone $query)->where('jenis_mutasi', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('jenis_mutasi', 'keluar')->sum('jumlah');
        $totalPenyesuaian = (clone $query)->where('jenis_mutasi', 'penyesuaian')->sum('jumlah');

        $riwayatMutasi = $query
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.laporan.stok', compact(
            'riwayatMutasi',
            'totalMasuk',
            'totalKeluar',
            'totalPenyesuaian'
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->validateFilters($request);

        $data = $this->query($request)
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->get();

        return Excel::download(
            new LaporanStokExport($data),
            'laporan-mutasi-stok-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->validateFilters($request);

        $data = $this->query($request)
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->get();

        $headers = ['Tanggal', 'Kode Produk', 'Produk', 'Jenis Mutasi', 'Jumlah', 'Sumber', 'Referensi', 'Catatan'];
        $rows = $data->map(function ($item) {
            $referensi = $item->detailPesanan?->pesanan?->kode_pesanan
                ?? ($item->hasilProduksi?->targetProduksi ? 'Target #' . $item->hasilProduksi->targetProduksi->id : '-');

            return [
                $item->tanggal_mutasi?->format('d/m/Y H:i') ?? '-',
                $item->stok?->produk?->kode_produk ?? '-',
                $item->stok?->produk?->nama_produk ?? '-',
                ucfirst($item->jenis_mutasi),
                (int) $item->jumlah,
                ucfirst(str_replace('_', ' ', $item->sumber)),
                $referensi,
                $item->catatan ?: '-',
            ];
        })->all();

        return Pdf::loadView('admin.laporan.pdf', [
            'judul' => 'Laporan Mutasi Stok',
            'deskripsi' => 'Riwayat perubahan stok berdasarkan transaksi dan produksi.',
            'headers' => $headers,
            'rows' => $rows,
            'filter' => $request->only(['q', 'tanggal_mulai', 'tanggal_akhir', 'jenis_mutasi', 'sumber']),
        ])->setPaper('a4', 'landscape')
            ->download('laporan-mutasi-stok-' . now()->format('Ymd-His') . '.pdf');
    }
}
