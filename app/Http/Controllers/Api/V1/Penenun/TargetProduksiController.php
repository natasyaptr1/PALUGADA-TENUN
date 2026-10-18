<?php

namespace App\Http\Controllers\Api\V1\Penenun;

use App\Http\Controllers\Controller;
use App\Http\Resources\TargetProduksiResource;
use App\Models\TargetProduksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TargetProduksiController extends Controller
{
    // Catatan: Route POST untuk input hasil produksi akan ditambahkan oleh anggota tim lain.

    public function ringkasan(Request $request): JsonResponse
    {
        $penenunId = $request->user()->id;
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $ringkasan = TargetProduksi::query()
            ->where('penenun_id', $penenunId)
            ->selectRaw("
                COUNT(CASE WHEN status = 'berjalan' THEN 1 END) as berjalan,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
                COUNT(CASE WHEN status = 'dibatalkan' THEN 1 END) as dibatalkan,
                COUNT(CASE WHEN status = 'berjalan' AND tanggal_deadline IS NOT NULL AND tanggal_deadline < ? THEN 1 END) as terlambat
            ", [$today])
            ->first();

        return response()->json([
            'data' => [
                'berjalan' => (int) ($ringkasan->berjalan ?? 0),
                'selesai' => (int) ($ringkasan->selesai ?? 0),
                'dibatalkan' => (int) ($ringkasan->dibatalkan ?? 0),
                'terlambat' => (int) ($ringkasan->terlambat ?? 0),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $penenunId = $request->user()->id;
        $status = $request->input('status');
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $perHalaman = min(max((int) $request->input('per_halaman', 10), 1), 50);

        $daftar = TargetProduksi::query()
            ->where('penenun_id', $penenunId)
            ->with(['produk.produkFoto'])
            ->withSum('hasilProduksi', 'jumlah_selesai')
            ->when($status === 'terlambat', function ($query) use ($today) {
                $query->where('status', 'berjalan')
                    ->whereNotNull('tanggal_deadline')
                    ->where('tanggal_deadline', '<', $today);
            })
            ->when(in_array($status, ['berjalan', 'selesai', 'dibatalkan'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw("
                CASE
                    WHEN status = 'berjalan' AND tanggal_deadline IS NOT NULL THEN 0
                    WHEN status = 'berjalan' THEN 1
                    ELSE 2
                END ASC
            ")
            ->orderByRaw("
                CASE
                    WHEN status = 'berjalan' AND tanggal_deadline IS NOT NULL THEN tanggal_deadline
                    ELSE NULL
                END ASC
            ")
            ->latest('id')
            ->paginate($perHalaman);

        return response()->json([
            'data' => TargetProduksiResource::collection($daftar),
            'meta' => [
                'halaman_sekarang' => $daftar->currentPage(),
                'per_halaman' => $daftar->perPage(),
                'total' => $daftar->total(),
                'halaman_terakhir' => $daftar->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int|string $target): JsonResponse
    {
        $penenunId = $request->user()->id;

        $targetProduksi = TargetProduksi::query()
            ->where('penenun_id', $penenunId)
            ->where('id', $target)
            ->with(['produk.produkFoto'])
            ->withSum('hasilProduksi', 'jumlah_selesai')
            ->first();

        if (!$targetProduksi) {
            return response()->json([
                'pesan' => 'Target produksi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => (new TargetProduksiResource($targetProduksi))->denganRiwayat(true),
        ]);
    }
}
