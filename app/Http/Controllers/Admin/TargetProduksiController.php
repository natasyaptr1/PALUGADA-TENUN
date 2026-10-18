<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\TargetProduksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TargetProduksiController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->input('cari'));
        $status = $request->input('status');

        $daftarTarget = TargetProduksi::query()
            ->with([
                'produk' => function ($query) {
                    $query->select('id', 'kode_produk', 'nama_produk', 'status');
                },
                'penenun' => function ($query) {
                    $query->select('id', 'nama', 'email', 'status')
                        ->with(['profilPenenun' => function ($q) {
                            $q->select('id', 'pengguna_id', 'kode_penenun', 'keahlian');
                        }]);
                },
            ])
            ->withSum('hasilProduksi', 'jumlah_selesai')
            ->when($status && in_array($status, ['berjalan', 'selesai', 'dibatalkan'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($sub) use ($cari) {
                    $sub->whereHas('produk', function ($q) use ($cari) {
                        $q->where('nama_produk', 'like', "%{$cari}%")
                            ->orWhere('kode_produk', 'like', "%{$cari}%");
                    })->orWhereHas('penenun', function ($q) use ($cari) {
                        $q->where('nama', 'like', "%{$cari}%");
                    });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.target_produksi.index', compact('daftarTarget'));
    }

    public function create(): View
    {
        $daftarProduk = Produk::query()
            ->where('status', 'aktif')
            ->orderBy('nama_produk')
            ->get();

        $daftarPenenun = Pengguna::query()
            ->where('peran', 'penenun')
            ->where('status', 'aktif')
            ->with(['profilPenenun.daftarKeahlian'])
            ->orderBy('nama')
            ->get();

        return view('admin.target_produksi.create', compact('daftarProduk', 'daftarPenenun'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['status'] = 'berjalan';

        try {
            TargetProduksi::create($data);

            return redirect()
                ->route('admin.target-produksi.index')
                ->with('success', 'Target produksi berhasil dibuat.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat target produksi: ' . $e->getMessage());
        }
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'produk_id' => [
                'required',
                Rule::exists('produk', 'id')->where(function ($query) {
                    $query->where('status', 'aktif');
                }),
            ],
            'penenun_id' => [
                'required',
                Rule::exists('pengguna', 'id')->where(function ($query) {
                    $query->where('peran', 'penenun')
                        ->where('status', 'aktif');
                }),
            ],
            'jumlah_target' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'tanggal_mulai' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'tanggal_deadline' => [
                'nullable',
                'date',
                'after_or_equal:tanggal_mulai',
            ],
        ], [
            'produk_id.required' => 'Produk wajib dipilih.',
            'produk_id.exists' => 'Produk yang dipilih tidak valid atau sedang nonaktif.',
            'penenun_id.required' => 'Penenun wajib dipilih.',
            'penenun_id.exists' => 'Penenun yang dipilih tidak valid atau sedang nonaktif.',
            'jumlah_target.required' => 'Jumlah target wajib diisi.',
            'jumlah_target.integer' => 'Jumlah target harus berupa bilangan bulat.',
            'jumlah_target.min' => 'Jumlah target minimal 1 unit.',
            'jumlah_target.max' => 'Jumlah target maksimal 10.000 unit.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date' => 'Format tanggal mulai tidak valid.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_deadline.date' => 'Format batas waktu penyelesaian tidak valid.',
            'tanggal_deadline.after_or_equal' => 'Batas waktu penyelesaian tidak boleh sebelum tanggal mulai.',
        ]);
    }
}
