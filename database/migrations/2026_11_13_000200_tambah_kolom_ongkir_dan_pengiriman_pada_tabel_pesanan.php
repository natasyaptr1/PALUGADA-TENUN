<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Menambahkan kolom ongkir, rincian pengiriman, dan subtotal pada tabel pesanan.
     *
     * Kontrak perhitungan nilai pesanan:
     * total = subtotal + ongkir
     *
     * Pesanan lama yang dibuat sebelum fitur ongkir ini diterapkan tidak memiliki ongkir (ongkir = 0),
     * sehingga subtotal diisi bernilai sama dengan total untuk baris yang subtotal-nya masih null.
     */
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('ongkir', 12, 2)->default(0);
            $table->string('metode_pengiriman', 20)->nullable();
            $table->string('kurir_kode', 30)->nullable();
            $table->string('kurir_nama', 60)->nullable();
            $table->string('layanan_kurir', 60)->nullable();
            $table->string('estimasi_kirim', 60)->nullable();
            $table->string('tujuan_id', 30)->nullable();
            $table->string('tujuan_label', 255)->nullable();
            $table->unsignedInteger('berat_total_gram')->nullable();
        });

        // Isi data lama: set subtotal = total untuk baris yang subtotal-nya null
        // Menggunakan Query Builder standar yang kompatibel dengan SQLite dan MySQL
        DB::table('pesanan')
            ->whereNull('subtotal')
            ->update([
                'subtotal' => DB::raw('total'),
            ]);
    }

    /**
     * Kembalikan migrasi.
     */
    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'ongkir',
                'metode_pengiriman',
                'kurir_kode',
                'kurir_nama',
                'layanan_kurir',
                'estimasi_kirim',
                'tujuan_id',
                'tujuan_label',
                'berat_total_gram',
            ]);
        });
    }
};

