<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KasirOrderController extends Controller
{
    /** Jumlah hari ke belakang untuk menghitung produk terlaris. */
    private const HARI_TERLARIS = 30;

    /** HPP sementara (persen dari harga jual) kalau kasir tidak mengisi HPP menu baru. */
    private const HPP_PERSEN = 0.4;

    /**
     * Halaman kasir. Tampilan menyesuaikan jenis outlet (config/outlets.php).
     */
    public function page()
    {
        $this->authorizeKasir();
        $outlet = $this->outlet();
        $cfg = $this->cfg();

        // Menu outlet lain yang boleh dijual dari kasir ini (satu pembayaran).
        $linked = collect($cfg['terhubung'] ?? [])
            ->filter(fn ($o) => is_array(config('outlets.list.' . $o)))
            ->map(function ($o) {
                $c = config('outlets.list.' . $o);

                return [
                    'outlet'   => $o,
                    'label'    => $c['label'],
                    'service'  => (float) ($c['service'] ?? 0),
                    'products' => $this->produkUntuk($o),
                ];
            })
            ->values();

        return view('kasir', [
            'outletName' => $outlet,
            'userName'   => $this->user()->name,
            'tables'     => (int) $cfg['tables'],
            'color'      => $cfg['color'],
            'label'      => $cfg['label'],
            'type'       => $cfg['type'],
            'kitchen'    => (bool) $cfg['kitchen'],
            'barcode'    => (bool) $cfg['barcode'],
            'service'    => (float) ($cfg['service'] ?? 0),
            'diskonMaks' => (int) config('outlets.diskon_maks_persen', 50),
            'linked'     => $linked,
            'produkJs'   => $this->produkUntuk($outlet, $this->terjual($outlet)),
        ]);
    }

    /**
     * Halaman cetak QR tiap meja.
     * Alamat QR diambil dari QR_BASE_URL di .env, supaya bisa dibuka dari HP customer.
     */
    public function qr()
    {
        $this->authorizeKasir();
        $outlet = $this->outlet();
        $tables = (int) $this->cfg()['tables'];
        abort_unless($tables > 0, 404);

        $dasar = rtrim((string) (config('outlets.qr_base_url') ?: url('/')), '/');
        $host = parse_url($dasar, PHP_URL_HOST);

        return view('qr-meja', [
            'outletName' => $outlet,
            'base'       => $dasar . '/order/' . Str::slug($outlet),
            'lokal'      => in_array($host, ['127.0.0.1', 'localhost', '::1'], true),
            'tables'     => $tables,
        ]);
    }

    /**
     * Data terbaru untuk halaman kasir: pesanan hari ini, stok produk, dan produk terlaris.
     * Dipanggil berkala oleh browser kasir.
     */
    public function feed()
    {
        $this->authorizeKasir();
        $outlet = $this->outlet();
        $cfg = $this->cfg();

        $orders = Order::with('items')
            ->where('outlet', $outlet)
            ->where('status', '!=', 'batal')
            ->where(function ($q) {
                $q->where('status', 'menunggu_bayar')
                    ->orWhere('created_at', '>=', today());
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($order) => $order->toPayload())
            ->values();

        $terhubung = array_values(array_filter($cfg['terhubung'] ?? [], fn ($o) => is_array(config('outlets.list.' . $o))));

        return response()->json([
            'orders'       => $orders,
            'products'     => Product::where('outlet', $outlet)->get(['id', 'stock']),
            'linked_stock' => $terhubung ? Product::whereIn('outlet', $terhubung)->get(['id', 'stock']) : [],
            'sold'         => $this->terjual($outlet),
        ]);
    }

    /**
     * Tambah menu baru di outlet kasir yang sedang login.
     * HPP boleh kosong: diisi sementara, lalu PIC mengubahnya dari dashboard.
     */
    public function storeMenu(Request $request)
    {
        $this->authorizeKasir();
        $outlet = $this->outlet();

        [$data, $atribut] = $this->siapkanMenu($request);

        $hppSementara = ! isset($data['cost']);
        $atribut['outlet'] = $outlet;
        $atribut['cost'] = $data['cost'] ?? (int) (round($data['price'] * self::HPP_PERSEN / 500) * 500);

        // Pakai supplier yang sama dengan menu lain di kategori itu, supaya langsung muncul di tab Supplier PIC.
        if (! empty($atribut['category']) && Schema::hasColumn('products', 'supplier_id')) {
            $atribut['supplier_id'] = Product::where('category', $atribut['category'])
                ->whereNotNull('supplier_id')
                ->value('supplier_id');
        }

        $p = Product::create($atribut);

        return response()->json([
            'product'       => $this->payloadProduk($p),
            'hpp_sementara' => $hppSementara,
        ], 201);
    }

    /**
     * Ubah menu. HPP yang dikosongkan berarti tidak diubah.
     * Harga dan HPP baru hanya berlaku untuk penjualan berikutnya (transaksi lama menyimpan nilainya sendiri).
     */
    public function updateMenu(Request $request, Product $product)
    {
        $this->authorizeKasir();
        abort_unless($product->outlet === $this->outlet(), 403, 'Menu ini bukan milik outlet Anda.');

        [$data, $atribut] = $this->siapkanMenu($request, $product->id);

        $p = DB::transaction(function () use ($product, $data, $atribut) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            // Pesanan ke supplier mencatat nama menu, jadi nama tidak boleh berubah selagi masih ada yang belum diterima.
            if ($atribut['name'] !== $p->name && $this->adaPesananSupplier($p)) {
                abort(409, 'Ada pesanan ke supplier untuk menu ini yang belum diterima. Terima dulu pesanannya sebelum mengganti nama.');
            }

            if (isset($data['cost'])) {
                $atribut['cost'] = $data['cost'];
            }

            // Kategori berubah: ikuti supplier kategori barunya (kalau ada), atau isi kalau belum punya supplier.
            if (! empty($atribut['category']) && Schema::hasColumn('products', 'supplier_id')
                && ($atribut['category'] !== $p->category || $p->supplier_id === null)) {
                $supplier = Product::where('category', $atribut['category'])->whereNotNull('supplier_id')->value('supplier_id');

                if ($supplier) {
                    $atribut['supplier_id'] = $supplier;
                }
            }

            $p->update($atribut);

            return $p->fresh();
        });

        return response()->json(['product' => $this->payloadProduk($p)]);
    }

    /**
     * Hapus menu. Riwayat penjualan tidak terpengaruh, karena nama dan harga tersimpan di setiap transaksi.
     * Ditolak kalau menu masih ada di pesanan yang berjalan atau pesanan supplier yang belum diterima.
     */
    public function destroyMenu(Product $product)
    {
        $this->authorizeKasir();
        abort_unless($product->outlet === $this->outlet(), 403, 'Menu ini bukan milik outlet Anda.');

        DB::transaction(function () use ($product) {
            $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $berjalan = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.product_id', $p->id)
                ->where('orders.status', '!=', 'batal')
                ->where(function ($q) {
                    $q->where('orders.status', 'menunggu_bayar')
                        ->orWhere('orders.kitchen_status', '!=', 'selesai');
                })
                ->exists();

            abort_if($berjalan, 409, 'Menu ini masih ada di pesanan yang belum selesai (belum dibayar atau masih di dapur). Selesaikan atau batalkan pesanan itu dulu.');
            abort_if($this->adaPesananSupplier($p), 409, 'Ada pesanan ke supplier untuk menu ini yang belum diterima. Terima dulu pesanannya sebelum menghapus menu.');

            $p->delete();
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Penjualan langsung di kasir (langsung dibayar).
     * Item boleh berasal dari outlet ini dan outlet yang terhubung. Setiap outlet mendapat pesanannya
     * sendiri (stok, dapur, dan laporan tetap per outlet), tetapi pembayarannya satu kali.
     */
    public function store(Request $request)
    {
        $this->authorizeKasir();
        $outlet = $this->outlet();
        $cfg = $this->cfg();
        $tables = (int) $cfg['tables'];
        $maksDiskon = (int) config('outlets.diskon_maks_persen', 50);

        $data = $request->validate([
            'table'          => $tables > 0 ? 'required|integer|min:1|max:' . $tables : 'nullable',
            'method'         => 'required|in:tunai,qris,debit',
            'paid'           => 'nullable|integer|min:0',
            'items'          => 'required|array|min:1|max:50',
            'items.*.id'     => 'required|integer',
            'items.*.qty'    => 'required|integer|min:1|max:99',
            'discount_type'  => 'nullable|in:persen,rupiah',
            'discount_value' => 'nullable|integer|min:0|max:100000000',
            'discount_note'  => 'nullable|string|max:100',
        ]);

        $diizinkan = array_merge([$outlet], $cfg['terhubung'] ?? []);

        $jumlah = collect($data['items'])
            ->groupBy('id')
            ->map(fn ($rows) => (int) $rows->sum('qty'));

        $produk = Product::whereIn('id', $jumlah->keys()->all())->get()->keyBy('id');
        abort_unless($produk->count() === $jumlah->count(), 422, 'Ada menu yang tidak tersedia.');

        foreach ($produk as $p) {
            abort_unless(in_array($p->outlet, $diizinkan, true), 422, 'Ada menu dari outlet yang tidak terhubung dengan kasir ini.');
        }

        // Kelompokkan item per outlet (outlet sendiri lebih dulu), hitung bagian yang boleh didiskon.
        $tanpa = config('outlets.tanpa_diskon_service', []);
        $grup = [];

        foreach ($diizinkan as $o) {
            $baris = $jumlah->filter(fn ($q, $id) => $produk[$id]->outlet === $o);

            if ($baris->isEmpty()) {
                continue;
            }

            $grup[$o] = [
                'items'       => $baris->map(fn ($q, $id) => ['id' => (int) $id, 'qty' => $q])->values()->all(),
                'elig'        => 0,
                'semuaParkir' => true,
            ];

            foreach ($baris as $id => $q) {
                $p = $produk[$id];

                if (! in_array($p->category, $tanpa, true)) {
                    $grup[$o]['elig'] += $p->price * $q;
                }
                if ($p->category !== 'Parkir') {
                    $grup[$o]['semuaParkir'] = false;
                }
            }
        }

        // Diskon: rupiah atau persen, dari total menu yang boleh didiskon.
        $totalElig = array_sum(array_column($grup, 'elig'));
        $tipe = $data['discount_type'] ?? null;
        $nilai = (int) ($data['discount_value'] ?? 0);
        $diskon = 0;

        if ($tipe && $nilai > 0) {
            abort_if($totalElig <= 0, 422, 'Tidak ada menu yang bisa didiskon (parkir, paket jam, dan aksesoris tidak kena diskon).');

            if ($tipe === 'persen') {
                abort_if($nilai > $maksDiskon, 422, "Diskon maksimal {$maksDiskon}%.");
                $diskon = (int) round($totalElig * $nilai / 100);
            } else {
                abort_if($nilai > $totalElig, 422, 'Diskon melebihi total menu yang bisa didiskon (Rp ' . number_format($totalElig, 0, ',', '.') . ').');
                $diskon = $nilai;
            }
        }

        // Bagi diskon ke tiap outlet sesuai porsinya. Outlet terakhir mendapat sisanya.
        $bagian = [];
        $sisa = $diskon;
        $kunci = array_keys($grup);
        $terakhir = end($kunci);

        foreach ($grup as $o => $g) {
            if ($o === $terakhir) {
                $bagian[$o] = max(0, min($sisa, $g['elig']));
            } else {
                $b = $totalElig > 0 ? (int) round($diskon * $g['elig'] / $totalElig) : 0;
                $b = min($b, $g['elig']);
                $bagian[$o] = $b;
                $sisa -= $b;
            }
        }

        $bill = count($grup) > 1 ? 'BILL-' . strtoupper(Str::random(6)) : null;

        [$orders, $grand, $dibayar] = DB::transaction(function () use ($grup, $bagian, $bill, $data, $outlet, $tables) {
            $orders = [];

            foreach ($grup as $o => $g) {
                $label = $this->labelMeja($o, $outlet, $tables, $data['table'] ?? null, $g['semuaParkir']);

                $orders[] = Order::place($o, $label, $g['items'], [
                    'source'    => 'kasir',
                    'bill_code' => $bill,
                ], [
                    'discount' => $bagian[$o],
                    'note'     => $data['discount_note'] ?? null,
                    'service'  => true,
                ]);
            }

            $grand = array_sum(array_map(fn ($x) => (int) $x->total + (int) $x->service_amount, $orders));
            $dibayar = $data['method'] === 'tunai' ? (int) ($data['paid'] ?? 0) : $grand;

            if ($dibayar < $grand) {
                abort(422, 'Uang diterima kurang dari total.');
            }

            // Pembayaran dibagi ke tiap pesanan. Pesanan terakhir menanggung kembaliannya.
            $sisaBayar = $dibayar;
            $n = count($orders);

            foreach ($orders as $i => $order) {
                $milik = (int) $order->total + (int) $order->service_amount;
                $bayar = $i === $n - 1 ? $sisaBayar : $milik;

                $order->update([
                    'status'         => 'lunas',
                    'payment_method' => $data['method'],
                    'paid_amount'    => $bayar,
                    'change_amount'  => $bayar - $milik,
                    'paid_at'        => now(),
                ]);

                $sisaBayar -= $milik;
            }

            return [$orders, $grand, $dibayar];
        });

        return response()->json([
            'orders' => array_map(fn ($o) => $o->toPayload(), $orders),
            'bill'   => [
                'code'          => $bill,
                'subtotal'      => array_sum(array_map(fn ($o) => (int) $o->subtotal, $orders)),
                'discount'      => array_sum(array_map(fn ($o) => (int) $o->discount, $orders)),
                'discount_note' => $data['discount_note'] ?? null,
                'service'       => array_sum(array_map(fn ($o) => (int) $o->service_amount, $orders)),
                'total'         => $grand,
                'paid'          => $dibayar,
                'change'        => $dibayar - $grand,
                'method'        => $data['method'],
            ],
        ], 201);
    }

    /**
     * Kasir menerima pembayaran untuk pesanan dari customer.
     * Yang ditagih = total menu + service charge.
     */
    public function pay(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        $data = $request->validate([
            'method' => 'required|in:tunai,qris,debit',
            'paid'   => 'nullable|integer|min:0',
        ]);

        $order = DB::transaction(function () use ($order, $data) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'menunggu_bayar') {
                abort(409, 'Pesanan ini sudah diproses.');
            }

            $grand = (int) $order->total + (int) $order->service_amount;
            $paid = $data['method'] === 'tunai' ? (int) ($data['paid'] ?? 0) : $grand;

            if ($paid < $grand) {
                abort(422, 'Uang diterima kurang dari total.');
            }

            $order->update([
                'status'         => 'lunas',
                'payment_method' => $data['method'],
                'paid_amount'    => $paid,
                'change_amount'  => $paid - $grand,
                'paid_at'        => now(),
            ]);

            return $order;
        });

        return response()->json(['order' => $order->toPayload()]);
    }

    /**
     * Batalkan pesanan yang belum dibayar. Stok dikembalikan (hanya produk yang memakai stok).
     */
    public function cancel(Order $order)
    {
        $this->authorizeOrder($order);

        DB::transaction(function () use ($order) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'menunggu_bayar') {
                abort(409, 'Pesanan ini sudah diproses.');
            }

            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)
                        ->where('track_stock', true)
                        ->increment('stock', $item->qty);
                }
            }

            $order->update(['status' => 'batal']);
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Validasi isian menu (dipakai tambah dan ubah). Mengembalikan [data tervalidasi, atribut siap simpan].
     * $kecualiId = id menu yang sedang diubah, supaya tidak dianggap bentrok dengan dirinya sendiri.
     */
    private function siapkanMenu(Request $request, ?int $kecualiId = null): array
    {
        $outlet = $this->outlet();
        $cfg = $this->cfg();

        $request->merge([
            'name'     => preg_replace('/\s+/', ' ', trim((string) $request->input('name'))),
            'category' => preg_replace('/\s+/', ' ', trim((string) $request->input('category'))) ?: null,
            'barcode'  => trim((string) $request->input('barcode')) ?: null,
        ]);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|integer|min:1|max:100000000',
            'cost'        => 'nullable|integer|min:0|max:100000000',
            'category'    => 'nullable|string|max:50',
            'stock'       => 'nullable|integer|min:0|max:1000000',
            'track_stock' => 'required|boolean',
            'to_kitchen'  => 'required|boolean',
            'barcode'     => 'nullable|string|max:50',
        ]);

        $track = (bool) $data['track_stock'];

        if ($track && ! isset($data['stock'])) {
            throw ValidationException::withMessages(['stock' => 'Isi stok, atau matikan "Pakai stok".']);
        }

        $nama = Product::where('outlet', $outlet)->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])]);
        if ($kecualiId) {
            $nama->where('id', '!=', $kecualiId);
        }
        if ($nama->exists()) {
            throw ValidationException::withMessages(['name' => "Menu \"{$data['name']}\" sudah ada di {$outlet}."]);
        }

        $barcode = $cfg['barcode'] ? ($data['barcode'] ?? null) : null;
        if ($barcode) {
            $b = Product::where('outlet', $outlet)->where('barcode', $barcode);
            if ($kecualiId) {
                $b->where('id', '!=', $kecualiId);
            }
            if ($b->exists()) {
                throw ValidationException::withMessages(['barcode' => 'Barcode ini sudah dipakai menu lain.']);
            }
        }

        return [$data, [
            'name'        => $data['name'],
            'price'       => $data['price'],
            'category'    => $data['category'] ?? null,
            'stock'       => $track ? (int) $data['stock'] : 0,
            'track_stock' => $track,
            'to_kitchen'  => (bool) $cfg['kitchen'] && (bool) $data['to_kitchen'],
            'barcode'     => $barcode,
        ]];
    }

    /** Apakah menu ini masih punya pesanan ke supplier yang belum diterima. */
    private function adaPesananSupplier(Product $p): bool
    {
        if (! Schema::hasTable('supplier_orders')) {
            return false;
        }

        return DB::table('supplier_orders')
            ->where('outlet', $p->outlet)
            ->where('product_name', $p->name)
            ->where('status', 'dipesan')
            ->exists();
    }

    /** Bentuk data satu menu yang dipakai halaman kasir. */
    private function payloadProduk(Product $p, int $sold = 0): array
    {
        return [
            'id'       => $p->id,
            'name'     => $p->name,
            'price'    => (int) $p->price,
            'stock'    => (int) $p->stock,
            'category' => $p->category,
            'barcode'  => $p->barcode,
            'track'    => (bool) $p->track_stock,
            'kit'      => (bool) $p->to_kitchen,
            'sold'     => $sold,
            'disk'     => ! in_array($p->category, config('outlets.tanpa_diskon_service', []), true),
        ];
    }

    /** Menu satu outlet dalam bentuk yang dipakai halaman kasir. */
    private function produkUntuk(string $outlet, $terjual = null)
    {
        return Product::menuFor($outlet)
            ->map(fn ($p) => $this->payloadProduk($p, $terjual ? (int) ($terjual[$p->id] ?? 0) : 0))
            ->values();
    }

    /** Label meja di pesanan. Pesanan outlet lain diberi label asalnya, supaya dapur tahu. */
    private function labelMeja(string $outletPesanan, string $asal, int $tables, $meja, bool $hanyaParkir): string
    {
        if ($hanyaParkir) {
            return 'Parkir';
        }

        if ($outletPesanan === $asal) {
            return $tables > 0 ? 'Meja ' . (int) $meja : 'Kasir';
        }

        return 'Dari ' . $asal . ($tables > 0 ? ' · Meja ' . (int) $meja : '');
    }

    /**
     * Jumlah terjual per produk di satu outlet (id produk => jumlah),
     * dari pesanan 30 hari terakhir yang tidak dibatalkan.
     */
    private function terjual(string $outlet)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.outlet', $outlet)
            ->where('orders.status', '!=', 'batal')
            ->where('orders.created_at', '>=', now()->subDays(self::HARI_TERLARIS))
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id as pid, SUM(order_items.qty) as terjual')
            ->pluck('terjual', 'pid')
            ->map(fn ($n) => (int) $n);
    }

    private function user(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user, 401);

        return $user;
    }

    private function outlet(): string
    {
        return $this->user()->outlet ?: 'Kals Maison';
    }

    /** Pengaturan outlet dari config/outlets.php. */
    private function cfg(): array
    {
        return config('outlets.list.' . $this->outlet(), config('outlets.default'));
    }

    private function authorizeKasir(): void
    {
        abort_unless(in_array($this->user()->role, ['kasir', 'pic'], true), 403);
    }

    private function authorizeOrder(Order $order): void
    {
        $this->authorizeKasir();
        abort_unless($order->outlet === $this->outlet(), 403);
    }
}
