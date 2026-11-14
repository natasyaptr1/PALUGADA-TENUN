<?php

namespace Tests\Feature;

use App\Exceptions\OngkirException;
use App\Services\OngkirService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OngkirServiceTest extends TestCase
{
    protected OngkirService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();

        Config::set('ongkir.api_key', 'test-api-key-12345');
        Config::set('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        Config::set('ongkir.asal_id', '501');
        Config::set('ongkir.kurir', ['jne', 'jnt', 'sicepat']);
        Config::set('ongkir.cache_tujuan_menit', 10080);
        Config::set('ongkir.cache_ongkir_menit', 1440);
        Config::set('ongkir.timeout_detik', 5);

        $this->service = app(OngkirService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Tes cariTujuan
    |--------------------------------------------------------------------------
    */

    public function test_cari_tujuan_menghasilkan_data_ternormalisasi_dengan_benar(): void
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

        $hasil = $this->service->cariTujuan('gambir', 10);

        $this->assertCount(1, $hasil);
        $this->assertSame([
            'id' => '1234',
            'label' => 'Kec. Gambir, Kota Jakarta Pusat, DKI Jakarta',
            'provinsi' => 'DKI Jakarta',
            'kota' => 'Jakarta Pusat',
            'kecamatan' => 'Gambir',
            'kelurahan' => 'Gambir',
            'kode_pos' => '10110',
        ], $hasil[0]);
    }

    public function test_cari_tujuan_kata_di_bawah_tiga_karakter_tidak_memanggil_api(): void
    {
        $hasil1 = $this->service->cariTujuan('ga');
        $hasil2 = $this->service->cariTujuan('  g ');
        $hasil3 = $this->service->cariTujuan('');

        $this->assertSame([], $hasil1);
        $this->assertSame([], $hasil2);
        $this->assertSame([], $hasil3);
        Http::assertNothingSent();
    }

    public function test_cari_tujuan_404_dan_200_kosong_keduanya_menghasilkan_array_kosong(): void
    {
        // Kasus 1: HTTP 404 (data null)
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 404, 'message' => 'Data Not Found', 'status' => 'error'],
                'data' => null,
            ], 404),
        ]);

        $hasil404 = $this->service->cariTujuan('tidakada', 10);
        $this->assertSame([], $hasil404);

        Cache::flush();

        // Kasus 2: HTTP 200 dengan data kosong
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [],
            ], 200),
        ]);

        $hasil200Kosong = $this->service->cariTujuan('kosonglagi', 10);
        $this->assertSame([], $hasil200Kosong);
    }

    public function test_cari_tujuan_hasil_di_cache_sehingga_permintaan_kedua_tidak_mengirim_http(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [
                    [
                        'id' => 999,
                        'label' => 'Kota Semarang, Jawa Tengah',
                        'province_name' => 'Jawa Tengah',
                        'city_name' => 'Semarang',
                        'district_name' => 'Semarang Barat',
                        'subdistrict_name' => 'Krobokan',
                        'zip_code' => '50141',
                    ],
                ],
            ], 200),
        ]);

        $hasilPertama = $this->service->cariTujuan('semarang', 10);
        $this->assertCount(1, $hasilPertama);
        Http::assertSentCount(1);

        // Panggilan kedua untuk kata kunci yang sama harus mengambil dari cache
        $hasilKedua = $this->service->cariTujuan('semarang', 10);
        $this->assertCount(1, $hasilKedua);
        Http::assertSentCount(1);
    }

    public function test_cari_tujuan_kegagalan_500_melempar_ongkir_exception_dan_tidak_di_cache(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 500, 'message' => 'Server Error', 'status' => 'error'],
            ], 500),
        ]);

        $this->expectException(OngkirException::class);

        try {
            $this->service->cariTujuan('bandung', 10);
        } finally {
            // Pastikan cache tidak menyimpan kegagalan
            $cacheKey = 'ongkir:tujuan:bandung:10';
            $this->assertFalse(Cache::has($cacheKey), 'Kegagalan tidak boleh disimpan di cache.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Tes hitung
    |--------------------------------------------------------------------------
    */

    public function test_hitung_opsi_dinormalisasi_dan_terurut_biaya_termurah_lalu_nama_kurir(): void
    {
        Config::set('ongkir.kurir', ['jne', 'sicepat']);

        Http::fake([
            '*/calculate/domestic-cost*' => function (Request $request) {
                $courier = $request['courier'];
                if ($courier === 'jne') {
                    return Http::response([
                        'meta' => ['code' => 200, 'status' => 'success'],
                        'data' => [
                            ['name' => 'JNE', 'code' => 'jne', 'service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => 30000, 'etd' => '1'],
                            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 18000, 'etd' => '2-3'],
                        ],
                    ], 200);
                }

                if ($courier === 'sicepat') {
                    return Http::response([
                        'meta' => ['code' => 200, 'status' => 'success'],
                        'data' => [
                            ['name' => 'SiCepat', 'code' => 'sicepat', 'service' => 'SIUNT', 'description' => 'SiUntung', 'cost' => 18000, 'etd' => '2-3'],
                            ['name' => 'SiCepat', 'code' => 'sicepat', 'service' => 'BEST', 'description' => 'Besok Sampai', 'cost' => 28000, 'etd' => '1'],
                        ],
                    ], 200);
                }

                return Http::response(null, 404);
            },
        ]);

        $opsi = $this->service->hitung('101', 500, ['jne', 'sicepat']);

        $this->assertCount(4, $opsi);

        // Biaya 18000 (JNE lalu SiCepat secara alfabetis)
        $this->assertSame('jne', $opsi[0]['kurir_kode']);
        $this->assertSame(18000, $opsi[0]['biaya']);

        $this->assertSame('sicepat', $opsi[1]['kurir_kode']);
        $this->assertSame(18000, $opsi[1]['biaya']);

        // Biaya 28000 (SiCepat BEST)
        $this->assertSame('sicepat', $opsi[2]['kurir_kode']);
        $this->assertSame(28000, $opsi[2]['biaya']);

        // Biaya 30000 (JNE YES)
        $this->assertSame('jne', $opsi[3]['kurir_kode']);
        $this->assertSame(30000, $opsi[3]['biaya']);
    }

    public function test_hitung_berat_dibulatkan_ke_atas_kelipatan_100_gram(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        // Berat 1050 gram harus dibulatkan menjadi 1100 gram
        $this->service->hitung('101', 1050, ['jne']);

        Http::assertSent(function (Request $request) {
            return (int) $request['weight'] === 1100;
        });

        Cache::flush();

        // Berat 1 gram harus dibulatkan menjadi 100 gram
        $this->service->hitung('101', 1, ['jne']);

        Http::assertSent(function (Request $request) {
            return (int) $request['weight'] === 100;
        });
    }

    public function test_hitung_hanya_kurir_yang_ada_di_config_yang_dipanggil(): void
    {
        Config::set('ongkir.kurir', ['jne', 'jnt']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        // Kirim kurir 'pos_indonesia' yang tidak ada di config
        $this->service->hitung('101', 500, ['jne', 'pos_indonesia']);

        Http::assertSent(function (Request $request) {
            return $request['courier'] === 'jne';
        });

        Http::assertNotSent(function (Request $request) {
            return $request['courier'] === 'pos_indonesia';
        });
    }

    public function test_hitung_header_key_terkirim_dan_parameter_price_tidak_dikirim(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $this->service->hitung('101', 500, ['jne']);

        Http::assertSent(function (Request $request) {
            $hasKeyHeader = $request->hasHeader('key', 'test-api-key-12345');
            $noPriceParam = !array_key_exists('price', $request->data());
            $isAsForm = $request->isForm();

            return $hasKeyHeader && $noPriceParam && $isAsForm;
        });
    }

    public function test_hitung_kombinasi_sama_di_cache_dan_kombinasi_berbeda_mengirim_http_baru(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        // Panggilan pertama (HTTP 1)
        $this->service->hitung('101', 500, ['jne']);
        Http::assertSentCount(1);

        // Panggilan kedua sama persis (ambil dari cache, sent count tetap 1)
        $this->service->hitung('101', 500, ['jne']);
        Http::assertSentCount(1);

        // Panggilan dengan tujuan berbeda (HTTP baru, sent count 2)
        $this->service->hitung('102', 500, ['jne']);
        Http::assertSentCount(2);

        // Panggilan dengan berat berbeda setelah pembulatan (HTTP baru, sent count 3)
        $this->service->hitung('101', 750, ['jne']);
        Http::assertSentCount(3);
    }

    public function test_hitung_400_pada_satu_kurir_dilewati_tanpa_melempar_exception(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 400, 'message' => 'Service not available for this route', 'status' => 'error'],
                'data' => null,
            ], 400),
        ]);

        $opsi = $this->service->hitung('999', 500, ['jne']);

        $this->assertSame([], $opsi);
        Http::assertSentCount(1);

        // Pastikan hasil 400 juga di-cache sebagai kosong
        $this->service->hitung('999', 500, ['jne']);
        Http::assertSentCount(1);
    }

    public function test_hitung_satu_kurir_sukses_dan_satu_timeout_mengembalikan_yang_sukses(): void
    {
        Config::set('ongkir.kurir', ['jne', 'sicepat']);

        Http::fake([
            '*/calculate/domestic-cost*' => function (Request $request) {
                if ($request['courier'] === 'jne') {
                    return Http::response([
                        'meta' => ['code' => 200, 'status' => 'success'],
                        'data' => [
                            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 19000, 'etd' => '2-3'],
                        ],
                    ], 200);
                }

                // SiCepat mengalami timeout / connection failure
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $opsi = $this->service->hitung('101', 500, ['jne', 'sicepat']);

        $this->assertCount(1, $opsi);
        $this->assertSame('jne', $opsi[0]['kurir_kode']);
        $this->assertSame(19000, $opsi[0]['biaya']);
    }

    public function test_hitung_semua_kurir_gagal_melempar_ongkir_exception(): void
    {
        Config::set('ongkir.kurir', ['jne', 'sicepat']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 500, 'message' => 'Internal Server Error'],
            ], 500),
        ]);

        $this->expectException(OngkirException::class);
        $this->expectExceptionMessage('Layanan ongkir sedang tidak dapat dihubungi. Silakan coba lagi.');

        $this->service->hitung('101', 500, ['jne', 'sicepat']);
    }

    public function test_hitung_asal_id_kosong_dan_api_key_kosong_melempar_exception(): void
    {
        // 1. Kunci API kosong
        Config::set('ongkir.api_key', '');
        Config::set('ongkir.asal_id', '501');

        try {
            $this->service->hitung('101', 500);
            $this->fail('Harus melempar exception ketika api_key kosong.');
        } catch (OngkirException $e) {
            $this->assertStringContainsString('kunci API belum diatur', $e->getMessage());
        }

        // 2. Asal ID kosong
        Config::set('ongkir.api_key', 'test-key');
        Config::set('ongkir.asal_id', '');

        try {
            $this->service->hitung('101', 500);
            $this->fail('Harus melempar exception ketika asal_id kosong.');
        } catch (OngkirException $e) {
            $this->assertStringContainsString('asal pengiriman toko belum diatur', $e->getMessage());
        }
    }

    public function test_hitung_berat_nol_dan_tujuan_kosong_ditolak(): void
    {
        // 1. Tujuan kosong
        try {
            $this->service->hitung('  ', 500);
            $this->fail('Harus menolak tujuan kosong.');
        } catch (OngkirException $e) {
            $this->assertStringContainsString('Tujuan pengiriman dan berat produk minimal 1 gram wajib diisi', $e->getMessage());
        }

        // 2. Berat 0 gram
        try {
            $this->service->hitung('101', 0);
            $this->fail('Harus menolak berat 0.');
        } catch (OngkirException $e) {
            $this->assertStringContainsString('Tujuan pengiriman dan berat produk minimal 1 gram wajib diisi', $e->getMessage());
        }
    }

    public function test_kunci_api_tidak_pernah_muncul_di_pesan_exception_atau_log(): void
    {
        $rahasia = 'RAHASIA_SUPER_SECRET_KEY_99999';
        Config::set('ongkir.api_key', $rahasia);
        Config::set('ongkir.asal_id', '501');
        Config::set('ongkir.kurir', ['jne']);

        Log::spy();

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 500, 'message' => "Error with {$rahasia}"],
            ], 500),
        ]);

        try {
            $this->service->hitung('101', 500, ['jne']);
            $this->fail('Harus melempar exception.');
        } catch (OngkirException $e) {
            $this->assertStringNotContainsString($rahasia, $e->getMessage());
        }

        // Pastikan tidak ada log warning yang memuat kunci API rahasia
        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message) use ($rahasia) {
                return !str_contains($message, $rahasia);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Tes temukanOpsi
    |--------------------------------------------------------------------------
    */

    public function test_temukan_opsi_menemukan_layanan_yang_cocok_case_insensitive_dan_null_jika_tidak_ada(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 18000, 'etd' => '2-3'],
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => 32000, 'etd' => '1'],
                ],
            ], 200),
        ]);

        // Pencarian dengan huruf kecil 'reg' harus cocok dengan layanan 'REG'
        $opsiReg = $this->service->temukanOpsi('101', 500, 'jne', 'reg');
        $this->assertNotNull($opsiReg);
        $this->assertSame('REG', $opsiReg['layanan']);
        $this->assertSame(18000, $opsiReg['biaya']);

        // Layanan yang tidak tersedia harus mengembalikan null
        $opsiTidakAda = $this->service->temukanOpsi('101', 500, 'jne', 'KARGO');
        $this->assertNull($opsiTidakAda);
    }

    /*
    |--------------------------------------------------------------------------
    | Tes Perintah Artisan
    |--------------------------------------------------------------------------
    */

    public function test_perintah_artisan_ongkir_cari_mencetak_tabel_saat_sukses(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'id' => 101,
                        'label' => 'Kec. Gambir, Jakarta Pusat',
                        'province_name' => 'DKI Jakarta',
                        'city_name' => 'Jakarta Pusat',
                        'district_name' => 'Gambir',
                        'subdistrict_name' => 'Gambir',
                        'zip_code' => '10110',
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('ongkir:cari', ['kata' => 'gambir'])
            ->expectsTable(['ID', 'Label'], [['101', 'Kec. Gambir, Jakarta Pusat']])
            ->assertExitCode(0);
    }

    public function test_perintah_artisan_ongkir_cari_keluar_kode_satu_saat_gagal(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([], 500),
        ]);

        $this->artisan('ongkir:cari', ['kata' => 'gambir'])
            ->assertExitCode(1);
    }

    public function test_perintah_artisan_ongkir_hitung_mencetak_tabel_saat_sukses(): void
    {
        Config::set('ongkir.kurir', ['jne']);

        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 18000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $this->artisan('ongkir:hitung', ['tujuan_id' => '101', 'berat_gram' => 500])
            ->expectsTable(
                ['Kurir', 'Layanan', 'Biaya', 'Estimasi'],
                [['JNE', 'REG', 'Rp 18.000', '2-3']]
            )
            ->assertExitCode(0);
    }

    public function test_perintah_artisan_ongkir_hitung_keluar_kode_satu_saat_gagal(): void
    {
        Config::set('ongkir.api_key', '');

        $this->artisan('ongkir:hitung', ['tujuan_id' => '101', 'berat_gram' => 500])
            ->assertExitCode(1);
    }
}
