<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\TargetProduksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TargetProduksiController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->input('cari'));
        $status = $request->input('status');
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $ringkasan = TargetProduksi::query()
            ->selectRaw("
                COUNT(CASE WHEN status = 'berjalan' THEN 1 END) as total_berjalan,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as total_selesai,
                COUNT(CASE WHEN status = 'dibatalkan' THEN 1 END) as total_dibatalkan,
                COUNT(CASE WHEN status = 'berjalan' AND tanggal_deadline IS NOT NULL AND tanggal_deadline < ? THEN 1 END) as total_terlambat
            ", [$today])
            ->first();

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
            ->when($status === 'terlambat', function ($query) use ($today) {
                $query->where('status', 'berjalan')
                    ->whereNotNull('tanggal_deadline')
                    ->where('tanggal_deadline', '<', $today);
            })
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

        return view('admin.target_produksi.index', compact('daftarTarget', 'ringkasan'));
    }

    public function show(TargetProduksi $targetProduksi): View
    {
        $targetProduksi->load([
            'produk',
            'penenun.profilPenenun.daftarKeahlian',
        ]);
        $targetProduksi->loadSum('hasilProduksi', 'jumlah_selesai');

        $riwayatHasil = $targetProduksi->hasilProduksi()
            ->latest('tanggal_input')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.target_produksi.show', compact('targetProduksi', 'riwayatHasil'));
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

    public function edit(TargetProduksi $targetProduksi): View|RedirectResponse
    {
        if ($targetProduksi->status !== 'berjalan') {
            return redirect()
                ->route('admin.target-produksi.index')
                ->with('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        $totalHasil = (int) $targetProduksi->hasilProduksi()->sum('jumlah_selesai');

        $daftarProduk = Produk::query()
            ->where(function ($query) use ($targetProduksi) {
                $query->where('status', 'aktif')
                    ->orWhere('id', $targetProduksi->produk_id);
            })
            ->orderBy('nama_produk')
            ->get();

        $daftarPenenun = Pengguna::query()
            ->where(function ($query) use ($targetProduksi) {
                $query->where(function ($sub) {
                    $sub->where('peran', 'penenun')->where('status', 'aktif');
                })->orWhere('id', $targetProduksi->penenun_id);
            })
            ->with(['profilPenenun.daftarKeahlian'])
            ->orderBy('nama')
            ->get();

        return view('admin.target_produksi.edit', compact(
            'targetProduksi',
            'daftarProduk',
            'daftarPenenun',
            'totalHasil'
        ));
    }

    public function update(Request $request, TargetProduksi $targetProduksi): RedirectResponse
    {
        if ($targetProduksi->status !== 'berjalan') {
            return redirect()
                ->route('admin.target-produksi.index')
                ->with('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        $totalHasil = (int) $targetProduksi->hasilProduksi()->sum('jumlah_selesai');

        $rules = [
            'jumlah_target' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
                function ($attribute, $value, $fail) use ($totalHasil) {
                    if ($totalHasil > 0 && (int) $value < $totalHasil) {
                        $fail("Jumlah target tidak boleh lebih kecil dari hasil produksi yang sudah masuk ({$totalHasil}).");
                    }
                },
            ],
        ];

        $messages = [
            'jumlah_target.required' => 'Jumlah target wajib diisi.',
            'jumlah_target.integer' => 'Jumlah target harus berupa bilangan bulat.',
            'jumlah_target.min' => 'Jumlah target minimal 1 unit.',
            'jumlah_target.max' => 'Jumlah target maksimal 10.000 unit.',
            'tanggal_deadline.date' => 'Format batas waktu penyelesaian tidak valid.',
            'tanggal_deadline.after_or_equal' => 'Batas waktu penyelesaian tidak boleh sebelum tanggal mulai.',
        ];

        if ($totalHasil > 0) {
            $tanggalMulaiLama = $targetProduksi->tanggal_mulai ? $targetProduksi->tanggal_mulai->format('Y-m-d') : null;
            $rules['tanggal_deadline'] = [
                'nullable',
                'date',
                $tanggalMulaiLama ? 'after_or_equal:' . $tanggalMulaiLama : 'nullable',
            ];

            $validated = $request->validate($rules, $messages);

            $updateData = [
                'jumlah_target' => (int) $validated['jumlah_target'],
                'tanggal_deadline' => $validated['tanggal_deadline'] ?? null,
            ];
        } else {
            $tanggalMulaiLama = $targetProduksi->tanggal_mulai ? $targetProduksi->tanggal_mulai->format('Y-m-d') : null;
            $tanggalMulaiBaru = $request->input('tanggal_mulai');

            $rules['produk_id'] = [
                'required',
                function ($attribute, $value, $fail) use ($targetProduksi) {
                    if ((int) $value === (int) $targetProduksi->produk_id) {
                        return;
                    }
                    $valid = Produk::where('id', $value)->where('status', 'aktif')->exists();
                    if (!$valid) {
                        $fail('Produk yang dipilih tidak valid atau sedang nonaktif.');
                    }
                },
            ];

            $rules['penenun_id'] = [
                'required',
                function ($attribute, $value, $fail) use ($targetProduksi) {
                    if ((int) $value === (int) $targetProduksi->penenun_id) {
                        return;
                    }
                    $valid = Pengguna::where('id', $value)
                        ->where('peran', 'penenun')
                        ->where('status', 'aktif')
                        ->exists();
                    if (!$valid) {
                        $fail('Penenun yang dipilih tidak valid atau sedang nonaktif.');
                    }
                },
            ];

            $tanggalMulaiRules = ['required', 'date'];
            if ($tanggalMulaiBaru !== $tanggalMulaiLama) {
                $tanggalMulaiRules[] = 'after_or_equal:today';
            }
            $rules['tanggal_mulai'] = $tanggalMulaiRules;
            $rules['tanggal_deadline'] = ['nullable', 'date', 'after_or_equal:tanggal_mulai'];

            $messages['produk_id.required'] = 'Produk wajib dipilih.';
            $messages['penenun_id.required'] = 'Penenun wajib dipilih.';
            $messages['tanggal_mulai.required'] = 'Tanggal mulai wajib diisi.';
            $messages['tanggal_mulai.date'] = 'Format tanggal mulai tidak valid.';
            $messages['tanggal_mulai.after_or_equal'] = 'Tanggal mulai tidak boleh sebelum hari ini.';

            $validated = $request->validate($rules, $messages);

            $updateData = [
                'produk_id' => (int) $validated['produk_id'],
                'penenun_id' => (int) $validated['penenun_id'],
                'jumlah_target' => (int) $validated['jumlah_target'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_deadline' => $validated['tanggal_deadline'] ?? null,
            ];
        }

        try {
            $targetProduksi->update($updateData);

            return redirect()
                ->route('admin.target-produksi.index')
                ->with('success', 'Target produksi berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui target produksi: ' . $e->getMessage());
        }
    }

    public function batalkan(TargetProduksi $targetProduksi): RedirectResponse
    {
        if ($targetProduksi->status !== 'berjalan') {
            return redirect()
                ->route('admin.target-produksi.index')
                ->with('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat dibatalkan.');
        }

        try {
            DB::transaction(function () use ($targetProduksi) {
                $target = TargetProduksi::where('id', $targetProduksi->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($target->status !== 'berjalan') {
                    throw new \DomainException('Target produksi yang sudah selesai atau dibatalkan tidak dapat dibatalkan.');
                }

                $target->update(['status' => 'dibatalkan']);
            });

            return redirect()
                ->route('admin.target-produksi.index')
                ->with('success', 'Target produksi berhasil dibatalkan.');
        } catch (\DomainException $e) {
            return redirect()
                ->route('admin.target-produksi.index')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.target-produksi.index')
                ->with('error', 'Gagal membatalkan target produksi: ' . $e->getMessage());
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
