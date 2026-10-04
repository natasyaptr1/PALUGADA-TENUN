<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HasilProduksi extends Model {
    protected $table='hasil_produksi';
    protected $fillable=['target_produksi_id','jumlah_selesai','tanggal_input','catatan'];
    protected function casts(): array { return ['tanggal_input'=>'datetime']; }
    public function targetProduksi(){ return $this->belongsTo(TargetProduksi::class,'target_produksi_id'); }
    public function mutasiStok(){ return $this->hasMany(MutasiStok::class,'hasil_produksi_id'); }
}
