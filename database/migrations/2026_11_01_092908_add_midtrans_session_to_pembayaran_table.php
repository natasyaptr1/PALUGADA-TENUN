<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('midtrans_order_id', 80)
                ->nullable()
                ->unique();

            $table->text('snap_redirect_url')->nullable();

            $table->timestamp('snap_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);

            $table->dropColumn([
                'midtrans_order_id',
                'snap_redirect_url',
                'snap_expires_at',
            ]);
        });
    }
};
