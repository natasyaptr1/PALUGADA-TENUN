<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Stok, MutasiStok};
use Illuminate\View\View;
class StokController extends Controller
{
    public function index(): View
    {
        $stok = Stok::with('produk')->whereHas('produk')->orderBy('produk_id')->paginate(6);
        $mutasi = MutasiStok::with('stok.produk')->latest('tanggal_mutasi')->paginate(6, ['*'], 'riwayat');
        return view('admin.stok.index', compact('stok', 'mutasi'));
    }
}
