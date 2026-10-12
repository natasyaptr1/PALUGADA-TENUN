<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Stok;
use App\Models\MutasiStok;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProdukMobileAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Produk::with('stok')
            ->when($request->filled('cari'), function ($q) use ($request) {
                $term = $request->input('cari');
                $q->where(function ($inner) use ($term) {
                    foreach (['nama_produk','kode_produk','jenis','motif','warna'] as $column) {
                        $inner->orWhere($column, 'like', '%' . $term . '%');
                    }
                });
            })
            ->paginate(min(max((int) $request->input('per_halaman', 50), 1), 50));
        return response()->json(['data' => $products->items(), 'meta' => ['total' => $products->total(), 'halaman_terakhir' => $products->lastPage()]]);
    }

    public function tambahStok(Request $request, Produk $produk): JsonResponse
    {
        $data = $request->validate([
            'jumlah' => ['required','integer','min:1','max:100000'],
            'catatan' => ['nullable','string','max:255'],
        ]);
        $stok = DB::transaction(function () use ($produk, $data) {
            $stok = Stok::where('produk_id', $produk->id)->lockForUpdate()->first();
            if (!$stok) {
                $stok = Stok::create(['produk_id' => $produk->id, 'jumlah_stok' => 0]);
            }
            $stok->increment('jumlah_stok', $data['jumlah']);
            MutasiStok::create([
                'stok_id' => $stok->id,
                'jenis_mutasi' => 'masuk',
                'jumlah' => $data['jumlah'],
                'sumber' => 'non_penjualan',
                'catatan' => $data['catatan'] ?? 'Tambah stok admin Android',
                'tanggal_mutasi' => now(),
            ]);
            return $stok->fresh();
        });
        return response()->json(['pesan' => 'Stok berhasil ditambahkan', 'data' => $stok]);
    }
}
