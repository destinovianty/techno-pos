<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** Stok awal SEMENTARA untuk menu resto. Ganti sesuai kondisi sebenarnya. */
    private const STOK_AWAL = 50;

    /** HPP SEMENTARA = persen dari harga jual (HPP asli belum ada). Ganti lewat dashboard PIC, tab Produk & Harga. */
    private const HPP_PERSEN = 0.4;

    /**
     * false = menu yang sudah ada TIDAK diubah harga dan HPP-nya (aman, karena PIC bisa mengubahnya dari dashboard).
     * true  = harga dan HPP di database ditimpa dengan isi seeder ini.
     */
    private const TIMPA_HARGA = false;

    /**
     * Satu menu. $opsi boleh berisi:
     *  - 'jasa'        = tanpa stok (paket / sewa)
     *  - 'tanpa_dapur' = tidak masuk dapur
     */
    private function m(string $name, int $price, int $cost, int $stock, string $category, array $opsi = []): array
    {
        return [
            'name'        => $name,
            'price'       => $price,
            'cost'        => $cost,
            'stock'       => $stock,
            'category'    => $category,
            'track_stock' => ! in_array('jasa', $opsi, true),
            'to_kitchen'  => ! in_array('tanpa_dapur', $opsi, true),
        ];
    }

    /** Menu resto: hanya nama, harga jual, dan kategori. HPP dan stok memakai nilai sementara. */
    private function r(string $name, int $price, string $category): array
    {
        $hpp = (int) (round($price * self::HPP_PERSEN / 500) * 500);

        return $this->m($name, $price, $hpp, self::STOK_AWAL, $category);
    }

    /** Minuman dengan pilihan panas / dingin. Isi null kalau varian itu tidak ada. */
    private function s(string $name, ?int $hot, ?int $ice, string $category): array
    {
        $rows = [];

        if ($hot !== null) {
            $rows[] = $this->r($name . ' (Hot)', $hot, $category);
        }
        if ($ice !== null) {
            $rows[] = $this->r($name . ' (Ice)', $ice, $category);
        }

        return $rows;
    }

    public function run(): void
    {
        // ===== MENU KALS MAISON =====
        $kals = [
            $this->r('Bruschetta with Mushroom', 30000, 'Appetizer'),
            $this->r('Bruschetta with Tomato Salsa', 30000, 'Appetizer'),
            $this->r('Chicken Quesadillas', 35000, 'Appetizer'),
            $this->r('Cheese Quesadillas', 30000, 'Appetizer'),
            $this->r('Chicken Fajitas', 35000, 'Appetizer'),
            $this->r('Beef Fajitas', 40000, 'Appetizer'),
            $this->r('Beef Taco', 40000, 'Appetizer'),
            $this->r('Cheese Nachos', 30000, 'Appetizer'),
            $this->r('Fried Mushroom with Tartar Sauce', 36000, 'Appetizer'),
            $this->r('Chicken Wings', 30000, 'Appetizer'),
            $this->r('Onion Blooming', 25000, 'Appetizer'),
            $this->r('French Fries', 26000, 'Appetizer'),

            $this->r('Mixgreen Salad with Thousand Island Dressing', 33500, 'Salad'),
            $this->r('Caesar Salad', 29000, 'Salad'),
            $this->r('Mixgreen Salad with Lemon Dressing', 27500, 'Salad'),

            $this->r('Mushroom Soup', 35000, 'Soup'),
            $this->r('French Onion Soup', 41000, 'Soup'),
            $this->r('Creamy Chicken Soup', 36000, 'Soup'),
            $this->r('Tortilla Soup', 30000, 'Soup'),
            $this->r('Beef Goulash Soup', 50000, 'Soup'),

            $this->r('Beef Burger', 60000, 'Burger'),
            $this->r('Chicken Burger', 40000, 'Burger'),
            $this->r('Fish Burger', 58000, 'Burger'),

            $this->r('Pizza Ground Beef', 85000, 'Pizza'),
            $this->r('Pizza Rendang', 80000, 'Pizza'),
            $this->r('Pizza Sambal Matah', 67500, 'Pizza'),
            $this->r('Pizza Sausage', 75000, 'Pizza'),

            $this->r('Spaghetti Bolognese', 34000, 'Pasta'),
            $this->r('Fettuccine Alfredo', 42000, 'Pasta'),
            $this->r('Spaghetti Aglio de Olio', 26000, 'Pasta'),
            $this->r('Pesto Pane Pasta', 24000, 'Pasta'),
            $this->r('Tomato Pane Pasta', 25000, 'Pasta'),
            $this->r('Pesto Fusili Pasta', 24000, 'Pasta'),
            $this->r('Tomato Fusili Pasta', 25000, 'Pasta'),

            $this->r('Fish and Chips', 34000, 'Main Course'),
            $this->r('Fried Chicken', 44500, 'Main Course'),
            $this->r('Chicken Steak', 37000, 'Main Course'),
            $this->r('Grilled Chicken', 40000, 'Main Course'),
            $this->r('Bistik Lidah Sapi', 69000, 'Main Course'),
            $this->r('Sirloin Steak Meltique', 128000, 'Main Course'),
            $this->r('Ribeye Steak Meltique', 115000, 'Main Course'),
            $this->r('Striploin Steak Meltique', 125000, 'Main Course'),
        ];

        // ===== MENU SKY WATCH =====
        $sky = [
            $this->r('Vegetable Samosa', 18000, 'Appetizer'),
            $this->r('Vegetable Fritter', 15000, 'Appetizer'),
            $this->r('Tempe Mendoan', 10000, 'Appetizer'),
            $this->r('Martabak Daging', 30000, 'Appetizer'),
            $this->r('Martabak Telur', 25000, 'Appetizer'),
            $this->r('Vegetable Spring Roll', 15000, 'Appetizer'),
            $this->r('Japanese Chicken Kani Roll', 25000, 'Appetizer'),

            $this->r('Thai Mango Salad', 23000, 'Salad'),
            $this->r('Salad Bangkok', 20000, 'Salad'),
            $this->r('Rujak Buah', 20000, 'Salad'),
            $this->r('Asinan Bogor', 20000, 'Salad'),
            $this->r('Thai Salad Namprik Dressing', 20000, 'Salad'),

            $this->r('Thai Green Chicken Curry', 25000, 'Main Course'),

            $this->r('Mie Goreng Sky Watch', 25000, 'Mie & Kwetiaw'),
            $this->r('Mie Godog Sky Watch', 25000, 'Mie & Kwetiaw'),
            $this->r('Kwetiaw Goreng Sky Watch', 25000, 'Mie & Kwetiaw'),
            $this->r('Mie Ayam Sky Watch', 25000, 'Mie & Kwetiaw'),
            $this->r('Phad Thai Noodle', 32000, 'Mie & Kwetiaw'),

            $this->r('Nasi Goreng Sky Watch', 38000, 'Nasi'),
            $this->r('Nasi Goreng Teri Medan', 26000, 'Nasi'),
            $this->r('Nasi Goreng Fire Cracker', 25000, 'Nasi'),
            $this->r('Beef Yakiniku Rice', 35000, 'Nasi'),
            $this->r('Beef Blackpepper Rice', 35000, 'Nasi'),
            $this->r('Chicken Katsu Rice', 32000, 'Nasi'),
            $this->r('Chicken Katsu Sambal Matah', 28000, 'Nasi'),
            $this->r('Nasi Liwet', 33000, 'Nasi'),
            $this->r('Nasi Timbel Ayam', 38000, 'Nasi'),
            $this->r('Nasi Ayam Goreng', 35000, 'Nasi'),
            $this->r('Nasi Soto Ayam', 20000, 'Nasi'),

            $this->r('Dori Asam Manis', 29000, 'Ikan'),
            $this->r('Gurame Terbang', 75000, 'Ikan'),
            $this->r('Gurame Bakar', 70000, 'Ikan'),
            $this->r('Dori Asam Pedas', 20000, 'Ikan'),

            $this->r('Sop Buntut', 95000, 'Soup'),
            $this->r('Sop Iga', 80000, 'Soup'),
            $this->r('Tom Yum Goong', 29000, 'Soup'),
            $this->r('Soup Khmer Cambodia', 20000, 'Soup'),
            $this->r('Beef Pho Soup', 35000, 'Soup'),
            $this->r('Miso Soup', 25000, 'Soup'),

            $this->r('Extra Nasi Putih', 7000, 'Tambahan'),
            $this->r('Extra Ayam Goreng', 10000, 'Tambahan'),
            $this->r('Extra Sambal Matah', 5000, 'Tambahan'),
            $this->r('Extra Sambal Kecap', 5000, 'Tambahan'),
            $this->r('Extra Sambal Bajak', 5000, 'Tambahan'),
            $this->r('Extra Saus Asam Manis', 8000, 'Tambahan'),
            $this->r('Extra Telur', 5000, 'Tambahan'),
            $this->r('Extra Emping', 5000, 'Tambahan'),
        ];

        // ===== MINUMAN =====
        $minuman = array_merge(
            // Tea Based
            $this->s('Tea', 15000, 15000, 'Tea'),
            $this->s('Sweet Tea', 15000, 15000, 'Tea'),
            $this->s('Lemon Tea', 18000, 18000, 'Tea'),
            $this->s('Thai Tea', null, 20000, 'Tea'),

            // Mojito (ice)
            [
                $this->r('Strawberries Mojito', 25000, 'Mojito'),
                $this->r('Mango Mojito', 25000, 'Mojito'),
                $this->r('Queen of the South', 25000, 'Mojito'),
                $this->r('Cinderella', 25000, 'Mojito'),
                $this->r('Virginies', 25000, 'Mojito'),

                // Mocktail (ice)
                $this->r('Introvert', 25000, 'Mocktail'),
                $this->r('Sundown Syndrome', 25000, 'Mocktail'),
                $this->r('Que Sera-Sera', 25000, 'Mocktail'),
            ],

            // Coffee Based
            $this->s('Espresso', 15000, null, 'Kopi'),
            $this->s('Americano', 15000, 15000, 'Kopi'),
            $this->s('Caffe Latte', 20000, null, 'Kopi'),
            $this->s('Kopi Susu', null, 20000, 'Kopi'),
            $this->s('Kopi Susu Gula Aren', null, 20000, 'Kopi'),
            $this->s('Kopi Lemon', null, 20000, 'Kopi'),

            // Manual Brew
            $this->s('VGO', 28000, null, 'Manual Brew'),
            $this->s('Vietnam Drip', 18000, null, 'Manual Brew'),
            $this->s('French Press', 20000, null, 'Manual Brew'),
            $this->s('Japanese', null, 28000, 'Manual Brew'),
            $this->s('Sundanese', null, 30000, 'Manual Brew'),

            // Milk Based
            $this->s('Taro Latte', 20000, 20000, 'Milk Based'),
            $this->s('Red Velvet Latte', 20000, 20000, 'Milk Based'),
            $this->s('Greentea Latte', 20000, 20000, 'Milk Based'),
            $this->s('Chocolate', 20000, 20000, 'Milk Based'),
            [
                $this->r('Mango Smoothies', 25000, 'Milk Based'),
                $this->r('Strawberry Smoothies', 25000, 'Milk Based'),
                $this->r('Rose Smoothies', 20000, 'Milk Based'),

                // Juice
                $this->r('Mango Juice', 25000, 'Juice'),
                $this->r('Strawberry Juice', 25000, 'Juice'),
                $this->r('Orange Squash', 20000, 'Juice'),
            ],
        );

        // Kals Maison dan Sky Watch memakai satu daftar menu gabungan (stok tetap terpisah per outlet).
        $bersama = array_merge($kals, $sky, $minuman);

        // Semua harga, HPP, stok, dan barcode di bawah (selain menu resto di atas) adalah CONTOH.
        $menu = [
            'Kals Maison' => $bersama,
            'Sky Watch'   => $bersama,

            'Needs Mart' => [
                $this->m('Indomie Goreng',     3500,  2800, 120, 'Sembako',         ['tanpa_dapur']),
                $this->m('Roti Tawar',        16000, 12000,  20, 'Sembako',         ['tanpa_dapur']),
                $this->m('Gula 1kg',          17000, 14500,   9, 'Sembako',         ['tanpa_dapur']),
                $this->m('Air Mineral 600ml',  4000,  2500, 120, 'Minuman',         ['tanpa_dapur']),
                $this->m('Teh Botol',          6000,  4200,  60, 'Minuman',         ['tanpa_dapur']),
                $this->m('Chitato',           12000,  9000,  48, 'Snack',           ['tanpa_dapur']),
                $this->m('Sabun Cuci',        14000, 10500,  31, 'Kebutuhan Rumah', ['tanpa_dapur']),
            ],

            'Billiard' => [
                $this->m('Paket 1 Jam',        40000,  8000,  0, 'Paket Jam', ['jasa', 'tanpa_dapur']),
                $this->m('Paket 2 Jam',        75000, 16000,  0, 'Paket Jam', ['jasa', 'tanpa_dapur']),
                $this->m('Paket 3 Jam',       105000, 24000,  0, 'Paket Jam', ['jasa', 'tanpa_dapur']),
                $this->m('Sewa Stik Cue',      10000,  1500,  0, 'Aksesoris', ['jasa', 'tanpa_dapur']),
                $this->m('Sewa Bridge Stick',   5000,   500,  0, 'Aksesoris', ['jasa', 'tanpa_dapur']),
                $this->m('Kapur Cue',           5000,  2000, 40, 'Aksesoris', ['tanpa_dapur']),
                $this->m('Nasi Goreng',        28000, 11000, 20, 'Makanan'),
                $this->m('Kentang Goreng',     20000,  8000, 22, 'Makanan'),
                $this->m('Es Teh',              8000,  2000, 50, 'Minuman'),
            ],

            'GPM PTDI' => [
                $this->m('Kopi Susu GPM', 18000,  6000, 41, 'Kopi'),
                $this->m('Americano',     15000,  4500, 30, 'Kopi'),
                $this->m('Latte',         22000,  7500, 26, 'Kopi'),
                $this->m('Matcha Latte',  24000,  9000, 18, 'Non-Kopi'),
                $this->m('Croissant',     20000,  9000, 18, 'Pastry'),
                $this->m('Roti Bakar',    18000,  7000, 22, 'Pastry'),
                $this->m('Cookies',       12000,  5000, 40, 'Pastry'),
                $this->m('Air Mineral',    5000,  2500, 80, 'Minuman'),
            ],

            'Technocoffe' => [
                $this->m('Nasi Ayam Geprek', 24000,  9500, 19, 'Makanan'),
                $this->m('Mie Goreng',       26000, 10000, 18, 'Makanan'),
                $this->m('Roti Bakar',       18000,  7000, 30, 'Makanan'),
                $this->m('Kentang Goreng',   20000,  8000, 22, 'Makanan'),
                $this->m('Kopi Susu',        18000,  6000, 41, 'Minuman'),
                $this->m('Es Teh',            8000,  2000, 60, 'Minuman'),
                $this->m('Thai Tea',         16000,  5000, 28, 'Minuman'),
                $this->m('Air Mineral',       5000,  2500, 80, 'Minuman'),
            ],
        ];

        // Menu lama Billiard yang sudah diganti paket per jam.
        Product::where('outlet', 'Billiard')->where('name', 'Sewa Meja 1 Jam')->delete();

        // Menu contoh lama Kals Maison dan Sky Watch, diganti menu asli di atas.
        // Riwayat transaksi tidak terpengaruh karena nama dan harga tersimpan di setiap transaksi.
        $contohLama = ['Beef Steak', 'Spaghetti Carbonara', 'Chicken Katsu', 'Nasi Goreng Techno', 'Ramen Shoyu', 'Dimsum Platter', 'Es Teh', 'Lemon Tea'];
        Product::whereIn('outlet', ['Kals Maison', 'Sky Watch'])->whereIn('name', $contohLama)->delete();

        foreach ($menu as $outlet => $items) {
            foreach ($items as $item) {
                $product = Product::firstOrNew(['outlet' => $outlet, 'name' => $item['name']]);
                $baru = ! $product->exists;

                // Stok hanya diisi saat menu baru dibuat, jadi seeder aman dijalankan ulang.
                if ($baru) {
                    $product->stock = $item['stock'];
                }

                // Harga dan HPP menu yang sudah ada dibiarkan (dikelola PIC dari dashboard), kecuali TIMPA_HARGA = true.
                if ($baru || self::TIMPA_HARGA) {
                    $product->price = $item['price'];
                    $product->cost  = $item['cost'];
                }

                $product->category    = $item['category'];
                $product->track_stock = $item['track_stock'];
                $product->to_kitchen  = $item['to_kitchen'];

                // Barcode CONTOH untuk Needs Mart. Ganti dengan barcode asli produk.
                if ($outlet === 'Needs Mart' && ! $product->barcode) {
                    $product->barcode = '8991' . substr(str_pad((string) crc32($item['name']), 10, '0', STR_PAD_LEFT), -9);
                }

                $product->save();
            }
        }
    }
}
