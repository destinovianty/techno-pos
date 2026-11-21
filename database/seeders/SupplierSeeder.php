<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, keterangan, kategori produk yang dipasok]
        $daftar = [
            ['Supplier A', 'Roti, snack, makanan jadi', ['Pastry', 'Snack', 'Makanan']],
            ['Supplier B', 'Bahan masak, daging, ayam, ikan', [
                'Western', 'Asia', 'Appetizer', 'Salad', 'Soup', 'Burger', 'Pizza', 'Pasta',
                'Main Course', 'Nasi', 'Mie & Kwetiaw', 'Ikan', 'Tambahan',
            ]],
            ['Supplier C', 'Kopi dan minuman', [
                'Kopi', 'Non-Kopi', 'Minuman', 'Tea', 'Mojito', 'Mocktail', 'Manual Brew', 'Milk Based', 'Juice',
            ]],
            ['Supplier D', 'Grosir minimarket dan aksesoris', ['Sembako', 'Kebutuhan Rumah', 'Aksesoris']],
        ];

        foreach ($daftar as [$nama, $ket, $kategori]) {
            $id = DB::table('suppliers')->where('name', $nama)->value('id');

            if (! $id) {
                $id = DB::table('suppliers')->insertGetId([
                    'name'       => $nama,
                    'note'       => $ket,
                    'whatsapp'   => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Hanya mengisi produk yang belum punya supplier, jadi aman dijalankan ulang.
            DB::table('products')
                ->whereIn('category', $kategori)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $id]);
        }
    }
}
