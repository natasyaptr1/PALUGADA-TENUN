<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        $daftarProduk=Produk::query()->with('stok')
            ->when($request->filled('cari'),function($query) use($request){
                $cari=$request->string('cari')->toString();
                $query->where(function($sub) use($cari){
                    $sub->where('kode_produk','like',"%{$cari}%")
                        ->orWhere('nama_produk','like',"%{$cari}%")
                        ->orWhere('motif','like',"%{$cari}%");
                });
            })->latest()->paginate(10)->withQueryString();
        return view('admin.produk.index',compact('daftarProduk'));
    }

    public function create(): View { return view('admin.produk.tambah'); }

    public function store(Request $request): RedirectResponse
    {
        $data=$this->validasi($request);
        $data['foto']=$request->file('foto')?->store('produk','public');
        DB::transaction(function() use($data){
            $produk=Produk::create($data);
            Stok::create(['produk_id'=>$produk->id,'jumlah_stok'=>0]);
        });
        return redirect()->route('admin.produk.index')->with('success','Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View { return view('admin.produk.ubah',compact('produk')); }

    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $data=$this->validasi($request,$produk->id);
        if($request->hasFile('foto')){
            if($produk->foto) Storage::disk('public')->delete($produk->foto);
            $data['foto']=$request->file('foto')->store('produk','public');
        }
        $produk->update($data);
        return redirect()->route('admin.produk.index')->with('success','Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        if($produk->foto) Storage::disk('public')->delete($produk->foto);
        $produk->delete();
        return redirect()->route('admin.produk.index')->with('success','Produk berhasil dihapus.');
    }

    private function validasi(Request $request, ?int $produkId=null): array
    {
        return $request->validate([
            'kode_produk'=>['required','string','max:50','unique:produk,kode_produk'.($produkId?','.$produkId:'')],
            'nama_produk'=>['required','string','max:100'],
            'jenis'=>['required','string','max:50'],
            'motif'=>['nullable','string','max:100'],
            'warna'=>['nullable','string','max:50'],
            'ukuran'=>['nullable','string','max:50'],
            'material'=>['nullable','string','max:100'],
            'harga'=>['required','numeric','min:0'],
            'foto'=>['nullable','image','max:2048'],
            'status'=>['required','in:aktif,nonaktif'],
        ]);
    }
}
