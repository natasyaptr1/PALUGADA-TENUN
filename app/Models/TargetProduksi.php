<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TargetProduksi extends Model {
    protected $table='target_produksi';
    protected $fillable=['produk_id','penenun_id','jumlah_target','tanggal_mulai','tanggal_deadline','status'];
    protected function casts(): array { return ['tanggal_mulai'=>'date','tanggal_deadline'=>'date']; }
    public function produk(){ return $this->belongsTo(Produk::class,'produk_id'); }
    public function penenun(){ return $this->belongsTo(Pengguna::class,'penenun_id'); }
    public function hasilProduksi(){ return $this->hasMany(HasilProduksi::class,'target_produksi_id'); }
}
