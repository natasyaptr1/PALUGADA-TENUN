<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    public function lanjutkan(Request $request, Pesanan $pesanan)
    {
        abort_unless(
            (int) $pesanan->pembeli_id === (int) $request->user()->id,
            403
        );

        $pembayaran = $pesanan->pembayaran()
            ->latest('id')
            ->first();

        if (!$pembayaran || $pembayaran->metode !== 'midtrans') {
            return back()->withErrors([
                'pembayaran' => 'Metode pembayaran tidak tersedia.',
            ]);
        }

        if (
            $pesanan->status_pesanan !== 'menunggu_pembayaran' ||
            $pembayaran->status_pembayaran !== 'menunggu'
        ) {
            return back()->withErrors([
                'pembayaran' => 'Pesanan tidak dapat dilanjutkan pembayarannya.',
            ]);
        }

        if (
            !$pembayaran->snap_redirect_url ||
            !$pembayaran->snap_expires_at
        ) {
            return back()->withErrors([
                'pembayaran' => 'Sesi pembayaran belum tersedia.',
            ]);
        }

        if ($pembayaran->snap_expires_at->isPast()) {
            return back()->withErrors([
                'pembayaran' => 'Sesi pembayaran sudah kedaluwarsa.',
            ]);
        }

        $url = $pembayaran->snap_redirect_url;

        if (
            parse_url($url, PHP_URL_SCHEME) !== 'https' ||
            !in_array(parse_url($url, PHP_URL_HOST), [
                'app.sandbox.midtrans.com',
                'app.midtrans.com',
            ], true)
        ) {
            return back()->withErrors([
                'pembayaran' => 'URL pembayaran tidak valid.',
            ]);
        }

        return redirect()->away($url);
    }
}
