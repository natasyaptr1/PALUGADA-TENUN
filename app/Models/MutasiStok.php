<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MutasiStok extends Model {
    protected $table='mutasi_stok';
    protected $fillable=['stok_id','jenis_mutasi','jumlah','sumber','hasil_produksi_id','detail_pesanan_id','catatan','tanggal_mutasi'];
    protected function casts(): array { return ['tanggal_mutasi'=>'datetime']; }
    public function stok(){ return $this->belongsTo(Stok::class,'stok_id'); }
    public function hasilProduksi(){ return $this->belongsTo(HasilProduksi::class,'hasil_produksi_id'); }
    public function detailPesanan(){ return $this->belongsTo(DetailPesanan::class,'detail_pesanan_id'); }
}
