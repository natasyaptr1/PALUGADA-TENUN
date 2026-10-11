<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Produk extends Model {
    use SoftDeletes;
    protected $table = 'produk';
    protected $fillable=['kode_produk','nama_produk','jenis_produk_id','jenis','motif','warna','ukuran','material','harga','foto','status'];
    protected function casts(): array { return ['harga'=>'decimal:2']; }
    public function jenisProduk(){ return $this->belongsTo(JenisProduk::class, 'jenis_produk_id'); }
    public function stok(){ return $this->hasOne(Stok::class,'produk_id'); }
    public function targetProduksi(){ return $this->hasMany(TargetProduksi::class,'produk_id'); }
    public function detailKeranjang(){ return $this->hasMany(DetailKeranjang::class,'produk_id'); }
    public function detailPesanan(){ return $this->hasMany(DetailPesanan::class,'produk_id'); }
    public function produkFoto(){ return $this->hasMany(ProdukFoto::class,'produk_id'); }
    public function fotos(){ return $this->hasMany(ProdukFoto::class,'produk_id'); }

    public function semuaFoto(): array
    {
        $daftar = $this->produkFoto->pluck('foto')->filter()->values()->all();
        if (empty($daftar) && !empty($this->foto)) {
            $daftar = [$this->foto];
        }
        return $daftar;
    }

    public static function generateKodeProduk(): string
    {
        $lastProduct = static::withTrashed()
            ->where('kode_produk', 'like', 'PRD-%')
            ->orderByRaw('CAST(SUBSTRING(kode_produk, 5) AS UNSIGNED) DESC')
            ->first();

        if ($lastProduct && preg_match('/PRD-(\d+)/', $lastProduct->kode_produk, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        $candidate = sprintf('PRD-%03d', $nextNumber);

        while (static::withTrashed()->where('kode_produk', $candidate)->exists()) {
            $nextNumber++;
            $candidate = sprintf('PRD-%03d', $nextNumber);
        }

        return $candidate;
    }
}
