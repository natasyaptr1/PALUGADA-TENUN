<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeranjangController extends Controller
{
    public function index(Request $request)
    {
        $keranjang = Keranjang::with(['detail.produk.stok', 'detail.produk.produkFoto'])
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        $total = $keranjang?->detail->sum(fn ($item) =>
            (float) ($item->produk?->harga ?? 0) * $item->jumlah
        ) ?? 0;

        return view('pembeli.keranjang', compact('keranjang', 'total'));
    }

    public function pilihCheckout(Request $request)
    {
        $data = $request->validate([
            'detail_ids' => ['required', 'array', 'min:1'],
            'detail_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'detail_ids.required' => 'Centang minimal satu produk untuk checkout.',
        ]);

        $ids = array_map('intval', $data['detail_ids']);
        $keranjang = Keranjang::where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')->first();

        if (!$keranjang || $keranjang->detail()->whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'detail_ids' => 'Pilihan produk tidak valid. Silakan pilih kembali.',
            ]);
        }

        $request->session()->put('checkout_selected_ids', $ids);

        return redirect()->route('pembeli.checkout');
    }

    public function tambah(Request $request)
    {
        $data = $request->validate([
            'produk_id' => 'required|integer|exists:produk,id',
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $data) {
            Pengguna::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            $produk = Produk::whereKey($data['produk_id'])->where('status', 'aktif')->first();
            if (!$produk) {
                throw ValidationException::withMessages(['produk_id' => 'Produk tidak tersedia.']);
            }

            $stok = Stok::where('produk_id', $produk->id)->lockForUpdate()->first();
            $keranjang = Keranjang::where('pembeli_id', $request->user()->id)
                ->where('status', 'aktif')->first();

            if (!$keranjang) {
                $keranjang = Keranjang::create([
                    'pembeli_id' => $request->user()->id, 'status' => 'aktif',
                ]);
            }

            $detail = DetailKeranjang::where('keranjang_id', $keranjang->id)
                ->where('produk_id', $produk->id)->first();

            $jumlahBaru = (int) $data['jumlah'] + (int) ($detail?->jumlah ?? 0);
            if ($jumlahBaru > (int) ($stok?->jumlah_stok ?? 0)) {
                throw ValidationException::withMessages(['jumlah' => 'Stok produk tidak mencukupi.']);
            }

            if ($detail) {
                $detail->update(['jumlah' => $jumlahBaru]);
            } else {
                DetailKeranjang::create([
                    'keranjang_id' => $keranjang->id,
                    'produk_id' => $produk->id,
                    'jumlah' => $jumlahBaru,
                ]);
            }
        });

        return redirect()->route('pembeli.keranjang')
            ->with('success', 'Produk berhasil masuk ke keranjang.');
    }

    public function ubah(Request $request, $detail)
    {
        $data = $request->validate(['jumlah' => 'required|integer|min:1']);

        DB::transaction(function () use ($request, $detail, $data) {
            $item = DetailKeranjang::whereKey($detail)
                ->whereHas('keranjang', fn ($query) => $query
                    ->where('pembeli_id', $request->user()->id)
                    ->where('status', 'aktif'))
                ->firstOrFail();

            $produk = Produk::whereKey($item->produk_id)->where('status', 'aktif')->first();
            if (!$produk) {
                throw ValidationException::withMessages(['jumlah' => 'Produk tidak tersedia.']);
            }

            $stok = Stok::where('produk_id', $item->produk_id)->lockForUpdate()->first();
            if ((int) $data['jumlah'] > (int) ($stok?->jumlah_stok ?? 0)) {
                throw ValidationException::withMessages(['jumlah' => 'Jumlah melebihi stok tersedia.']);
            }

            $item->update(['jumlah' => $data['jumlah']]);
        });

        return redirect()->route('pembeli.keranjang')
            ->with('success', 'Jumlah produk berhasil diperbarui.');
    }

    public function hapus(Request $request, $detail)
    {
        $item = DetailKeranjang::whereKey($detail)
            ->whereHas('keranjang', fn ($query) => $query
                ->where('pembeli_id', $request->user()->id)
                ->where('status', 'aktif'))
            ->firstOrFail();

        $item->delete();
        $request->session()->forget('checkout_selected_ids');

        return redirect()->route('pembeli.keranjang')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
