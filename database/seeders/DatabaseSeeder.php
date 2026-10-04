<?php
namespace Database\Seeders;
use App\Models\Pengguna; use App\Models\Produk; use App\Models\ProfilPenenun; use App\Models\Stok;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        Pengguna::firstOrCreate(['email'=>'admin@palugada.test'],['nama'=>'Administrator Palugada','no_hp'=>'081234567890','kata_sandi'=>Hash::make('password'),'peran'=>'admin','status'=>'aktif']);
        $penenun=Pengguna::firstOrCreate(['email'=>'penenun@palugada.test'],['nama'=>'Penenun Demo','no_hp'=>'081234567891','kata_sandi'=>Hash::make('password'),'peran'=>'penenun','status'=>'aktif']);
        ProfilPenenun::firstOrCreate(['pengguna_id'=>$penenun->id],['kode_penenun'=>'PNN-001','keahlian'=>'Tenun Ikat']);
        $produk=Produk::firstOrCreate(['kode_produk'=>'TNP-001'],['nama_produk'=>'Tenun Ikat Motif Klasik','jenis'=>'Kain Tenun','motif'=>'Klasik','warna'=>'Navy-Cream','ukuran'=>'200 x 100 cm','material'=>'Benang katun','harga'=>450000,'status'=>'aktif']);
        Stok::firstOrCreate(['produk_id'=>$produk->id],['jumlah_stok'=>0]);
    }
}
