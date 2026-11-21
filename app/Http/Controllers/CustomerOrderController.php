<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    /**
     * true  = tombol "Saya sudah bayar" langsung melunasi pesanan (SIMULASI, untuk uji coba).
     * false = pelunasan lewat tombol ditolak. Ganti ke false setelah payment gateway
     *         (Midtrans / Xendit) terpasang, lalu lunasi pesanan lewat webhook.
     */
    private const QRIS_DEMO = true;

    /** Batas pesanan customer yang belum dibayar per meja (mencegah stok terkunci oleh pesanan iseng). */
    private const MAKS_BELUM_BAYAR = 5;

    /**
     * Halaman menu yang dibuka customer setelah scan QR di meja.
     */
    public function menu(string $outlet, int $table)
    {
        $outletName = $this->outletName($outlet);
        $cfg = $this->cfg($outletName);
        $this->checkTable($table, $cfg);

        $tanpa = config('outlets.tanpa_diskon_service', []);

        // Customer hanya boleh memesan yang masuk dapur (makanan dan minuman),
        // bukan paket jam, sewa, parkir, atau barang minimart.
        $produkJs = Product::menuFor($outletName)
            ->where('to_kitchen', true)
            ->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'price'    => (int) $p->price,
                'stock'    => (int) $p->stock,
                'category' => $p->category,
                'track'    => (bool) $p->track_stock,
                'disk'     => ! in_array($p->category, $tanpa, true),
            ])
            ->values();

        return view('customer-order', [
            'outletName' => $outletName,
            'slug'       => $outlet,
            'table'      => $table,
            'color'      => $cfg['color'],
            'label'      => $cfg['label'],
            'service'    => (float) ($cfg['service'] ?? 0),
            'demo'       => self::QRIS_DEMO,
            'produkJs'   => $produkJs,
        ]);
    }

    /**
     * Customer mengirim pesanan. Service charge dihitung otomatis oleh server.
     */
    public function store(Request $request, string $outlet, int $table)
    {
        $outletName = $this->outletName($outlet);
        $cfg = $this->cfg($outletName);
        $this->checkTable($table, $cfg);

        $data = $request->validate([
            'items'          => 'required|array|min:1|max:50',
            'items.*.id'     => 'required|integer',
            'items.*.qty'    => 'required|integer|min:1|max:99',
            'pay_preference' => 'required|in:meja,kasir',
        ]);

        $namaMeja = 'Meja ' . $table;

        $belumBayar = Order::where('outlet', $outletName)
            ->where('table_name', $namaMeja)
            ->where('source', 'customer')
            ->where('status', 'menunggu_bayar')
            ->count();

        abort_if(
            $belumBayar >= self::MAKS_BELUM_BAYAR,
            429,
            'Masih ada beberapa pesanan di meja ini yang belum dibayar. Silakan bayar atau panggil kasir.'
        );

        // Semua menu yang dipesan harus milik outlet ini dan boleh dipesan dari meja.
        $ids = collect($data['items'])->pluck('id')->unique();
        $valid = Product::where('outlet', $outletName)
            ->where('to_kitchen', true)
            ->whereIn('id', $ids->all())
            ->count();
        abort_unless($valid === $ids->count(), 422, 'Ada menu yang tidak tersedia untuk dipesan dari meja.');

        $order = Order::place($outletName, $namaMeja, $data['items'], [
            'source'         => 'customer',
            'pay_preference' => $data['pay_preference'],
        ], [
            'service' => true,
        ]);

        return response()->json(['order' => $order->toPayload()], 201);
    }

    /**
     * Status pesanan (dicek berkala oleh halaman customer).
     */
    public function status(string $code)
    {
        $order = Order::with('items')
            ->where('code', $code)
            ->where('source', 'customer')
            ->firstOrFail();

        return response()->json(['order' => $order->toPayload()]);
    }

    /**
     * Bayar di meja (QRIS). MASIH SIMULASI: tombol "Saya sudah bayar" langsung
     * menandai lunas. Untuk produksi, set QRIS_DEMO = false dan lunasi pesanan lewat
     * webhook dari payment gateway.
     */
    public function payAtTable(string $code)
    {
        abort_unless(self::QRIS_DEMO, 501, 'Pembayaran QRIS belum tersambung ke payment gateway.');

        $order = DB::transaction(function () use ($code) {
            $order = Order::with('items')
                ->where('code', $code)
                ->where('source', 'customer')
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->pay_preference !== 'meja') {
                abort(409, 'Pesanan ini dibayar di kasir.');
            }

            if ($order->status === 'menunggu_bayar') {
                $grand = (int) $order->total + (int) $order->service_amount;

                $order->update([
                    'status'         => 'lunas',
                    'payment_method' => 'qris',
                    'paid_amount'    => $grand,
                    'change_amount'  => 0,
                    'paid_at'        => now(),
                ]);
            }

            return $order;
        });

        return response()->json(['order' => $order->toPayload()]);
    }

    private function outletName(string $slug): string
    {
        $name = Product::query()
            ->distinct()
            ->pluck('outlet')
            ->first(fn ($outlet) => Str::slug($outlet) === $slug);

        abort_unless($name, 404);

        return $name;
    }

    /** Pengaturan outlet dari config/outlets.php. */
    private function cfg(string $outletName): array
    {
        return config('outlets.list.' . $outletName, config('outlets.default'));
    }

    private function checkTable(int $table, array $cfg): void
    {
        $max = (int) $cfg['tables'];
        abort_unless($cfg['kitchen'] && $max > 0 && $table >= 1 && $table <= $max, 404);
    }
}
