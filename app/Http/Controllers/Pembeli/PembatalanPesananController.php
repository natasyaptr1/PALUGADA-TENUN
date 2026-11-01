<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Midtrans\Transaction;

class PembatalanPesananController extends Controller
{
    public function batalkan(Request $request, Pesanan $pesanan)
    {
        abort_unless(
            (int) $pesanan->pembeli_id === (int) $request->user()->id,
            403
        );

        $pembayaran = $pesanan->pembayaran()
            ->latest('id')
            ->first();

        if (
            $pesanan->status_pesanan !== 'menunggu_pembayaran' ||
            !$pembayaran ||
            $pembayaran->status_pembayaran !== 'menunggu'
        ) {
            return back()->withErrors([
                'pesanan' => 'Pesanan ini tidak dapat dibatalkan.',
            ]);
        }

        if ($pembayaran->metode === 'midtrans') {
            try {
                app(MidtransService::class);

                $orderId = $pembayaran->midtrans_order_id
                    ?: $pesanan->kode_pesanan;

                try {
                    $hasil = Transaction::status($orderId);
                    $status = $hasil->transaction_status ?? null;
           
                    if ($status === 'pending') {
                        $batal = Transaction::cancel($orderId);

                        if ((string) $batal !== '200') {
                            throw new \RuntimeException(
                                'Midtrans belum mengonfirmasi pembatalan.'
                            );
                        }
                    }


                } catch (\Throwable $e) {
                    // Status 404 berarti transaksi Core API belum ditemukan.
                    // Ini TIDAK berarti sesi Snap sudah tidak aktif.
                    if (!preg_match(
                        '/HTTP status code:\s*404\b/',
                        $e->getMessage()
                    )) {
                        throw $e;
                    }

                    $url = $pembayaran->snap_redirect_url;

                    if (!$url) {
                        throw new \RuntimeException(
                            'URL sesi Snap tidak tersedia untuk dibatalkan.'
                        );
                    }

                    $host = parse_url($url, PHP_URL_HOST);
                    $path = parse_url($url, PHP_URL_PATH);
                    $scheme = parse_url($url, PHP_URL_SCHEME);

                    $isProduction = (bool) config(
                        'midtrans.is_production',
                        false
                    );

                    $expectedHost = $isProduction
                        ? 'app.midtrans.com'
                        : 'app.sandbox.midtrans.com';

                    if (
                        $scheme !== 'https' ||
                        $host !== $expectedHost ||
                        !is_string($path)
                    ) {
                        throw new \RuntimeException(
                            'URL sesi Snap tidak valid.'
                        );
                    }

                    $token = $pembayaran->snap_token;

                    if (
                        !$token ||
                        !preg_match('/^[A-Za-z0-9_-]+$/', $token)
                    ) {
                        throw new \RuntimeException(
                            'Token sesi Snap tidak valid.'
                        );
                    }

                    $serverKey = config('midtrans.server_key');

                    if (!$serverKey) {
                        throw new \RuntimeException(
                            'Server Key Midtrans belum dikonfigurasi.'
                        );
                    }

                    $endpoint = 'https://' . $expectedHost
                        . '/snap/v1/transactions/'
                        . rawurlencode($token)
                        . '/cancel';

                    // PERBAIKAN: Authorization khusus pembatalan sesi Snap.
                    $response = Http::withHeaders([
                        'Authorization' => $serverKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                        ->timeout(15)
                        ->post($endpoint);

                    if (
                        !$response->successful() ||
                        !$response->json('canceled_at')
                    ) {
                        
                Log::warning(
                    'Pembatalan sesi Snap belum dikonfirmasi',
                    [
                        'pesanan_id' => $pesanan->id,
                        'http_status' => $response->status(),
                        'error_messages' => $response->json('error_messages'),
                        'key_configured' => !empty(config('midtrans.server_key')),
                        'is_production' => (bool) config('midtrans.is_production'),
                    ]
                );


                        throw new \RuntimeException(
                            'Midtrans belum mengonfirmasi pembatalan sesi Snap.'
                        );
                    }
                }

            } catch (\Throwable $e) {
                Log::warning(
                    'Pembatalan pesanan Midtrans gagal',
                    [
                        'pesanan_id' => $pesanan->id,
                        'error' => $e->getMessage(),
                    ]
                );

                return back()->withErrors([
                    'pesanan' =>
                        'Pembatalan belum berhasil diverifikasi oleh Midtrans. ' .
                        'Pesanan tetap menunggu agar tidak terjadi kesalahan pembayaran.',
                ]);
            }
        }

        $berhasil = DB::transaction(function () use ($pesanan) {
            $pesananTerkunci = Pesanan::whereKey($pesanan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pembayaranTerkunci = $pesananTerkunci
                ->pembayaran()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (
                $pesananTerkunci->status_pesanan !== 'menunggu_pembayaran' ||
                !$pembayaranTerkunci ||
                $pembayaranTerkunci->status_pembayaran !== 'menunggu'
            ) {
                return false;
            }

            $pesananTerkunci->update([
                'status_pesanan' => 'batal',
            ]);

            $pembayaranTerkunci->update([
                'status_pembayaran' => 'gagal',
                'snap_redirect_url' => null,
                'snap_expires_at' => null,
            ]);

            return true;
        });

        if (!$berhasil) {
            return back()->withErrors([
                'pesanan' =>
                    'Status pesanan sudah berubah. Silakan muat ulang halaman.',
            ]);
        }

        return redirect()
            ->route('pembeli.pesanan')
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }
}
