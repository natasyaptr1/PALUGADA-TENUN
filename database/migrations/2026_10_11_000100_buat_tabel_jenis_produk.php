<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jenis_produk')) {
            Schema::create('jenis_produk', function (Blueprint $table) {
                $table->id();
                $table->string('nama_jenis', 50)->unique();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('produk', 'jenis_produk_id')) {
            Schema::table('produk', function (Blueprint $table) {
                $table->foreignId('jenis_produk_id')
                    ->nullable()
                    ->after('nama_produk')
                    ->constrained('jenis_produk')
                    ->nullOnDelete();
            });
        }

        // Seed data awal jenis produk
        $daftarAwal = [
            'Kain Tenun',
            'Sarung Tenun',
            'Selendang & Syal',
            'Baju Tenun',
            'Aksesoris Tenun',
        ];

        foreach ($daftarAwal as $nama) {
            DB::table('jenis_produk')->updateOrInsert(
                ['nama_jenis' => $nama],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Migrasi data lama dari kolom jenis di tabel produk
        if (Schema::hasColumn('produk', 'jenis')) {
            $produkLama = DB::table('produk')->whereNotNull('jenis')->get();
            foreach ($produkLama as $item) {
                $namaJenis = trim((string) $item->jenis);
                if ($namaJenis === '') {
                    continue;
                }

                $jenisId = DB::table('jenis_produk')->where('nama_jenis', $namaJenis)->value('id');
                if (!$jenisId) {
                    $jenisId = DB::table('jenis_produk')->insertGetId([
                        'nama_jenis' => $namaJenis,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('produk')->where('id', $item->id)->update([
                    'jenis_produk_id' => $jenisId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('produk', 'jenis_produk_id')) {
            Schema::table('produk', function (Blueprint $table) {
                $table->dropForeign(['jenis_produk_id']);
                $table->dropColumn('jenis_produk_id');
            });
        }

        Schema::dropIfExists('jenis_produk');
    }
};
