<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('hasil_produksi', function (Blueprint $table) {
            $table->string('request_id', 80)->nullable()->unique();
        });
    }
    public function down(): void {
        Schema::table('hasil_produksi', function (Blueprint $table) {
            $table->dropUnique(['request_id']);
            $table->dropColumn('request_id');
        });
    }
};
