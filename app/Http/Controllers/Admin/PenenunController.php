<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\ProfilPenenun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PenenunController extends Controller
{
    public function index(Request $request): View
    {
        $daftarPenenun=Pengguna::query()->where('peran','penenun')->with('profilPenenun')
            ->when($request->filled('cari'),function($query) use($request){
                $cari=$request->string('cari')->toString();
                $query->where(function($sub) use($cari){
                    $sub->where('nama','like',"%{$cari}%")
                        ->orWhere('email','like',"%{$cari}%")
                        ->orWhereHas('profilPenenun',fn($profil)=>$profil->where('kode_penenun','like',"%{$cari}%"));
                });
            })->latest()->paginate(10)->withQueryString();
        return view('admin.penenun.index',compact('daftarPenenun'));
    }

    public function create(): View { return view('admin.penenun.tambah'); }

    public function store(Request $request): RedirectResponse
    {
        $data=$this->validasi($request);
        DB::transaction(function() use($data){
            $penenun=Pengguna::create([
                'nama'=>$data['nama'],'email'=>$data['email'],'no_hp'=>$data['no_hp']??null,
                'alamat'=>$data['alamat']??null,'kata_sandi'=>Hash::make($data['kata_sandi']),
                'peran'=>'penenun','status'=>$data['status'],
            ]);
            ProfilPenenun::create([
                'pengguna_id'=>$penenun->id,'kode_penenun'=>$data['kode_penenun'],
                'keahlian'=>$data['keahlian']??null,'catatan'=>$data['catatan']??null,
            ]);
        });
        return redirect()->route('admin.penenun.index')->with('success','Data penenun berhasil ditambahkan.');
    }

    public function edit(Pengguna $penenun): View
    {
        abort_unless($penenun->peran==='penenun',404);
        $penenun->load('profilPenenun');
        return view('admin.penenun.ubah',compact('penenun'));
    }

    public function update(Request $request, Pengguna $penenun): RedirectResponse
    {
        abort_unless($penenun->peran==='penenun',404);
        $data=$this->validasi($request,$penenun->id,false,$penenun->profilPenenun?->id);
        DB::transaction(function() use($data,$penenun){
            $payload=['nama'=>$data['nama'],'email'=>$data['email'],'no_hp'=>$data['no_hp']??null,'alamat'=>$data['alamat']??null,'status'=>$data['status']];
            if(!empty($data['kata_sandi'])) $payload['kata_sandi']=Hash::make($data['kata_sandi']);
            $penenun->update($payload);
            $penenun->profilPenenun()->updateOrCreate([],['kode_penenun'=>$data['kode_penenun'],'keahlian'=>$data['keahlian']??null,'catatan'=>$data['catatan']??null]);
        });
        return redirect()->route('admin.penenun.index')->with('success','Data penenun berhasil diperbarui.');
    }

    public function destroy(Pengguna $penenun): RedirectResponse
    {
        abort_unless($penenun->peran==='penenun',404);
        $penenun->delete();
        return redirect()->route('admin.penenun.index')->with('success','Data penenun berhasil dihapus.');
    }

    private function validasi(Request $request, ?int $penggunaId=null, bool $wajibKataSandi=true, ?int $profilId=null): array
    {
        return $request->validate([
            'nama'=>['required','string','max:100'],
            'email'=>['required','email','max:120','unique:pengguna,email'.($penggunaId?','.$penggunaId:'')],
            'no_hp'=>['nullable','string','max:20'],
            'alamat'=>['nullable','string'],
            'kode_penenun'=>['required','string','max:30','unique:profil_penenun,kode_penenun'.($profilId?','.$profilId:'')],
            'keahlian'=>['nullable','string','max:100'],
            'catatan'=>['nullable','string'],
            'status'=>['required','in:aktif,nonaktif'],
            'kata_sandi'=>[$wajibKataSandi?'required':'nullable','confirmed',Password::min(8)],
        ]);
    }
}
