<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pengguna';
    protected $fillable = ['nama', 'email', 'no_hp', 'alamat', 'kata_sandi', 'peran', 'status'];
    protected $hidden = ['kata_sandi', 'remember_token'];

    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    public function profilPenenun() { return $this->hasOne(ProfilPenenun::class, 'pengguna_id'); }
    public function targetProduksi() { return $this->hasMany(TargetProduksi::class, 'penenun_id'); }
    public function keranjang() { return $this->hasMany(Keranjang::class, 'pembeli_id'); }
    public function pesanan() { return $this->hasMany(Pesanan::class, 'pembeli_id'); }
    public function pembayaranDikonfirmasi() { return $this->hasMany(Pembayaran::class, 'dikonfirmasi_oleh'); }
}
