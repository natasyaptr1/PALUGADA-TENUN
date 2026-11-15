<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     * Menambahkan kolom berat_gram ke tabel produk.
     * Satuan: gram. Default sementara: 500 gram (dapat diubah oleh admin).
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->unsignedInteger('berat_gram')
                ->default(500)
                ->comment('Berat produk dalam satuan gram (default sementara 500g, dapat diubah oleh admin)');
        });
    }

    /**
     * Kembalikan migrasi.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn('berat_gram');
        });
    }
};

