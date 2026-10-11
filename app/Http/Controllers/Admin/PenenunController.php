<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keahlian;
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
        $daftarPenenun=Pengguna::query()->where('peran','penenun')->with('profilPenenun.daftarKeahlian')
            ->when($request->filled('cari'),function($query) use($request){
                $cari=$request->string('cari')->toString();
                $query->where(function($sub) use($cari){
                    $sub->where('nama','like',"%{$cari}%")
                        ->orWhere('email','like',"%{$cari}%")
                        ->orWhereHas('profilPenenun',function($profil) use($cari){
                            $profil->where('kode_penenun','like',"%{$cari}%")
                                ->orWhere('keahlian','like',"%{$cari}%")
                                ->orWhereHas('daftarKeahlian',fn($k)=>$k->where('nama_keahlian','like',"%{$cari}%"));
                        });
                });
            })->latest()->paginate(10)->withQueryString();
        return view('admin.penenun.index',compact('daftarPenenun'));
    }

    public function create(): View
    {
        $kodeOtomatis = ProfilPenenun::generateKodePenenun();
        $daftarKeahlian = Keahlian::orderBy('nama_keahlian')->get();
        return view('admin.penenun.tambah', compact('kodeOtomatis', 'daftarKeahlian'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$this->validasi($request);
        if (empty($data['kode_penenun'])) {
            $data['kode_penenun'] = ProfilPenenun::generateKodePenenun();
        }
        $keahlianIds = $request->input('keahlian_ids', []);
        $namaKeahlianStr = !empty($keahlianIds)
            ? Keahlian::whereIn('id', $keahlianIds)->pluck('nama_keahlian')->join(', ')
            : ($data['keahlian'] ?? null);

        DB::transaction(function() use($data, $keahlianIds, $namaKeahlianStr){
            $penenun=Pengguna::create([
                'nama'=>$data['nama'],'email'=>$data['email'],'no_hp'=>$data['no_hp']??null,
                'alamat'=>$data['alamat']??null,'kata_sandi'=>Hash::make($data['kata_sandi']),
                'peran'=>'penenun','status'=>$data['status'],
            ]);
            $profil = ProfilPenenun::create([
                'pengguna_id'=>$penenun->id,'kode_penenun'=>$data['kode_penenun'],
                'keahlian'=>$namaKeahlianStr,'catatan'=>$data['catatan']??null,
            ]);
            if (!empty($keahlianIds)) {
                $profil->daftarKeahlian()->sync($keahlianIds);
            }
        });
        return redirect()->route('admin.penenun.index')->with('success','Data penenun berhasil ditambahkan.');
    }

    public function edit(Pengguna $penenun): View
    {
        abort_unless($penenun->peran==='penenun',404);
        $penenun->load('profilPenenun.daftarKeahlian');
        $daftarKeahlian = Keahlian::orderBy('nama_keahlian')->get();
        return view('admin.penenun.ubah',compact('penenun', 'daftarKeahlian'));
    }

    public function update(Request $request, Pengguna $penenun): RedirectResponse
    {
        abort_unless($penenun->peran==='penenun',404);
        $data=$this->validasi($request,$penenun->id,false,$penenun->profilPenenun?->id);
        $kodeLama = $penenun->profilPenenun?->kode_penenun ?? ProfilPenenun::generateKodePenenun();
        $keahlianIds = $request->input('keahlian_ids', []);
        $namaKeahlianStr = !empty($keahlianIds)
            ? Keahlian::whereIn('id', $keahlianIds)->pluck('nama_keahlian')->join(', ')
            : ($data['keahlian'] ?? null);

        DB::transaction(function() use($data,$penenun,$kodeLama,$keahlianIds,$namaKeahlianStr){
            $payload=['nama'=>$data['nama'],'email'=>$data['email'],'no_hp'=>$data['no_hp']??null,'alamat'=>$data['alamat']??null,'status'=>$data['status']];
            if(!empty($data['kata_sandi'])) $payload['kata_sandi']=Hash::make($data['kata_sandi']);
            $penenun->update($payload);
            $profil = $penenun->profilPenenun()->updateOrCreate(
                [],
                ['kode_penenun'=>$kodeLama,'keahlian'=>$namaKeahlianStr,'catatan'=>$data['catatan']??null]
            );
            $profil->daftarKeahlian()->sync($keahlianIds);
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
            'no_hp'=>['nullable','string','regex:/^(?:\+62|62|0)8[0-9]{8,13}$/'],
            'alamat'=>['nullable','string'],
            'kode_penenun'=>['nullable','string','max:30','unique:profil_penenun,kode_penenun'.($profilId?','.$profilId:'')],
            'keahlian_ids'=>['nullable','array'],
            'keahlian_ids.*'=>['exists:keahlian,id'],
            'keahlian'=>['nullable','string','max:100'],
            'catatan'=>['nullable','string'],
            'status'=>['required','in:aktif,nonaktif'],
            'kata_sandi'=>[$wajibKataSandi?'required':'nullable','string','min:8','confirmed'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum sesuai.',
            'email.unique' => 'Email ini sudah digunakan akun lain.',
            'no_hp.regex' => 'Format nomor HP tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890 atau +628123456789).',
            'kode_penenun.required' => 'Kode penenun wajib diisi.',
            'kode_penenun.unique' => 'Kode penenun ini sudah digunakan.',
            'keahlian_ids.*.exists' => 'Pilihan keahlian tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
            'kata_sandi.min' => 'Sandi minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi belum sama.',
        ]);
    }
}
