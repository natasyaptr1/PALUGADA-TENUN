<?php

namespace App\Http\Controllers\Pembeli;

use App\Enums\MetodePengiriman;
use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
use App\Services\PengirimanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /**
     * Konstruktor CheckoutController dengan injeksi PengirimanService.
     */
    public function __construct(
        protected PengirimanService $pengirimanService
    ) {}

    /**
     * Tampilkan halaman checkout pesanan untuk pembeli.
     */
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

        // Hitung subtotal dan berat total via PengirimanService
        $ringkasan = $this->pengirimanService->ringkasKeranjang($keranjang->detail);
        $subtotal = $ringkasan['subtotal'];
        $beratTotalGram = $ringkasan['berat_gram'];

        $pembayaranPerMetode = (array) config('ongkir.pembayaran_per_metode', [
            'ambil_di_tempat' => ['cash'],
            'ekspedisi'       => ['midtrans'],
        ]);

        $ongkirTersedia = !empty(config('ongkir.api_key')) && !empty(config('ongkir.asal_id'));

        return view('pembeli.checkout', compact(
            'keranjang',
            'total',
            'subtotal',
            'beratTotalGram',
            'pembayaranPerMetode',
            'ongkirTersedia'
        ));
    }

    /**
     * Simpan transaksi pesanan dan pembayaran baru.
     */
    public function simpan(Request $request)
    {
        // 1. Validasi input form checkout
        $data = $request->validate([
            'nama_penerima' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'regex:/^(?:\+62|62|0)8[0-9]{8,13}$/'],
            'metode_pengiriman' => ['required', Rule::enum(MetodePengiriman::class)],
            'metode' => ['required', 'in:cash,midtrans'],
            'alamat_pengiriman' => ['required_if:metode_pengiriman,ekspedisi', 'nullable', 'string', 'max:1000'],
            'tujuan_id' => ['required_if:metode_pengiriman,ekspedisi', 'nullable', 'string', 'max:30'],
            'tujuan_label' => ['nullable', 'string', 'max:255'],
            'kurir_kode' => ['required_if:metode_pengiriman,ekspedisi', 'nullable', 'string', 'max:30'],
            'layanan_kurir' => ['required_if:metode_pengiriman,ekspedisi', 'nullable', 'string', 'max:50'],
        ], [
            'nama_penerima.required' => 'Nama penerima wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'metode_pengiriman.required' => 'Metode pengiriman wajib dipilih.',
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'metode.in' => 'Metode pembayaran tidak valid.',
            'alamat_pengiriman.required_if' => 'Alamat pengiriman wajib diisi untuk pengiriman ekspedisi.',
            'tujuan_id.required_if' => 'Tujuan pengiriman wajib dipilih untuk pengiriman ekspedisi.',
            'kurir_kode.required_if' => 'Kurir pengiriman wajib dipilih untuk pengiriman ekspedisi.',
            'layanan_kurir.required_if' => 'Layanan kurir wajib dipilih untuk pengiriman ekspedisi.',
        ]);

        // 2. SEBELUM DB::transaction: ambil keranjang aktif tanpa lock dan siapkan rincian pengiriman
        $keranjangAwal = Keranjang::with('detail.produk')
            ->where('pembeli_id', $request->user()->id)
            ->where('status', 'aktif')
            ->first();

        if (!$keranjangAwal || $keranjangAwal->detail->isEmpty()) {
            throw ValidationException::withMessages([
                'keranjang' => 'Keranjang sudah diproses atau kosong.',
            ]);
        }

        $ringkasanAwal = $this->pengirimanService->ringkasKeranjang($keranjangAwal->detail);

        if ($ringkasanAwal['berat_gram'] <= 0 || $ringkasanAwal['subtotal'] <= 0) {
            throw ValidationException::withMessages([
                'keranjang' => 'Keranjang masih kosong.',
            ]);
        }

        // Panggil siapkan() SEBELUM DB::transaction (API kurir dipanggil di luar transaksi)
        $rincian = $this->pengirimanService->siapkan(
            $data,
            $ringkasanAwal['subtotal'],
            $ringkasanAwal['berat_gram']
        );

        // 3. DI DALAM DB::transaction: Kunci data, cek stok, pastikan harga dan subtotal cocok
        $pesanan = DB::transaction(function () use ($request, $data, $rincian) {
            // Mencegah checkout bersamaan dari akun yang sama
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

            $items = $keranjang->detail()->with('produk')->orderBy('produk_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Keranjang masih kosong.',
                ]);
            }

            $rincianPesanan = [];
            $subtotalTerkunci = 0.0;
            $beratTerkunci = 0;
            $beratDefault = (int) config('ongkir.berat_default_gram', 500);

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

                $beratSatuan = isset($produk->berat_gram) && (int) $produk->berat_gram > 0
                    ? (int) $produk->berat_gram
                    : $beratDefault;
                $beratTerkunci += ($beratSatuan * $item->jumlah);
            }

            $subtotalTerkunci = round($subtotalTerkunci, 2);

            // Validasi apakah isi keranjang atau harga berubah saat proses checkout
            if (!$rincian->cocokDengan($subtotalTerkunci, $beratTerkunci)) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Isi keranjang atau harga berubah. Silakan periksa ulang dan pilih pengiriman kembali.',
                ]);
            }

            // Gabungkan atribut pesanan dengan RincianPengiriman
            $atributPesanan = array_merge([
                'pembeli_id' => $request->user()->id,
                'keranjang_id' => $keranjang->id,
                'kode_pesanan' => 'PSN-' . strtoupper(Str::random(12)),
                'tanggal_pesanan' => now(),
                'status_pesanan' => 'menunggu_pembayaran',
                'nama_penerima' => $data['nama_penerima'],
                'no_hp' => $data['no_hp'],
            ], $rincian->keAtributPesanan());

            $pesanan = Pesanan::create($atributPesanan);

            $pesanan->detail()->createMany($rincianPesanan);

            // Buat record pembayaran
            $pesanan->pembayaran()->create([
                'metode' => $rincian->metodePembayaran,
                'jumlah_bayar' => $rincian->total,
                'status_pembayaran' => 'menunggu',
            ]);

            $keranjang->update(['status' => 'checkout']);

            return $pesanan;
        });

        // 4. Pesan sukses menyebut kode pesanan dan, jika ekspedisi, total termasuk ongkir
        $pesanSukses = 'Pesanan ' . $pesanan->kode_pesanan . ' berhasil dibuat.';
        if ($rincian->metodePengiriman === MetodePengiriman::Ekspedisi) {
            $pesanSukses .= ' Total bayar (termasuk ongkir): Rp ' . number_format($rincian->total, 0, ',', '.') . '.';
        }
        $pesanSukses .= ' Pembayaran masih menunggu.';

        return redirect()
            ->route('pembeli.pesanan')
            ->with('success', $pesanSukses);
    }
}
