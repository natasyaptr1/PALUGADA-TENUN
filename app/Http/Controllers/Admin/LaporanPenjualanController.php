<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPenjualanExport;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class LaporanPenjualanController extends Controller
{
    private function query(Request $request)
    {
        return Pesanan::query()
            ->with([
                'detail.produk',
                'pembayaran' => fn ($query) => $query->latest('id'),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $cari = trim((string) $request->query('q'));

                $query->where(function ($sub) use ($cari) {
                    $sub->where('kode_pesanan', 'like', "%{$cari}%")
                        ->orWhere('nama_penerima', 'like', "%{$cari}%")
                        ->orWhereHas('detail.produk', function ($produk) use ($cari) {
                            $produk->where('nama_produk', 'like', "%{$cari}%")
                                ->orWhere('kode_produk', 'like', "%{$cari}%");
                        });
                });
            })
            ->when($request->filled('tanggal_mulai'), fn ($query) =>
                $query->whereDate('tanggal_pesanan', '>=', $request->query('tanggal_mulai'))
            )
            ->when($request->filled('tanggal_akhir'), fn ($query) =>
                $query->whereDate('tanggal_pesanan', '<=', $request->query('tanggal_akhir'))
            )
            ->when($request->filled('status_pesanan'), fn ($query) =>
                $query->where('status_pesanan', $request->query('status_pesanan'))
            )
            ->when($request->filled('metode'), fn ($query) =>
                $query->whereHas('pembayaran', fn ($pembayaran) =>
                    $pembayaran->where('metode', $request->query('metode'))
                )
            )
            ->when($request->filled('status_pembayaran'), fn ($query) =>
                $query->whereHas('pembayaran', fn ($pembayaran) =>
                    $pembayaran->where('status_pembayaran', $request->query('status_pembayaran'))
                )
            );
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_pesanan' => ['nullable', 'in:menunggu_pembayaran,dibayar,diproses,selesai,batal'],
            'metode' => ['nullable', 'in:cash,midtrans'],
            'status_pembayaran' => ['nullable', 'in:menunggu,berhasil,gagal,kedaluwarsa'],
        ]);
    }

    private function summary($query): array
    {
        $pesananBerhasil = (clone $query)->whereHas('pembayaran', fn ($pembayaran) =>
            $pembayaran->where('status_pembayaran', 'berhasil')
        );

        return [
            'jumlahPesanan' => (clone $query)->count(),
            'jumlahTransaksiBerhasil' => (clone $pesananBerhasil)->count(),
            'totalOmzet' => (clone $pesananBerhasil)->sum(
                Schema::hasColumn('pesanan', 'subtotal') ? 'subtotal' : 'total'
            ),
        ];
    }

    public function index(Request $request)
    {
        $this->validateFilters($request);
        $query = $this->query($request);
        $ringkasan = $this->summary($query);

        $laporan = $query
            ->orderByDesc('tanggal_pesanan')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        return view('admin.laporan.penjualan', compact('laporan', 'ringkasan'));
    }

    public function exportExcel(Request $request)
    {
        $this->validateFilters($request);
        $data = $this->query($request)
            ->orderByDesc('tanggal_pesanan')
            ->orderByDesc('id')
            ->get();

        return Excel::download(
            new LaporanPenjualanExport($data),
            'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->validateFilters($request);
        $data = $this->query($request)
            ->orderByDesc('tanggal_pesanan')
            ->orderByDesc('id')
            ->get();

        $headers = ['Tanggal', 'Kode Pesanan', 'Penerima', 'Jumlah Item', 'Subtotal Produk', 'Ongkir', 'Total Dibayar', 'Metode', 'Status Bayar', 'Status Pesanan'];
        $rows = $data->map(fn ($pesanan) => [
            $pesanan->tanggal_pesanan?->format('d/m/Y H:i') ?? '-',
            $pesanan->kode_pesanan,
            $pesanan->nama_penerima,
            (int) $pesanan->detail->sum('jumlah'),
            'Rp ' . number_format((float) ($pesanan->subtotal ?? $pesanan->total), 0, ',', '.'),
            'Rp ' . number_format((float) ($pesanan->ongkir ?? 0), 0, ',', '.'),
            'Rp ' . number_format((float) $pesanan->total, 0, ',', '.'),
            strtoupper($pesanan->pembayaran->first()?->metode ?? '-'),
            ucfirst($pesanan->pembayaran->first()?->status_pembayaran ?? 'belum ada'),
            ucfirst(str_replace('_', ' ', $pesanan->status_pesanan)),
        ])->all();

        return Pdf::loadView('admin.laporan.pdf', [
            'judul' => 'Laporan Penjualan',
            'deskripsi' => 'Daftar pesanan dan status pembayaran. Omzet hanya menghitung transaksi dengan pembayaran berhasil.',
            'headers' => $headers,
            'rows' => $rows,
            'filter' => $request->only(['q', 'tanggal_mulai', 'tanggal_akhir', 'status_pesanan', 'metode', 'status_pembayaran']),
        ])->setPaper('a4', 'landscape')
            ->download('laporan-penjualan-' . now()->format('Ymd-His') . '.pdf');
    }
}
