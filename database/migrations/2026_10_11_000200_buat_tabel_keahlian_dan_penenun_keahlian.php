<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('keahlian')) {
            Schema::create('keahlian', function (Blueprint $table) {
                $table->id();
                $table->string('nama_keahlian', 50)->unique();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('penenun_keahlian')) {
            Schema::create('penenun_keahlian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profil_penenun_id')->constrained('profil_penenun')->cascadeOnDelete();
                $table->foreignId('keahlian_id')->constrained('keahlian')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['profil_penenun_id', 'keahlian_id']);
            });
        }

        // Seed data keahlian awal
        $daftarAwal = [
            'Tenun Ikat',
            'Tenun Songket',
            'Pewarnaan Alami',
            'Desain Motif',
            'Penggulungan Benang',
            'Finishing & Jahit',
        ];

        foreach ($daftarAwal as $nama) {
            DB::table('keahlian')->updateOrInsert(
                ['nama_keahlian' => $nama],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Migrasikan data keahlian dari profil_penenun yang sudah ada
        if (Schema::hasTable('profil_penenun')) {
            $penenunLama = DB::table('profil_penenun')->whereNotNull('keahlian')->get();
            foreach ($penenunLama as $profil) {
                $keahlianStr = trim((string) $profil->keahlian);
                if ($keahlianStr === '') {
                    continue;
                }

                // Mendukung pemisahan jika ada koma
                $parts = array_map('trim', explode(',', $keahlianStr));
                foreach ($parts as $namaKeahlian) {
                    if ($namaKeahlian === '') {
                        continue;
                    }

                    $keahlianId = DB::table('keahlian')->where('nama_keahlian', $namaKeahlian)->value('id');
                    if (!$keahlianId) {
                        $keahlianId = DB::table('keahlian')->insertGetId([
                            'nama_keahlian' => $namaKeahlian,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('penenun_keahlian')->updateOrInsert(
                        [
                            'profil_penenun_id' => $profil->id,
                            'keahlian_id' => $keahlianId,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penenun_keahlian');
        Schema::dropIfExists('keahlian');
    }
};
