<?php

namespace Tests\Feature;

use App\Models\HasilProduksi;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\ProfilPenenun;
use App\Models\TargetProduksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminTargetProduksiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pengguna $pembeli;
    private Pengguna $penenun;
    private Produk $produk;

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
            'nama' => 'Siti Rohani',
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);

        ProfilPenenun::create([
            'pengguna_id' => $this->penenun->id,
            'kode_penenun' => 'PNN-001',
            'keahlian' => 'Tenun Troso Ikat',
        ]);

        $this->produk = Produk::create([
            'kode_produk' => 'PRD-001',
            'nama_produk' => 'Kain Tenun Troso Masterpiece',
            'jenis' => 'Kain Tenun',
            'harga' => 350000,
            'status' => 'aktif',
        ]);
    }

    public function test_admin_melihat_daftar_target_produksi(): void
    {
        $deadline = Carbon::today()->addDays(20);

        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => $deadline->toDateString(),
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.target-produksi.index'));

        $response->assertOk();
        $response->assertSee('Target Produksi');
        $response->assertSee('Kain Tenun Troso Masterpiece');
        $response->assertSee('Siti Rohani');
        $response->assertSee('0 / 20');
        $response->assertSee($deadline->format('d M Y'));
        $response->assertSee('Berjalan');
    }

    public function test_admin_melihat_form_tambah_target_produksi(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.target-produksi.create'));

        $response->assertOk();
        $response->assertSee('Buat Target Produksi');
        $response->assertSee('PRODUKSI');
        $response->assertSee('Kain Tenun Troso Masterpiece');
        $response->assertSee('Siti Rohani - PNN-001 - Tenun Troso Ikat');
        $response->assertSee('Simpan Target');
        $response->assertSee('Tidak boleh sebelum hari ini.');
    }

    public function test_tambah_target_valid_tersimpan_dengan_status_berjalan(): void
    {
        $tanggalMulai = Carbon::today()->toDateString();
        $tanggalDeadline = Carbon::today()->addMonth()->toDateString();

        $payload = [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 15,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_deadline' => $tanggalDeadline,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.target-produksi.store'), $payload);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success', 'Target produksi berhasil dibuat.');

        $this->assertDatabaseHas('target_produksi', [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 15,
            'status' => 'berjalan',
        ]);
    }

    public function test_validasi_wajib_isi(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => '',
                'penenun_id' => '',
                'jumlah_target' => '',
                'tanggal_mulai' => '',
            ]);

        $response->assertRedirect(route('admin.target-produksi.create'));
        $response->assertSessionHasErrors([
            'produk_id',
            'penenun_id',
            'jumlah_target',
            'tanggal_mulai',
        ]);
    }

    public function test_jumlah_target_nol_atau_negatif_ditolak(): void
    {
        $tanggalMulai = Carbon::today()->toDateString();

        $responseNol = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 0,
                'tanggal_mulai' => $tanggalMulai,
            ]);

        $responseNol->assertRedirect(route('admin.target-produksi.create'));
        $responseNol->assertSessionHasErrors(['jumlah_target']);

        $responseNegatif = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => -5,
                'tanggal_mulai' => $tanggalMulai,
            ]);

        $responseNegatif->assertRedirect(route('admin.target-produksi.create'));
        $responseNegatif->assertSessionHasErrors(['jumlah_target']);
    }

    public function test_penenun_nonaktif_ditolak(): void
    {
        $penenunNonaktif = Pengguna::factory()->create([
            'peran' => 'penenun',
            'status' => 'nonaktif',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $penenunNonaktif->id,
                'jumlah_target' => 10,
                'tanggal_mulai' => Carbon::today()->toDateString(),
            ]);

        $response->assertRedirect(route('admin.target-produksi.create'));
        $response->assertSessionHasErrors(['penenun_id']);
    }

    public function test_produk_nonaktif_ditolak(): void
    {
        $produkNonaktif = Produk::create([
            'kode_produk' => 'PRD-OFF',
            'nama_produk' => 'Produk Diarsipkan',
            'jenis' => 'Tenun',
            'harga' => 100000,
            'status' => 'nonaktif',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $produkNonaktif->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 10,
                'tanggal_mulai' => Carbon::today()->toDateString(),
            ]);

        $response->assertRedirect(route('admin.target-produksi.create'));
        $response->assertSessionHasErrors(['produk_id']);
    }

    public function test_batas_waktu_sebelum_tanggal_mulai_ditolak(): void
    {
        $tanggalMulai = Carbon::today()->addDays(5)->toDateString();
        $tanggalDeadlineSalah = Carbon::today()->addDays(2)->toDateString();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 10,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_deadline' => $tanggalDeadlineSalah,
            ]);

        $response->assertRedirect(route('admin.target-produksi.create'));
        $response->assertSessionHasErrors(['tanggal_deadline']);
    }

    public function test_tanggal_mulai_kemarin_ditolak(): void
    {
        $tanggalKemarin = Carbon::yesterday()->toDateString();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.target-produksi.create'))
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 10,
                'tanggal_mulai' => $tanggalKemarin,
            ]);

        $response->assertRedirect(route('admin.target-produksi.create'));
        $response->assertSessionHasErrors([
            'tanggal_mulai' => 'Tanggal mulai tidak boleh sebelum hari ini.',
        ]);

        $this->assertDatabaseMissing('target_produksi', [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'tanggal_mulai' => $tanggalKemarin,
        ]);
    }

    public function test_tanggal_mulai_hari_ini_diterima(): void
    {
        $tanggalHariIni = Carbon::today()->toDateString();
        $tanggalDeadline = Carbon::today()->addDays(7)->toDateString();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 12,
                'tanggal_mulai' => $tanggalHariIni,
                'tanggal_deadline' => $tanggalDeadline,
            ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success', 'Target produksi berhasil dibuat.');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_produksi', [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 12,
            'status' => 'berjalan',
        ]);

        $targetHariIni = TargetProduksi::latest('id')->first();
        $this->assertNotNull($targetHariIni);
        $this->assertEquals($tanggalHariIni, $targetHariIni->tanggal_mulai->format('Y-m-d'));
    }

    public function test_tanggal_mulai_besok_diterima(): void
    {
        $tanggalBesok = Carbon::tomorrow()->toDateString();
        $tanggalDeadline = Carbon::tomorrow()->addDays(14)->toDateString();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 8,
                'tanggal_mulai' => $tanggalBesok,
                'tanggal_deadline' => $tanggalDeadline,
            ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success', 'Target produksi berhasil dibuat.');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_produksi', [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 8,
            'status' => 'berjalan',
        ]);

        $targetBesok = TargetProduksi::latest('id')->first();
        $this->assertNotNull($targetBesok);
        $this->assertEquals($tanggalBesok, $targetBesok->tanggal_mulai->format('Y-m-d'));
    }

    public function test_pencarian_berdasarkan_nama_produk(): void
    {
        $produkBaron = Produk::create([
            'kode_produk' => 'PRD-BARON',
            'nama_produk' => 'Kain Baron Eksklusif',
            'jenis' => 'Kain',
            'harga' => 400000,
            'status' => 'aktif',
        ]);

        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        TargetProduksi::create([
            'produk_id' => $produkBaron->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 5,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.target-produksi.index', ['cari' => 'Baron']));

        $response->assertOk();
        $response->assertSee('Kain Baron Eksklusif');
        $response->assertDontSee('Kain Tenun Troso Masterpiece');
    }

    public function test_filter_status(): void
    {
        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 10,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 8,
            'tanggal_mulai' => Carbon::today()->subMonth()->toDateString(),
            'status' => 'selesai',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.target-produksi.index', ['status' => 'berjalan']));

        $response->assertOk();
        $response->assertSee('Berjalan');
        $response->assertDontSee('value="selesai" selected');
    }

    public function test_pembeli_tidak_bisa_mengakses(): void
    {
        $responseIndex = $this->actingAs($this->pembeli)
            ->get(route('admin.target-produksi.index'));
        $responseIndex->assertForbidden();

        $responseCreate = $this->actingAs($this->pembeli)
            ->get(route('admin.target-produksi.create'));
        $responseCreate->assertForbidden();

        $responseStore = $this->actingAs($this->pembeli)
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 5,
                'tanggal_mulai' => Carbon::today()->toDateString(),
            ]);
        $responseStore->assertForbidden();
    }

    public function test_penenun_tidak_bisa_mengakses(): void
    {
        $responseIndex = $this->actingAs($this->penenun)
            ->get(route('admin.target-produksi.index'));
        $responseIndex->assertForbidden();

        $responseCreate = $this->actingAs($this->penenun)
            ->get(route('admin.target-produksi.create'));
        $responseCreate->assertForbidden();

        $responseStore = $this->actingAs($this->penenun)
            ->post(route('admin.target-produksi.store'), [
                'produk_id' => $this->produk->id,
                'penenun_id' => $this->penenun->id,
                'jumlah_target' => 5,
                'tanggal_mulai' => Carbon::today()->toDateString(),
            ]);
        $responseStore->assertForbidden();
    }

    public function test_admin_melihat_halaman_ubah(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.target-produksi.edit', $target));

        $response->assertOk();
        $response->assertSee('Ubah Target Produksi');
        $response->assertSee('PRODUKSI');
        $response->assertSee('Simpan Perubahan');
        $response->assertSee($this->produk->nama_produk);
    }

    public function test_ubah_target_berjalan_berhasil(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $tanggalDeadline = Carbon::today()->addDays(30)->toDateString();

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 35,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_deadline' => $tanggalDeadline,
        ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success', 'Target produksi berhasil diperbarui.');

        $target->refresh();
        $this->assertEquals(35, $target->jumlah_target);
        $this->assertEquals($tanggalDeadline, $target->tanggal_deadline->toDateString());
    }

    public function test_target_selesai_ditolak(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'selesai',
        ]);

        $responseEdit = $this->actingAs($this->admin)->get(route('admin.target-produksi.edit', $target));
        $responseEdit->assertRedirect(route('admin.target-produksi.index'));
        $responseEdit->assertSessionHas('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');

        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'jumlah_target' => 50,
        ]);
        $responseUpdate->assertRedirect(route('admin.target-produksi.index'));
        $responseUpdate->assertSessionHas('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');
    }

    public function test_target_dibatalkan_ditolak(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'dibatalkan',
        ]);

        $responseEdit = $this->actingAs($this->admin)->get(route('admin.target-produksi.edit', $target));
        $responseEdit->assertRedirect(route('admin.target-produksi.index'));
        $responseEdit->assertSessionHas('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');

        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'jumlah_target' => 30,
        ]);
        $responseUpdate->assertRedirect(route('admin.target-produksi.index'));
        $responseUpdate->assertSessionHas('error', 'Target produksi yang sudah selesai atau dibatalkan tidak dapat diubah.');
    }

    public function test_jumlah_target_di_bawah_total_hasil_ditolak(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 10,
            'tanggal_input' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'jumlah_target' => 9,
        ]);

        $response->assertSessionHasErrors(['jumlah_target']);
        $this->assertEquals(
            'Jumlah target tidak boleh lebih kecil dari hasil produksi yang sudah masuk (10).',
            session('errors')->first('jumlah_target')
        );
    }

    public function test_jumlah_target_sama_dengan_total_hasil_diterima(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 10,
            'tanggal_input' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'jumlah_target' => 10,
        ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success', 'Target produksi berhasil diperbarui.');

        $target->refresh();
        $this->assertEquals(10, $target->jumlah_target);
    }

    public function test_produk_dan_penenun_diabaikan_jika_sudah_ada_hasil(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        HasilProduksi::create([
            'target_produksi_id' => $target->id,
            'jumlah_selesai' => 5,
            'tanggal_input' => Carbon::now(),
        ]);

        $produkB = Produk::create([
            'kode_produk' => 'PRD-002',
            'nama_produk' => 'Kain Sutra Asli',
            'jenis' => 'Sutra',
            'harga' => 500000,
            'status' => 'aktif',
        ]);

        $penenunB = Pengguna::factory()->create([
            'peran' => 'penenun',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $produkB->id,
            'penenun_id' => $penenunB->id,
            'jumlah_target' => 25,
        ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertEquals($this->produk->id, $target->produk_id);
        $this->assertEquals($this->penenun->id, $target->penenun_id);
        $this->assertEquals(25, $target->jumlah_target);
    }

    public function test_produk_bisa_diganti_jika_belum_ada_hasil(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $produkB = Produk::create([
            'kode_produk' => 'PRD-003',
            'nama_produk' => 'Kain Songket Baru',
            'jenis' => 'Songket',
            'harga' => 600000,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $produkB->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertEquals($produkB->id, $target->produk_id);
    }

    public function test_tanggal_mulai_lama_yang_tidak_diubah_tidak_ditolak(): void
    {
        $tanggalMulaiLama = Carbon::today()->subDays(7)->toDateString();

        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => $tanggalMulaiLama,
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 30,
            'tanggal_mulai' => $tanggalMulaiLama,
        ]);

        $response->assertRedirect(route('admin.target-produksi.index'));
        $response->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertEquals(30, $target->jumlah_target);
    }

    public function test_tanggal_mulai_diubah_ke_kemarin_ditolak(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::yesterday()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['tanggal_mulai']);
        $this->assertEquals(
            'Tanggal mulai tidak boleh sebelum hari ini.',
            session('errors')->first('tanggal_mulai')
        );
    }

    public function test_batas_waktu_sebelum_tanggal_mulai_ditolak_pada_update(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->addDays(5)->toDateString(),
            'status' => 'berjalan',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.target-produksi.update', $target), [
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->addDays(5)->toDateString(),
            'tanggal_deadline' => Carbon::today()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors(['tanggal_deadline']);
    }

    public function test_pembeli_tidak_bisa_mengakses_halaman_ubah(): void
    {
        $target = TargetProduksi::create([
            'produk_id' => $this->produk->id,
            'penenun_id' => $this->penenun->id,
            'jumlah_target' => 20,
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'status' => 'berjalan',
        ]);

        $responseEdit = $this->actingAs($this->pembeli)
            ->get(route('admin.target-produksi.edit', $target));
        $responseEdit->assertForbidden();

        $responseUpdate = $this->actingAs($this->pembeli)
            ->put(route('admin.target-produksi.update', $target), [
                'jumlah_target' => 25,
            ]);
        $responseUpdate->assertForbidden();
    }
}
