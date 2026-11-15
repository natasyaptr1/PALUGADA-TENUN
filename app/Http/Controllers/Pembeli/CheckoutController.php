<?php

namespace App\Http\Controllers\Pembeli;

use App\Enums\MetodePengiriman;
use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
use App\Services\MidtransService;
use App\Services\PengirimanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected PengirimanService $pengirimanService
    ) {}

    public function index(Request $request)
    {
        $ids = $this->ambilIdTerpilih($request);

        if (empty($ids)) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors([
                    'keranjang' => 'Pilih produk yang ingin di-checkout dahulu.',
                ]);
        }

        $keranjang = Keranjang::with('detail.produk.stok')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjang) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors([
                    'keranjang' => 'Keranjang aktif tidak ditemukan.',
                ]);
        }

        $terpilih = $keranjang->detail
            ->whereIn('id', $ids)
            ->values();

        if ($terpilih->count() !== count($ids)) {
            return redirect()->route('pembeli.keranjang')
                ->withErrors([
                    'keranjang' => 'Pilihan produk berubah. Pilih kembali.',
                ]);
        }

        foreach ($terpilih as $item) {
            $produk = $item->produk;

            if (
                !$produk ||
                $produk->status !== 'aktif' ||
                $item->jumlah > (int) ($produk->stok?->jumlah_stok ?? 0)
            ) {
                return redirect()->route('pembeli.keranjang')
                    ->withErrors([
                        'stok' => 'Ada produk yang tidak tersedia atau stoknya kurang.',
                    ]);
            }
        }

        // Hanya tampilkan produk yang dipilih.
        $keranjang->setRelation('detail', $terpilih);

        // Subtotal dan berat dihitung oleh layanan pengiriman.
        $ringkasan = $this->pengirimanService
            ->ringkasKeranjang($terpilih);

        $subtotal = $ringkasan['subtotal'];
        $total = $subtotal;
        $beratTotalGram = $ringkasan['berat_gram'];

        $pembayaranPerMetode = (array) config(
            'ongkir.pembayaran_per_metode',
            [
                'ambil_di_tempat' => ['cash'],
                'ekspedisi' => ['midtrans'],
            ]
        );

        $ongkirTersedia =
            !empty(config('ongkir.api_key')) &&
            !empty(config('ongkir.asal_id'));

        return view('pembeli.checkout', compact(
            'keranjang',
            'total',
            'subtotal',
            'beratTotalGram',
            'pembayaranPerMetode',
            'ongkirTersedia'
        ));
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'nama_penerima' => [
                'required',
                'string',
                'max:100',
            ],
            'no_hp' => [
                'required',
                'string',
                'regex:/^(?:\+62|62|0)8[0-9]{8,13}$/',
            ],
            'metode_pengiriman' => [
                'required',
                Rule::enum(MetodePengiriman::class),
            ],
            'metode' => [
                'required',
                'in:cash,midtrans',
            ],
            'alamat_pengiriman' => [
                'required_if:metode_pengiriman,ekspedisi',
                'nullable',
                'string',
                'max:1000',
            ],
            'tujuan_id' => [
                'required_if:metode_pengiriman,ekspedisi',
                'nullable',
                'string',
                'max:30',
            ],
            'tujuan_label' => [
                'nullable',
                'string',
                'max:255',
            ],
            'kurir_kode' => [
                'required_if:metode_pengiriman,ekspedisi',
                'nullable',
                'string',
                'max:30',
            ],
            'layanan_kurir' => [
                'required_if:metode_pengiriman,ekspedisi',
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'nama_penerima.required' => 'Nama penerima wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'metode_pengiriman.required' => 'Metode pengiriman wajib dipilih.',
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'alamat_pengiriman.required_if' => 'Alamat pengiriman wajib diisi.',
            'tujuan_id.required_if' => 'Tujuan pengiriman wajib dipilih.',
            'kurir_kode.required_if' => 'Kurir wajib dipilih.',
            'layanan_kurir.required_if' => 'Layanan kurir wajib dipilih.',
        ]);

        if (
            $data['metode'] === 'midtrans' &&
            !filter_var($request->user()->email, FILTER_VALIDATE_EMAIL)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Akun harus memiliki email valid untuk pembayaran Midtrans.',
            ]);
        }

        $ids = $this->ambilIdTerpilih($request);

        if (empty($ids)) {
            throw ValidationException::withMessages([
                'keranjang' => 'Pilihan checkout tidak ditemukan.',
            ]);
        }

        // Ambil hanya produk terpilih sebelum memanggil API ongkir.
        $keranjangAwal = Keranjang::with('detail.produk')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjangAwal) {
            throw ValidationException::withMessages([
                'keranjang' => 'Keranjang aktif tidak ditemukan.',
            ]);
        }

        $terpilih = $keranjangAwal->detail
            ->whereIn('id', $ids)
            ->values();

        if (
            $terpilih->isEmpty() ||
            $terpilih->count() !== count($ids)
        ) {
            throw ValidationException::withMessages([
                'keranjang' => 'Produk pilihan sudah berubah. Pilih kembali.',
            ]);
        }

        $ringkasanAwal = $this->pengirimanService
            ->ringkasKeranjang($terpilih);

        if (
            $ringkasanAwal['subtotal'] <= 0 ||
            $ringkasanAwal['berat_gram'] <= 0
        ) {
            throw ValidationException::withMessages([
                'keranjang' => 'Subtotal atau berat produk tidak valid.',
            ]);
        }

        // Perhitungan API ongkir dilakukan sebelum transaksi database.
        $rincian = $this->pengirimanService->siapkan(
            $data,
            $ringkasanAwal['subtotal'],
            $ringkasanAwal['berat_gram']
        );

        $pesanan = DB::transaction(function () use (
            $request,
            $data,
            $ids,
            $rincian
        ) {
            // Mencegah checkout bersamaan dari pembeli yang sama.
            DB::table('pengguna')
                ->where('id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            $keranjangAktif = Keranjang::query()
                ->where('pembeli_id', $request->user()->id)
                ->where('status', 'aktif')
                ->lockForUpdate()
                ->first();

            if (!$keranjangAktif) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Keranjang aktif tidak ditemukan.',
                ]);
            }

            $items = $keranjangAktif->detail()
                ->whereIn('id', $ids)
                ->orderBy('produk_id')
                ->lockForUpdate()
                ->get();

            if ($items->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Produk pilihan berubah. Pilih ulang.',
                ]);
            }

            $rincianPesanan = [];
            $subtotalTerkunci = 0.0;
            $beratTerkunci = 0;

            $beratDefault = (int) config(
                'ongkir.berat_default_gram',
                500
            );

            foreach ($items as $item) {
                $produk = Produk::query()
                    ->whereKey($item->produk_id)
                    ->where('status', 'aktif')
                    ->first();

                if (!$produk) {
                    throw ValidationException::withMessages([
                        'produk' => 'Produk tidak tersedia.',
                    ]);
                }

                $stok = Stok::query()
                    ->where('produk_id', $produk->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$stok ||
                    $item->jumlah > $stok->jumlah_stok
                ) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok ' . $produk->nama_produk . ' tidak mencukupi.',
                    ]);
                }

                $harga = (float) $produk->harga;
                $itemSubtotal = $harga * $item->jumlah;

                $rincianPesanan[] = [
                    'produk_id' => $produk->id,
                    'jumlah' => $item->jumlah,
                    'harga' => $harga,
                    'subtotal' => $itemSubtotal,
                ];

                $subtotalTerkunci += $itemSubtotal;

                $beratSatuan = (int) ($produk->berat_gram ?? 0);

                if ($beratSatuan <= 0) {
                    $beratSatuan = $beratDefault;
                }

                $beratTerkunci +=
                    $beratSatuan * $item->jumlah;
            }

            $subtotalTerkunci = round($subtotalTerkunci, 2);

            // Tolak jika harga, jumlah, atau berat berubah.
            if (!$rincian->cocokDengan(
                $subtotalTerkunci,
                $beratTerkunci
            )) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Produk, harga, atau berat berubah. Silakan pilih pengiriman kembali.',
                ]);
            }

            // Satu pesanan menggunakan satu keranjang checkout.
            // Produk yang tidak dipilih tetap di keranjang aktif.
            $keranjangCheckout = Keranjang::create([
                'pembeli_id' => $request->user()->id,
                'status' => 'checkout',
            ]);

            $atributPesanan = array_merge([
                'pembeli_id' => $request->user()->id,
                'keranjang_id' => $keranjangCheckout->id,
                'kode_pesanan' => 'PSN-' . strtoupper(
                    Str::random(12)
                ),
                'tanggal_pesanan' => now(),
                'status_pesanan' => 'menunggu_pembayaran',
                'nama_penerima' => $data['nama_penerima'],
                'no_hp' => $data['no_hp'],
            ], $rincian->keAtributPesanan());

            $pesanan = Pesanan::create($atributPesanan);

            $pesanan->detail()->createMany($rincianPesanan);

            $pesanan->pembayaran()->create([
                'metode' => $rincian->metodePembayaran,
                'jumlah_bayar' => $rincian->total,
                'status_pembayaran' => 'menunggu',
            ]);

            // Pindahkan HANYA item yang dipilih.
            $keranjangAktif->detail()
                ->whereIn('id', $ids)
                ->update([
                    'keranjang_id' => $keranjangCheckout->id,
                ]);

            return $pesanan;
        });

        // Pilihan checkout dihapus setelah transaksi berhasil.
        $request->session()->forget('checkout_selected_ids');

        // Midtrans menggunakan total akhir termasuk ongkir.
        if ($data['metode'] === 'midtrans') {
            try {
                $totalHarga = (int) round(
                    (float) $pesanan->total
                );

                if (
                    abs((float) $pesanan->total - $totalHarga)
                    > 0.001
                ) {
                    throw new \RuntimeException(
                        'Total pesanan harus rupiah bulat.'
                    );
                }

                $transaksi = app(MidtransService::class)
                    ->buatSnapTransaction(
                        $pesanan->kode_pesanan,
                        $totalHarga,
                        $data['nama_penerima'],
                        $request->user()->email
                    );

                $paymentUrl = $transaksi['redirect_url'];
                $snapToken = $transaksi['token'];

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
                Log::error(
                    'Gagal membuat transaksi Midtrans saat checkout',
                    [
                        'pesanan_id' => $pesanan->id,
                        'error' => $e->getMessage(),
                    ]
                );

                return redirect()
                    ->route('pembeli.pesanan')
                    ->withErrors([
                        'midtrans' => 'Pesanan berhasil dibuat, tetapi pembayaran belum dapat dibuka. Periksa kembali status pesanan.',
                    ]);
            }
        }

        $pesanSukses = 'Pesanan '
            . $pesanan->kode_pesanan
            . ' berhasil dibuat.';

        if (
            $rincian->metodePengiriman ===
            MetodePengiriman::Ekspedisi
        ) {
            $pesanSukses .=
                ' Total termasuk ongkir: Rp '
                . number_format(
                    $rincian->total,
                    0,
                    ',',
                    '.'
                )
                . '.';
        }

        $pesanSukses .= ' Pembayaran masih menunggu.';

        return redirect()
            ->route('pembeli.pesanan')
            ->with('success', $pesanSukses);
    }

    private function ambilIdTerpilih(Request $request): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                'intval',
                (array) $request->session()->get(
                    'checkout_selected_ids',
                    []
                )
            ),
            fn ($id) => $id > 0
        )));
    }
}
