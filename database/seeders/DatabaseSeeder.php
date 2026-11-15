<?php

namespace Database\Seeders;

use App\Models\JenisProduk;
use App\Models\Keahlian;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\ProdukFoto;
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
                'foto' => 'produk/MMBkjkpHNvINNGrUOgRdf23Jqop0YGDDhKhxEjwb.jpg',
                'status' => 'aktif',
            ]
        );

        if (!$produk->jenis_produk_id) {
            $produk->update(['jenis_produk_id' => $jenisKain->id]);
        }
        if (!$produk->foto) {
            $produk->update(['foto' => 'produk/MMBkjkpHNvINNGrUOgRdf23Jqop0YGDDhKhxEjwb.jpg']);
        }

        $stokTnp = Stok::firstOrNew(['produk_id' => $produk->id]);
        if (($stokTnp->jumlah_stok ?? 0) <= 0) {
            $stokTnp->jumlah_stok = 20;
            $stokTnp->save();
        }
        ProdukFoto::firstOrCreate(['produk_id' => $produk->id, 'foto' => 'produk/MMBkjkpHNvINNGrUOgRdf23Jqop0YGDDhKhxEjwb.jpg']);
        ProdukFoto::firstOrCreate(['produk_id' => $produk->id, 'foto' => 'produk/lsCCPQOko980teBNyBGCDp0Dyhh9Q66En4StVNRm.jpg']);

        $daftarProdukDemo = [
            ['kode_produk' => 'PRD-001', 'nama_produk' => 'Tenun Ikat Motif Baron', 'jenis' => 'Kain Tenun', 'motif' => 'Baron', 'warna' => 'Hitam Emas', 'ukuran' => '220 x 105 cm', 'material' => 'Katun Mercerized', 'harga' => 550000, 'foto' => 'produk/65ZddMJ4UZf0TgzPdPIWs6UMScUPWbW5oV0MrmPI.jpg'],
            ['kode_produk' => 'PRD-002', 'nama_produk' => 'Sarung Goyor Bandar', 'jenis' => 'Sarung Tenun', 'motif' => 'Goyor Geometris', 'warna' => 'Marun Gelap', 'ukuran' => '125 x 105 cm', 'material' => 'Viscose Rayon', 'harga' => 380000, 'foto' => 'produk/sarung_goyor_bandar.jpg'],
            ['kode_produk' => 'PRD-004', 'nama_produk' => 'Kemeja Pria Tenun Ikat', 'jenis' => 'Baju Tenun', 'motif' => 'Tumpal Kombinasi', 'warna' => 'Biru Navy', 'ukuran' => 'L', 'material' => 'Katun Tenun', 'harga' => 420000, 'foto' => 'produk/kemeja_tenun_pria.jpg'],
            ['kode_produk' => 'PRD-005', 'nama_produk' => 'Tas Jinjing Tenun Etnik', 'jenis' => 'Aksesoris Tenun', 'motif' => 'Larik Klasik', 'warna' => 'Cokelat Kayu', 'ukuran' => '35 x 30 cm', 'material' => 'Tenun & Kulit Nabati', 'harga' => 195000, 'foto' => 'produk/JRIlGEyuI7bb71Nw0kTDemSc4CNXHdp6zVYXaRk4.webp'],
            ['kode_produk' => 'PRD-006', 'nama_produk' => 'Kain Tenun Lurik Tradisional', 'jenis' => 'Kain Tenun', 'motif' => 'Lurik Gerimis', 'warna' => 'Abu-abu Natural', 'ukuran' => '200 x 100 cm', 'material' => 'Katun Halus', 'harga' => 320000, 'foto' => 'produk/kain_tenun_lurik.jpg'],
            ['kode_produk' => 'PRD-007', 'nama_produk' => 'Sarung Tenun Ikat Eksklusif', 'jenis' => 'Sarung Tenun', 'motif' => 'Mega Mendung Bandar', 'warna' => 'Hijau Botol', 'ukuran' => '120 x 105 cm', 'material' => 'Katun Mercerized', 'harga' => 490000, 'foto' => 'produk/sarung_tenun_eksklusif.jpg'],
            ['kode_produk' => 'PRD-008', 'nama_produk' => 'Syal Tenun Gradasi Indigo', 'jenis' => 'Selendang & Syal', 'motif' => 'Ombak Laut', 'warna' => 'Indigo Alami', 'ukuran' => '175 x 50 cm', 'material' => 'Katun Organik', 'harga' => 225000, 'foto' => 'produk/syal_tenun_indigo.jpg'],
            ['kode_produk' => 'PRD-009', 'nama_produk' => 'Outer Wanita Tenun Ikat Modern', 'jenis' => 'Baju Tenun', 'motif' => 'Flora Geometris', 'warna' => 'Krem Sage', 'ukuran' => 'All Size', 'material' => 'Tenun Ikat ATBM', 'harga' => 485000, 'foto' => 'produk/5UAYwj0AhL2bEoZX4Ow7KllkXzLUr3pGT1bRD59c.jpg'],
            ['kode_produk' => 'PRD-010', 'nama_produk' => 'Dompet Pouch Tenun Ikat', 'jenis' => 'Aksesoris Tenun', 'motif' => 'Batik Tenun', 'warna' => 'Merah Bata', 'ukuran' => '22 x 15 cm', 'material' => 'Tenun Ikat & Furing', 'harga' => 85000, 'foto' => 'produk/dompet_pouch_tenun.jpg'],
            ['kode_produk' => 'PRD-011', 'nama_produk' => 'Kain Tenun Baron Motif Keraton', 'jenis' => 'Kain Tenun', 'motif' => 'Keraton Klasik', 'warna' => 'Ungu Royal', 'ukuran' => '240 x 110 cm', 'material' => 'Katun Mercerized', 'harga' => 620000, 'foto' => 'produk/kain_tenun_baron_keraton.jpg'],
            ['kode_produk' => 'PRD-012', 'nama_produk' => 'Selendang Tenun Sutra Alam', 'jenis' => 'Selendang & Syal', 'motif' => 'Pucuk Rebung', 'warna' => 'Terracotta', 'ukuran' => '180 x 60 cm', 'material' => 'Sutra Alam', 'harga' => 275000, 'foto' => 'produk/selendang_tenun_sutra.jpg'],
        ];

        foreach ($daftarProdukDemo as $item) {
            $j = JenisProduk::firstOrCreate(['nama_jenis' => $item['jenis']]);
            $p = Produk::withTrashed()->where('kode_produk', $item['kode_produk'])->first();
            if ($p) {
                if ($p->trashed()) {
                    $p->restore();
                }
                $p->update([
                    'nama_produk' => $item['nama_produk'],
                    'jenis_produk_id' => $j->id,
                    'jenis' => $item['jenis'],
                    'motif' => $item['motif'],
                    'warna' => $item['warna'],
                    'ukuran' => $item['ukuran'],
                    'material' => $item['material'],
                    'harga' => $item['harga'],
                    'foto' => $item['foto'],
                    'status' => 'aktif',
                ]);
            } else {
                $p = Produk::create([
                    'kode_produk' => $item['kode_produk'],
                    'nama_produk' => $item['nama_produk'],
                    'jenis_produk_id' => $j->id,
                    'jenis' => $item['jenis'],
                    'motif' => $item['motif'],
                    'warna' => $item['warna'],
                    'ukuran' => $item['ukuran'],
                    'material' => $item['material'],
                    'harga' => $item['harga'],
                    'foto' => $item['foto'],
                    'status' => 'aktif',
                ]);
            }
            $stok = Stok::firstOrNew(['produk_id' => $p->id]);
            if (($stok->jumlah_stok ?? 0) <= 0) {
                $stok->jumlah_stok = 15;
                $stok->save();
            }
            ProdukFoto::firstOrCreate(['produk_id' => $p->id, 'foto' => $item['foto']]);
        }
    }
}
