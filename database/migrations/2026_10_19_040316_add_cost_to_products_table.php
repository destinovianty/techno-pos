<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'cost')) {
                // harga modal per satuan, kosong = belum diisi
                $table->unsignedInteger('cost')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja dikosongkan: kolom ini dipakai perhitungan laba di halaman PIC.
    }
};
