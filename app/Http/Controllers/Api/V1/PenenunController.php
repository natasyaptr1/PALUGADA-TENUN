<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PenenunController extends Controller {
    public function index(Request $request): JsonResponse {
        $daftar=Pengguna::query()->where('peran','penenun')->with('profilPenenun')
            ->when($request->filled('cari'),function($query)use($request){$cari=$request->string('cari')->toString();$query->where(function($sub)use($cari){$sub->where('nama','like',"%{$cari}%")->orWhere('email','like',"%{$cari}%")->orWhereHas('profilPenenun',fn($p)=>$p->where('kode_penenun','like',"%{$cari}%"));});})
            ->paginate(min(max((int)$request->input('per_halaman',15),1),50));
        return response()->json(['data'=>$daftar->items(),'meta'=>['halaman_sekarang'=>$daftar->currentPage(),'per_halaman'=>$daftar->perPage(),'total'=>$daftar->total(),'halaman_terakhir'=>$daftar->lastPage()]]);
    }
    public function show(Pengguna $penenun): JsonResponse { abort_unless($penenun->peran==='penenun',404); return response()->json(['data'=>$penenun->load('profilPenenun')]); }
}
