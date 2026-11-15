<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisProduk;
use App\Models\Produk;
use App\Models\ProdukFoto;
use App\Models\Stok;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk admin.
     */
    public function index(Request $request): View
    {
        $perHalaman = (int) $request->input('per_halaman', 10);

        if ($perHalaman < 1 || $perHalaman > 100) {
            $perHalaman = 10;
        }

        $daftarProduk = Produk::query()
            ->with(['stok', 'produkFoto', 'jenisProduk'])
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->string('cari')->toString();

                $query->where(function ($sub) use ($cari) {
                    $sub->where('kode_produk', 'like', "%{$cari}%")
                        ->orWhere('nama_produk', 'like', "%{$cari}%")
                        ->orWhere('motif', 'like', "%{$cari}%")
                        ->orWhere('jenis', 'like', "%{$cari}%")
                        ->orWhereHas('jenisProduk', function ($q) use ($cari) {
                            $q->where('nama_jenis', 'like', "%{$cari}%");
                        });
                });
            })
            ->latest()
            ->paginate($perHalaman)
            ->withQueryString();

        return view(
            'admin.produk.index',
            compact('daftarProduk')
        );
    }

    /**
     * Menampilkan form tambah produk.
     */
    public function create(): View
    {
        $kodeOtomatis = Produk::generateKodeProduk();

        $daftarJenis = JenisProduk::orderBy('nama_jenis')->get();

        return view(
            'admin.produk.tambah',
            compact('kodeOtomatis', 'daftarJenis')
        );
    }

    /**
     * Menyimpan produk baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        if (empty($data['kode_produk'])) {
            $data['kode_produk'] = Produk::generateKodeProduk();
        }

        $jenisModel = JenisProduk::find($data['jenis_produk_id']);

        $data['jenis'] = $jenisModel?->nama_jenis ?? '';

        // Berat wajib diisi untuk produk baru.
        $data['berat_gram'] = (int) $data['berat_gram'];

        $uploadedFiles = [];

        if ($request->hasFile('foto')) {
            $files = $request->file('foto');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $uploadedFiles[] = $file->store('produk', 'public');
                }
            }
        }

        if (!empty($uploadedFiles)) {
            $data['foto'] = $uploadedFiles[0];
        } else {
            $data['foto'] = null;
        }

        DB::transaction(function () use ($data, $uploadedFiles) {
            $produk = Produk::create($data);

            // Stok awal produk baru adalah nol.
            Stok::create([
                'produk_id' => $produk->id,
                'jumlah_stok' => 0,
            ]);

            // Simpan seluruh foto tambahan.
            foreach ($uploadedFiles as $path) {
                $produk->produkFoto()->create([
                    'foto' => $path,
                ]);
            }
        });

        return redirect()
            ->route('admin.produk.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit produk.
     */
    public function edit(Produk $produk): View
    {
        $produk->load(['stok', 'produkFoto', 'jenisProduk']);

        // Mempertahankan kompatibilitas foto produk lama.
        if (!empty($produk->foto) && $produk->produkFoto->isEmpty()) {
            $produk->produkFoto()->create([
                'foto' => $produk->foto,
            ]);

            $produk->load('produkFoto');
        }

        $daftarJenis = JenisProduk::orderBy('nama_jenis')->get();

        return view(
            'admin.produk.ubah',
            compact('produk', 'daftarJenis')
        );
    }

    /**
     * Memperbarui produk.
     */
    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $data = $this->validasi($request, $produk->id);

        // Kode produk tidak berubah saat edit.
        $data['kode_produk'] = $produk->kode_produk;

        $jenisModel = JenisProduk::find($data['jenis_produk_id']);

        $data['jenis'] = $jenisModel?->nama_jenis ?? '';

        // Jika form edit tidak mengirim berat,
        // pertahankan berat produk sebelumnya.
        if (!array_key_exists('berat_gram', $data)) {
            $data['berat_gram'] = $produk->berat_gram;
        } else {
            $data['berat_gram'] = (int) $data['berat_gram'];
        }

        $uploadedFiles = [];

        if ($request->hasFile('foto')) {
            $files = $request->file('foto');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $uploadedFiles[] = $file->store('produk', 'public');
                }
            }
        }

        // Jika produk belum memiliki foto utama,
        // gunakan foto baru pertama sebagai foto utama.
        if (!empty($uploadedFiles) && empty($produk->foto)) {
            $data['foto'] = $uploadedFiles[0];
        } else {
            $data['foto'] = $produk->foto;
        }

        DB::transaction(function () use ($produk, $data, $uploadedFiles) {
            $produk->update($data);

            foreach ($uploadedFiles as $path) {
                $produk->produkFoto()->create([
                    'foto' => $path,
                ]);
            }
        });

        return redirect()
            ->route('admin.produk.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Menghapus satu foto produk.
     */
    public function destroyFoto(
        Produk $produk,
        ProdukFoto $foto
    ): RedirectResponse {
        abort_unless($foto->produk_id === $produk->id, 404);

        if ($foto->foto) {
            Storage::disk('public')->delete($foto->foto);
        }

        // Jika foto yang dihapus merupakan foto utama,
        // gunakan foto lain apabila tersedia.
        if ($produk->foto === $foto->foto) {
            $nextFoto = $produk->produkFoto()
                ->where('id', '!=', $foto->id)
                ->first();

            $produk->update([
                'foto' => $nextFoto?->foto,
            ]);
        }

        $foto->delete();

        return back()->with(
            'success',
            'Foto produk berhasil dihapus.'
        );
    }

    /**
     * Menghapus produk apabila stok sudah kosong.
     */
    public function destroy(Produk $produk): RedirectResponse
    {
        $produk->loadMissing('stok');

        $jumlahStok = (int) ($produk->stok?->jumlah_stok ?? 0);

        if ($jumlahStok > 0) {
            return redirect()
                ->route('admin.produk.index')
                ->with(
                    'error',
                    'Produk tidak dapat dihapus karena stok masih tersedia.'
                );
        }

        $produk->load('produkFoto');

        if ($produk->foto) {
            Storage::disk('public')->delete($produk->foto);
        }

        foreach ($produk->produkFoto as $item) {
            if ($item->foto && $item->foto !== $produk->foto) {
                Storage::disk('public')->delete($item->foto);
            }
        }

        $produk->produkFoto()->delete();

        $produk->stok()->delete();

        $produk->delete();

        return redirect()
            ->route('admin.produk.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Validasi data produk.
     *
     * Berat wajib pada produk baru.
     * Pada edit, berat lama dipertahankan jika tidak dikirim.
     */
    private function validasi(
        Request $request,
        ?int $produkId = null
    ): array {
        // Mengubah format harga yang dimasukkan admin,
        // misalnya Rp 150.000 menjadi 150000.
        if (
            $request->has('harga') &&
            is_string($request->input('harga'))
        ) {
            $rawHarga = preg_replace(
                '/[^0-9]/',
                '',
                (string) $request->input('harga')
            );

            $request->merge([
                'harga' => $rawHarga !== '' ? $rawHarga : null,
            ]);
        }

        $rules = [
            'kode_produk' => [
                'nullable',
                'string',
                'max:50',
                'unique:produk,kode_produk' .
                    ($produkId ? ',' . $produkId : ''),
            ],

            'nama_produk' => [
                'required',
                'string',
                'max:100',
            ],

            'jenis_produk_id' => [
                'required',
                'exists:jenis_produk,id',
            ],

            'motif' => [
                'nullable',
                'string',
                'max:100',
            ],

            'warna' => [
                'nullable',
                'string',
                'max:50',
            ],

            'ukuran' => [
                'nullable',
                'string',
                'max:50',
            ],

            'material' => [
                'nullable',
                'string',
                'max:100',
            ],

            'harga' => [
                'required',
                'numeric',
                'min:0',
            ],

            'foto' => [
                'nullable',
            ],

            'foto.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'status' => [
                'required',
                'in:aktif,nonaktif',
            ],
        ];

        // Produk baru wajib memiliki berat.
        // Saat edit, berat divalidasi jika field dikirim.
        if ($produkId === null || $request->has('berat_gram')) {
            $rules['berat_gram'] = [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ];
        }

        return $request->validate($rules, [
            'nama_produk.required' =>
                'Nama produk wajib diisi.',

            'jenis_produk_id.required' =>
                'Jenis produk wajib dipilih.',

            'jenis_produk_id.exists' =>
                'Jenis produk yang dipilih tidak valid.',

            'harga.required' =>
                'Harga produk wajib diisi.',

            'harga.numeric' =>
                'Harga produk harus berupa angka.',

            'harga.min' =>
                'Harga produk minimal Rp 0.',

            'status.required' =>
                'Status produk wajib dipilih.',

            'berat_gram.required' =>
                'Berat produk wajib diisi.',

            'berat_gram.integer' =>
                'Berat produk harus berupa angka bulat (gram).',

            'berat_gram.min' =>
                'Berat produk minimal 1 gram.',

            'berat_gram.max' =>
                'Berat produk maksimal 100.000 gram.',

            'foto.*.image' =>
                'File yang diunggah harus berupa gambar.',

            'foto.*.mimes' =>
                'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',

            'foto.*.max' =>
                'Ukuran setiap file foto maksimal 2MB.',
        ]);
    }
}
