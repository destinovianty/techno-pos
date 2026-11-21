<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OutletMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Nama outlet: ejaan di akun yang sudah terdaftar didahulukan.
        // Kalau belum ada akunnya, pakai nama di config/outlets.php.
        $names = [];

        foreach (array_keys(config('outlets.list', [])) as $name) {
            $names[$this->key($name)] = $name;
        }

        foreach (User::whereNotNull('outlet')->distinct()->pluck('outlet') as $name) {
            $names[$this->key($name)] = $name;
        }

        foreach ($this->menus() as $menuName => $rows) {
            $outlet = $names[$this->key($menuName)] ?? $menuName;

            $this->seedOutlet($outlet, $rows);

            $this->command?->info($outlet . ': ' . count($rows) . ' produk');
        }
    }

    /**
     * Isi menu satu outlet. Produk lama di outlet itu yang tidak ada di daftar baru dihapus.
     */
    private function seedOutlet(string $outlet, array $rows): void
    {
        $keep = [];

        foreach ($rows as $row) {
            [$name, $category, $price] = $row;

            // baris dengan 5 kolom = produk yang memakai stok (+ barcode)
            $track = isset($row[3]);
            $keep[] = $name;

            Product::updateOrCreate(
                ['outlet' => $outlet, 'name' => $name],
                [
                    'category'    => $category,
                    'price'       => $price,
                    'track_stock' => $track,
                    'stock'       => $track ? $row[3] : 0,
                    'barcode'     => $track ? ($row[4] ?? null) : null,
                ]
            );
        }

        Product::where('outlet', $outlet)->whereNotIn('name', $keep)->delete();
    }

    /**
     * Samakan variasi ejaan: "Kal's Maison" = "Kals Maison", "Technocoffe" = "Technocoffee".
     */
    private function key(string $name): string
    {
        return preg_replace('/(.)\1+/', '$1', Str::slug($name));
    }

    /**
     * Format baris:
     *   [nama, kategori, harga]                       -> tanpa stok
     *   [nama, kategori, harga, stok, barcode]        -> pakai stok
     * Semua harga, stok, dan barcode di bawah ini CONTOH. Ganti sesuai kondisi sebenarnya.
     */
    private function menus(): array
    {
        return [

            'Sky Watch' => [
                ['Nasi Goreng Techno',     'Makanan', 28000],
                ['Mie Goreng Seafood',     'Makanan', 30000],
                ['Chicken Katsu',          'Makanan', 38000],
                ['Ramen Shoyu',            'Makanan', 42000],
                ['Chicken Teriyaki Rice',  'Makanan', 35000],
                ['Dimsum Platter',         'Camilan', 32000],
                ['Gyoza',                  'Camilan', 25000],
                ['Es Teh',                 'Minuman', 8000],
                ['Lemon Tea',              'Minuman', 15000],
                ['Thai Tea',               'Minuman', 16000],
            ],

            'Needs Mart' => [
                ['Air Mineral 600 ml',     'Minuman',        4000,  120, '8990000000011'],
                ['Teh Botol 350 ml',       'Minuman',        5000,  96,  '8990000000028'],
                ['Susu UHT 1 L',           'Minuman',        18500, 48,  '8990000000035'],
                ['Kopi Sachet',            'Minuman',        2000,  200, '8990000000042'],
                ['Mi Instan Goreng',       'Makanan Instan', 3500,  150, '8990000000059'],
                ['Mi Instan Soto',         'Makanan Instan', 3500,  150, '8990000000066'],
                ['Roti Tawar',             'Makanan Instan', 16000, 30,  '8990000000073'],
                ['Biskuit Cokelat',        'Snack',          9000,  60,  '8990000000080'],
                ['Keripik Kentang',        'Snack',          11000, 60,  '8990000000097'],
                ['Permen Mint',            'Snack',          2000,  100, '8990000000103'],
                ['Gula Pasir 1 kg',        'Sembako',        17000, 40,  '8990000000110'],
                ['Minyak Goreng 1 L',      'Sembako',        18000, 40,  '8990000000127'],
                ['Beras 5 kg',             'Sembako',        72000, 25,  '8990000000134'],
                ['Telur 1 kg',             'Sembako',        28000, 30,  '8990000000141'],
                ['Sabun Mandi',            'Kebutuhan Rumah', 5500, 80,  '8990000000158'],
                ['Tisu Gulung',            'Kebutuhan Rumah', 12000, 50, '8990000000165'],
            ],

            'Billiard' => [
                ['Paket 1 Jam',            'Paket Jam',       40000],
                ['Paket 2 Jam',            'Paket Jam',       75000],
                ['Paket 3 Jam',            'Paket Jam',       105000],
                ['Sewa Stik Billiard',     'Sewa Aksesoris',  10000],
                ['Sewa Sarung Tangan',     'Sewa Aksesoris',  5000],
                ['Sewa Bridge',            'Sewa Aksesoris',  5000],
                ['Nasi Goreng',            'Makanan',         22000],
                ['Mie Goreng',             'Makanan',         18000],
                ['Kentang Goreng',         'Makanan',         20000],
                ['Es Teh',                 'Minuman',         6000],
                ['Kopi Hitam',             'Minuman',         8000],
                ['Air Mineral',            'Minuman',         5000],
            ],

            'GPM PTDI' => [
                ['Americano',              'Kopi',      15000],
                ['Kopi Susu',              'Kopi',      18000],
                ['Cappuccino',             'Kopi',      20000],
                ['Caffe Latte',            'Kopi',      22000],
                ['Matcha Latte',           'Non-Kopi',  24000],
                ['Cokelat Panas',          'Non-Kopi',  20000],
                ['Lemon Tea',              'Non-Kopi',  15000],
                ['Nasi Ayam Geprek',       'Makanan',   22000],
                ['Croissant',              'Makanan',   18000],
                ['Roti Bakar',             'Makanan',   15000],
                ['Kentang Goreng',         'Camilan',   20000],
                ['Pisang Goreng',          'Camilan',   12000],
            ],

            'Technocoffee' => [
                ['Kopi Techno',            'Minuman',  15000],
                ['Es Kopi Susu',           'Minuman',  16000],
                ['Teh Manis',              'Minuman',  6000],
                ['Es Jeruk',               'Minuman',  10000],
                ['Nasi Goreng',            'Makanan',  20000],
                ['Mie Goreng',             'Makanan',  18000],
                ['Mie Rebus',              'Makanan',  15000],
                ['Roti Bakar',             'Makanan',  14000],
                ['Pisang Goreng',          'Camilan',  12000],
                ['Kentang Goreng',         'Camilan',  18000],
            ],

        ];
    }
}
