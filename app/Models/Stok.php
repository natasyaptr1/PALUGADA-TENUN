<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Stok extends Model {
    protected $table='stok';
    protected $fillable=['produk_id','jumlah_stok'];
    public function produk(){ return $this->belongsTo(Produk::class,'produk_id'); }
    public function mutasi(){ return $this->hasMany(MutasiStok::class,'stok_id'); }
}
