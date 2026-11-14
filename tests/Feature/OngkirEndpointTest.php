<?php

namespace Tests\Feature;

use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Pengguna;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OngkirEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $pembeli;
    protected Pengguna $admin;
    protected Pengguna $penenun;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();

        Config::set('ongkir.api_key', 'test-komerce-api-key-999');
        Config::set('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        Config::set('ongkir.asal_id', '501');
        Config::set('ongkir.kurir', ['jne', 'jnt', 'sicepat']);
        Config::set('ongkir.berat_default_gram', 500);
        Config::set('ongkir.timeout_detik', 5);

        $this->pembeli = Pengguna::create([
            'nama' => 'Pembeli Uji',
            'email' => 'pembeli@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->admin = Pengguna::create([
            'nama' => 'Admin Uji',
            'email' => 'admin@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'admin',
            'status' => 'aktif',
        ]);

        $this->penenun = Pengguna::create([
            'nama' => 'Penenun Uji',
            'email' => 'penenun@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Hak Akses & Middleware
    |--------------------------------------------------------------------------
    */

    public function test_tamu_tidak_dapat_mengakses_endpoint_ongkir(): void
    {
        $responseTujuan = $this->getJson(route('pembeli.ongkir.tujuan', ['q' => 'kediri']));
        $responseTujuan->assertStatus(401);

        $responseOpsi = $this->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '123']));
        $responseOpsi->assertStatus(401);
    }

    public function test_admin_dan_penenun_ditolak_dengan_status_403(): void
    {
        $responseAdminTujuan = $this->actingAs($this->admin)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'kediri']));
        $responseAdminTujuan->assertStatus(403);

        $responseAdminOpsi = $this->actingAs($this->admin)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '123']));
        $responseAdminOpsi->assertStatus(403);

        $responsePenenunTujuan = $this->actingAs($this->penenun)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'kediri']));
        $responsePenenunTujuan->assertStatus(403);

        $responsePenenunOpsi = $this->actingAs($this->penenun)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '123']));
        $responsePenenunOpsi->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Endpoint Tujuan (GET /akun/ongkir/tujuan)
    |--------------------------------------------------------------------------
    */

    public function test_tujuan_validasi_q_wajib_dan_minimal_tiga_karakter(): void
    {
        // 1. Tanpa parameter q
        $resKosong = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan'));
        $resKosong->assertStatus(422)
            ->assertJsonStructure(['pesan']);

        // 2. Kurang dari 3 karakter
        $resPendek = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'ab']));
        $resPendek->assertStatus(422)
            ->assertJsonStructure(['pesan']);

        // 3. Lebih dari 60 karakter
        $resPanjang = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => str_repeat('a', 61)]));
        $resPanjang->assertStatus(422)
            ->assertJsonStructure(['pesan']);
    }

    public function test_tujuan_menghasilkan_data_ternormalisasi_dengan_benar(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [
                    [
                        'id' => 1234,
                        'label' => 'Kec. Gambir, Kota Jakarta Pusat, DKI Jakarta',
                        'province_name' => 'DKI Jakarta',
                        'city_name' => 'Jakarta Pusat',
                        'district_name' => 'Gambir',
                        'subdistrict_name' => 'Gambir',
                        'zip_code' => '10110',
                    ],
                ],
            ], 200),
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'gambir']));

        $res->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => '1234',
                        'label' => 'Kec. Gambir, Kota Jakarta Pusat, DKI Jakarta',
                        'provinsi' => 'DKI Jakarta',
                        'kota' => 'Jakarta Pusat',
                        'kecamatan' => 'Gambir',
                        'kelurahan' => 'Gambir',
                        'kode_pos' => '10110',
                    ],
                ],
            ]);
    }

    public function test_tujuan_menghasilkan_array_kosong_saat_tidak_ada_data(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [],
            ], 200),
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'wilayahantahberantah']));

        $res->assertStatus(200)
            ->assertExactJson(['data' => []]);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Endpoint Opsi (GET /akun/ongkir/opsi)
    |--------------------------------------------------------------------------
    */

    public function test_opsi_validasi_tujuan_id_wajib_dan_hanya_angka(): void
    {
        // 1. Tanpa tujuan_id
        $resKosong = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi'));
        $resKosong->assertStatus(422)->assertJsonStructure(['pesan']);

        // 2. tujuan_id bukan angka
        $resBukanAngka = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => 'abc']));
        $resBukanAngka->assertStatus(422)->assertJsonStructure(['pesan']);

        // 3. tujuan_id melebihi 30 karakter
        $resPanjang = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => str_repeat('1', 31)]));
        $resPanjang->assertStatus(422)->assertJsonStructure(['pesan']);
    }

    public function test_opsi_validasi_kurir_hanya_huruf_dan_angka(): void
    {
        $resInvalid = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', [
            'tujuan_id' => '501',
            'kurir' => 'jne;rm -rf',
        ]));
        $resInvalid->assertStatus(422)
            ->assertJson(['pesan' => 'Format kode kurir tidak valid. Hanya boleh berupa huruf dan angka.']);
    }

    public function test_opsi_menolak_jika_keranjang_tidak_ada_atau_kosong(): void
    {
        // Pembeli tidak punya keranjang sama sekali
        $resTanpaKeranjang = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '501']));
        $resTanpaKeranjang->assertStatus(422)->assertJson(['pesan' => 'Keranjang masih kosong.']);

        // Pembeli punya keranjang tapi tanpa item detail
        Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);
        $resDetailKosong = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '501']));
        $resDetailKosong->assertStatus(422)->assertJson(['pesan' => 'Keranjang masih kosong.']);
    }

    public function test_opsi_mengabaikan_keranjang_berstatus_checkout(): void
    {
        $keranjangCheckout = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'checkout',
        ]);

        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-CHK',
            'nama_produk' => 'Tenun Uji Checkout',
            'jenis' => 'Kain Tenun',
            'harga' => 150000,
            'berat_gram' => 400,
            'status' => 'aktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjangCheckout->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '501']));
        $res->assertStatus(422)->assertJson(['pesan' => 'Keranjang masih kosong.']);
    }

    public function test_opsi_mengabaikan_keranjang_milik_pembeli_lain(): void
    {
        $pembeliLain = Pengguna::create([
            'nama' => 'Pembeli Lain',
            'email' => 'lain@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $keranjangLain = Keranjang::create([
            'pembeli_id' => $pembeliLain->id,
            'status' => 'aktif',
        ]);

        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-LAIN',
            'nama_produk' => 'Tenun Uji Pembeli Lain',
            'jenis' => 'Kain Tenun',
            'harga' => 200000,
            'berat_gram' => 500,
            'status' => 'aktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjangLain->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '501']));
        $res->assertStatus(422)->assertJson(['pesan' => 'Keranjang masih kosong.']);
    }

    public function test_opsi_mengabaikan_produk_nonaktif_dan_terhapus(): void
    {
        $keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        $produkNonaktif = Produk::create([
            'kode_produk' => 'PRD-TEST-NON',
            'nama_produk' => 'Produk Nonaktif',
            'jenis' => 'Kain',
            'harga' => 100000,
            'berat_gram' => 300,
            'status' => 'nonaktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produkNonaktif->id,
            'jumlah' => 1,
        ]);

        // Karena hanya produk nonaktif, keranjang dianggap kosong
        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', ['tujuan_id' => '501']));
        $res->assertStatus(422)->assertJson(['pesan' => 'Keranjang masih kosong.']);
    }

    public function test_opsi_menghitung_berat_dan_subtotal_dengan_benar_dan_membulatkan_berat_di_api(): void
    {
        $keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        // Produk 1: berat 300g, harga 100.000, jumlah 2 -> berat 600g, subtotal 200.000
        $produk1 = Produk::create([
            'kode_produk' => 'PRD-TEST-01',
            'nama_produk' => 'Tenun Ikat 1',
            'jenis' => 'Kain Tenun',
            'harga' => 100000,
            'berat_gram' => 300,
            'status' => 'aktif',
        ]);
        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk1->id,
            'jumlah' => 2,
        ]);

        // Produk 2: berat 250g, harga 150.000, jumlah 1 -> berat 250g, subtotal 150.000
        $produk2 = Produk::create([
            'kode_produk' => 'PRD-TEST-02',
            'nama_produk' => 'Tenun Ikat 2',
            'jenis' => 'Kain Tenun',
            'harga' => 150000,
            'berat_gram' => 250,
            'status' => 'aktif',
        ]);
        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk2->id,
            'jumlah' => 1,
        ]);

        // Total berat = 600 + 250 = 850 gram. Di API dibulatkan ke atas per 100g = 900 gram.
        // Total subtotal = 200.000 + 150.000 = 350.000.

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [
                    [
                        'code' => 'jne',
                        'name' => 'JNE',
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 24000,
                        'etd' => '2-3 hari',
                    ],
                ],
            ], 200),
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', [
            'tujuan_id' => '501',
            'kurir' => 'jne',
        ]));

        $res->assertStatus(200)
            ->assertJson([
                'data' => [
                    'berat_total_gram' => 850,
                    'subtotal' => 350000.0,
                    'opsi' => [
                        [
                            'kurir_kode' => 'jne',
                            'kurir_nama' => 'JNE',
                            'layanan' => 'REG',
                            'biaya' => 24000,
                            'estimasi' => '2-3 hari',
                        ],
                    ],
                ],
            ]);

        // Verifikasi body HTTP yang dikirim ke API kurir: weight harus 900 gram
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost'
                && $request['destination'] == '501'
                && $request['weight'] === 900
                && $request['courier'] === 'jne';
        });
    }

    public function test_opsi_memakai_berat_default_saat_berat_gram_produk_nol(): void
    {
        $keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        // Produk dengan berat_gram = 0 (harus memakai default 500g)
        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-DEF',
            'nama_produk' => 'Syal Default',
            'jenis' => 'Aksesoris',
            'harga' => 75000,
            'berat_gram' => 0,
            'status' => 'aktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
        ]);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [
                    [
                        'code' => 'jnt',
                        'name' => 'J&T Express',
                        'service' => 'EZ',
                        'description' => 'Reguler Service',
                        'cost' => 18000,
                        'etd' => '1-2 hari',
                    ],
                ],
            ], 200),
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', [
            'tujuan_id' => '501',
            'kurir' => 'jnt',
        ]));

        $res->assertStatus(200)
            ->assertJson([
                'data' => [
                    'berat_total_gram' => 500,
                    'subtotal' => 75000.0,
                ],
            ]);

        Http::assertSent(function (Request $request) {
            return $request['weight'] === 500;
        });
    }

    public function test_opsi_menghasilkan_opsi_kosong_dengan_pesan_ramah_saat_kurir_tidak_tersedia(): void
    {
        $keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-OPS',
            'nama_produk' => 'Tenun Kosong Opsi',
            'jenis' => 'Kain',
            'harga' => 80000,
            'berat_gram' => 500,
            'status' => 'aktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
        ]);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [],
            ], 200),
        ]);

        $res = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', [
            'tujuan_id' => '501',
            'kurir' => 'jne',
        ]));

        $res->assertStatus(200)
            ->assertJson([
                'data' => [
                    'berat_total_gram' => 500,
                    'subtotal' => 80000.0,
                    'opsi' => [],
                ],
                'pesan' => 'Tidak ada layanan pengiriman untuk tujuan ini.',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Penanganan Error API & Kemanan Kunci API
    |--------------------------------------------------------------------------
    */

    public function test_kegagalan_api_500_dan_timeout_menghasilkan_status_503_tanpa_kunci_api(): void
    {
        $keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        $produk = Produk::create([
            'kode_produk' => 'PRD-TEST-ERR',
            'nama_produk' => 'Tenun Uji Error',
            'jenis' => 'Kain',
            'harga' => 100000,
            'berat_gram' => 500,
            'status' => 'aktif',
        ]);

        DetailKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
        ]);

        // 1. Uji kegagalan 500 pada opsi
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]);

        $res500 = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.opsi', [
            'tujuan_id' => '501',
            'kurir' => 'jne',
        ]));

        $res500->assertStatus(503)
            ->assertJson(['pesan' => 'Layanan ongkir sedang tidak tersedia. Silakan coba lagi.']);
        $this->assertStringNotContainsString('test-komerce-api-key-999', (string) $res500->getContent());

        // 2. Uji timeout pada tujuan
        Cache::flush();
        Http::fake([
            '*/destination/domestic-destination*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $resTimeout = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'kediri']));

        $resTimeout->assertStatus(503)
            ->assertJson(['pesan' => 'Layanan ongkir sedang tidak tersedia. Silakan coba lagi.']);
        $this->assertStringNotContainsString('test-komerce-api-key-999', (string) $resTimeout->getContent());
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Throttle
    |--------------------------------------------------------------------------
    */

    public function test_throttle_permintaan_ke_31_pada_tujuan_mengembalikan_429(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [],
            ], 200),
        ]);

        // Kirim 30 permintaan pertama (harus berhasil status 200)
        for ($i = 1; $i <= 30; $i++) {
            $response = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'bandar']));
            $response->assertStatus(200);
        }

        // Permintaan ke-31 harus ditolak dengan status 429 Too Many Requests
        $responseKe31 = $this->actingAs($this->pembeli)->getJson(route('pembeli.ongkir.tujuan', ['q' => 'bandar']));
        $responseKe31->assertStatus(429);
    }
}

