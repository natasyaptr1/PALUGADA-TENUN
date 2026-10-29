<?php

namespace Tests\Feature;

use App\Models\DetailPesanan;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPesananTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pengguna $pembeli;
    private Pengguna $penenun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Pengguna::factory()->create([
            'peran' => 'admin',
            'status' => 'aktif',
        ]);

        $this->pembeli = Pengguna::factory()->create([
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->penenun = Pengguna::factory()->create([
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);
    }

    private function buatPesanan(array $atribut = []): Pesanan
    {
        static $urutan = 1;

        $pembeli = $atribut['pembeli'] ?? $this->pembeli;
        unset($atribut['pembeli']);

        $keranjang = Keranjang::create([
            'pembeli_id' => $pembeli->id,
            'status' => 'checkout',
        ]);

        return Pesanan::create(array_merge([
            'pembeli_id' => $pembeli->id,
            'keranjang_id' => $keranjang->id,
            'kode_pesanan' => 'ORD-' . sprintf('%05d', $urutan++),
            'tanggal_pesanan' => now(),
            'total' => 200000,
            'status_pesanan' => 'menunggu_pembayaran',
            'nama_penerima' => 'Penerima Contoh',
            'no_hp' => '081234567890',
            'alamat_pengiriman' => 'Jl. Pengrajin No. 10, Bandar, Kediri',
        ], $atribut));
    }

    public function test_admin_dapat_melihat_daftar_pesanan(): void
    {
        $this->buatPesanan(['kode_pesanan' => 'ORD-10001']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));

        $response->assertOk();
        $response->assertSee('PENJUALAN');
        $response->assertSee('Pesanan');
        $response->assertSee('Pantau dan kelola pesanan pembeli.');
        $response->assertSee('ORD-10001');
        $response->assertDontSee('Tambah Pesanan');
    }

    public function test_pesanan_tampil_dengan_data_yang_benar(): void
    {
        $pembeliKhusus = Pengguna::factory()->create([
            'nama' => 'Dewi Sartika',
            'email' => 'dewi@example.com',
            'peran' => 'pembeli',
        ]);

        $produk = Produk::create([
            'kode_produk' => 'PRD-TENUN-01',
            'nama_produk' => 'Kain Ikat Troso Premium',
            'jenis' => 'Tenun Ikat',
            'harga' => 350000,
            'status' => 'aktif',
        ]);

        $pesanan = $this->buatPesanan([
            'pembeli' => $pembeliKhusus,
            'kode_pesanan' => 'ORD-99001',
            'tanggal_pesanan' => now()->setDate(2026, 10, 25)->setTime(14, 30),
            'total' => 350000,
            'status_pesanan' => 'menunggu_pembayaran',
            'nama_penerima' => 'Dewi Sartika',
        ]);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
            'harga' => 350000,
            'subtotal' => 350000,
        ]);

        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'cash',
            'jumlah_bayar' => 350000,
            'status_pembayaran' => 'menunggu',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));

        $response->assertOk();
        $response->assertSee('ORD-99001');
        $response->assertSee('Dewi Sartika');
        $response->assertSee('dewi@example.com');
        $response->assertSee('25 Okt 2026, 14:30');
        $response->assertSee('1 item');
        $response->assertSee('Rp 350.000');
        $response->assertSee('Cash');
        $response->assertSee('Menunggu');
        $response->assertSee('Menunggu Pembayaran');
    }

    public function test_pencarian_pesanan_berdasarkan_kode_pesanan(): void
    {
        $this->buatPesanan(['kode_pesanan' => 'ORD-KODE-MATCH']);
        $this->buatPesanan(['kode_pesanan' => 'ORD-OTHER-999']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index', [
            'cari' => 'KODE-MATCH',
        ]));

        $response->assertOk();
        $response->assertSee('ORD-KODE-MATCH');
        $response->assertDontSee('ORD-OTHER-999');
    }

    public function test_pencarian_pesanan_berdasarkan_nama_pembeli(): void
    {
        $pembeliTarget = Pengguna::factory()->create(['nama' => 'Bambang Pamungkas', 'peran' => 'pembeli']);
        $pembeliLain = Pengguna::factory()->create(['nama' => 'Ahmad Dahlan', 'peran' => 'pembeli']);

        $this->buatPesanan(['pembeli' => $pembeliTarget, 'kode_pesanan' => 'ORD-BAMBANG']);
        $this->buatPesanan(['pembeli' => $pembeliLain, 'kode_pesanan' => 'ORD-AHMAD']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index', [
            'cari' => 'Bambang',
        ]));

        $response->assertOk();
        $response->assertSee('ORD-BAMBANG');
        $response->assertSee('Bambang Pamungkas');
        $response->assertDontSee('ORD-AHMAD');
        $response->assertDontSee('Ahmad Dahlan');
    }

    public function test_pencarian_pesanan_berdasarkan_nama_penerima(): void
    {
        $this->buatPesanan([
            'nama_penerima' => 'Raden Kartini',
            'kode_pesanan' => 'ORD-KARTINI',
        ]);
        $this->buatPesanan([
            'nama_penerima' => 'Teuku Umar',
            'kode_pesanan' => 'ORD-UMAR',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index', [
            'cari' => 'Kartini',
        ]));

        $response->assertOk();
        $response->assertSee('ORD-KARTINI');
        $response->assertDontSee('ORD-UMAR');
    }

    public function test_filter_pesanan_berdasarkan_tiap_status(): void
    {
        $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran', 'kode_pesanan' => 'ORD-STATUS-MENUNGGU']);
        $this->buatPesanan(['status_pesanan' => 'dibayar', 'kode_pesanan' => 'ORD-STATUS-DIBAYAR']);
        $this->buatPesanan(['status_pesanan' => 'diproses', 'kode_pesanan' => 'ORD-STATUS-DIPROSES']);
        $this->buatPesanan(['status_pesanan' => 'selesai', 'kode_pesanan' => 'ORD-STATUS-SELESAI']);
        $this->buatPesanan(['status_pesanan' => 'batal', 'kode_pesanan' => 'ORD-STATUS-BATAL']);

        // 1. Filter menunggu_pembayaran
        $res = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'menunggu_pembayaran']));
        $res->assertOk();
        $res->assertSee('ORD-STATUS-MENUNGGU');
        $res->assertDontSee('ORD-STATUS-DIBAYAR');

        // 2. Filter dibayar
        $res = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'dibayar']));
        $res->assertOk();
        $res->assertSee('ORD-STATUS-DIBAYAR');
        $res->assertDontSee('ORD-STATUS-MENUNGGU');

        // 3. Filter diproses
        $res = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'diproses']));
        $res->assertOk();
        $res->assertSee('ORD-STATUS-DIPROSES');
        $res->assertDontSee('ORD-STATUS-DIBAYAR');

        // 4. Filter selesai
        $res = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'selesai']));
        $res->assertOk();
        $res->assertSee('ORD-STATUS-SELESAI');
        $res->assertDontSee('ORD-STATUS-DIPROSES');

        // 5. Filter dibatalkan / batal
        $res = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'dibatalkan']));
        $res->assertOk();
        $res->assertSee('ORD-STATUS-BATAL');
        $res->assertDontSee('ORD-STATUS-SELESAI');

        $resBatal = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['status' => 'batal']));
        $resBatal->assertOk();
        $resBatal->assertSee('ORD-STATUS-BATAL');
    }

    public function test_status_tak_dikenal_menampilkan_semua_pesanan(): void
    {
        $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran', 'kode_pesanan' => 'ORD-ALL-1']);
        $this->buatPesanan(['status_pesanan' => 'selesai', 'kode_pesanan' => 'ORD-ALL-2']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index', [
            'status' => 'status_tidak_jelas_123',
        ]));

        $response->assertOk();
        $response->assertSee('ORD-ALL-1');
        $response->assertSee('ORD-ALL-2');
    }

    public function test_kartu_ringkasan_menghitung_semua_pesanan_dan_tidak_terpengaruh_filter(): void
    {
        // 2 menunggu_pembayaran, 3 dibayar, 1 diproses, 4 selesai
        $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran']);
        $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran']);

        $this->buatPesanan(['status_pesanan' => 'dibayar']);
        $this->buatPesanan(['status_pesanan' => 'dibayar']);
        $this->buatPesanan(['status_pesanan' => 'dibayar']);

        $this->buatPesanan(['status_pesanan' => 'diproses']);

        $this->buatPesanan(['status_pesanan' => 'selesai']);
        $this->buatPesanan(['status_pesanan' => 'selesai']);
        $this->buatPesanan(['status_pesanan' => 'selesai']);
        $this->buatPesanan(['status_pesanan' => 'selesai']);

        // Request dengan filter hanya status 'dibayar'
        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index', [
            'status' => 'dibayar',
        ]));

        $response->assertOk();

        // Kartu ringkasan tetap menampilkan jumlah agregat seluruh pesanan
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee('Dibayar');
        $response->assertSee('Diproses');
        $response->assertSee('Selesai');

        // Pastikan variabel ringkasan memiliki nilai agregat yang benar
        $response->assertViewHas('ringkasan', function ($ringkasan) {
            return (int) $ringkasan->menunggu_pembayaran === 2
                && (int) $ringkasan->dibayar === 3
                && (int) $ringkasan->diproses === 1
                && (int) $ringkasan->selesai === 4;
        });
    }

    public function test_pagination_10_pesanan_per_halaman(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->buatPesanan([
                'kode_pesanan' => sprintf('ORD-PAGI-%02d', $i),
                'tanggal_pesanan' => now()->subMinutes(15 - $i),
            ]);
        }

        $resPage1 = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));
        $resPage1->assertOk();
        $resPage1->assertViewHas('daftarPesanan', function ($daftar) {
            return $daftar->count() === 10 && $daftar->total() === 15;
        });

        $resPage2 = $this->actingAs($this->admin)->get(route('admin.pesanan.index', ['page' => 2]));
        $resPage2->assertOk();
        $resPage2->assertViewHas('daftarPesanan', function ($daftar) {
            return $daftar->count() === 5 && $daftar->total() === 15;
        });
    }

    public function test_urutan_terbaru_dulu_berdasarkan_tanggal_dan_id(): void
    {
        $pesananLama = $this->buatPesanan([
            'kode_pesanan' => 'ORD-LAMA-01',
            'tanggal_pesanan' => now()->subDays(5),
        ]);

        $pesananTengah = $this->buatPesanan([
            'kode_pesanan' => 'ORD-TENGAH-02',
            'tanggal_pesanan' => now()->subDay(),
        ]);

        $pesananBaru = $this->buatPesanan([
            'kode_pesanan' => 'ORD-BARU-03',
            'tanggal_pesanan' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));
        $response->assertOk();

        $content = $response->getContent();
        $posBaru = strpos($content, 'ORD-BARU-03');
        $posTengah = strpos($content, 'ORD-TENGAH-02');
        $posLama = strpos($content, 'ORD-LAMA-01');

        $this->assertNotFalse($posBaru);
        $this->assertNotFalse($posTengah);
        $this->assertNotFalse($posLama);
        $this->assertTrue($posBaru < $posTengah);
        $this->assertTrue($posTengah < $posLama);
    }

    public function test_pembayaran_terbaru_yang_ditampilkan_jika_ada_lebih_dari_satu(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-MULTI-BAYAR']);

        // Pembayaran lama
        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'cash',
            'jumlah_bayar' => 200000,
            'status_pembayaran' => 'gagal',
        ]);

        // Pembayaran baru
        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'midtrans',
            'jumlah_bayar' => 200000,
            'status_pembayaran' => 'berhasil',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));
        $response->assertOk();

        // Harus menampilkan metode & status dari pembayaran terbaru
        $response->assertSee('Midtrans');
        $response->assertSee('Berhasil');
    }

    public function test_pesanan_tanpa_pembayaran_tampil_strip(): void
    {
        $this->buatPesanan(['kode_pesanan' => 'ORD-NO-PAYMENT']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));
        $response->assertOk();
        $response->assertSee('ORD-NO-PAYMENT');
        $response->assertSeeInOrder(['ORD-NO-PAYMENT', '-', 'Menunggu Pembayaran']);
        $response->assertDontSee('pesanan-payment-cell');
    }

    public function test_empty_state_tampil_saat_belum_ada_pesanan(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));

        $response->assertOk();
        $response->assertSee('Belum ada pesanan.');
    }

    public function test_pembeli_dan_penenun_tidak_bisa_mengakses_halaman_admin_pesanan(): void
    {
        $resPembeli = $this->actingAs($this->pembeli)->get(route('admin.pesanan.index'));
        $resPembeli->assertForbidden();

        $resPenenun = $this->actingAs($this->penenun)->get(route('admin.pesanan.index'));
        $resPenenun->assertForbidden();
    }

    public function test_dashboard_admin_masih_bisa_dibuka(): void
    {
        $this->buatPesanan(['kode_pesanan' => 'ORD-DASHBOARD-01']);

        $response = $this->actingAs($this->admin)->get(route('admin.beranda'));

        $response->assertOk();
        $response->assertSee('Selamat datang');
        $response->assertSee(route('admin.pesanan.index'));
        $response->assertSee('ORD-DASHBOARD-01');
    }
}
