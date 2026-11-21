<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable();
            }

            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->index();
            }

            if (! Schema::hasColumn('products', 'track_stock')) {
                $table->boolean('track_stock')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Sengaja dikosongkan: kolom-kolom ini sudah dipakai halaman kasir.
    }
};
