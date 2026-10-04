<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Pesanan extends Model {
    protected $table='pesanan';
    protected $fillable=['pembeli_id','keranjang_id','kode_pesanan','tanggal_pesanan','total','status_pesanan','nama_penerima','no_hp','alamat_pengiriman'];
    protected function casts(): array { return ['tanggal_pesanan'=>'datetime','total'=>'decimal:2']; }
    public function pembeli(){ return $this->belongsTo(Pengguna::class,'pembeli_id'); }
    public function keranjang(){ return $this->belongsTo(Keranjang::class,'keranjang_id'); }
    public function detail(){ return $this->hasMany(DetailPesanan::class,'pesanan_id'); }
    public function pembayaran(){ return $this->hasMany(Pembayaran::class,'pesanan_id'); }
}
