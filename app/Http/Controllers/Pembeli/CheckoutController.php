<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $ids = array_values(array_unique(array_map('intval',
            (array) $request->session()->get('checkout_selected_ids', [])
        )));

        if (!$ids) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors(['keranjang' => 'Pilih produk yang ingin di-checkout dahulu.']);
        }

        $keranjang = Keranjang::with('detail.produk.stok')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')->first();

        if (!$keranjang) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors(['keranjang' => 'Keranjang aktif tidak ditemukan.']);
        }

        $terpilih = $keranjang->detail->whereIn('id', $ids)->values();

        if ($terpilih->count() !== count($ids)) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors(['keranjang' => 'Pilihan produk telah berubah. Pilih kembali.']);
        }

        $total = 0;

        foreach ($terpilih as $item) {
            $produk = $item->produk;

            if (!$produk || $produk->status !== 'aktif'
                || $item->jumlah > (int) ($produk->stok?->jumlah_stok ?? 0)) {
                return redirect()->route('pembeli.keranjang')
                    ->withErrors(['stok' => 'Ada produk terpilih yang tidak tersedia atau stoknya kurang.']);
            }

            $total += (float) $produk->harga * $item->jumlah;
        }

        // Hanya memfilter relasi untuk tampilan; database belum diubah.
        $keranjang->setRelation('detail', $terpilih);

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

        if ($data['metode'] === 'midtrans' &&
            !filter_var($request->user()->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Akun harus memiliki email valid untuk pembayaran Midtrans.',
            ]);
        }

        $ids = array_values(array_unique(array_map('intval',
            (array) $request->session()->get('checkout_selected_ids', [])
        )));

        if (!$ids) {
            throw ValidationException::withMessages([
                'keranjang' => 'Pilihan checkout tidak ditemukan. Kembali ke keranjang.',
            ]);
        }

        $pesanan = DB::transaction(function () use ($request, $data, $ids) {
            DB::table('pengguna')->where('id', $request->user()->id)
                ->lockForUpdate()->first();

            $keranjangAktif = Keranjang::where('pembeli_id', $request->user()->id)
                ->where('status', 'aktif')->lockForUpdate()->first();

            if (!$keranjangAktif) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Keranjang aktif tidak ditemukan.',
                ]);
            }

            $items = $keranjangAktif->detail()->whereIn('id', $ids)
                ->orderBy('produk_id')->lockForUpdate()->get();

            if ($items->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Sebagian produk sudah berubah. Pilih ulang produk.',
                ]);
            }

            $rincian = [];
            $total = 0;

            foreach ($items as $item) {
                $produk = Produk::whereKey($item->produk_id)
                    ->where('status', 'aktif')->first();

                if (!$produk) {
                    throw ValidationException::withMessages([
                        'produk' => 'Ada produk terpilih yang tidak tersedia.',
                    ]);
                }

                $stok = Stok::where('produk_id', $produk->id)
                    ->lockForUpdate()->first();

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

            // Skema PALUGADA mengaitkan satu keranjang dengan satu pesanan.
            // Keranjang baru menampung item terpilih; item lain tetap aktif.
            $keranjangCheckout = Keranjang::create([
                'pembeli_id' => $request->user()->id,
                'status' => 'checkout',
            ]);

            $pesanan = Pesanan::create([
                'pembeli_id' => $request->user()->id,
                'keranjang_id' => $keranjangCheckout->id,
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

            $keranjangAktif->detail()->whereIn('id', $ids)
                ->update(['keranjang_id' => $keranjangCheckout->id]);

            return $pesanan;
        });

        $request->session()->forget('checkout_selected_ids');

        if ($data['metode'] === 'midtrans') {
            try {
                $totalHarga = (int) round((float) $pesanan->total);

                if (abs((float) $pesanan->total - $totalHarga) > 0.001) {
                    throw new \RuntimeException('Total pesanan harus rupiah bulat.');
                }

                                
                $transaksi = app(MidtransService::class)->buatSnapTransaction(
                    $pesanan->kode_pesanan,
                    $totalHarga,
                    $data['nama_penerima'],
                    $request->user()->email
                );

                $paymentUrl = $transaksi['redirect_url'];
                $snapToken = $transaksi['token'];


                // TAMBAHAN: Simpan sesi Midtrans untuk tombol Bayar Sekarang.
                $pesanan->pembayaran()
                    ->where('metode', 'midtrans')
                    ->latest('id')
                    ->firstOrFail()
                    ->update([
                        'midtrans_order_id' => $pesanan->kode_pesanan,
                        'snap_token' => $snapToken,
                        'snap_redirect_url' => $paymentUrl,
                        'snap_expires_at' => now()->addDay(),
                    ]);

                return redirect()->away($paymentUrl);

            } catch (\Throwable $e) {
                Log::error('Gagal membuat transaksi Midtrans', [
                    'pesanan_id' => $pesanan->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('pembeli.pesanan')->withErrors([
                    'midtrans' => 'Pesanan berhasil dibuat, tetapi halaman pembayaran belum dapat dibuka. Hubungi admin.',
                ]);
            }
        }

        return redirect()->route('pembeli.pesanan')->with(
            'success',
            'Pesanan '.$pesanan->kode_pesanan.' berhasil dibuat. Pembayaran masih menunggu.'
        );
    }
}
