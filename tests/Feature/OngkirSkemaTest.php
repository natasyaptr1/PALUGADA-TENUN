<?php

namespace Tests\Feature;

use App\Enums\MetodePengiriman;
use App\Models\Keranjang;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OngkirSkemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan kolom baru ada pada tabel produk dan tabel pesanan.
     */
    public function test_kolom_baru_tersedia_pada_tabel_produk_dan_pesanan(): void
    {
        $this->assertTrue(
            Schema::hasColumn('produk', 'berat_gram'),
            'Kolom berat_gram harus ada pada tabel produk.'
        );

        $kolomPesanan = [
            'subtotal',
            'ongkir',
            'metode_pengiriman',
            'kurir_kode',
            'kurir_nama',
            'layanan_kurir',
            'estimasi_kirim',
            'tujuan_id',
            'tujuan_label',
            'berat_total_gram',
        ];

        foreach ($kolomPesanan as $kolom) {
            $this->assertTrue(
                Schema::hasColumn('pesanan', $kolom),
                "Kolom {$kolom} harus ada pada tabel pesanan."
            );
        }
    }

    /**
     * Memastikan produk baru yang dibuat tanpa mengisi berat_gram akan bernilai default 500 gram.
     */
    public function test_produk_baru_tanpa_berat_otomatis_memiliki_berat_500_gram(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-500',
            'nama_produk' => 'Kain Tenun Baron Default',
            'jenis' => 'Kain Tenun',
            'harga' => 350000,
            'status' => 'aktif',
        ]);

        $produk->refresh();

        $this->assertSame(500, $produk->berat_gram);
    }

    /**
     * Memastikan pesanan yang dibuat tanpa kolom baru tetap valid dan kompatibel dengan kode lama.
     */
    public function test_pesanan_dibuat_tanpa_kolom_baru_tetap_valid_dan_kompatibel(): void
    {
        $pembeli = Pengguna::factory()->create(['peran' => 'pembeli']);
        $keranjang = Keranjang::create(['pembeli_id' => $pembeli->id]);

        $pesanan = Pesanan::create([
            'pembeli_id' => $pembeli->id,
            'keranjang_id' => $keranjang->id,
            'kode_pesanan' => 'ORD-TEST-LAMA-001',
            'tanggal_pesanan' => now(),
            'total' => 250000,
            'status_pesanan' => 'menunggu_pembayaran',
            'nama_penerima' => 'Ahmad Dahlan',
            'no_hp' => '081234567890',
            'alamat_pengiriman' => 'Jl. Malioboro No. 1, Yogyakarta',
        ]);

        $this->assertNotNull($pesanan->id);

        $pesanan->refresh();

        // Ongkir bernilai default 0 (cast desimal)
        $this->assertEquals('0.00', $pesanan->ongkir);
        $this->assertEquals(0, (float) $pesanan->ongkir);

        // Kolom pengiriman dan kurir bernilai null
        $this->assertNull($pesanan->subtotal);
        $this->assertNull($pesanan->metode_pengiriman);
        $this->assertNull($pesanan->kurir_kode);
        $this->assertNull($pesanan->kurir_nama);
        $this->assertNull($pesanan->layanan_kurir);
        $this->assertNull($pesanan->estimasi_kirim);
        $this->assertNull($pesanan->tujuan_id);
        $this->assertNull($pesanan->tujuan_label);
        $this->assertNull($pesanan->berat_total_gram);
    }

    /**
     * Memastikan seluruh kolom baru dapat diisi melalui mass assignment pada model Produk dan Pesanan.
     */
    public function test_kolom_baru_dapat_diisi_lewat_mass_assignment(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-CUSTOM',
            'nama_produk' => 'Kain Tenun Sutra Berat',
            'jenis' => 'Kain Tenun',
            'harga' => 750000,
            'status' => 'aktif',
            'berat_gram' => 850,
        ]);

        $produk->refresh();
        $this->assertSame(850, $produk->berat_gram);

        $pembeli = Pengguna::factory()->create(['peran' => 'pembeli']);
        $keranjang = Keranjang::create(['pembeli_id' => $pembeli->id]);

        $pesanan = Pesanan::create([
            'pembeli_id' => $pembeli->id,
            'keranjang_id' => $keranjang->id,
            'kode_pesanan' => 'ORD-TEST-BARU-001',
            'tanggal_pesanan' => now(),
            'subtotal' => 750000,
            'ongkir' => 25000,
            'total' => 775000,
            'status_pesanan' => 'menunggu_pembayaran',
            'metode_pengiriman' => MetodePengiriman::Ekspedisi->value,
            'kurir_kode' => 'jne',
            'kurir_nama' => 'JNE Express',
            'layanan_kurir' => 'REG',
            'estimasi_kirim' => '2-3 hari',
            'tujuan_id' => '401',
            'tujuan_label' => 'Kec. Sukasari, Kota Bandung, Jawa Barat',
            'berat_total_gram' => 850,
            'nama_penerima' => 'Dewi Lestari',
            'no_hp' => '081298765432',
            'alamat_pengiriman' => 'Jl. Setiabudi No. 10, Bandung',
        ]);

        $pesanan->refresh();

        $this->assertEquals('750000.00', $pesanan->subtotal);
        $this->assertEquals('25000.00', $pesanan->ongkir);
        $this->assertEquals('775000.00', $pesanan->total);
        $this->assertSame('ekspedisi', $pesanan->metode_pengiriman);
        $this->assertSame('jne', $pesanan->kurir_kode);
        $this->assertSame('JNE Express', $pesanan->kurir_nama);
        $this->assertSame('REG', $pesanan->layanan_kurir);
        $this->assertSame('2-3 hari', $pesanan->estimasi_kirim);
        $this->assertSame('401', $pesanan->tujuan_id);
        $this->assertSame('Kec. Sukasari, Kota Bandung, Jawa Barat', $pesanan->tujuan_label);
        $this->assertSame(850, $pesanan->berat_total_gram);
    }

    /**
     * Memastikan casting tipe data pada model Produk dan Pesanan berfungsi dengan benar.
     */
    public function test_casting_bekerja_dengan_benar(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-CAST',
            'nama_produk' => 'Tenun Uji Cast',
            'jenis' => 'Kain Tenun',
            'harga' => 125000.50,
            'status' => 'aktif',
            'berat_gram' => '650', // string, harus dicast ke integer
        ]);

        $this->assertIsInt($produk->berat_gram);
        $this->assertSame(650, $produk->berat_gram);

        $pembeli = Pengguna::factory()->create(['peran' => 'pembeli']);
        $keranjang = Keranjang::create(['pembeli_id' => $pembeli->id]);

        $pesanan = Pesanan::create([
            'pembeli_id' => $pembeli->id,
            'keranjang_id' => $keranjang->id,
            'kode_pesanan' => 'ORD-TEST-CAST-001',
            'tanggal_pesanan' => now(),
            'subtotal' => 250000,
            'ongkir' => 17500,
            'total' => 267500,
            'status_pesanan' => 'menunggu_pembayaran',
            'nama_penerima' => 'Rina S',
            'no_hp' => '081234567899',
            'alamat_pengiriman' => 'Jl. Test No. 9',
            'berat_total_gram' => '1300', // string, harus dicast ke integer
        ]);

        $pesanan->refresh();

        // ongkir dan subtotal diformat 2 desimal (string representasi desimal di Eloquent)
        $this->assertSame('17500.00', (string) $pesanan->ongkir);
        $this->assertSame('250000.00', (string) $pesanan->subtotal);

        // berat_total_gram berupa integer
        $this->assertIsInt($pesanan->berat_total_gram);
        $this->assertSame(1300, $pesanan->berat_total_gram);
    }

    /**
     * Memastikan enum MetodePengiriman memiliki tepat dua case dengan nilai dan label yang benar.
     */
    public function test_enum_metode_pengiriman_memiliki_dua_case_dan_label_yang_benar(): void
    {
        $cases = MetodePengiriman::cases();
        $this->assertCount(2, $cases);

        $this->assertSame('ambil_di_tempat', MetodePengiriman::AmbilDiTempat->value);
        $this->assertSame('Ambil di Tempat', MetodePengiriman::AmbilDiTempat->label());

        $this->assertSame('ekspedisi', MetodePengiriman::Ekspedisi->value);
        $this->assertSame('Ekspedisi', MetodePengiriman::Ekspedisi->label());
    }

    /**
     * Memastikan konfigurasi ongkir memiliki nilai default yang benar.
     */
    public function test_konfigurasi_ongkir_memiliki_nilai_default_yang_benar(): void
    {
        $this->assertSame('https://rajaongkir.komerce.id/api/v1', config('ongkir.base_url'));

        $kurir = config('ongkir.kurir');
        $this->assertIsArray($kurir);
        $this->assertNotEmpty($kurir);
        $this->assertFalse(in_array('', $kurir, true), 'Array kurir tidak boleh mengandung elemen string kosong.');
        $this->assertEquals(['jne', 'jnt', 'sicepat'], $kurir);

        $this->assertSame(500, config('ongkir.berat_default_gram'));
        if (empty(env('ONGKIR_ASAL_ID'))) {
            $this->assertNull(config('ongkir.asal_id'), 'asal_id harus null jika environment kosong.');
        } else {
            $this->assertSame((string) env('ONGKIR_ASAL_ID'), (string) config('ongkir.asal_id'));
        }
        $this->assertSame('Ambil di tempat (Palugada Bandar)', config('ongkir.label_ambil_di_tempat'));

        $this->assertSame(1440, config('ongkir.cache_ongkir_menit'));
        $this->assertSame(10080, config('ongkir.cache_tujuan_menit'));
        $this->assertSame(10, config('ongkir.timeout_detik'));
    }

    /**
     * Memastikan query pengisian data lama: pesanan yang subtotal-nya null diisi sama dengan total.
     */
    public function test_pengisian_subtotal_sama_dengan_total_untuk_pesanan_lama(): void
    {
        $pembeli = Pengguna::factory()->create(['peran' => 'pembeli']);
        $keranjang = Keranjang::create(['pembeli_id' => $pembeli->id]);

        $pesananId = DB::table('pesanan')->insertGetId([
            'pembeli_id' => $pembeli->id,
            'keranjang_id' => $keranjang->id,
            'kode_pesanan' => 'ORD-MIGRATION-TEST',
            'tanggal_pesanan' => now(),
            'total' => 320000.00,
            'subtotal' => null,
            'ongkir' => 0,
            'status_pesanan' => 'selesai',
            'nama_penerima' => 'Pelanggan Lama',
            'no_hp' => '081234567891',
            'alamat_pengiriman' => 'Alamat lama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Jalankan query update seperti yang ada pada migration up()
        DB::table('pesanan')
            ->whereNull('subtotal')
            ->update([
                'subtotal' => DB::raw('total'),
            ]);

        $pesanan = Pesanan::find($pesananId);
        $this->assertEquals('320000.00', $pesanan->subtotal);
        $this->assertEquals($pesanan->total, $pesanan->subtotal);
    }

    /**
     * Memastikan method down() pada kedua migration menghapus kolom baru dengan benar.
     */
    public function test_method_down_migration_menghapus_kolom_baru(): void
    {
        $migrationProduk = require database_path('migrations/2026_11_13_000100_tambah_kolom_berat_gram_pada_tabel_produk.php');
        $migrationProduk->down();
        $this->assertFalse(Schema::hasColumn('produk', 'berat_gram'));
        $migrationProduk->up();
        $this->assertTrue(Schema::hasColumn('produk', 'berat_gram'));

        $migrationPesanan = require database_path('migrations/2026_11_13_000200_tambah_kolom_ongkir_dan_pengiriman_pada_tabel_pesanan.php');
        $migrationPesanan->down();
        $this->assertFalse(Schema::hasColumn('pesanan', 'subtotal'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'ongkir'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'metode_pengiriman'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'kurir_kode'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'kurir_nama'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'layanan_kurir'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'estimasi_kirim'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'tujuan_id'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'tujuan_label'));
        $this->assertFalse(Schema::hasColumn('pesanan', 'berat_total_gram'));
        $migrationPesanan->up();
        $this->assertTrue(Schema::hasColumn('pesanan', 'ongkir'));
    }
}
