<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Produk extends Model {
    use SoftDeletes;
    protected $table = 'produk';
    protected $fillable=['kode_produk','nama_produk','jenis','motif','warna','ukuran','material','harga','foto','status'];
    protected function casts(): array { return ['harga'=>'decimal:2']; }
    public function stok(){ return $this->hasOne(Stok::class,'produk_id'); }
    public function targetProduksi(){ return $this->hasMany(TargetProduksi::class,'produk_id'); }
    public function detailKeranjang(){ return $this->hasMany(DetailKeranjang::class,'produk_id'); }
    public function detailPesanan(){ return $this->hasMany(DetailPesanan::class,'produk_id'); }
}
