<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanProduksiExport;
use App\Http\Controllers\Controller;
use App\Models\TargetProduksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanProduksiController extends Controller
{
    private function query(Request $request)
    {
        return TargetProduksi::query()
            ->with(['produk', 'penenun'])
            ->withSum('hasilProduksi as total_selesai', 'jumlah_selesai')
            ->withMax('hasilProduksi as tanggal_selesai_aktual', 'tanggal_input')
            ->when($request->filled('q'), function ($query) use ($request) {
                $cari = trim((string) $request->query('q'));

                $query->where(function ($sub) use ($cari) {
                    $sub->whereHas('produk', function ($produk) use ($cari) {
                        $produk->where('nama_produk', 'like', "%{$cari}%")
                            ->orWhere('kode_produk', 'like', "%{$cari}%");
                    })->orWhereHas('penenun', function ($penenun) use ($cari) {
                        $penenun->where('nama', 'like', "%{$cari}%");
                    });
                });
            })
            ->when($request->filled('tanggal_mulai'), fn ($query) =>
                $query->whereDate('tanggal_mulai', '>=', $request->query('tanggal_mulai'))
            )
            ->when($request->filled('tanggal_akhir'), fn ($query) =>
                $query->whereDate('tanggal_mulai', '<=', $request->query('tanggal_akhir'))
            )
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->query('status'))
            );
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['nullable', 'in:berjalan,selesai,dibatalkan'],
        ]);
    }

    public function index(Request $request)
    {
        $this->validateFilters($request);

        $laporan = $this->query($request)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.laporan.produksi', compact('laporan'));
    }

    public function exportExcel(Request $request)
    {
        $this->validateFilters($request);

        $data = $this->query($request)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        return Excel::download(
            new LaporanProduksiExport($data),
            'laporan-produksi-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->validateFilters($request);

        $data = $this->query($request)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        $headers = [
            'Produk', 'Penenun', 'Mulai', 'Target Selesai',
            'Selesai Aktual', 'Target Unit', 'Hasil Unit', 'Sisa Unit', 'Status',
        ];

        $rows = $data->map(fn ($item) => [
            $item->produk?->nama_produk ?? '-',
            $item->penenun?->nama ?? '-',
            $item->tanggal_mulai?->format('d/m/Y') ?? '-',
            $item->tanggal_deadline?->format('d/m/Y') ?? '-',
            $item->status === 'selesai' && $item->tanggal_selesai_aktual
                ? \Illuminate\Support\Carbon::parse($item->tanggal_selesai_aktual)->format('d/m/Y')
                : '-',
            (int) $item->jumlah_target,
            (int) ($item->total_selesai ?? 0),
            max(0, (int) $item->jumlah_target - (int) ($item->total_selesai ?? 0)),
            ucfirst($item->status),
        ])->all();

        $pdf = Pdf::loadView('admin.laporan.pdf', [
            'judul' => 'Laporan Produksi',
            'deskripsi' => 'Rekap target dan realisasi produksi tenun.',
            'headers' => $headers,
            'rows' => $rows,
            'filter' => $request->only(['q', 'tanggal_mulai', 'tanggal_akhir', 'status']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-produksi-' . now()->format('Ymd-His') . '.pdf');
    }
}
