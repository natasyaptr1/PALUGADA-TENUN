<?php

namespace Tests\Feature;

use App\Models\JenisProduk;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProdukBeratTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $admin;
    protected Pengguna $pembeli;
    protected Pengguna $penenun;
    protected JenisProduk $jenis;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ongkir.berat_default_gram', 500);

        $this->admin = Pengguna::create([
            'nama' => 'Admin Toko',
            'email' => 'admin@palugadabandar.id',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'admin',
            'status' => 'aktif',
        ]);

        $this->pembeli = Pengguna::create([
            'nama' => 'Pembeli Uji',
            'email' => 'pembeli@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->penenun = Pengguna::create([
            'nama' => 'Penenun Uji',
            'email' => 'penenun@test.com',
            'kata_sandi' => Hash::make('password123'),
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);

        $this->jenis = JenisProduk::create([
            'nama_jenis' => 'Kain Tenun Ikat',
        ]);
    }

    /**
     * Helper untuk membuat produk contoh.
     */
    protected function buatProduk(array $overrides = []): Produk
    {
        $produk = Produk::create(array_merge([
            'kode_produk' => 'PRD-BERAT-01',
            'nama_produk' => 'Kain Tenun Baron',
            'jenis_produk_id' => $this->jenis->id,
            'jenis' => $this->jenis->nama_jenis,
            'motif' => 'Baron',
            'warna' => 'Hitam',
            'ukuran' => '200x100',
            'material' => 'Katun',
            'harga' => 350000,
            'berat_gram' => 500,
            'status' => 'aktif',
        ], $overrides));

        Stok::create([
            'produk_id' => $produk->id,
            'jumlah_stok' => 5,
        ]);

        return $produk;
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Tambah Produk dengan Kolom Berat
    |--------------------------------------------------------------------------
    */

    public function test_admin_dapat_menambah_produk_dengan_berat_tersimpan(): void
    {
        $payload = [
            'nama_produk' => 'Syal Tenun Eksklusif',
            'jenis_produk_id' => $this->jenis->id,
            'harga' => '150.000',
            'berat_gram' => 750,
            'status' => 'aktif',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.produk.store'), $payload);

        $response->assertRedirect(route('admin.produk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Syal Tenun Eksklusif',
            'berat_gram' => 750,
        ]);
    }

    public function test_berat_kosong_nol_negatif_dan_non_angka_ditolak_saat_tambah(): void
    {
        $basePayload = [
            'nama_produk' => 'Produk Uji Berat',
            'jenis_produk_id' => $this->jenis->id,
            'harga' => 200000,
            'status' => 'aktif',
        ];

        // 1. Berat kosong
        $resKosong = $this->actingAs($this->admin)->post(route('admin.produk.store'), array_merge($basePayload, [
            'berat_gram' => '',
        ]));
        $resKosong->assertSessionHasErrors(['berat_gram']);

        // 2. Berat 0
        $resNol = $this->actingAs($this->admin)->post(route('admin.produk.store'), array_merge($basePayload, [
            'berat_gram' => 0,
        ]));
        $resNol->assertSessionHasErrors(['berat_gram']);

        // 3. Berat negatif
        $resNegatif = $this->actingAs($this->admin)->post(route('admin.produk.store'), array_merge($basePayload, [
            'berat_gram' => -50,
        ]));
        $resNegatif->assertSessionHasErrors(['berat_gram']);

        // 4. Berat bukan angka
        $resBukanAngka = $this->actingAs($this->admin)->post(route('admin.produk.store'), array_merge($basePayload, [
            'berat_gram' => 'lima_ratus',
        ]));
        $resBukanAngka->assertSessionHasErrors(['berat_gram']);

        // 5. Berat melebihi 100.000 gram
        $resLebih = $this->actingAs($this->admin)->post(route('admin.produk.store'), array_merge($basePayload, [
            'berat_gram' => 100001,
        ]));
        $resLebih->assertSessionHasErrors(['berat_gram']);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Ubah Produk dengan Kolom Berat
    |--------------------------------------------------------------------------
    */

    public function test_admin_dapat_mengubah_berat_produk(): void
    {
        $produk = $this->buatProduk(['berat_gram' => 500]);

        $payload = [
            'nama_produk' => $produk->nama_produk,
            'jenis_produk_id' => $this->jenis->id,
            'harga' => 350000,
            'berat_gram' => 850,
            'status' => 'aktif',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.produk.update', $produk), $payload);

        $response->assertRedirect(route('admin.produk.index'));
        $response->assertSessionHas('success');

        $produk->refresh();
        $this->assertEquals(850, $produk->berat_gram);
    }

    public function test_update_tanpa_berat_gram_tidak_mereset_berat(): void
    {
        $produk = $this->buatProduk(['berat_gram' => 650]);

        // Kirim update tanpa field berat_gram sama sekali
        $payload = [
            'nama_produk' => 'Nama Baru Produk',
            'jenis_produk_id' => $this->jenis->id,
            'harga' => 400000,
            'status' => 'aktif',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.produk.update', $produk), $payload);

        $response->assertRedirect(route('admin.produk.index'));
        $response->assertSessionHas('success');

        $produk->refresh();
        // Berat tidak boleh berubah atau reset ke 0 / null
        $this->assertEquals(650, $produk->berat_gram);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Tampilan Daftar dan Form
    |--------------------------------------------------------------------------
    */

    public function test_halaman_daftar_dan_form_menampilkan_berat(): void
    {
        $produk = $this->buatProduk(['berat_gram' => 500]);

        // 1. Halaman daftar menampilkan kolom Berat dan format "500 g"
        $resIndex = $this->actingAs($this->admin)->get(route('admin.produk.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('Berat');
        $resIndex->assertSee('500 g');

        // 2. Form tambah menampilkan input Berat (gram) dan default 500
        $resCreate = $this->actingAs($this->admin)->get(route('admin.produk.create'));
        $resCreate->assertOk();
        $resCreate->assertSee('Berat (gram)');
        $resCreate->assertSee('value="500"', false);
        $resCreate->assertSee('Dipakai untuk menghitung ongkos kirim. Contoh: 500 gram.');

        // 3. Form ubah menampilkan berat tersimpan
        $produkKhusus = $this->buatProduk([
            'kode_produk' => 'PRD-KHUSUS-99',
            'nama_produk' => 'Produk Khusus 900g',
            'berat_gram' => 900,
        ]);
        $resEdit = $this->actingAs($this->admin)->get(route('admin.produk.edit', $produkKhusus));
        $resEdit->assertOk();
        $resEdit->assertSee('Berat (gram)');
        $resEdit->assertSee('value="900"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Uji Hak Akses (Pembeli dan Penenun Ditolak)
    |--------------------------------------------------------------------------
    */

    public function test_pembeli_dan_penenun_tidak_bisa_mengakses_halaman_produk_admin(): void
    {
        // Pembeli ditolak 403
        $this->actingAs($this->pembeli)->get(route('admin.produk.index'))->assertForbidden();
        $this->actingAs($this->pembeli)->get(route('admin.produk.create'))->assertForbidden();

        // Penenun ditolak 403
        $this->actingAs($this->penenun)->get(route('admin.produk.index'))->assertForbidden();
        $this->actingAs($this->penenun)->get(route('admin.produk.create'))->assertForbidden();
    }
}
