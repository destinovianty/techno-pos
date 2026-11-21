<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ParkirSeeder extends Seeder
{
    public function run(): void
    {
        $tarif = config('outlets.parkir_tarif', []);

        foreach (config('outlets.list') as $outlet => $cfg) {
            if (empty($cfg['parkir'])) {
                continue;
            }

            foreach ($tarif as $jenis => $harga) {
                $p = Product::firstOrNew(['outlet' => $outlet, 'name' => 'Parkir ' . $jenis]);

                // Tarif hanya diisi saat produk baru dibuat, jadi tarif yang diubah PIC tidak tertimpa.
                if (! $p->exists) {
                    $p->price = $harga;
                    $p->cost  = 0;
                    $p->stock = 0;
                }

                $p->category    = 'Parkir';
                $p->track_stock = false;   // jasa: tanpa stok
                $p->to_kitchen  = false;   // tidak masuk dapur
                $p->save();
            }
        }
    }
}
