<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'subtotal')) {
                $table->unsignedBigInteger('subtotal')->default(0);          // jumlah harga item sebelum diskon
            }
            if (! Schema::hasColumn('orders', 'discount')) {
                $table->unsignedBigInteger('discount')->default(0);          // diskon dalam rupiah
            }
            if (! Schema::hasColumn('orders', 'discount_note')) {
                $table->string('discount_note')->nullable();                 // alasan diskon
            }
            if (! Schema::hasColumn('orders', 'service_rate')) {
                $table->decimal('service_rate', 5, 2)->default(0);           // persen service charge
            }
            if (! Schema::hasColumn('orders', 'service_amount')) {
                $table->unsignedBigInteger('service_amount')->default(0);    // service charge dalam rupiah
            }
            if (! Schema::hasColumn('orders', 'bill_code')) {
                $table->string('bill_code')->nullable()->index();            // mengikat pesanan yang dibayar sekali (gabungan outlet)
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['subtotal', 'discount', 'discount_note', 'service_rate', 'service_amount', 'bill_code'] as $kolom) {
                if (Schema::hasColumn('orders', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
