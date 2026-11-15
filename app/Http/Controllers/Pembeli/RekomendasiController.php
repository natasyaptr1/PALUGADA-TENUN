<?php

namespace App\Http\Controllers\Pembeli;

use App\Http\Controllers\Controller;
use App\Services\RekomendasiProdukService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekomendasiController extends Controller
{
    public function index(
        Request $request,
        RekomendasiProdukService $service
    ): View {
        $pengguna = $request->user();

        $kategoriFavorit = $pengguna->preferensi()
            ->pluck('kategori');

        $rekomendasiProduk = $service->rekomendasiUntuk(
            $pengguna,
            8
        );

        $produkTerlaris = $service->produkTerlaris(8);

        return view('pembeli.rekomendasi', compact(
            'kategoriFavorit',
            'rekomendasiProduk',
            'produkTerlaris'
        ));
    }
}
