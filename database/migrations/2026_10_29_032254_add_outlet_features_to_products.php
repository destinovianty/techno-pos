<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('category')->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('products', 'track_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('track_stock')->default(true);   // false = jasa/sewa, tanpa stok
            });
        }

        if (! Schema::hasColumn('products', 'to_kitchen')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('to_kitchen')->default(true);    // false = tidak masuk dapur
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'cost')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedInteger('cost')->default(0);     // HPP saat transaksi terjadi
            });
        }
    }

    public function down(): void
    {
        foreach (['category', 'barcode', 'track_stock', 'to_kitchen'] as $kolom) {
            if (Schema::hasColumn('products', $kolom)) {
                Schema::table('products', function (Blueprint $table) use ($kolom) {
                    $table->dropColumn($kolom);
                });
            }
        }
    }
};
