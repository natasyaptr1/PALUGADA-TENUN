<?php

namespace App\Http\Controllers\Admin;

use App\Events\PembayaranBerhasil;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Support\AlurStatusPesanan;
use App\Support\LabelPesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PesananController extends Controller
{
    /**
     * Menampilkan daftar pesanan Admin dengan pencarian,
     * filter status, dan pagination tanpa kartu statistik.
     */
    public function index(Request $request): View
    {
        $cari = trim((string) $request->input('cari'));
        $statusInput = (string) $request->input('status');
        $statusValid = LabelPesanan::normalisasiStatusFilter($statusInput);

        $daftarPesanan = Pesanan::query()
            ->whereNull('disembunyikan_admin_pada')
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
            ->paginate(5)
            ->withQueryString();

        return view('admin.pesanan.index', compact(
            'daftarPesanan',
            'cari',
            'statusInput'
        ));
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
     * Memperbarui status operasional pesanan.
     * Admin hanya boleh memproses dan menyelesaikan pesanan.
     */
    public function ubahStatus(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $request->validate([
            'aksi' => [
                'required',
                'string',
                Rule::in([
                    AlurStatusPesanan::AKSI_PROSES,
                    AlurStatusPesanan::AKSI_SELESAI,
                ]),
            ],
        ], [
            'aksi.required' => 'Aksi status wajib dipilih.',
            'aksi.in' => 'Aksi yang dipilih tidak valid.',
        ]);

        $aksi = (string) $request->input('aksi');

        $berhasil = false;
        $pesanSukses = '';

        DB::transaction(function () use (
            $pesanan,
            $aksi,
            &$berhasil,
            &$pesanSukses
        ) {
            /** @var Pesanan|null $pesananTerkunci */
            $pesananTerkunci = Pesanan::lockForUpdate()
                ->find($pesanan->id);

            if (
                !$pesananTerkunci
                || !AlurStatusPesanan::boleh(
                    $aksi,
                    $pesananTerkunci->status_pesanan
                )
            ) {
                return;
            }

            $statusLama = $pesananTerkunci->status_pesanan;
            $statusBaru = AlurStatusPesanan::statusTujuan($aksi);

            $pesananTerkunci->status_pesanan = $statusBaru;
            $pesananTerkunci->save();

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
                ->with(
                    'error',
                    'Status pesanan sudah berubah atau aksi tidak diizinkan. Muat ulang halaman.'
                );
        }

        return redirect()
            ->route('admin.pesanan.show', $pesanan)
            ->with('success', $pesanSukses);
    }

    /**
     * Mengonfirmasi pembayaran Cash pesanan oleh Admin.
     */
    public function konfirmasiPembayaran(Pesanan $pesanan): RedirectResponse
    {
        $pesanGagalPrekondisi = null;

        try {
            DB::transaction(function () use (
                $pesanan,
                &$pesanGagalPrekondisi
            ) {
                /** @var Pesanan|null $pesananTerkunci */
                $pesananTerkunci = Pesanan::lockForUpdate()
                    ->find($pesanan->id);

                if (
                    !$pesananTerkunci
                    || $pesananTerkunci->status_pesanan !== 'menunggu_pembayaran'
                ) {
                    $pesanGagalPrekondisi = 'Pesanan ini tidak menunggu pembayaran.';
                    return;
                }

                /** @var Pembayaran|null $pembayaranTerbaru */
                $pembayaranTerbaru = $pesananTerkunci->pembayaran()
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (
                    !$pembayaranTerbaru
                    || $pembayaranTerbaru->metode !== 'cash'
                    || $pembayaranTerbaru->status_pembayaran !== 'menunggu'
                ) {
                    $pesanGagalPrekondisi =
                        'Pembayaran terbaru bukan pembayaran Cash yang menunggu konfirmasi.';
                    return;
                }

                $jumlahBayarFormat = number_format(
                    (float) $pembayaranTerbaru->jumlah_bayar,
                    2,
                    '.',
                    ''
                );

                $totalPesananFormat = number_format(
                    (float) $pesananTerkunci->total,
                    2,
                    '.',
                    ''
                );

                if ($jumlahBayarFormat !== $totalPesananFormat) {
                    $pesanGagalPrekondisi =
                        'Jumlah pembayaran tidak sesuai dengan total pesanan.';
                    return;
                }

                // Ubah status pembayaran
                $pembayaranTerbaru->status_pembayaran = 'berhasil';
                $pembayaranTerbaru->tanggal_bayar = now();
                $pembayaranTerbaru->dikonfirmasi_oleh = auth()->id();
                $pembayaranTerbaru->save();

                // Ubah status pesanan
                $pesananTerkunci->status_pesanan = 'dibayar';
                $pesananTerkunci->save();

                // Memicu pengurangan stok melalui listener yang sudah ada
                PembayaranBerhasil::dispatch($pesananTerkunci->fresh());

                Log::info('Pembayaran Cash pesanan berhasil dikonfirmasi', [
                    'pesanan_id' => $pesananTerkunci->id,
                    'pembayaran_id' => $pembayaranTerbaru->id,
                    'admin_id' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengonfirmasi pembayaran Cash', [
                'pesanan_id' => $pesanan->id,
                'error' => $e->getMessage(),
            ]);

            $isBusiness = !(
                $e instanceof \Illuminate\Database\QueryException
                || $e instanceof \PDOException
                || $e instanceof \Error
            );

            $pesanSingkat = $isBusiness && !empty($e->getMessage())
                ? rtrim($e->getMessage(), '.')
                : 'Terjadi kesalahan sistem';

            return redirect()
                ->route('admin.pesanan.show', $pesanan)
                ->with(
                    'error',
                    "Konfirmasi gagal diproses. Tidak ada perubahan yang disimpan: {$pesanSingkat}."
                );
        }

        if ($pesanGagalPrekondisi !== null) {
            return redirect()
                ->route('admin.pesanan.show', $pesanan)
                ->with('error', $pesanGagalPrekondisi);
        }

        return redirect()
            ->route('admin.pesanan.show', $pesanan)
            ->with(
                'success',
                'Pembayaran Cash berhasil dikonfirmasi. Pesanan berstatus Dibayar.'
            );
    }

    /**
     * Menyembunyikan pesanan batal dari daftar Admin.
     * Data transaksi tidak dihapus permanen.
     */
    public function destroy(Pesanan $pesanan): RedirectResponse
    {
        $berhasil = DB::transaction(function () use ($pesanan) {
            $pesananTerkunci = Pesanan::query()
                ->whereKey($pesanan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $pesananTerkunci->status_pesanan !== 'batal'
                || $pesananTerkunci->disembunyikan_admin_pada !== null
            ) {
                return false;
            }

            // Jangan sembunyikan pesanan dengan pembayaran
            // yang masih menunggu atau sudah berhasil.
            $pembayaranAktif = $pesananTerkunci->pembayaran()
                ->whereIn('status_pembayaran', [
                    'menunggu',
                    'berhasil',
                ])
                ->exists();

            if ($pembayaranAktif) {
                return false;
            }

            $pesananTerkunci->disembunyikan_admin_pada = now();
            $pesananTerkunci->save();

            Log::info('Pesanan disembunyikan dari daftar Admin', [
                'pesanan_id' => $pesananTerkunci->id,
                'admin_id' => auth()->id(),
            ]);

            return true;
        });

        if (!$berhasil) {
            return redirect()
                ->route('admin.pesanan.index')
                ->with(
                    'error',
                    'Pesanan tidak dapat dihapus. Hanya pesanan batal tanpa pembayaran aktif yang diperbolehkan.'
                );
        }

        return redirect()
            ->route('admin.pesanan.index')
            ->with(
                'success',
                'Pesanan berhasil dihapus dari daftar Admin.'
            );
    }
}
