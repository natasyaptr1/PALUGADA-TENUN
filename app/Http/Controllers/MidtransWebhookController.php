<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required'],
            'signature_key' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'transaction_id' => ['nullable', 'string'],
            'payment_type' => ['nullable', 'string'],
            'fraud_status' => ['nullable', 'string'],
        ]);

        $serverKey = config('midtrans.server_key');

        if (!$serverKey) {
            return response()->json(
                ['message' => 'Konfigurasi Midtrans belum tersedia.'],
                500
            );
        }

        $signature = hash(
            'sha512',
            $data['order_id']
                . $data['status_code']
                . $data['gross_amount']
                . $serverKey
        );

        if (!hash_equals($signature, $data['signature_key'])) {
            return response()->json(
                ['message' => 'Signature Midtrans tidak valid.'],
                403
            );
        }

        $status = $data['transaction_status'];
        $fraudStatus = $data['fraud_status'] ?? null;

        $berhasil = $data['status_code'] === '200'
            && (
                $status === 'settlement'
                || (
                    $status === 'capture'
                    && $fraudStatus === 'accept'
                )
            )
            && $fraudStatus !== 'deny'
            && $fraudStatus !== 'challenge';

        $statusBaru = match (true) {
            $berhasil => 'berhasil',
            in_array($status, ['deny', 'cancel', 'failure'], true)
                => 'gagal',
            $status === 'expire' => 'kedaluwarsa',
            default => 'menunggu',
        };

        $hasil = DB::transaction(function () use (
            $data,
            $statusBaru
        ) {
            $pesanan = Pesanan::where(
                'kode_pesanan',
                $data['order_id']
            )->lockForUpdate()->first();

            if (!$pesanan) {
                return 'tidak_ditemukan';
            }

            // Bandingkan nominal sampai dua angka desimal.
            $nominalPesanan = number_format(
                (float) $pesanan->total,
                2,
                '.',
                ''
            );

            $nominalMidtrans = (string) $data['gross_amount'];

            if (!is_numeric($nominalMidtrans)
                || bccomp(
                    $nominalPesanan,
                    $nominalMidtrans,
                    2
                ) !== 0) {
                return 'nominal_tidak_cocok';
            }

            $pembayaran = Pembayaran::where(
                'pesanan_id',
                $pesanan->id
            )->lockForUpdate()->first();

            if (!$pembayaran || $pembayaran->metode !== 'midtrans') {
                return 'pembayaran_tidak_ditemukan';
            }

            // Notifikasi duplikat tidak boleh memproses ulang
            // pembayaran yang sudah berhasil.
            if ($pembayaran->status_pembayaran === 'berhasil') {
                return 'sudah_berhasil';
            }

            if (
                $pesanan->status_pesanan === 'batal'
                && $statusBaru === 'berhasil'
            ) {
                return 'perlu_peninjauan';
            }

            // Jangan mengembalikan transaksi terminal
            // ke status menunggu akibat notifikasi lama.
            if (
                in_array(
                    $pembayaran->status_pembayaran,
                    ['gagal', 'kedaluwarsa'],
                    true
                )
                && $statusBaru === 'menunggu'
            ) {
                return 'diabaikan';
            }

            $pembayaran->update([
                'id_transaksi' => $data['transaction_id']
                    ?? $pembayaran->id_transaksi,
                'tipe_pembayaran' => $data['payment_type']
                    ?? $pembayaran->tipe_pembayaran,
                'status_pembayaran' => $statusBaru,
                'tanggal_bayar' => $statusBaru === 'berhasil'
                    ? now()
                    : null,
            ]);

            if ($statusBaru === 'berhasil') {
                $pesanan->update([
                    'status_pesanan' => 'dibayar',
                ]);
            }

            return 'diproses';
        });

        if ($hasil === 'tidak_ditemukan') {
            return response()->json(
                ['message' => 'Pesanan tidak ditemukan.'],
                404
            );
        }

        if (in_array(
            $hasil,
            [
                'nominal_tidak_cocok',
                'pembayaran_tidak_ditemukan',
            ],
            true
        )) {
            return response()->json(
                ['message' => 'Data pembayaran tidak cocok.'],
                422
            );
        }

        if ($hasil === 'perlu_peninjauan') {
            return response()->json(
                ['message' => 'Pesanan dibatalkan, perlu pemeriksaan.'],
                409
            );
        }

        return response()->json([
            'message' => 'Notifikasi diterima.',
            'hasil' => $hasil,
        ]);
    }
}
