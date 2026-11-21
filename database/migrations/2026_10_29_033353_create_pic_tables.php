<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('note')->nullable();       // keterangan: apa yang dipasok
                $table->string('whatsapp')->nullable();   // format 62812xxxx
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('supplier_orders')) {
            Schema::create('supplier_orders', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->string('outlet')->index();
                $table->string('product_name');
                $table->unsignedInteger('qty');
                $table->string('note')->nullable();
                $table->string('status')->default('dipesan');   // dipesan | diterima
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('from_outlet');
                $table->string('to_outlet');
                $table->string('product_name');
                $table->unsignedInteger('qty');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('supplier_orders');
        Schema::dropIfExists('suppliers');
    }
};
