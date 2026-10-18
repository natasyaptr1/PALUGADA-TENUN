<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{TargetProduksi, HasilProduksi, Stok, MutasiStok};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProduksiPenenunController extends Controller
{
    public function targets(Request $request): JsonResponse
    {
        $data = TargetProduksi::where('penenun_id', $request->user()->id)
            ->with('produk:id,kode_produk,nama_produk')
            ->withSum('hasilProduksi', 'jumlah_selesai')
            ->latest()->get();
        return response()->json(['data' => $data]);
    }

    public function riwayat(Request $request): JsonResponse
    {
        $data = HasilProduksi::whereHas('targetProduksi', fn($q) => $q->where('penenun_id', $request->user()->id))
            ->with('targetProduksi.produk:id,kode_produk,nama_produk')
            ->latest('tanggal_input')->get();
        return response()->json(['data' => $data]);
    }

    public function simpan(Request $request, TargetProduksi $target): JsonResponse
    {
        $data = $request->validate([
            'jumlah_selesai' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'request_id' => ['required', 'string', 'max:80'],
        ]);
        if ((int) $target->penenun_id !== (int) $request->user()->id) abort(403, 'Target ini bukan milik akun Anda.');

        $hasil = DB::transaction(function () use ($target, $data) {
            $terkunci = TargetProduksi::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $sudahAda = HasilProduksi::where('request_id', $data['request_id'])->first();
            if ($sudahAda) {
                if ((int) $sudahAda->target_produksi_id !== (int) $terkunci->id) {
                    throw ValidationException::withMessages(['request_id' => 'Identitas pengiriman tidak sesuai.']);
                }
                return $sudahAda;
            }
            if ($terkunci->status !== 'berjalan') {
                throw ValidationException::withMessages(['target' => 'Target tidak lagi berjalan.']);
            }
            $sudah = (int) $terkunci->hasilProduksi()->sum('jumlah_selesai');
            $jumlah = (int) $data['jumlah_selesai'];
            if ($jumlah > $terkunci->jumlah_target - $sudah) {
                throw ValidationException::withMessages(['jumlah_selesai' => 'Jumlah melampaui sisa target.']);
            }
            $hasil = HasilProduksi::create([
                'target_produksi_id' => $terkunci->id,
                'jumlah_selesai' => $jumlah,
                'tanggal_input' => now(),
                'catatan' => $data['catatan'] ?? null,
                'request_id' => $data['request_id'],
            ]);
            $stok = Stok::firstOrCreate(['produk_id' => $terkunci->produk_id], ['jumlah_stok' => 0]);
            $stok = Stok::whereKey($stok->id)->lockForUpdate()->firstOrFail();
            $stok->increment('jumlah_stok', $jumlah);
            MutasiStok::create([
                'stok_id' => $stok->id,
                'jenis_mutasi' => 'masuk',
                'jumlah' => $jumlah,
                'sumber' => 'produksi',
                'hasil_produksi_id' => $hasil->id,
                'catatan' => 'Hasil produksi target #'.$terkunci->id,
                'tanggal_mutasi' => now(),
            ]);
            if ($sudah + $jumlah >= $terkunci->jumlah_target) $terkunci->update(['status' => 'selesai']);
            return $hasil;
        });
        return response()->json(['pesan' => 'Hasil produksi berhasil diproses.', 'data' => $hasil], 201);
    }
}
