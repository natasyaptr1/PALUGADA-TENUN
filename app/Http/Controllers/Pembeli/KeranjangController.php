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
        $keranjang = Keranjang::with('detail.produk.stok')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        $total = 0;

        if ($keranjang) {
            foreach ($keranjang->detail as $item) {
                $total += (float) ($item->produk?->harga ?? 0)
                    * $item->jumlah;
            }
        }

        return view('pembeli.keranjang', compact(
            'keranjang',
            'total'
        ));
    }

    public function tambah(Request $request)
    {
        $data = $request->validate([
            'produk_id' => 'required|integer|exists:produk,id',
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $data) {
            Pengguna::whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $produk = Produk::whereKey($data['produk_id'])
                ->where('status', 'aktif')
                ->first();

            if (!$produk) {
                throw ValidationException::withMessages([
                    'produk_id' => 'Produk tidak tersedia.',
                ]);
            }

            $stok = Stok::where('produk_id', $produk->id)
                ->lockForUpdate()
                ->first();

            $keranjang = Keranjang::where(
                'pembeli_id',
                $request->user()->id
            )->where('status', 'aktif')->first();

            if (!$keranjang) {
                $keranjang = Keranjang::create([
                    'pembeli_id' => $request->user()->id,
                    'status' => 'aktif',
                ]);
            }

            $detail = DetailKeranjang::where(
                'keranjang_id',
                $keranjang->id
            )->where('produk_id', $produk->id)
                ->first();

            $jumlahBaru = (int) $data['jumlah']
                + (int) ($detail?->jumlah ?? 0);

            if ($jumlahBaru > (int) ($stok?->jumlah_stok ?? 0)) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Stok produk tidak mencukupi.',
                ]);
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

        return redirect()
            ->route('pembeli.keranjang')
            ->with('success', 'Produk berhasil masuk ke keranjang.');
    }

    public function ubah(Request $request, $detail)
    {
        $data = $request->validate([
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $detail, $data) {
            $item = DetailKeranjang::whereKey($detail)
                ->whereHas('keranjang', function ($query) use ($request) {
                    $query->where('pembeli_id', $request->user()->id)
                        ->where('status', 'aktif');
                })
                ->firstOrFail();

            $produk = Produk::whereKey($item->produk_id)
                ->where('status', 'aktif')
                ->first();

            if (!$produk) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Produk tidak tersedia.',
                ]);
            }

            $stok = Stok::where('produk_id', $item->produk_id)
                ->lockForUpdate()
                ->first();

            if ((int) $data['jumlah'] > (int) ($stok?->jumlah_stok ?? 0)) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah melebihi stok tersedia.',
                ]);
            }

            $item->update([
                'jumlah' => $data['jumlah'],
            ]);
        });

        return redirect()
            ->route('pembeli.keranjang')
            ->with('success', 'Jumlah produk berhasil diperbarui.');
    }

    public function hapus(Request $request, $detail)
    {
        $item = DetailKeranjang::whereKey($detail)
            ->whereHas('keranjang', function ($query) use ($request) {
                $query->where('pembeli_id', $request->user()->id)
                    ->where('status', 'aktif');
            })
            ->firstOrFail();

        $item->delete();

        return redirect()
            ->route('pembeli.keranjang')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
