<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\TargetProduksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetProduksiController extends Controller
{
    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('cari', ''));
        $status = (string) $request->query('status', '');

        $query = TargetProduksi::with(['produk', 'penenun'])
            ->withSum('hasilProduksi', 'jumlah_selesai');

        if ($pencarian !== '') {
            $query->where(function ($q) use ($pencarian) {
                $q->whereHas('produk', fn ($p) => $p->where('nama_produk', 'like', '%' . $pencarian . '%')
                    ->orWhere('kode_produk', 'like', '%' . $pencarian . '%'))
                    ->orWhereHas('penenun', fn ($p) => $p->where('nama', 'like', '%' . $pencarian . '%'));
            });
        }

        if (in_array($status, ['berjalan', 'selesai', 'dibatalkan'], true)) {
            $query->where('status', $status);
        }

        $targets = $query->latest('id')->paginate(10)->withQueryString();
        $produk = Produk::where('status', 'aktif')->orderBy('nama_produk')->get();
        $penenun = Pengguna::where('peran', 'penenun')->where('status', 'aktif')->orderBy('nama')->get();

        return view('admin.target_produksi.index', compact('targets', 'produk', 'penenun', 'pencarian', 'status'));
    }

    public function show(TargetProduksi $target): View
    {
        $target->load(['produk', 'penenun', 'hasilProduksi' => fn ($q) => $q->latest('tanggal_input')]);
        $selesai = (int) $target->hasilProduksi->sum('jumlah_selesai');
        $persen = (int) ($target->jumlah_target > 0 ? min(100, round(100 * $selesai / $target->jumlah_target)) : 0);

        return view('admin.target_produksi.show', compact('target', 'selesai', 'persen'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produk_id' => ['required', 'exists:produk,id'],
            'penenun_id' => ['required', 'exists:pengguna,id'],
            'jumlah_target' => ['required', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_deadline' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);
        if (!Pengguna::whereKey($data['penenun_id'])->where('peran', 'penenun')->where('status', 'aktif')->exists()) {
            return back()->withErrors(['penenun_id' => 'Pilih akun penenun aktif.'])->withInput();
        }
        if (!Produk::whereKey($data['produk_id'])->where('status', 'aktif')->exists()) {
            return back()->withErrors(['produk_id' => 'Pilih produk yang masih aktif.'])->withInput();
        }
        TargetProduksi::create([...$data, 'status' => 'berjalan']);
        return redirect()->route('admin.target-produksi')->with('success', 'Target produksi berhasil dibuat.');
    }

    public function update(Request $request, TargetProduksi $target): RedirectResponse
    {
        if ($target->status !== 'berjalan') {
            return back()->with('error', 'Hanya target berjalan yang dapat diubah.');
        }
        $data = $request->validate([
            'jumlah_target' => ['required', 'integer', 'min:1'],
            'tanggal_deadline' => ['nullable', 'date', 'after_or_equal:' . $target->tanggal_mulai->format('Y-m-d')],
        ]);
        $selesai = (int) $target->hasilProduksi()->sum('jumlah_selesai');
        if ($data['jumlah_target'] < $selesai) {
            return back()->withErrors(['jumlah_target' => 'Jumlah target tidak boleh lebih kecil daripada hasil yang telah dicatat.']);
        }
        $data['status'] = $data['jumlah_target'] === $selesai ? 'selesai' : 'berjalan';
        $target->update($data);
        return back()->with('success', 'Target produksi berhasil diperbarui.');
    }

    public function cancel(TargetProduksi $target): RedirectResponse
    {
        if ($target->status !== 'berjalan') {
            return back()->with('error', 'Target ini tidak dapat dibatalkan.');
        }
        if ($target->hasilProduksi()->exists()) {
            return back()->with('error', 'Target yang sudah memiliki hasil produksi tidak dapat dibatalkan.');
        }
        $target->update(['status' => 'dibatalkan']);
        return back()->with('success', 'Target produksi dibatalkan.');
    }

    public function destroy(TargetProduksi $target): RedirectResponse
    {
        if ($target->hasilProduksi()->exists()) {
            return back()->with('error', 'Target yang sudah memiliki hasil produksi tidak dapat dihapus.');
        }
        $target->delete();
        return redirect()->route('admin.target-produksi')->with('success', 'Target produksi dihapus.');
    }

    public function create(): \Illuminate\View\View
{
    $produk = \App\Models\Produk::where('status', 'aktif')
        ->orderBy('nama_produk')
        ->get();

    $penenun = \App\Models\Pengguna::where('peran', 'penenun')
        ->where('status', 'aktif')
        ->orderBy('nama')
        ->get();

    return view('admin.target_produksi.create', compact('produk', 'penenun'));
}
}
