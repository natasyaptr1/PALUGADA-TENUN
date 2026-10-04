<?php
namespace Tests\Feature;
use App\Models\Pengguna; use App\Models\Produk; use App\Models\Stok; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class SprintSatuSmokeTest extends TestCase {
    use RefreshDatabase;
    public function test_api_produk_publik_tersedia(): void {
        $produk=Produk::create(['kode_produk'=>'TEST-001','nama_produk'=>'Tenun Test','jenis'=>'Kain Tenun','harga'=>100000,'status'=>'aktif']);
        Stok::create(['produk_id'=>$produk->id,'jumlah_stok'=>4]);
        $this->getJson('/api/v1/produk')->assertOk()->assertJsonFragment(['kode_produk'=>'TEST-001']);
    }
    public function test_admin_bisa_membuka_halaman_produk(): void {
        $admin=Pengguna::factory()->create(['peran'=>'admin','status'=>'aktif']);
        $this->actingAs($admin)->get('/admin/produk')->assertOk();
    }
    public function test_pembeli_tidak_bisa_membuka_halaman_admin(): void {
        $pembeli=Pengguna::factory()->create(['peran'=>'pembeli','status'=>'aktif']);
        $this->actingAs($pembeli)->get('/admin/produk')->assertForbidden();
    }
}
