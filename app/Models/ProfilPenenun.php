<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProfilPenenun extends Model {
    protected $table = 'profil_penenun';
    protected $fillable = ['pengguna_id','kode_penenun','keahlian','catatan'];
    public function pengguna(){ return $this->belongsTo(Pengguna::class,'pengguna_id'); }
    public function daftarKeahlian(){ return $this->belongsToMany(Keahlian::class, 'penenun_keahlian', 'profil_penenun_id', 'keahlian_id')->withTimestamps(); }

    public static function generateKodePenenun(): string
    {
        $lastPenenun = static::where('kode_penenun', 'like', 'PNN-%')
            ->orderByRaw('CAST(SUBSTRING(kode_penenun, 5) AS UNSIGNED) DESC')
            ->first();

        if ($lastPenenun && preg_match('/PNN-(\d+)/', $lastPenenun->kode_penenun, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        $candidate = sprintf('PNN-%03d', $nextNumber);

        while (static::where('kode_penenun', $candidate)->exists()) {
            $nextNumber++;
            $candidate = sprintf('PNN-%03d', $nextNumber);
        }

        return $candidate;
    }
}
