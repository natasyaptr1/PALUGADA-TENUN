<?php

namespace Tests\Feature;

use App\Models\HasilProduksi;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\ProfilPenenun;
use App\Models\TargetProduksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTargetProduksiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $penenun1;
    private Pengguna $penenun2;
    private Pengguna $admin;
    private Pengguna $pembeli;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->penenun1 = Pengguna::factory()->create([
            'nama' => 'Penenun Satu',
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);
        ProfilPenenun::create([
            'pengguna_id' => $this->penenun1->id,
            'kode_penenun' => 'PNN-001',
            'keahlian' => 'Tenun Ikat',
        ]);

        $this->penenun2 = Pengguna::factory()->create([
            'nama' => 'Penenun Dua',
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);
        ProfilPenenun::create([
            'pengguna_id' => $this->penenun2->id,
            'kode_penenun' => 'PNN-002',
            'keahlian' => 'Tenun Troso',
        ]);

        $this->admin = Pengguna::factory()->create([
            'peran' => 'admin',
            'status' => 'aktif',
        ]);

        $this->pembeli = Pengguna::factory()->create([
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->produk = Produk::create([
            'kode_produk' => 'PRD-001',
            'nama_produk' => 'Kain Tenun Baron',
            'jenis' => 'Kain Tenun',
            'motif' => 'Geometris',
            'warna' => 'Hijau',
            'ukuran' => '240x115 cm',
            'material' => 'Katun',
            'harga' => 350000,
            'status' => 'aktif',
        ]);
    }

    public function test_tanpa_token_mendapat_401(): void
    {
        $this->getJson('/api/v1/penenun/target')->assertUnauthorized();
        $this->getJson('/api/v1/penenun/target/ringkasan')->assertUnauthorized();
        $this->getJson('/api/v1/penenun/target/1')->assertUnauthorized();
    }

    public function test_admin_dan_pembeli_mendapat_403(): void
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/penenun/target')->assertForbidden();
        $this->getJson('/api/v1/penenun/target/ringkasan')->assertForbidden();
        $this->getJson('/api/v1/penenun/target/1')->assertForbidden();

        Sanctum::actingAs($this->pembeli);
        $this->getJson('/api/v1/penenun/target')->assertForbidden();
        $this->getJson('/api/v1/penenun/target/ringkasan')->assertForbidden();
        $this->getJson('/api/v1/penenun/target/1')->assertForbidden();
    }

    public function test_penenun_melihat_daftar_miliknya_saja_dan_target_penenun_lain_tidak_muncul(): void
    {
        $target1 = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $target2 = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun2->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $target1->id]);
        $response->assertJsonMissing(['id' => $target2->id]);
    }

    public function test_struktur_json_dan_perhitungan_persen_sisa_dan_terlambat_benar(): void
    {
        $deadlineKemarin = Carbon::today()->subDay();
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(5)->toDateString(),
            'tanggal_deadline' => $deadlineKemarin->toDateString(),
            'status' => 'berjalan',
        ]);

        HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 4,
            'tanggal_input' => Carbon::now()->subDays(2),
            'catatan' => '4 potong selesai',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target');
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'produk' => [
                        'id',
                        'kode_produk',
                        'nama_produk',
                        'jenis',
                        'motif',
                        'warna',
                        'ukuran',
                        'material',
                        'foto_url',
                    ],
                    'jumlah_target',
                    'total_selesai',
                    'sisa_target',
                    'persen_progres',
                    'tanggal_mulai',
                    'tanggal_deadline',
                    'status',
                    'terlambat',
                    'hari_terlambat',
                    'dibuat_pada',
                ],
            ],
            'meta' => [
                'halaman_sekarang',
                'per_halaman',
                'total',
                'halaman_terakhir',
            ],
        ]);

        $item = $response->json('data.0');
        $this->assertEquals(10, $item['jumlah_target']);
        $this->assertEquals(4, $item['total_selesai']);
        $this->assertEquals(6, $item['sisa_target']);
        $this->assertEquals(40, $item['persen_progres']);
        $this->assertTrue($item['terlambat']);
        $this->assertEquals(1, $item['hari_terlambat']);
        $this->assertEquals($deadlineKemarin->toDateString(), $item['tanggal_deadline']);
    }

    public function test_detail_milik_sendiri_ok_dengan_riwayat_hasil_terurut_terbaru_dulu(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $hasilLama = HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 3,
            'tanggal_input' => Carbon::now()->subDays(3),
            'catatan' => 'Selesai 3 unit lama',
        ]);

        $hasilBaru = HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 5,
            'tanggal_input' => Carbon::now()->subHour(),
            'catatan' => 'Selesai 5 unit terbaru',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target/' . $target->id);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'produk',
                'jumlah_target',
                'total_selesai',
                'sisa_target',
                'persen_progres',
                'tanggal_mulai',
                'tanggal_deadline',
                'status',
                'terlambat',
                'hari_terlambat',
                'dibuat_pada',
                'riwayat_hasil' => [
                    [
                        'id',
                        'jumlah_selesai',
                        'tanggal_input',
                        'catatan',
                    ],
                ],
            ],
        ]);

        $this->assertEquals(8, $response->json('data.total_selesai'));
        $this->assertCount(2, $response->json('data.riwayat_hasil'));
        $this->assertEquals($hasilBaru->id, $response->json('data.riwayat_hasil.0.id'));
        $this->assertEquals($hasilLama->id, $response->json('data.riwayat_hasil.1.id'));
        $this->assertEquals('Selesai 5 unit terbaru', $response->json('data.riwayat_hasil.0.catatan'));
    }

    public function test_detail_milik_penenun_lain_mengembalikan_404(): void
    {
        $targetPenenun2 = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun2->id,
            'jumlah_target' => 15,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target/' . $targetPenenun2->id);
        $response->assertNotFound();
        $response->assertJson(['pesan' => 'Target produksi tidak ditemukan.']);
    }

    public function test_id_tidak_ada_mengembalikan_404(): void
    {
        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target/999999');
        $response->assertNotFound();
        $response->assertJson(['pesan' => 'Target produksi tidak ditemukan.']);
    }

    public function test_filter_status(): void
    {
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'selesai',
        ]);

        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'dibatalkan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $this->getJson('/api/v1/penenun/target?status=berjalan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'berjalan');

        $this->getJson('/api/v1/penenun/target?status=selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'selesai');

        $this->getJson('/api/v1/penenun/target?status=dibatalkan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'dibatalkan');

        // Nilai lain menampilkan semua target penenun1 (3)
        $this->getJson('/api/v1/penenun/target?status=semua')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_filter_terlambat(): void
    {
        $targetTerlambat = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(5)->toDateString(),
            'tanggal_deadline' => Carbon::today()->subDay()->toDateString(),
            'status' => 'berjalan',
        ]);

        $targetTepatWaktu = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => Carbon::today()->addDays(5)->toDateString(),
            'status' => 'berjalan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target?status=terlambat');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $targetTerlambat->id]);
        $response->assertJsonMissing(['id' => $targetTepatWaktu->id]);
    }

    public function test_paginasi_dan_batas_per_halaman_maks_50(): void
    {
        for ($i = 0; $i < 15; $i++) {
            TargetProduksi::create([
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun1->id,
                'jumlah_target' => 10 + $i,
                'tanggal_mulai' => Carbon::today()->toDateString(),
                'status' => 'berjalan',
            ]);
        }

        Sanctum::actingAs($this->penenun1);

        $responsePage = $this->getJson('/api/v1/penenun/target?per_halaman=5');
        $responsePage->assertOk();
        $responsePage->assertJsonCount(5, 'data');
        $responsePage->assertJsonPath('meta.per_halaman', 5);
        $responsePage->assertJsonPath('meta.total', 15);
        $responsePage->assertJsonPath('meta.halaman_terakhir', 3);

        $responseMax = $this->getJson('/api/v1/penenun/target?per_halaman=100');
        $responseMax->assertOk();
        $responseMax->assertJsonPath('meta.per_halaman', 50);
    }

    public function test_urutan_berjalan_lebih_dulu_dengan_batas_waktu_terdekat_dulu(): void
    {
        // Target Selesai
        $targetSelesai = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'selesai',
        ]);

        // Target Berjalan dengan deadline lebih lama (10 hari lagi)
        $targetBerjalanLama = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => Carbon::today()->addDays(10)->toDateString(),
            'status' => 'berjalan',
        ]);

        // Target Berjalan dengan deadline lebih dekat (2 hari lagi)
        $targetBerjalanDekat = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => Carbon::today()->addDays(2)->toDateString(),
            'status' => 'berjalan',
        ]);

        // Target Berjalan tanpa deadline
        $targetBerjalanTanpaDeadline = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => null,
            'status' => 'berjalan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([
            $targetBerjalanDekat->id,
            $targetBerjalanLama->id,
            $targetBerjalanTanpaDeadline->id,
            $targetSelesai->id,
        ], $ids);
    }

    public function test_ringkasan_menghitung_benar_dan_hanya_milik_sendiri(): void
    {
        // Penenun 1:
        // 1 berjalan tepat waktu
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => Carbon::today()->addDays(5)->toDateString(),
            'status' => 'berjalan',
        ]);

        // 1 berjalan terlambat
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(5)->toDateString(),
            'tanggal_deadline' => Carbon::today()->subDays(2)->toDateString(),
            'status' => 'berjalan',
        ]);

        // 1 selesai
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'selesai',
        ]);

        // 1 dibatalkan
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun1->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'dibatalkan',
        ]);

        // Penenun 2 (harus tidak terhitung untuk penenun 1)
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun2->id,
            'jumlah_target' => 50,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        Sanctum::actingAs($this->penenun1);

        $response = $this->getJson('/api/v1/penenun/target/ringkasan');
        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'berjalan' => 2,
                'selesai' => 1,
                'dibatalkan' => 1,
                'terlambat' => 1,
            ],
        ]);
    }
}
