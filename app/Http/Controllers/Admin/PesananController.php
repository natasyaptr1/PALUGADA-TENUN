<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\AlurStatusPesanan;
use App\Support\LabelPesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * Menampilkan detail informasi pesanan pembeli.
     */
    public function show(Pesanan $pesanan): View
    {
        $pesanan->load([
            'pembeli',
            'detail.produk' => function ($query) {
                $query->withTrashed()->with('produkFoto');
            },
            'pembayaran' => function ($query) {
                $query->orderByDesc('id')->with('pengonfirmasi');
            },
        ]);

        return view('admin.pesanan.show', compact('pesanan'));
    }

    /**
     * Memperbarui status operasional pesanan (proses, selesai, batalkan).
     */
    public function ubahStatus(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $request->validate([
            'aksi' => ['required', 'string', 'in:' . implode(',', AlurStatusPesanan::AKSI_VALID)],
        ], [
            'aksi.required' => 'Aksi status wajib dipilih.',
            'aksi.in' => 'Aksi yang dipilih tidak valid.',
        ]);

        $aksi = (string) $request->input('aksi');
        $berhasil = false;
        $pesanSukses = '';

        DB::transaction(function () use ($pesanan, $aksi, &$berhasil, &$pesanSukses) {
            /** @var Pesanan|null $pesananTerkunci */
            $pesananTerkunci = Pesanan::lockForUpdate()->find($pesanan->id);

            if (!$pesananTerkunci || !AlurStatusPesanan::boleh($aksi, $pesananTerkunci->status_pesanan)) {
                return;
            }

            $statusLama = $pesananTerkunci->status_pesanan;
            $statusBaru = AlurStatusPesanan::statusTujuan($aksi);

            $pesananTerkunci->status_pesanan = $statusBaru;
            $pesananTerkunci->save();

            if ($aksi === AlurStatusPesanan::AKSI_BATALKAN) {
                $pesananTerkunci->pembayaran()
                    ->where('status_pembayaran', 'menunggu')
                    ->update(['status_pembayaran' => 'gagal']);
            }

            Log::info('Status operasional pesanan diubah', [
                'pesanan_id' => $pesananTerkunci->id,
                'status_lama' => $statusLama,
                'status_baru' => $statusBaru,
                'admin_id' => auth()->id(),
            ]);

            $berhasil = true;
            $pesanSukses = AlurStatusPesanan::pesanSukses($aksi);
        });

        if (!$berhasil) {
            return redirect()
                ->route('admin.pesanan.show', $pesanan)
                ->with('error', 'Status pesanan sudah berubah atau aksi tidak diizinkan. Muat ulang halaman.');
        }

        return redirect()
            ->route('admin.pesanan.show', $pesanan)
            ->with('success', $pesanSukses);
    }
}
