<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DetailPesanan extends Model {
    protected $table='detail_pesanan';
    protected $fillable=['pesanan_id','produk_id','jumlah','harga','subtotal'];
    protected function casts(): array { return ['harga'=>'decimal:2','subtotal'=>'decimal:2']; }
    public function pesanan(){ return $this->belongsTo(Pesanan::class,'pesanan_id'); }
    public function produk(){ return $this->belongsTo(Produk::class,'produk_id'); }
    public function mutasiStok(){ return $this->hasMany(MutasiStok::class,'detail_pesanan_id'); }
}
