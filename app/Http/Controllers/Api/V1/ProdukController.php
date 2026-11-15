<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProdukController extends Controller
{
    // Daftar produk untuk katalog Android.
    public function index(Request $request): JsonResponse
    {
        $filter = $request->validate([
            'per_halaman' => [
                'nullable', 'integer', 'min:1', 'max:50'
            ],
        ]);

        $daftar = Produk::with('stok')
            ->orderByDesc('id')
            ->paginate($filter['per_halaman'] ?? 50);

        return response()->json([
            'data' => $this->formatProduk($daftar->items()),
            'meta' => [
                'halaman_sekarang' => $daftar->currentPage(),
                'per_halaman' => $daftar->perPage(),
                'total' => $daftar->total(),
                'halaman_terakhir' => $daftar->lastPage(),
            ],
        ]);
    }

    // Daftar seluruh produk untuk admin.
    public function adminIndex(Request $request): JsonResponse
    {
        $filter = $request->validate([
            'per_halaman' => [
                'nullable', 'integer', 'min:1', 'max:50'
            ],
        ]);

        $daftar = Produk::with('stok')
            ->orderByDesc('id')
            ->paginate($filter['per_halaman'] ?? 50);

        return response()->json([
            'data' => $this->formatProduk($daftar->items()),
            'meta' => [
                'halaman_sekarang' => $daftar->currentPage(),
                'per_halaman' => $daftar->perPage(),
                'total' => $daftar->total(),
                'halaman_terakhir' => $daftar->lastPage(),
            ],
        ]);
    }

    // Mengirim ciphertext tanpa konversi ke angka.
    private function formatProduk(array $items): array
    {
        return collect($items)
            ->map(function ($produk) {
                $data = $produk->toArray();

                $data['harga'] =
                    $produk->getRawOriginal('harga');

                return $data;
            })
            ->values()
            ->all();
    }

    public function show(Produk $produk): JsonResponse
    {
        $produk->load('stok');

        $data = $produk->toArray();
        $data['harga'] = $produk->getRawOriginal('harga');

        return response()->json([
            'data' => $data,
        ]);
    }

    // Simpan produk dari Android.
    public function store(Request $request): JsonResponse
    {
        $data = $this->validasi($request);

        if (empty($data['kode_produk'])) {
            $data['kode_produk'] =
                Produk::generateKodeProduk();
        }

        // Khusus demonstrasi PML:
        // Menggunakan salah satu jenis produk yang sudah tersedia.
        $jenisProdukId = \App\Models\JenisProduk::query()
            ->value('id');

        if (!$jenisProdukId) {
            return response()->json([
                'pesan' => 'Belum ada data jenis produk.',
            ], 422);
        }

        $data['jenis_produk_id'] = $jenisProdukId;

        $data['foto'] = $request->file('foto')
            ?->store('produk', 'public');

        $produk = DB::transaction(function () use ($data) {
            $produk = Produk::create($data);

            Stok::create([
                'produk_id' => $produk->id,
                'jumlah_stok' => 0,
            ]);

            return $produk;
        });

        return response()->json([
            'pesan' => 'Produk berhasil ditambahkan.',
            'data' => $produk->load('stok'),
        ], 201);
    }

    // Edit produk dari Android.
    public function update(
        Request $request,
        Produk $produk
    ): JsonResponse {
        $data = $this->validasi($request, $produk->id);

        // Kode produk tidak diubah saat edit.
        $data['kode_produk'] = $produk->kode_produk;

        if ($request->hasFile('foto')) {
            $fotoLama = $produk->foto;

            $data['foto'] = $request->file('foto')
                ->store('produk', 'public');

            $produk->update($data);

            if ($fotoLama) {
                Storage::disk('public')->delete($fotoLama);
            }
        } else {
            $produk->update($data);
        }

        return response()->json([
            'pesan' => 'Produk berhasil diperbarui.',
            'data' => $produk->load('stok'),
        ]);
    }

    // Upload foto produk.
    public function uploadPhoto(Request $request): JsonResponse
    {
        $data = $request->validate([
            'produk_id' => [
                'required',
                'integer',
                'exists:produk,id',
            ],
            'file' => [
                'required',
                'image',
                'max:2048',
            ],
        ]);

        $produk = Produk::findOrFail($data['produk_id']);
        $fotoLama = $produk->foto;

        $fotoBaru = $request->file('file')
            ->store('produk', 'public');

        try {
            $produk->update([
                'foto' => $fotoBaru,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($fotoBaru);
            throw $e;
        }

        if ($fotoLama && $fotoLama !== $fotoBaru) {
            Storage::disk('public')->delete($fotoLama);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto produk berhasil diperbarui.',
            'foto_url' => Storage::disk('public')->url($fotoBaru),
            'data' => $produk->load('stok'),
        ]);
    }

    public function destroy(Produk $produk): JsonResponse
    {
        if ((int) ($produk->stok?->jumlah_stok ?? 0) > 0) {
            return response()->json([
                'pesan' =>
                    'Produk tidak dapat dihapus karena stok masih tersedia.',
            ], 422);
        }

        $foto = $produk->foto;

        $produk->delete();

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }

        return response()->json([
            'pesan' => 'Produk berhasil dihapus.',
        ]);
    }

    // Validasi data AES untuk praktikum PML.
    private function validasi(
        Request $request,
        ?int $produkId = null
    ): array {
        return $request->validate([
            'kode_produk' => [
                $produkId ? 'required' : 'nullable',
                'string',
                'max:255',
                'unique:produk,kode_produk' .
                    ($produkId ? ',' . $produkId : ''),
            ],
            'nama_produk' => [
                'required', 'string', 'max:2048',
            ],
            'jenis' => [
                'required', 'string', 'max:2048',
            ],
            'motif' => [
                'nullable', 'string', 'max:2048',
            ],
            'warna' => [
                'nullable', 'string', 'max:2048',
            ],
            'ukuran' => [
                'nullable', 'string', 'max:2048',
            ],
            'material' => [
                'nullable', 'string', 'max:2048',
            ],
            'harga' => [
                'required', 'string', 'max:2048',
            ],
            'status' => [
                'required', 'string', 'max:2048',
            ],
            'foto' => [
                'nullable', 'image', 'max:2048',
            ],
        ]);
    }
}
