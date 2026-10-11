<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AutentikasiController extends Controller {
    public function daftar(Request $request): JsonResponse {
        $data=$request->validate([
            'nama'=>['required','string','max:100'],
            'email'=>['required','email','max:120','unique:pengguna,email'],
            'no_hp'=>['nullable','string','regex:/^(?:\+62|62|0)8[0-9]{8,13}$/'],
            'kata_sandi'=>['required','string','min:8','confirmed'],
            'nama_perangkat'=>['nullable','string','max:80'],
        ], [
            'nama.required'=>'Nama wajib diisi.',
            'email.required'=>'Email wajib diisi.',
            'email.email'=>'Format email belum sesuai.',
            'email.unique'=>'Email ini sudah terdaftar.',
            'no_hp.regex'=>'Format nomor HP tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890 atau +628123456789).',
            'kata_sandi.required'=>'Kata sandi wajib diisi.',
            'kata_sandi.min'=>'Sandi minimal 8 karakter.',
            'kata_sandi.confirmed'=>'Konfirmasi kata sandi belum sama.',
        ]);
        $pengguna=Pengguna::create(['nama'=>$data['nama'],'email'=>$data['email'],'no_hp'=>$data['no_hp']??null,'kata_sandi'=>Hash::make($data['kata_sandi']),'peran'=>'pembeli','status'=>'aktif']);
        $token=$pengguna->createToken($data['nama_perangkat']??'mobile')->plainTextToken;
        return response()->json(['pesan'=>'Registrasi berhasil.','token'=>$token,'pengguna'=>$pengguna],201);
    }
    public function masuk(Request $request): JsonResponse {
        $data=$request->validate(['email'=>['required','email'],'kata_sandi'=>['required'],'nama_perangkat'=>['nullable','string','max:80']]);
        $pengguna=Pengguna::where('email',$data['email'])->first();
        if(!$pengguna || !Hash::check($data['kata_sandi'],$pengguna->kata_sandi) || $pengguna->status!=='aktif') return response()->json(['pesan'=>'Email atau kata sandi tidak sesuai.'],422);
        $token=$pengguna->createToken($data['nama_perangkat']??'mobile')->plainTextToken;
        return response()->json(['pesan'=>'Login berhasil.','token'=>$token,'pengguna'=>$pengguna]);
    }
    public function saya(Request $request): JsonResponse {
        $pengguna = $request->user();
        if ($pengguna->peran === 'penenun') {
            $pengguna->load('profilPenenun');
        }
        return response()->json(['data'=>$pengguna]);
    }
    public function keluar(Request $request): JsonResponse { $request->user()->currentAccessToken()?->delete(); return response()->json(['pesan'=>'Logout berhasil.']); }
}
