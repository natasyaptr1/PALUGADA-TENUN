<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Keranjang extends Model {
    protected $table='keranjang';
    protected $fillable=['pembeli_id','status'];
    public function pembeli(){ return $this->belongsTo(Pengguna::class,'pembeli_id'); }
    public function detail(){ return $this->hasMany(DetailKeranjang::class,'keranjang_id'); }
    public function pesanan(){ return $this->hasOne(Pesanan::class,'keranjang_id'); }
}
