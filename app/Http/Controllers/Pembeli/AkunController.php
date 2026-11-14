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

    public function keranjang(): View
    {
        return view('pembeli.keranjang');
    }
}
