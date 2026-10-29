<?php

namespace Tests\Feature;

use App\Models\DetailPesanan;
use App\Models\Keranjang;
use App\Models\MutasiStok;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
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

    public function test_admin_membuka_detail_pesanan_dan_datanya_benar(): void
    {
        $pesanan = $this->buatPesanan([
            'kode_pesanan' => 'ORD-DETAIL-001',
            'nama_penerima' => 'Surya Kencana',
            'no_hp' => '08987654321',
            'alamat_pengiriman' => "Jl. Merdeka No. 45\nKel. Bandar, Kediri",
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertSee('PENJUALAN');
        $response->assertSee('Detail Pesanan');
        $response->assertSee('ORD-DETAIL-001');
        $response->assertSee('Surya Kencana');
        $response->assertSee('08987654321');
        $response->assertSee("Jl. Merdeka No. 45<br />\nKel. Bandar, Kediri", false);
        $response->assertSee(route('admin.pesanan.index'));
    }

    public function test_item_pesanan_tampil_dengan_harga_jumlah_dan_subtotal(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-DETAIL-ITEM',
            'nama_produk' => 'Kain Tenun Ikat Baron',
            'jenis' => 'Tenun Ikat',
            'harga' => 250000,
            'status' => 'aktif',
        ]);

        $pesanan = $this->buatPesanan([
            'kode_pesanan' => 'ORD-DETAIL-ITEM-01',
            'total' => 500000,
        ]);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'produk_id' => $produk->id,
            'jumlah' => 2,
            'harga' => 250000,
            'subtotal' => 500000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertSee('Kain Tenun Ikat Baron');
        $response->assertSee('PRD-DETAIL-ITEM');
        $response->assertSee('Rp 250.000');
        $response->assertSee('2');
        $response->assertSee('Rp 500.000');
    }

    public function test_pembayaran_pada_detail_tampil_terbaru_dulu(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-PAY-ORDER']);

        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'cash',
            'jumlah_bayar' => 150000,
            'status_pembayaran' => 'gagal',
            'id_transaksi' => 'TX-LAMA-001',
        ]);

        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'midtrans',
            'jumlah_bayar' => 150000,
            'status_pembayaran' => 'berhasil',
            'id_transaksi' => 'TX-BARU-002',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();

        $content = $response->getContent();
        $posBaru = strpos($content, 'TX-BARU-002');
        $posLama = strpos($content, 'TX-LAMA-001');

        $this->assertNotFalse($posBaru);
        $this->assertNotFalse($posLama);
        $this->assertTrue($posBaru < $posLama);
    }

    public function test_pesanan_tanpa_pembayaran_menampilkan_teks_belum_ada_data_pembayaran(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-NO-PAY-DETAIL']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertSee('Belum ada data pembayaran.');
    }

    public function test_dikonfirmasi_oleh_tampil_jika_ada(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-CONFIRM']);

        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'cash',
            'jumlah_bayar' => 200000,
            'status_pembayaran' => 'berhasil',
            'dikonfirmasi_oleh' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertSee($this->admin->nama);
    }

    public function test_produk_yang_sudah_dihapus_soft_delete_tidak_membuat_halaman_error(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-DELETED-99',
            'nama_produk' => 'Tenun Khusus Edisi Terbatas',
            'jenis' => 'Tenun Eksklusif',
            'harga' => 450000,
            'status' => 'aktif',
        ]);

        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-DELETED-PRD']);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
            'harga' => 450000,
            'subtotal' => 450000,
        ]);

        $produk->delete(); // Soft delete

        $this->assertSoftDeleted('produk', ['id' => $produk->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertSee('Tenun Khusus Edisi Terbatas');
        $response->assertSee('(produk dihapus)');
    }

    public function test_detail_pesanan_dengan_id_tidak_ada_menghasilkan_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', 999999));

        $response->assertNotFound();
    }

    public function test_tautan_detail_ada_di_halaman_index(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-INDEX-LINK']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.index'));

        $response->assertOk();
        $response->assertSee(route('admin.pesanan.show', $pesanan));
        $response->assertSee('Detail');
    }

    public function test_pembeli_dan_penenun_tidak_bisa_mengakses_detail_pesanan(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-FORBIDDEN']);

        $resPembeli = $this->actingAs($this->pembeli)->get(route('admin.pesanan.show', $pesanan));
        $resPembeli->assertForbidden();

        $resPenenun = $this->actingAs($this->penenun)->get(route('admin.pesanan.show', $pesanan));
        $resPenenun->assertForbidden();
    }

    public function test_halaman_detail_tidak_menampilkan_tombol_konfirmasi_pembayaran(): void
    {
        $pesanan = $this->buatPesanan(['kode_pesanan' => 'ORD-NO-ACTION']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pesanan));

        $response->assertOk();
        $response->assertDontSee('Konfirmasi Pembayaran');
    }

    public function test_transisi_status_valid_berhasil_proses_selesai_batalkan(): void
    {
        // 1. Aksi proses (dari dibayar ke diproses)
        $pesananDibayar = $this->buatPesanan([
            'kode_pesanan' => 'ORD-TR-DIBAYAR',
            'status_pesanan' => 'dibayar',
        ]);

        $responseProses = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesananDibayar), [
            'aksi' => 'proses',
        ]);
        $responseProses->assertRedirect(route('admin.pesanan.show', $pesananDibayar));
        $responseProses->assertSessionHas('success', 'Pesanan berhasil diproses.');
        $pesananDibayar->refresh();
        $this->assertEquals('diproses', $pesananDibayar->status_pesanan);

        // 2. Aksi selesai (dari diproses ke selesai)
        $responseSelesai = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesananDibayar), [
            'aksi' => 'selesai',
        ]);
        $responseSelesai->assertRedirect(route('admin.pesanan.show', $pesananDibayar));
        $responseSelesai->assertSessionHas('success', 'Pesanan ditandai selesai.');
        $pesananDibayar->refresh();
        $this->assertEquals('selesai', $pesananDibayar->status_pesanan);

        // 3. Aksi batalkan (dari menunggu_pembayaran ke batal)
        $pesananMenunggu = $this->buatPesanan([
            'kode_pesanan' => 'ORD-TR-MENUNGGU',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);

        $responseBatal = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesananMenunggu), [
            'aksi' => 'batalkan',
        ]);
        $responseBatal->assertRedirect(route('admin.pesanan.show', $pesananMenunggu));
        $responseBatal->assertSessionHas('success', 'Pesanan berhasil dibatalkan.');
        $pesananMenunggu->refresh();
        $this->assertEquals('batal', $pesananMenunggu->status_pesanan);
    }

    public function test_batalkan_mengubah_pembayaran_menunggu_menjadi_gagal_dan_tidak_mengubah_pembayaran_berhasil(): void
    {
        $pesanan = $this->buatPesanan([
            'kode_pesanan' => 'ORD-BATAL-BAYAR',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);

        $bayarMenunggu = Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'cash',
            'jumlah_bayar' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $bayarBerhasil = Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode' => 'midtrans',
            'jumlah_bayar' => 100000,
            'status_pembayaran' => 'berhasil',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'batalkan',
        ]);

        $response->assertRedirect(route('admin.pesanan.show', $pesanan));
        $response->assertSessionHas('success');

        $bayarMenunggu->refresh();
        $bayarBerhasil->refresh();

        $this->assertEquals('gagal', $bayarMenunggu->status_pembayaran);
        $this->assertEquals('berhasil', $bayarBerhasil->status_pembayaran);
    }

    public function test_tiap_transisi_tidak_valid_ditolak_dan_status_tidak_berubah(): void
    {
        $pesanError = 'Status pesanan sudah berubah atau aksi tidak diizinkan. Muat ulang halaman.';

        // 1. menunggu_pembayaran ke proses
        $p1 = $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran']);
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p1), ['aksi' => 'proses'])
            ->assertRedirect(route('admin.pesanan.show', $p1))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('menunggu_pembayaran', $p1->fresh()->status_pesanan);

        // 2. menunggu_pembayaran ke selesai
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p1), ['aksi' => 'selesai'])
            ->assertRedirect(route('admin.pesanan.show', $p1))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('menunggu_pembayaran', $p1->fresh()->status_pesanan);

        // 3. dibayar ke selesai
        $p2 = $this->buatPesanan(['status_pesanan' => 'dibayar']);
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p2), ['aksi' => 'selesai'])
            ->assertRedirect(route('admin.pesanan.show', $p2))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('dibayar', $p2->fresh()->status_pesanan);

        // 4. dibayar ke batalkan
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p2), ['aksi' => 'batalkan'])
            ->assertRedirect(route('admin.pesanan.show', $p2))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('dibayar', $p2->fresh()->status_pesanan);

        // 5. diproses ke batalkan
        $p3 = $this->buatPesanan(['status_pesanan' => 'diproses']);
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p3), ['aksi' => 'batalkan'])
            ->assertRedirect(route('admin.pesanan.show', $p3))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('diproses', $p3->fresh()->status_pesanan);

        // 6. diproses ke proses
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p3), ['aksi' => 'proses'])
            ->assertRedirect(route('admin.pesanan.show', $p3))
            ->assertSessionHas('error', $pesanError);
        $this->assertEquals('diproses', $p3->fresh()->status_pesanan);

        // 7. selesai ke aksi apa pun
        $p4 = $this->buatPesanan(['status_pesanan' => 'selesai']);
        foreach (['proses', 'selesai', 'batalkan'] as $aksi) {
            $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p4), ['aksi' => $aksi])
                ->assertRedirect(route('admin.pesanan.show', $p4))
                ->assertSessionHas('error', $pesanError);
            $this->assertEquals('selesai', $p4->fresh()->status_pesanan);
        }

        // 8. batal ke aksi apa pun
        $p5 = $this->buatPesanan(['status_pesanan' => 'batal']);
        foreach (['proses', 'selesai', 'batalkan'] as $aksi) {
            $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $p5), ['aksi' => $aksi])
                ->assertRedirect(route('admin.pesanan.show', $p5))
                ->assertSessionHas('error', $pesanError);
            $this->assertEquals('batal', $p5->fresh()->status_pesanan);
        }
    }

    public function test_aksi_tidak_dikenal_ditolak(): void
    {
        $pesanan = $this->buatPesanan(['status_pesanan' => 'dibayar']);

        $response = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'konfirmasi_ngawur',
        ]);

        $response->assertSessionHasErrors(['aksi']);
        $this->assertEquals('dibayar', $pesanan->fresh()->status_pesanan);
    }

    public function test_permintaan_kedua_yang_basi_tidak_mengubah_apa_pun(): void
    {
        $pesanan = $this->buatPesanan(['status_pesanan' => 'dibayar']);

        // Permintaan pertama
        $res1 = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'proses',
        ]);
        $res1->assertSessionHas('success');
        $this->assertEquals('diproses', $pesanan->fresh()->status_pesanan);

        // Permintaan kedua dengan status yang sudah basi (sekarang sudah diproses, minta proses lagi)
        $res2 = $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'proses',
        ]);
        $res2->assertSessionHas('error', 'Status pesanan sudah berubah atau aksi tidak diizinkan. Muat ulang halaman.');
        $this->assertEquals('diproses', $pesanan->fresh()->status_pesanan);
    }

    public function test_jumlah_stok_dan_baris_mutasi_stok_tidak_berubah_oleh_semua_aksi(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'PRD-STOK-TEST',
            'nama_produk' => 'Kain Stok Uji',
            'jenis' => 'Tenun Ikat',
            'harga' => 100000,
            'status' => 'aktif',
        ]);
        $stok = Stok::create(['produk_id' => $produk->id, 'jumlah_stok' => 15]);

        $mutasiAwal = MutasiStok::count();

        // 1. Aksi proses
        $pesanan1 = $this->buatPesanan(['status_pesanan' => 'dibayar']);
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan1), ['aksi' => 'proses']);
        $this->assertEquals(15, $stok->fresh()->jumlah_stok);
        $this->assertEquals($mutasiAwal, MutasiStok::count());

        // 2. Aksi selesai
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan1), ['aksi' => 'selesai']);
        $this->assertEquals(15, $stok->fresh()->jumlah_stok);
        $this->assertEquals($mutasiAwal, MutasiStok::count());

        // 3. Aksi batalkan
        $pesanan2 = $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran']);
        $this->actingAs($this->admin)->patch(route('admin.pesanan.status', $pesanan2), ['aksi' => 'batalkan']);
        $this->assertEquals(15, $stok->fresh()->jumlah_stok);
        $this->assertEquals($mutasiAwal, MutasiStok::count());
    }

    public function test_method_get_ke_url_status_menghasilkan_405(): void
    {
        $pesanan = $this->buatPesanan(['status_pesanan' => 'dibayar']);

        $response = $this->actingAs($this->admin)->get(route('admin.pesanan.status', $pesanan));

        $response->assertStatus(405);
    }

    public function test_pembeli_dan_penenun_tidak_bisa_memakai_route_status(): void
    {
        $pesanan = $this->buatPesanan(['status_pesanan' => 'dibayar']);

        $resPembeli = $this->actingAs($this->pembeli)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'proses',
        ]);
        $resPembeli->assertForbidden();

        $resPenenun = $this->actingAs($this->penenun)->patch(route('admin.pesanan.status', $pesanan), [
            'aksi' => 'proses',
        ]);
        $resPenenun->assertForbidden();

        $this->assertEquals('dibayar', $pesanan->fresh()->status_pesanan);
    }

    public function test_tombol_yang_tampil_di_halaman_detail_sesuai_status(): void
    {
        // 1. Menunggu Pembayaran
        $pMenunggu = $this->buatPesanan(['status_pesanan' => 'menunggu_pembayaran']);
        $resMenunggu = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pMenunggu));
        $resMenunggu->assertOk();
        $resMenunggu->assertSee('Batalkan Pesanan');
        $resMenunggu->assertSee('Menunggu pembayaran. Status berubah menjadi Dibayar otomatis setelah pembayaran dikonfirmasi.');
        $resMenunggu->assertDontSee('Proses Pesanan');
        $resMenunggu->assertDontSee('Tandai Selesai');

        // 2. Dibayar
        $pDibayar = $this->buatPesanan(['status_pesanan' => 'dibayar']);
        $resDibayar = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pDibayar));
        $resDibayar->assertOk();
        $resDibayar->assertSee('Proses Pesanan');
        $resDibayar->assertSee('Pesanan yang sudah dibayar tidak dapat dibatalkan dari halaman ini.');
        $resDibayar->assertDontSee('Batalkan Pesanan');
        $resDibayar->assertDontSee('Tandai Selesai');

        // 3. Diproses
        $pDiproses = $this->buatPesanan(['status_pesanan' => 'diproses']);
        $resDiproses = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pDiproses));
        $resDiproses->assertOk();
        $resDiproses->assertSee('Tandai Selesai');
        $resDiproses->assertSee('Pesanan yang sudah dibayar tidak dapat dibatalkan dari halaman ini.');
        $resDiproses->assertDontSee('Proses Pesanan');
        $resDiproses->assertDontSee('Batalkan Pesanan');

        // 4. Selesai
        $pSelesai = $this->buatPesanan(['status_pesanan' => 'selesai']);
        $resSelesai = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pSelesai));
        $resSelesai->assertOk();
        $resSelesai->assertSee('Pesanan ini sudah selesai dan statusnya tidak dapat diubah.');
        $resSelesai->assertDontSee('Proses Pesanan');
        $resSelesai->assertDontSee('Tandai Selesai');
        $resSelesai->assertDontSee('Batalkan Pesanan');

        // 5. Batal
        $pBatal = $this->buatPesanan(['status_pesanan' => 'batal']);
        $resBatal = $this->actingAs($this->admin)->get(route('admin.pesanan.show', $pBatal));
        $resBatal->assertOk();
        $resBatal->assertSee('Pesanan ini sudah dibatalkan dan statusnya tidak dapat diubah.');
        $resBatal->assertDontSee('Proses Pesanan');
        $resBatal->assertDontSee('Tandai Selesai');
        $resBatal->assertDontSee('Batalkan Pesanan');
    }
}
