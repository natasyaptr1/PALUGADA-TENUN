<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function beranda(): RedirectResponse
    {
        return redirect()->route('pembeli.profil');
    }

    public function profil(Request $request): View
    {
        return view('pembeli.profil', ['pengguna' => $request->user()]);
    }

    public function perbaruiProfil(Request $request): RedirectResponse
    {
        $pengguna = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('pengguna', 'email')->ignore($pengguna->id)],
            'no_hp' => ['nullable', 'string', 'regex:/^(?:\+62|62|0)8[0-9]{8,13}$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum sesuai.',
            'email.unique' => 'Email ini sudah digunakan akun lain.',
            'no_hp.regex' => 'Format nomor HP tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890 atau +628123456789).',
        ]);

        $pengguna->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function pesanan(Request $request): View
    {
        $daftarPesanan = $request->user()
            ->pesanan()
            ->with('detail.produk', 'pembayaran')
            ->latest('tanggal_pesanan')
            ->paginate(10);

        return view('pembeli.pesanan', compact('daftarPesanan'));
    }

    /**
     * Membuat Snap Token Midtrans untuk pesanan pembeli yang menunggu pembayaran online.
     */
    public function bayar(Request $request, \App\Models\Pesanan $pesanan, \App\Services\MidtransService $midtransService): \Illuminate\Http\JsonResponse
    {
        if ($pesanan->pembeli_id !== $request->user()->id) {
            abort(403);
        }

        if ($pesanan->status_pesanan !== 'menunggu_pembayaran') {
            return response()->json(['message' => 'Pesanan ini tidak menunggu pembayaran.'], 422);
        }

        $pembayaran = $pesanan->pembayaran()->orderByDesc('id')->first();
        if (!$pembayaran || $pembayaran->metode !== 'midtrans') {
            return response()->json(['message' => 'Metode pembayaran bukan Midtrans.'], 422);
        }

        try {
            $snapToken = $midtransService->buatSnapToken(
                $pesanan->kode_pesanan,
                (int) $pesanan->total,
                $pesanan->nama_penerima ?: $request->user()->nama,
                $request->user()->email
            );

            return response()->json([
                'snap_token' => $snapToken,
                'kode_pesanan' => $pesanan->kode_pesanan,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Memeriksa status transaksi langsung ke Midtrans API (sangat berguna terutama di localhost tanpa webhook publik).
     */
    public function cekStatus(Request $request, \App\Models\Pesanan $pesanan, \App\Services\MidtransService $midtransService): \Illuminate\Http\JsonResponse
    {
        if ($pesanan->pembeli_id !== $request->user()->id) {
            abort(403);
        }

        if ($pesanan->status_pesanan === 'dibayar') {
            return response()->json(['status' => 'dibayar', 'message' => 'Pesanan sudah berstatus Dibayar.']);
        }

        try {
            $status = \Midtrans\Transaction::status($pesanan->kode_pesanan);
            $trxStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;

            $berhasil = ($status->status_code ?? '') === '200'
                && ($trxStatus === 'settlement' || ($trxStatus === 'capture' && $fraudStatus === 'accept'))
                && $fraudStatus !== 'deny'
                && $fraudStatus !== 'challenge';

            if ($berhasil) {
                $pembayaran = $pesanan->pembayaran()->orderByDesc('id')->first();
                if ($pembayaran) {
                    $pembayaran->update([
                        'id_transaksi' => $status->transaction_id ?? $pembayaran->id_transaksi,
                        'tipe_pembayaran' => $status->payment_type ?? $pembayaran->tipe_pembayaran,
                        'status_pembayaran' => 'berhasil',
                        'tanggal_bayar' => now(),
                    ]);
                }

                $pesanan->update(['status_pesanan' => 'dibayar']);
                \App\Events\PembayaranBerhasil::dispatch($pesanan->fresh());

                return response()->json([
                    'status' => 'dibayar',
                    'message' => 'Pembayaran berhasil dikonfirmasi dari Midtrans!',
                ]);
            }

            return response()->json([
                'status' => $trxStatus ?: 'menunggu',
                'message' => 'Status pembayaran di Midtrans: ' . ($trxStatus ?: 'menunggu pembayaran'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada transaksi di Midtrans atau terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function keranjang(): View
    {
        return view('pembeli.keranjang');
    }
}
