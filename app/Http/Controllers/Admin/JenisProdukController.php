<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JenisProdukController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->input('cari'));

        $daftarJenisProduk = JenisProduk::query()
            ->withCount('produk')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($sub) use ($cari) {
                    $sub->where('nama_jenis', 'like', "%{$cari}%")
                        ->orWhere('keterangan', 'like', "%{$cari}%");
                });
            })
            ->orderBy('nama_jenis')
            ->paginate(10)
            ->withQueryString();

        return view('admin.jenis_produk.index', compact('daftarJenisProduk'));
    }

    public function create(): View
    {
        return view('admin.jenis_produk.tambah');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        try {
            JenisProduk::create($data);
            return redirect()
                ->route('admin.jenis-produk.index')
                ->with('success', 'Jenis produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan jenis produk: ' . $e->getMessage());
        }
    }

    public function edit(JenisProduk $jenisProduk): View
    {
        return view('admin.jenis_produk.ubah', compact('jenisProduk'));
    }

    public function update(Request $request, JenisProduk $jenisProduk): RedirectResponse
    {
        $data = $this->validasi($request, $jenisProduk->id);

        try {
            $jenisProduk->update($data);

            // Sinkronkan nama jenis pada kolom string 'jenis' di tabel produk terkait
            $jenisProduk->produk()->update(['jenis' => $data['nama_jenis']]);

            return redirect()
                ->route('admin.jenis-produk.index')
                ->with('success', 'Jenis produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui jenis produk: ' . $e->getMessage());
        }
    }

    public function destroy(JenisProduk $jenisProduk): RedirectResponse
    {
        $jumlahTerkait = $jenisProduk->produk()->count();
        if ($jumlahTerkait > 0) {
            return redirect()
                ->route('admin.jenis-produk.index')
                ->with('error', "Jenis produk tidak dapat dihapus karena masih digunakan oleh {$jumlahTerkait} produk.");
        }

        try {
            $jenisProduk->delete();
            return redirect()
                ->route('admin.jenis-produk.index')
                ->with('success', 'Jenis produk berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.jenis-produk.index')
                ->with('error', 'Gagal menghapus jenis produk: ' . $e->getMessage());
        }
    }

    private function validasi(Request $request, ?int $jenisProdukId = null): array
    {
        return $request->validate([
            'nama_jenis' => [
                'required',
                'string',
                'max:50',
                'unique:jenis_produk,nama_jenis' . ($jenisProdukId ? ',' . $jenisProdukId : ''),
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'nama_jenis.required' => 'Nama jenis produk wajib diisi dan tidak boleh kosong.',
            'nama_jenis.string' => 'Nama jenis produk harus berupa teks.',
            'nama_jenis.max' => 'Nama jenis produk maksimal 50 karakter.',
            'nama_jenis.unique' => 'Nama jenis produk sudah digunakan.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
        ]);
    }
}
