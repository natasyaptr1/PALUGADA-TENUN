<?php

namespace Database\Seeders;

use App\Models\JenisProduk;
use App\Models\Keahlian;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\ProfilPenenun;
use App\Models\Stok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@palugadabandar.id');
        $adminPassword = env('ADMIN_PASSWORD', 'PalugadaAdmin123!');

        $admin = Pengguna::where('peran', 'admin')->orderBy('id')->first();

        if (!$admin) {
            $admin = new Pengguna();
        }

        $admin->fill([
            'nama' => 'Administrator Palugada',
            'email' => $adminEmail,
            'no_hp' => '081234567890',
            'kata_sandi' => Hash::make($adminPassword),
            'peran' => 'admin',
            'status' => 'aktif',
        ])->save();

        $penenun = Pengguna::firstOrCreate(
            ['email' => 'penenun@palugada.test'],
            [
                'nama' => 'Penenun Demo',
                'no_hp' => '081234567891',
                'kata_sandi' => Hash::make('password'),
                'peran' => 'penenun',
                'status' => 'aktif',
            ]
        );

        $keahlianIkat = Keahlian::firstOrCreate(['nama_keahlian' => 'Tenun Ikat']);
        $keahlianWarna = Keahlian::firstOrCreate(['nama_keahlian' => 'Pewarnaan Alami']);
        Keahlian::firstOrCreate(['nama_keahlian' => 'Tenun Songket']);
        Keahlian::firstOrCreate(['nama_keahlian' => 'Desain Motif']);
        Keahlian::firstOrCreate(['nama_keahlian' => 'Penggulungan Benang']);
        Keahlian::firstOrCreate(['nama_keahlian' => 'Finishing & Jahit']);

        $profilPenenun = ProfilPenenun::firstOrCreate(
            ['pengguna_id' => $penenun->id],
            ['kode_penenun' => 'PNN-001', 'keahlian' => 'Tenun Ikat, Pewarnaan Alami']
        );
        $profilPenenun->daftarKeahlian()->syncWithoutDetaching([$keahlianIkat->id, $keahlianWarna->id]);

        $jenisKain = JenisProduk::firstOrCreate(['nama_jenis' => 'Kain Tenun']);
        JenisProduk::firstOrCreate(['nama_jenis' => 'Sarung Tenun']);
        JenisProduk::firstOrCreate(['nama_jenis' => 'Selendang & Syal']);
        JenisProduk::firstOrCreate(['nama_jenis' => 'Baju Tenun']);
        JenisProduk::firstOrCreate(['nama_jenis' => 'Aksesoris Tenun']);

        $produk = Produk::firstOrCreate(
            ['kode_produk' => 'TNP-001'],
            [
                'nama_produk' => 'Tenun Ikat Motif Klasik',
                'jenis_produk_id' => $jenisKain->id,
                'jenis' => 'Kain Tenun',
                'motif' => 'Klasik',
                'warna' => 'Navy-Cream',
                'ukuran' => '200 x 100 cm',
                'material' => 'Benang katun',
                'harga' => 450000,
                'status' => 'aktif',
            ]
        );

        if (!$produk->jenis_produk_id) {
            $produk->update(['jenis_produk_id' => $jenisKain->id]);
        }

        Stok::firstOrCreate(['produk_id' => $produk->id], ['jumlah_stok' => 0]);
    }
}
