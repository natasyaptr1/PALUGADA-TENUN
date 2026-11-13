<?php

namespace Tests\Feature;

use App\Enums\MetodePengiriman;
use App\Exceptions\OngkirException;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Services\OngkirService;
use App\Services\PengirimanService;
use App\Services\RincianPengiriman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PengirimanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PengirimanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();

        Config::set('ongkir.api_key', 'test-key-pengiriman-123');
        Config::set('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        Config::set('ongkir.asal_id', '34381');
        Config::set('ongkir.kurir', ['jne', 'jnt', 'sicepat']);
        Config::set('ongkir.berat_default_gram', 500);
        Config::set('ongkir.label_ambil_di_tempat', 'Ambil di tempat (Palugada Bandar)');
        Config::set('ongkir.pembayaran_per_metode', [
            'ambil_di_tempat' => ['cash'],
            'ekspedisi'       => ['midtrans'],
        ]);

        $this->service = app(PengirimanService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Jalur 1: Ambil Di Tempat
    |--------------------------------------------------------------------------
    */

    public function test_ambil_di_tempat_dengan_cash_berhasil_dan_api_tidak_dipanggil(): void
    {
        $input = [
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
            // Data kurir dan tujuan yang mungkin dikirim pembeli harus diabaikan
            'tujuan_id' => '999',
            'tujuan_label' => 'Kota Surabaya',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Alamat input pembeli yang harus diabaikan',
            'ongkir' => 75000, // Harus diabaikan
        ];

        $rincian = $this->service->siapkan($input, 250000.0, 1200);

        // API kurir tidak boleh dipanggil sama sekali
        Http::assertNothingSent();

        $this->assertInstanceOf(RincianPengiriman::class, $rincian);
        $this->assertSame(MetodePengiriman::AmbilDiTempat, $rincian->metodePengiriman);
        $this->assertSame('cash', $rincian->metodePembayaran);
        $this->assertSame(250000.0, $rincian->subtotal);
        $this->assertSame(0, $rincian->ongkir);
        $this->assertSame(250000.0, $rincian->total);
        $this->assertSame(1200, $rincian->beratTotalGram);

        // Kolom kurir dan tujuan harus bernilai null
        $this->assertNull($rincian->kurirKode);
        $this->assertNull($rincian->kurirNama);
        $this->assertNull($rincian->layananKurir);
        $this->assertNull($rincian->estimasiKirim);
        $this->assertNull($rincian->tujuanId);
        $this->assertNull($rincian->tujuanLabel);

        // Alamat pengiriman harus sesuai label konfigurasi ambil di tempat
        $this->assertSame('Ambil di tempat (Palugada Bandar)', $rincian->alamatPengiriman);
    }

    public function test_ambil_di_tempat_dengan_midtrans_ditolak(): void
    {
        $input = [
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'midtrans',
        ];

        try {
            $this->service->siapkan($input, 100000.0, 500);
            $this->fail('Harus melempar ValidationException untuk kombinasi ambil di tempat + midtrans.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metode', $e->errors());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Jalur 2: Ekspedisi
    |--------------------------------------------------------------------------
    */

    public function test_ekspedisi_dengan_midtrans_valid_dan_berhasil(): void
    {
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'name' => 'JNE Express',
                        'code' => 'jne',
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 22000,
                        'etd' => '2-3 hari',
                    ],
                ],
            ], 200),
        ]);

        $input = [
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '12345',
            'tujuan_label' => 'Kec. Gambir, Jakarta Pusat',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'reg', // Huruf kecil harus tetap cocok (case-insensitive)
            'alamat_pengiriman' => 'Jl. Medan Merdeka Barat No. 10',
            'ongkir' => 100, // Harus diabaikan, ongkir harus dari API (22000)
        ];

        $rincian = $this->service->siapkan($input, 350000.50, 750);

        $this->assertSame(MetodePengiriman::Ekspedisi, $rincian->metodePengiriman);
        $this->assertSame('midtrans', $rincian->metodePembayaran);
        $this->assertSame(350000.50, $rincian->subtotal);
        $this->assertSame(22000, $rincian->ongkir);
        $this->assertSame(372000.50, $rincian->total);
        $this->assertSame(372001, $rincian->totalRupiah()); // Pembulatan untuk payment gateway
        $this->assertSame('jne', $rincian->kurirKode);
        $this->assertSame('JNE Express', $rincian->kurirNama);
        $this->assertSame('reg', $rincian->layananKurir);
        $this->assertSame('2-3 hari', $rincian->estimasiKirim);
        $this->assertSame('12345', $rincian->tujuanId);
        $this->assertSame('Kec. Gambir, Jakarta Pusat', $rincian->tujuanLabel);
        $this->assertSame(750, $rincian->beratTotalGram);
        $this->assertSame('Jl. Medan Merdeka Barat No. 10', $rincian->alamatPengiriman);

        // Uji konversi ke atribut pesanan
        $atribut = $rincian->keAtributPesanan();
        $this->assertSame([
            'subtotal' => 350000.50,
            'ongkir' => 22000,
            'total' => 372000.50,
            'metode_pengiriman' => 'ekspedisi',
            'kurir_kode' => 'jne',
            'kurir_nama' => 'JNE Express',
            'layanan_kurir' => 'reg',
            'estimasi_kirim' => '2-3 hari',
            'tujuan_id' => '12345',
            'tujuan_label' => 'Kec. Gambir, Jakarta Pusat',
            'berat_total_gram' => 750,
            'alamat_pengiriman' => 'Jl. Medan Merdeka Barat No. 10',
        ], $atribut);
    }

    public function test_ekspedisi_dengan_cash_ditolak(): void
    {
        $input = [
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'cash',
        ];

        try {
            $this->service->siapkan($input, 150000.0, 500);
            $this->fail('Harus melempar ValidationException untuk kombinasi ekspedisi + cash.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metode', $e->errors());
        }
    }

    public function test_ekspedisi_validasi_input_kosong_dan_tidak_valid(): void
    {
        $baseInput = [
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '12345',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Jl. Sudirman No. 1',
        ];

        // 1. tujuan_id kosong
        $input1 = array_merge($baseInput, ['tujuan_id' => '']);
        try {
            $this->service->siapkan($input1, 100000.0, 500);
            $this->fail('Tujuan ID kosong harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tujuan_id', $e->errors());
        }

        // 2. tujuan_id bukan angka
        $input2 = array_merge($baseInput, ['tujuan_id' => 'abc-123']);
        try {
            $this->service->siapkan($input2, 100000.0, 500);
            $this->fail('Tujuan ID bukan angka harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tujuan_id', $e->errors());
        }

        // 3. kurir_kode kosong
        $input3 = array_merge($baseInput, ['kurir_kode' => '']);
        try {
            $this->service->siapkan($input3, 100000.0, 500);
            $this->fail('Kurir kosong harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('kurir', $e->errors());
        }

        // 4. layanan_kurir kosong
        $input4 = array_merge($baseInput, ['layanan_kurir' => '']);
        try {
            $this->service->siapkan($input4, 100000.0, 500);
            $this->fail('Layanan kurir kosong harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('kurir', $e->errors());
        }

        // 5. alamat_pengiriman kosong
        $input5 = array_merge($baseInput, ['alamat_pengiriman' => '   ']);
        try {
            $this->service->siapkan($input5, 100000.0, 500);
            $this->fail('Alamat kosong harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('alamat_pengiriman', $e->errors());
        }

        // 6. berat 0 gram
        try {
            $this->service->siapkan($baseInput, 100000.0, 0);
            $this->fail('Berat 0 gram harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertTrue(
                isset($e->errors()['ongkir']) || isset($e->errors()['berat']),
                'Harus menolak berat 0 pada kunci ongkir atau berat.'
            );
        }

        // 7. subtotal 0
        try {
            $this->service->siapkan($baseInput, 0.0, 500);
            $this->fail('Subtotal 0 harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertTrue(
                isset($e->errors()['subtotal']) || isset($e->errors()['ongkir']),
                'Harus menolak subtotal 0 pada kunci subtotal atau ongkir.'
            );
        }
    }

    public function test_ekspedisi_layanan_tidak_tersedia_di_api_ditolak_pada_kunci_kurir(): void
    {
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 18000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $input = [
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'SUPER_SPEED_EXPRESS',
            'alamat_pengiriman' => 'Jl. Test No. 1',
        ];

        try {
            $this->service->siapkan($input, 100000.0, 500);
            $this->fail('Harus melempar ValidationException jika layanan kurir tidak tersedia.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('kurir', $e->errors());
            $this->assertStringContainsString('tidak tersedia lagi', $e->errors()['kurir'][0]);
        }
    }

    public function test_ekspedisi_api_gagal_500_dan_timeout_menghasilkan_validation_exception_ongkir_tanpa_api_key(): void
    {
        $rahasia = 'KUNCI_RAHASIA_API_ONGKIR_999';
        Config::set('ongkir.api_key', $rahasia);

        $input = [
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Jl. Test No. 1',
        ];

        // 1. Kasus HTTP 500
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 500, 'message' => "Error with {$rahasia}"],
            ], 500),
        ]);

        try {
            $this->service->siapkan($input, 100000.0, 500);
            $this->fail('Harus melempar ValidationException saat API 500.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ongkir', $e->errors());
            $this->assertStringNotContainsString($rahasia, $e->errors()['ongkir'][0]);
        }

        Cache::flush();

        // 2. Kasus Timeout
        Http::fake([
            '*/calculate/domestic-cost*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        try {
            $this->service->siapkan($input, 100000.0, 500);
            $this->fail('Harus melempar ValidationException saat API timeout.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ongkir', $e->errors());
            $this->assertStringNotContainsString($rahasia, $e->errors()['ongkir'][0]);
        }
    }

    public function test_metode_pengiriman_tidak_dikenal_ditolak_dan_input_ongkir_diabaikan(): void
    {
        // 1. Metode pengiriman tidak dikenal
        try {
            $this->service->siapkan([
                'metode_pengiriman' => 'teleportasi_instan',
                'metode' => 'cash',
            ], 100000.0, 500);
            $this->fail('Metode pengiriman tidak valid harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metode_pengiriman', $e->errors());
        }

        // 2. Input ongkir pembeli diabaikan
        $rincianAmbil = $this->service->siapkan([
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
            'ongkir' => 999999, // Angka palsu dari pembeli
        ], 100000.0, 500);
        $this->assertSame(0, $rincianAmbil->ongkir);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 15000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $rincianEkspedisi = $this->service->siapkan([
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Jl. Test No. 1',
            'ongkir' => 10, // Angka palsu dari pembeli
        ], 100000.0, 500);
        $this->assertSame(15000, $rincianEkspedisi->ongkir); // Harus tetap 15000 dari server
    }

    public function test_tujuan_label_dibersihkan_dari_html_dipangkas_dan_fallback_ke_id(): void
    {
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 15000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        // 1. Tag HTML dibuang
        $rincian1 = $this->service->siapkan([
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Alamat test',
            'tujuan_label' => '<b>Kota Kediri</b> <script>alert(1)</script>',
        ], 100000.0, 500);
        $this->assertSame('Kota Kediri', $rincian1->tujuanLabel);

        // 2. Kosong fallback ke Tujuan #id
        $rincian2 = $this->service->siapkan([
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Alamat test',
            'tujuan_label' => '   ',
        ], 100000.0, 500);
        $this->assertSame('Tujuan #123', $rincian2->tujuanLabel);

        // 3. String panjang dipangkas 255 karakter
        $labelPanjang = str_repeat('A', 300);
        $rincian3 = $this->service->siapkan([
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Alamat test',
            'tujuan_label' => $labelPanjang,
        ], 100000.0, 500);
        $this->assertSame(255, strlen($rincian3->tujuanLabel));
    }

    public function test_aturan_pasangan_dapat_diubah_lewat_config(): void
    {
        // Ubah konfigurasi agar ekspedisi juga menerima cash
        Config::set('ongkir.pembayaran_per_metode.ekspedisi', ['cash', 'midtrans']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 15000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $rincian = $this->service->siapkan([
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'cash',
            'tujuan_id' => '123',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'alamat_pengiriman' => 'Alamat test',
        ], 100000.0, 500);

        $this->assertSame('cash', $rincian->metodePembayaran);
    }

    public function test_pembayaran_diizinkan_mengikuti_config(): void
    {
        $this->assertSame(['cash'], $this->service->pembayaranDiizinkan(MetodePengiriman::AmbilDiTempat));
        $this->assertSame(['midtrans'], $this->service->pembayaranDiizinkan(MetodePengiriman::Ekspedisi));

        Config::set('ongkir.pembayaran_per_metode.ekspedisi', ['cash', 'midtrans', 'transfer']);
        $this->assertSame(['cash', 'midtrans', 'transfer'], $this->service->pembayaranDiizinkan(MetodePengiriman::Ekspedisi));
    }

    public function test_cocok_dengan_membandingkan_subtotal_dan_berat(): void
    {
        $rincian = new RincianPengiriman(
            metodePengiriman: MetodePengiriman::AmbilDiTempat,
            metodePembayaran: 'cash',
            subtotal: 100000.0,
            ongkir: 0,
            total: 100000.0,
            kurirKode: null,
            kurirNama: null,
            layananKurir: null,
            estimasiKirim: null,
            tujuanId: null,
            tujuanLabel: null,
            beratTotalGram: 500,
            alamatPengiriman: 'Ambil di tempat',
        );

        // Cocok persis
        $this->assertTrue($rincian->cocokDengan(100000.0, 500));

        // Toleransi selisih subtotal <= 0.01
        $this->assertTrue($rincian->cocokDengan(100000.005, 500));

        // Selisih subtotal > 0.01 tidak cocok
        $this->assertFalse($rincian->cocokDengan(100000.05, 500));

        // Berat berbeda harus tidak cocok
        $this->assertFalse($rincian->cocokDengan(100000.0, 501));
    }

    public function test_ringkas_keranjang_menghitung_berat_dan_subtotal_dengan_benar(): void
    {
        $pembeli = Pengguna::factory()->create();
        $keranjang = Keranjang::create(['pembeli_id' => $pembeli->id]);

        // Produk 1: berat 600g, harga 100.000, jumlah 2
        $produk1 = Produk::create([
            'kode_produk' => 'PRD-KART-01',
            'nama_produk' => 'Tenun Ikat Premium',
            'jenis' => 'Kain Tenun',
            'harga' => 100000,
            'berat_gram' => 600,
            'status' => 'aktif',
        ]);
        $detail1 = DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk1->id,
            'jumlah' => 2,
        ]);

        // Produk 2: berat 0 (harus memakai default config 500g), harga 50.000, jumlah 1
        $produk2 = Produk::create([
            'kode_produk' => 'PRD-KART-02',
            'nama_produk' => 'Syal Tenun',
            'jenis' => 'Aksesoris',
            'harga' => 50000,
            'berat_gram' => 0,
            'status' => 'aktif',
        ]);
        $detail2 = DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk2->id,
            'jumlah' => 1,
        ]);

        // Produk 3: status nonaktif (harus diabaikan)
        $produk3 = Produk::create([
            'kode_produk' => 'PRD-KART-03',
            'nama_produk' => 'Produk Nonaktif',
            'jenis' => 'Kain Tenun',
            'harga' => 200000,
            'berat_gram' => 500,
            'status' => 'nonaktif',
        ]);
        $detail3 = DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk3->id,
            'jumlah' => 1,
        ]);

        // Produk 4: sudah dihapus/soft deleted (harus diabaikan)
        $produk4 = Produk::create([
            'kode_produk' => 'PRD-KART-04',
            'nama_produk' => 'Produk Terhapus',
            'jenis' => 'Kain Tenun',
            'harga' => 300000,
            'berat_gram' => 500,
            'status' => 'aktif',
        ]);
        $produk4->delete();
        $detail4 = DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk4->id,
            'jumlah' => 1,
        ]);

        // Muat relasi produk
        $detailList = DetailKeranjang::with('produk')->where('keranjang_id', $keranjang->id)->get();

        $ringkasan = $this->service->ringkasKeranjang($detailList);

        // Berat = (600 * 2) + (500 * 1) = 1200 + 500 = 1700 gram
        $this->assertSame(1700, $ringkasan['berat_gram']);

        // Subtotal = (100.000 * 2) + (50.000 * 1) = 200.000 + 50.000 = 250.000
        $this->assertSame(250000.0, $ringkasan['subtotal']);
    }
}

