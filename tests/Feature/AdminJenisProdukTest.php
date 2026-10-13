<?php

namespace Tests\Feature;

use App\Models\JenisProduk;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJenisProdukTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pengguna $pembeli;

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
    }

    public function test_admin_dapat_melihat_daftar_jenis_produk(): void
    {
        JenisProduk::create([
            'nama_jenis' => 'Tenun Ikat Troso',
            'keterangan' => 'Kain tenun khas Troso Jepara',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jenis-produk.index'));

        $response->assertOk();
        $response->assertSee('Tenun Ikat Troso');
        $response->assertSee('Kain tenun khas Troso Jepara');
        $response->assertSee('Jenis Produk');
    }

    public function test_admin_dapat_melihat_halaman_tambah_jenis_produk(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.jenis-produk.create'));

        $response->assertOk();
        $response->assertSee('Tambah Jenis Produk');
        $response->assertSee('Nama Jenis Produk');
    }

    public function test_admin_dapat_menambah_jenis_produk(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.jenis-produk.store'), [
            'nama_jenis' => 'Kain Songket Sutra',
            'keterangan' => 'Songket ditenun dengan benang sutra',
        ]);

        $response->assertRedirect(route('admin.jenis-produk.index'));
        $response->assertSessionHas('success', 'Jenis produk berhasil ditambahkan.');

        $this->assertDatabaseHas('jenis_produk', [
            'nama_jenis' => 'Kain Songket Sutra',
            'keterangan' => 'Songket ditenun dengan benang sutra',
        ]);
    }

    public function test_validasi_nama_jenis_produk_wajib_diisi(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.jenis-produk.create'))->post(route('admin.jenis-produk.store'), [
            'nama_jenis' => '',
            'keterangan' => 'Tanpa nama',
        ]);

        $response->assertRedirect(route('admin.jenis-produk.create'));
        $response->assertSessionHasErrors(['nama_jenis']);
    }

    public function test_validasi_nama_jenis_produk_tidak_boleh_duplikat(): void
    {
        JenisProduk::create(['nama_jenis' => 'Tenun Endek']);

        $response = $this->actingAs($this->admin)->from(route('admin.jenis-produk.create'))->post(route('admin.jenis-produk.store'), [
            'nama_jenis' => 'Tenun Endek',
            'keterangan' => 'Duplikat',
        ]);

        $response->assertRedirect(route('admin.jenis-produk.create'));
        $response->assertSessionHasErrors(['nama_jenis']);
    }

    public function test_admin_dapat_melihat_halaman_edit_jenis_produk(): void
    {
        $jenis = JenisProduk::create([
            'nama_jenis' => 'Sarung Samarinda',
            'keterangan' => 'Khas Kalimantan Timur',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jenis-produk.edit', $jenis));

        $response->assertOk();
        $response->assertSee('Ubah Jenis Produk');
        $response->assertSee('Sarung Samarinda');
    }

    public function test_admin_dapat_mengubah_jenis_produk(): void
    {
        $jenis = JenisProduk::create([
            'nama_jenis' => 'Kain Tenun Baron',
            'keterangan' => 'Motif baron',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.jenis-produk.update', $jenis), [
            'nama_jenis' => 'Kain Tenun Baron 1D',
            'keterangan' => 'Motif baron satu dimensi',
        ]);

        $response->assertRedirect(route('admin.jenis-produk.index'));
        $response->assertSessionHas('success', 'Jenis produk berhasil diperbarui.');

        $this->assertDatabaseHas('jenis_produk', [
            'id' => $jenis->id,
            'nama_jenis' => 'Kain Tenun Baron 1D',
            'keterangan' => 'Motif baron satu dimensi',
        ]);
    }

    public function test_jenis_produk_tidak_dapat_dihapus_jika_memiliki_produk_terkait(): void
    {
        $jenis = JenisProduk::create(['nama_jenis' => 'Kain Tenun Ikat']);

        $produk = Produk::create([
            'kode_produk' => 'PRD-999',
            'nama_produk' => 'Tenun Ikat Eksklusif',
            'jenis_produk_id' => $jenis->id,
            'jenis' => $jenis->nama_jenis,
            'harga' => 250000,
            'status' => 'aktif',
        ]);
        Stok::create(['produk_id' => $produk->id, 'jumlah_stok' => 5]);

        $response = $this->actingAs($this->admin)->delete(route('admin.jenis-produk.destroy', $jenis));

        $response->assertRedirect(route('admin.jenis-produk.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('jenis_produk', [
            'id' => $jenis->id,
        ]);
    }

    public function test_admin_dapat_menghapus_jenis_produk_tanpa_produk_terkait(): void
    {
        $jenis = JenisProduk::create([
            'nama_jenis' => 'Jenis Sementara',
            'keterangan' => 'Tidak dipakai produk',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.jenis-produk.destroy', $jenis));

        $response->assertRedirect(route('admin.jenis-produk.index'));
        $response->assertSessionHas('success', 'Jenis produk berhasil dihapus.');

        $this->assertDatabaseMissing('jenis_produk', [
            'id' => $jenis->id,
        ]);
    }

    public function test_pembeli_tidak_dapat_mengakses_halaman_jenis_produk(): void
    {
        $response = $this->actingAs($this->pembeli)->get(route('admin.jenis-produk.index'));
        $response->assertForbidden();
    }

    public function test_dropdown_jenis_produk_di_tambah_produk_menampilkan_data_jenis_produk(): void
    {
        $jenis = JenisProduk::create(['nama_jenis' => 'Kain Tenun Ulos']);

        $response = $this->actingAs($this->admin)->get(route('admin.produk.create'));

        $response->assertOk();
        $response->assertSee('Kain Tenun Ulos');
    }

    public function test_fitur_pencarian_jenis_produk(): void
    {
        JenisProduk::create(['nama_jenis' => 'Tenun Troso Klasik', 'keterangan' => 'Motif antik']);
        JenisProduk::create(['nama_jenis' => 'Songket Palembang', 'keterangan' => 'Benang emas']);

        $response = $this->actingAs($this->admin)->get(route('admin.jenis-produk.index', ['cari' => 'Troso']));
        $response->assertOk();
        $response->assertSee('Tenun Troso Klasik');
        $response->assertDontSee('Songket Palembang');
    }

    public function test_validasi_nama_jenis_produk_tidak_boleh_hanya_spasi(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.jenis-produk.create'))->post(route('admin.jenis-produk.store'), [
            'nama_jenis' => '     ',
            'keterangan' => 'Nama spasi saja',
        ]);

        $response->assertRedirect(route('admin.jenis-produk.create'));
        $response->assertSessionHasErrors(['nama_jenis']);
    }

    public function test_admin_dapat_mengakses_lewat_route_alias_underscore(): void
    {
        JenisProduk::create(['nama_jenis' => 'Tenun Sumba']);

        $response = $this->actingAs($this->admin)->get(route('admin.jenis_produk.index'));
        $response->assertOk();
        $response->assertSee('Tenun Sumba');
    }

    public function test_update_jenis_produk_mensinkronkan_kolom_jenis_pada_produk(): void
    {
        $jenis = JenisProduk::create(['nama_jenis' => 'Tenun Toraja Lama']);
        $produk = Produk::create([
            'kode_produk' => 'PRD-888',
            'nama_produk' => 'Kain Tenun Toraja',
            'jenis_produk_id' => $jenis->id,
            'jenis' => 'Tenun Toraja Lama',
            'harga' => 300000,
            'status' => 'aktif',
        ]);
        Stok::create(['produk_id' => $produk->id, 'jumlah_stok' => 2]);

        $this->actingAs($this->admin)->put(route('admin.jenis-produk.update', $jenis), [
            'nama_jenis' => 'Tenun Toraja Baru',
            'keterangan' => 'Diperbarui',
        ]);

        $produk->refresh();
        $this->assertEquals('Tenun Toraja Baru', $produk->jenis);
    }
}

