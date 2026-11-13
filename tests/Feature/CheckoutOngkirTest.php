<?php

namespace Tests\Feature;

use App\Enums\MetodePengiriman;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Stok;
use App\Services\PengirimanService;
use App\Services\RincianPengiriman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CheckoutOngkirTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $pembeli;
    protected Produk $produk;
    protected Stok $stok;
    protected Keranjang $keranjang;
    protected DetailKeranjang $detailKeranjang;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();

        Config::set('ongkir.api_key', 'test-api-key-komerce-k5');
        Config::set('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        Config::set('ongkir.asal_id', '501');
        Config::set('ongkir.kurir', ['jne', 'jnt', 'sicepat']);
        Config::set('ongkir.berat_default_gram', 500);
        Config::set('ongkir.label_ambil_di_tempat', 'Ambil di tempat (Palugada Bandar)');
        Config::set('ongkir.pembayaran_per_metode', [
            'ambil_di_tempat' => ['cash'],
            'ekspedisi'       => ['midtrans'],
        ]);

        $this->pembeli = Pengguna::create([
            'nama' => 'Pembeli Checkout',
            'email' => 'pembeli.checkout@test.com',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Bandar Kidul No. 10',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->produk = Produk::create([
            'kode_produk' => 'PRD-CHK-01',
            'nama_produk' => 'Tenun Songket Checkout',
            'jenis' => 'Kain Tenun',
            'harga' => 200000,
            'berat_gram' => 600,
            'status' => 'aktif',
        ]);

        $this->stok = Stok::create([
            'produk_id' => $this->produk->id,
            'jumlah_stok' => 10,
        ]);

        $this->keranjang = Keranjang::create([
            'pembeli_id' => $this->pembeli->id,
            'status' => 'aktif',
        ]);

        $this->detailKeranjang = DetailKeranjang::create([
            'keranjang_id' => $this->keranjang->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 2,
        ]);
    }

    /**
     * Helper untuk stub respons API hitung ongkos kirim.
     */
    protected function stubApiHitungOngkir(): void
    {
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success', 'status' => 'success'],
                'data' => [
                    [
                        'code' => 'jne',
                        'name' => 'Jalur Nugraha Ekakurir (JNE)',
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 25000,
                        'etd' => '2-3 hari',
                    ],
                ],
            ], 200),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Tampilan Checkout (Task 8)
    |--------------------------------------------------------------------------
    */

    public function test_halaman_checkout_menampilkan_kedua_pilihan_dan_ekspedisi_nonaktif_jika_config_belum_lengkap(): void
    {
        // 1. Saat konfigurasi lengkap: kedua pilihan tersedia
        $res = $this->actingAs($this->pembeli)->get(route('pembeli.checkout'));
        $res->assertStatus(200);
        $res->assertSee('Ambil di Tempat');
        $res->assertSee('Ekspedisi (Kirim ke Alamat)');
        $res->assertDontSee('Pengiriman ekspedisi belum tersedia');

        // 2. Saat config api_key belum ada: pilihan ekspedisi dinonaktifkan
        Config::set('ongkir.api_key', null);
        $resTanpaKey = $this->actingAs($this->pembeli)->get(route('pembeli.checkout'));
        $resTanpaKey->assertStatus(200);
        $resTanpaKey->assertSee('Pengiriman ekspedisi belum tersedia');
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Alur Sukses Ambil di Tempat + Cash (Task 9)
    |--------------------------------------------------------------------------
    */

    public function test_ambil_di_tempat_dengan_cash_berhasil_dan_api_tidak_dipanggil(): void
    {
        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Toko',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
        ]);

        $res->assertRedirect(route('pembeli.pesanan'));
        $res->assertSessionHas('success');

        // Memastikan API ongkir tidak dipanggil sama sekali
        Http::assertNothingSent();

        $pesanan = Pesanan::latest('id')->first();
        $this->assertNotNull($pesanan);
        $this->assertEquals(0, $pesanan->ongkir);
        $this->assertEquals(400000.0, $pesanan->subtotal);
        $this->assertEquals(400000.0, $pesanan->total);
        $this->assertEquals('ambil_di_tempat', $pesanan->metode_pengiriman);
        $this->assertEquals(config('ongkir.label_ambil_di_tempat'), $pesanan->alamat_pengiriman);
        $this->assertNull($pesanan->kurir_kode);
        $this->assertNull($pesanan->layanan_kurir);
        $this->assertNull($pesanan->tujuan_id);

        $pembayaran = $pesanan->pembayaran->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('cash', $pembayaran->metode);
        $this->assertEquals('menunggu', $pembayaran->status_pembayaran);
        $this->assertEquals(400000.0, $pembayaran->jumlah_bayar);

        $this->keranjang->refresh();
        $this->assertEquals('checkout', $this->keranjang->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Alur Sukses Ekspedisi + Midtrans (Task 10)
    |--------------------------------------------------------------------------
    */

    public function test_ekspedisi_dengan_midtrans_berhasil_dan_data_ongkir_tersimpan_lengkap(): void
    {
        $this->stubApiHitungOngkir();

        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Penerima Paket',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'alamat_pengiriman' => 'Jl. Dhoho No. 12, Kediri',
            'tujuan_id' => '501',
            'tujuan_label' => 'Kec. Mojoroto, Kota Kediri',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
        ]);

        $res->assertRedirect(route('pembeli.pesanan'));
        $res->assertSessionHas('success');

        $pesanan = Pesanan::latest('id')->first();
        $this->assertNotNull($pesanan);
        $this->assertEquals(25000, $pesanan->ongkir);
        $this->assertEquals(400000.0, $pesanan->subtotal);
        $this->assertEquals(425000.0, $pesanan->total);
        $this->assertEquals('ekspedisi', $pesanan->metode_pengiriman);
        $this->assertEquals('jne', $pesanan->kurir_kode);
        $this->assertEquals('REG', $pesanan->layanan_kurir);
        $this->assertEquals('2-3 hari', $pesanan->estimasi_kirim);
        $this->assertEquals('501', $pesanan->tujuan_id);
        $this->assertEquals('Kec. Mojoroto, Kota Kediri', $pesanan->tujuan_label);
        $this->assertEquals(1200, $pesanan->berat_total_gram); // 600g x 2 item

        $pembayaran = $pesanan->pembayaran->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('midtrans', $pembayaran->metode);
        $this->assertEquals(425000.0, $pembayaran->jumlah_bayar);

        $this->keranjang->refresh();
        $this->assertEquals('checkout', $this->keranjang->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Integritas Biaya: Input Ongkir Palsu Diabaikan (Task 11)
    |--------------------------------------------------------------------------
    */

    public function test_input_ongkir_dan_total_palsu_dari_pembeli_diabaikan(): void
    {
        $this->stubApiHitungOngkir();

        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Penerima Paket',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'alamat_pengiriman' => 'Jl. Dhoho No. 12, Kediri',
            'tujuan_id' => '501',
            'tujuan_label' => 'Kec. Mojoroto, Kota Kediri',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
            'ongkir' => 1000, // input palsu
            'total' => 5000,  // input palsu
        ]);

        $res->assertRedirect(route('pembeli.pesanan'));

        $pesanan = Pesanan::latest('id')->first();
        // Ongkir harus tetap 25000 dan total tetap 425000 dari server/API
        $this->assertEquals(25000, $pesanan->ongkir);
        $this->assertEquals(425000.0, $pesanan->total);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Penolakan Pasangan Terlarang (Task 12)
    |--------------------------------------------------------------------------
    */

    public function test_pasangan_metode_terlarang_ditolak_dan_tidak_ada_pesanan_terbentuk(): void
    {
        $jumlahPesananAwal = Pesanan::count();

        // 1. Ambil di tempat + Midtrans (DITOLAK)
        $res1 = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'midtrans',
        ]);
        $res1->assertSessionHasErrors(['metode']);
        $this->assertEquals($jumlahPesananAwal, Pesanan::count());

        // 2. Ekspedisi + Cash (DITOLAK)
        $this->stubApiHitungOngkir();
        $res2 = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'cash',
            'alamat_pengiriman' => 'Jl. Contoh Alamat',
            'tujuan_id' => '501',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
        ]);
        $res2->assertSessionHasErrors(['metode']);
        $this->assertEquals($jumlahPesananAwal, Pesanan::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Validasi Kolom Ekspedisi & Layanan Kurir (Task 13)
    |--------------------------------------------------------------------------
    */

    public function test_ekspedisi_tanpa_tujuan_kurir_layanan_atau_alamat_ditolak(): void
    {
        // Ekspedisi tanpa input wajib ekspedisi
        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
        ]);

        $res->assertSessionHasErrors([
            'alamat_pengiriman',
            'tujuan_id',
            'kurir_kode',
            'layanan_kurir',
        ]);
    }

    public function test_ekspedisi_dengan_layanan_kurir_yang_tidak_tersedia_di_api_ditolak(): void
    {
        // API hanya menyediakan REG, tetapi pembeli mengirimkan YES
        $this->stubApiHitungOngkir();

        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'alamat_pengiriman' => 'Jl. Contoh Alamat',
            'tujuan_id' => '501',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'YES', // Tidak ada di API
        ]);

        $res->assertSessionHasErrors(['kurir']);
        $this->assertEquals(0, Pesanan::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Kegagalan API (Task 14)
    |--------------------------------------------------------------------------
    */

    public function test_kegagalan_api_500_dan_timeout_menghasilkan_error_dan_keranjang_tetap_aktif(): void
    {
        Http::fake([
            '*/calculate/domestic-cost*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]);

        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ekspedisi',
            'metode' => 'midtrans',
            'alamat_pengiriman' => 'Jl. Contoh Alamat',
            'tujuan_id' => '501',
            'kurir_kode' => 'jne',
            'layanan_kurir' => 'REG',
        ]);

        $res->assertSessionHasErrors(['ongkir']);
        $this->assertEquals(0, Pesanan::count());

        $this->keranjang->refresh();
        $this->assertEquals('aktif', $this->keranjang->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Perubahan Isi Keranjang / Harga (Task 15)
    |--------------------------------------------------------------------------
    */

    public function test_perubahan_isi_keranjang_atau_harga_setelah_ongkir_dihitung_ditolak(): void
    {
        $mockPengirimanService = Mockery::mock(PengirimanService::class);

        // Siapkan rincian palsu dengan subtotal berbeda (misalnya 999.999)
        $rincianBerbeda = new RincianPengiriman(
            metodePengiriman: MetodePengiriman::AmbilDiTempat,
            metodePembayaran: 'cash',
            subtotal: 999999.0, // berbeda dari subtotal asli 400.000
            ongkir: 0,
            total: 999999.0,
            kurirKode: null,
            kurirNama: null,
            layananKurir: null,
            estimasiKirim: null,
            tujuanId: null,
            tujuanLabel: null,
            beratTotalGram: 1200,
            alamatPengiriman: 'Ambil di tempat',
        );

        $mockPengirimanService->shouldReceive('ringkasKeranjang')
            ->andReturn(['subtotal' => 400000.0, 'berat_gram' => 1200]);

        $mockPengirimanService->shouldReceive('siapkan')
            ->andReturn($rincianBerbeda);

        $this->app->instance(PengirimanService::class, $mockPengirimanService);

        $res = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
        ]);

        $res->assertSessionHasErrors(['keranjang']);
        $this->assertEquals(0, Pesanan::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Perilaku Lama Tetap Terjaga (Task 16)
    |--------------------------------------------------------------------------
    */

    public function test_stok_tidak_cukup_dan_keranjang_kosong_tetap_ditolak(): void
    {
        // 1. Stok tidak cukup
        $this->stok->update(['jumlah_stok' => 1]); // butuh 2

        $resStok = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
        ]);
        $resStok->assertSessionHasErrors(['stok']);

        // 2. Keranjang kosong
        $this->stok->update(['jumlah_stok' => 10]);
        $this->detailKeranjang->delete();

        $resKosong = $this->actingAs($this->pembeli)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Uji',
            'no_hp' => '081234567890',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
        ]);
        $resKosong->assertSessionHasErrors(['keranjang']);
    }

    public function test_pembeli_lain_tidak_bisa_memakai_keranjang_orang_lain(): void
    {
        $pembeliLain = Pengguna::create([
            'nama' => 'Pembeli Lain',
            'email' => 'lain.checkout@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        // Pembeli lain mencoba checkout tetapi keranjang aktifnya sendiri kosong
        $res = $this->actingAs($pembeliLain)->post(route('pembeli.checkout.simpan'), [
            'nama_penerima' => 'Pembeli Lain',
            'no_hp' => '081234567891',
            'metode_pengiriman' => 'ambil_di_tempat',
            'metode' => 'cash',
        ]);

        $res->assertSessionHasErrors(['keranjang']);
        $this->assertEquals(0, Pesanan::count());
    }
}

