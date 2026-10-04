<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProfilPenenun extends Model {
    protected $table = 'profil_penenun';
    protected $fillable = ['pengguna_id','kode_penenun','keahlian','catatan'];
    public function pengguna(){ return $this->belongsTo(Pengguna::class,'pengguna_id'); }
}
