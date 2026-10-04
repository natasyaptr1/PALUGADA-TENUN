<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AutentikasiController extends Controller
{
    public function tampilMasuk(): View
    {
        return view('autentikasi.masuk');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'kata_sandi' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum sesuai.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
        ]);

        $pengguna = Pengguna::where('email', $data['email'])->first();

        if (!$pengguna || !Hash::check($data['kata_sandi'], $pengguna->kata_sandi)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        if ($pengguna->status !== 'aktif') {
            return back()->withErrors(['email' => 'Akun sedang nonaktif. Silakan hubungi administrator.'])->onlyInput('email');
        }

        Auth::login($pengguna, $request->boolean('ingat'));
        $request->session()->regenerate();

        return match ($pengguna->peran) {
            'admin' => redirect()->route('admin.beranda'),
            'penenun' => redirect()->route('penenun.beranda'),
            default => redirect()->route('pembeli.akun'),
        };
    }

    public function tampilDaftar(): View
    {
        return view('autentikasi.daftar');
    }

    public function daftar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:pengguna,email'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'kata_sandi' => ['required', 'confirmed', Password::min(8)],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum sesuai.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk menggunakan akun tersebut.',
            'no_hp.max' => 'Nomor HP maksimal 20 karakter.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi belum sama.',
            'kata_sandi.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $pengguna = Pengguna::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'] ?? null,
            'kata_sandi' => Hash::make($data['kata_sandi']),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect()->route('pembeli.akun')->with('success', 'Akun berhasil dibuat. Selamat datang di Palugada Bandar.');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }
}
