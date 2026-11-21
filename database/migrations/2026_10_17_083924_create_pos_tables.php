<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produk per outlet. HPP disimpan di kolom "cost".
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('outlet')->index();
                $table->string('name');
                $table->unsignedInteger('price');            // harga jual
                $table->unsignedInteger('cost')->default(0); // HPP
                $table->integer('stock')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('outlet')->index();
                $table->string('table_name');
                $table->unsignedBigInteger('total')->default(0);
                $table->string('source')->default('kasir');            // kasir | customer
                $table->string('status')->default('menunggu_bayar');   // menunggu_bayar | lunas | batal
                $table->string('pay_preference')->nullable();          // meja | kasir
                $table->string('payment_method')->nullable();          // tunai | qris | debit
                $table->unsignedBigInteger('paid_amount')->nullable();
                $table->unsignedBigInteger('change_amount')->nullable();
                $table->timestamp('paid_at')->nullable();

                // Alur dapur
                $table->string('kitchen_status')->default('antrian')->index(); // antrian | diambil | dimasak | serving | selesai
                $table->timestamp('taken_at')->nullable();
                $table->timestamp('cooking_at')->nullable();
                $table->timestamp('serving_at')->nullable();
                $table->timestamp('done_at')->nullable();

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('name');
                $table->unsignedInteger('price');
                $table->unsignedInteger('cost')->default(0); // HPP saat transaksi terjadi
                $table->unsignedInteger('qty');
                $table->unsignedBigInteger('subtotal');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
