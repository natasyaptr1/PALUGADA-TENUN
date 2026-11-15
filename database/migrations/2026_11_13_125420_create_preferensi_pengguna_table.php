<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferensi_pengguna', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengguna_id')
                ->constrained('pengguna')
                ->cascadeOnDelete();

            $table->string('kategori', 100);

            $table->timestamps();

            $table->unique(['pengguna_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferensi_pengguna');
    }
};
