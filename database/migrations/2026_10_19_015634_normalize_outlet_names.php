<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'orders', 'products'] as $tabel) {
            DB::table($tabel)->where('outlet', "Kal's Maison")->update(['outlet' => 'Kals Maison']);
        }
    }

    public function down(): void
    {
        // Tidak perlu dikembalikan.
    }
};
