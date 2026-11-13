<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $keranjang = Keranjang::with('detail.produk.stok')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjang || $keranjang->detail->isEmpty()) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors(['keranjang' => 'Keranjang masih kosong.']);
        }

        $total = 0;

        foreach ($keranjang->detail as $item) {
            $produk = $item->produk;

            if (
                !$produk ||
                $produk->status !== 'aktif' ||
                $item->jumlah > (int) ($produk->stok?->jumlah_stok ?? 0)
            ) {
                return redirect()->route('pembeli.keranjang')
                    ->withErrors(['stok' => 'Ada produk yang stoknya tidak mencukupi.']);
            }

            $total += (float) $produk->harga * $item->jumlah;
        }

        return view('pembeli.checkout', compact('keranjang', 'total'));
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'nama_penerima' => 'required|string|max:100',
            'no_hp' => ['required', 'string', 'regex:/^(?:\+62|62|0)8[0-9]{8,13}$/'],
            'alamat_pengiriman' => 'required|string|max:1000',
            'metode' => 'required|in:cash,midtrans',
        ]);

        $pesanan = DB::transaction(function () use ($request, $data) {
            // Mencegah checkout bersamaan dari akun yang sama.
            DB::table('pengguna')
                ->where('id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            $keranjang = Keranjang::where('pembeli_id', $request->user()->id)
                ->where('status', 'aktif')
                ->lockForUpdate()
                ->first();

            if (!$keranjang) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Keranjang sudah diproses atau kosong.',
                ]);
            }

            $items = $keranjang->detail()->orderBy('produk_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Keranjang masih kosong.',
                ]);
            }

            $rincian = [];
            $total = 0;

            foreach ($items as $item) {
                $produk = Produk::whereKey($item->produk_id)
                    ->where('status', 'aktif')
                    ->first();

                if (!$produk) {
                    throw ValidationException::withMessages([
                        'produk' => 'Ada produk yang tidak tersedia.',
                    ]);
                }

                $stok = Stok::where('produk_id', $produk->id)
                    ->lockForUpdate()
                    ->first();

                if (!$stok || $item->jumlah > $stok->jumlah_stok) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok '.$produk->nama_produk.' tidak mencukupi.',
                    ]);
                }

                $harga = (float) $produk->harga;
                $subtotal = $harga * $item->jumlah;

                $rincian[] = [
                    'produk_id' => $produk->id,
                    'jumlah' => $item->jumlah,
                    'harga' => $harga,
                    'subtotal' => $subtotal,
                ];

                $total += $subtotal;
            }

            $pesanan = Pesanan::create([
                'pembeli_id' => $request->user()->id,
                'keranjang_id' => $keranjang->id,
                'kode_pesanan' => 'PSN-'.strtoupper(Str::random(12)),
                'tanggal_pesanan' => now(),
                'total' => $total,
                'status_pesanan' => 'menunggu_pembayaran',
                'nama_penerima' => $data['nama_penerima'],
                'no_hp' => $data['no_hp'],
                'alamat_pengiriman' => $data['alamat_pengiriman'],
            ]);

            $pesanan->detail()->createMany($rincian);

            $pesanan->pembayaran()->create([
                'metode' => $data['metode'],
                'jumlah_bayar' => $total,
                'status_pembayaran' => 'menunggu',
            ]);

            $keranjang->update(['status' => 'checkout']);

            return $pesanan;
        });

        return redirect()
            ->route('pembeli.pesanan')
            ->with(
                'success',
                'Pesanan '.$pesanan->kode_pesanan.' berhasil dibuat. Pembayaran masih menunggu.'
            );
    }
}
