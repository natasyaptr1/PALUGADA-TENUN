<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class ProdukController extends Controller {
    public function index(Request $request): JsonResponse
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'harga_min' => ['nullable', 'numeric', 'min:0'],
            'harga_max' => ['nullable', 'numeric', 'min:0', 'gte:harga_min'],
            'stok_tersedia' => ['nullable', 'boolean'],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $daftar = Produk::with('stok')
            ->where('status', 'aktif')

            // Pencarian berdasarkan nama produk
            ->when($request->filled('cari'), function ($query) use ($filter) {
                $query->where('nama_produk', 'like', '%' . $filter['cari'] . '%');
            })

            // Filter berdasarkan jenis produk
            ->when($request->filled('jenis'), function ($query) use ($filter) {
                $query->where('jenis', $filter['jenis']);
            })

            // Filter harga minimum
            ->when($request->filled('harga_min'), function ($query) use ($filter) {
                $query->where('harga', '>=', $filter['harga_min']);
            })

            // Filter harga maksimum
            ->when($request->filled('harga_max'), function ($query) use ($filter) {
                $query->where('harga', '<=', $filter['harga_max']);
            })

            // Filter produk yang stoknya tersedia
            ->when($request->boolean('stok_tersedia'), function ($query) {
                $query->whereHas('stok', function ($stok) {
                    $stok->where('jumlah_stok', '>', 0);
                });
            })

            ->orderBy('nama_produk')
            ->paginate($filter['per_halaman'] ?? 12);

        return response()->json([
            'data' => $daftar->items(),
            'meta' => [
                'halaman_sekarang' => $daftar->currentPage(),
                'per_halaman' => $daftar->perPage(),
                'total' => $daftar->total(),
                'halaman_terakhir' => $daftar->lastPage(),
            ],
        ]);
    }

    public function show(Produk $produk): JsonResponse { abort_unless($produk->status==='aktif',404); return response()->json(['data'=>$produk->load('stok')]); }
    public function store(Request $request): JsonResponse {
        $data=$this->validasi($request);
        if (empty($data['kode_produk'])) {
            $data['kode_produk'] = Produk::generateKodeProduk();
        }
        $data['foto']=$request->file('foto')?->store('produk','public');
        $produk=DB::transaction(function()use($data){$produk=Produk::create($data);Stok::create(['produk_id'=>$produk->id,'jumlah_stok'=>0]);return $produk;});
        return response()->json(['pesan'=>'Produk berhasil ditambahkan.','data'=>$produk->load('stok')],201);
    }
    public function update(Request $request, Produk $produk): JsonResponse {
        $data=$this->validasi($request,$produk->id);
        $data['kode_produk'] = $produk->kode_produk;
        if($request->hasFile('foto')){if($produk->foto)Storage::disk('public')->delete($produk->foto);$data['foto']=$request->file('foto')->store('produk','public');}
        $produk->update($data); return response()->json(['pesan'=>'Produk berhasil diperbarui.','data'=>$produk->load('stok')]);
    }
    public function destroy(Produk $produk): JsonResponse {
        if ((int) ($produk->stok?->jumlah_stok ?? 0) > 0) {
            return response()->json(['pesan' => 'Produk tidak dapat dihapus karena stok masih tersedia.'], 422);
        }
        if ($produk->foto) Storage::disk('public')->delete($produk->foto);
        $produk->delete();
        return response()->json(['pesan'=>'Produk berhasil dihapus.']);
    }
    private function validasi(Request $request,?int $produkId=null): array { return $request->validate([
        'kode_produk'=>[$produkId ? 'required' : 'nullable','string','max:50','unique:produk,kode_produk'.($produkId?','.$produkId:'')],
        'nama_produk'=>['required','string','max:100'],'jenis'=>['required','string','max:50'],'motif'=>['nullable','string','max:100'],
        'warna'=>['nullable','string','max:50'],'ukuran'=>['nullable','string','max:50'],'material'=>['nullable','string','max:100'],
        'harga'=>['required','numeric','min:0'],'foto'=>['nullable','image','max:2048'],'status'=>['required','in:aktif,nonaktif'],
    ]); }
}
