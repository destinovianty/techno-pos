<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    /** Jumlah meja untuk halaman customer (QR). Kasir memakai config/outlets.php. */
    public const TABLES = 10;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paid_at'    => 'datetime',
            'taken_at'   => 'datetime',
            'cooking_at' => 'datetime',
            'serving_at' => 'datetime',
            'done_at'    => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function newCode(): string
    {
        do {
            $code = 'ORD-' . strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Buat pesanan baru. Harga dan HPP dihitung ulang dari database (bukan dari browser).
     * Stok dicek dan dikurangi hanya untuk produk yang memakai stok.
     * Pesanan masuk antrian dapur hanya kalau ada item yang perlu dimasak.
     *
     * $biaya (opsional):
     *   'discount' => diskon dalam rupiah (dibatasi total menu yang boleh didiskon)
     *   'note'     => alasan diskon
     *   'service'  => true = tambahkan service charge sesuai config outlet
     *
     * Kolom "total" = harga item dikurangi diskon (tanpa service charge).
     * Yang dibayar customer = total + service_amount.
     *
     * @param  array<int, array{id:int, qty:int}>  $items
     */
    public static function place(string $outlet, string $tableName, array $items, array $attributes = [], array $biaya = []): self
    {
        return DB::transaction(function () use ($outlet, $tableName, $items, $attributes, $biaya) {
            $wanted = collect($items)
                ->groupBy('id')
                ->map(fn ($rows) => (int) $rows->sum('qty'));

            $products = Product::where('outlet', $outlet)
                ->whereIn('id', $wanted->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $tanpa = config('outlets.tanpa_diskon_service', []);

            $lines = [];
            $subtotal = 0;
            $eligible = 0;   // bagian yang boleh didiskon dan kena service charge
            $perluDapur = false;

            foreach ($wanted as $id => $qty) {
                $product = $products->get($id);

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Ada menu yang tidak tersedia.']);
                }

                if ($product->track_stock) {
                    if ($product->stock < $qty) {
                        throw ValidationException::withMessages(['items' => "Stok {$product->name} tidak cukup."]);
                    }
                    $product->decrement('stock', $qty);
                }

                if ($product->to_kitchen) {
                    $perluDapur = true;
                }

                $jumlah = $product->price * $qty;

                $lines[] = [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'cost'       => $product->cost,
                    'qty'        => $qty,
                    'subtotal'   => $jumlah,
                ];

                $subtotal += $jumlah;

                if (! in_array($product->category, $tanpa, true)) {
                    $eligible += $jumlah;
                }
            }

            $diskon = min(max((int) ($biaya['discount'] ?? 0), 0), $eligible);

            $rate = ! empty($biaya['service'])
                ? (float) config('outlets.list.' . $outlet . '.service', 0)
                : 0.0;
            $service = $rate > 0 ? (int) round(($eligible - $diskon) * $rate / 100) : 0;

            $order = static::create(array_merge([
                'code'           => static::newCode(),
                'outlet'         => $outlet,
                'table_name'     => $tableName,
                'subtotal'       => $subtotal,
                'discount'       => $diskon,
                'discount_note'  => $diskon > 0 ? ($biaya['note'] ?? null) : null,
                'service_rate'   => $rate,
                'service_amount' => $service,
                'total'          => $subtotal - $diskon,
                'kitchen_status' => $perluDapur ? 'antrian' : 'selesai',
            ], $attributes));

            $order->items()->createMany($lines);

            return $order->load('items');
        });
    }

    /**
     * Bentuk data yang dikirim ke browser (kasir, customer, dapur).
     */
    public function toPayload(): array
    {
        $this->loadMissing('items');

        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'outlet'         => $this->outlet,
            'table'          => $this->table_name,
            'source'         => $this->source,
            'status'         => $this->status,
            'pay_preference' => $this->pay_preference,
            'payment_method' => $this->payment_method,
            'subtotal'       => (int) ($this->subtotal ?: $this->total),
            'discount'       => (int) $this->discount,
            'discount_note'  => $this->discount_note,
            'service_rate'   => (float) $this->service_rate,
            'service'        => (int) $this->service_amount,
            'total'          => (int) $this->total,
            'grand_total'    => (int) $this->total + (int) $this->service_amount,
            'bill_code'      => $this->bill_code,
            'paid_amount'    => $this->paid_amount,
            'change'         => $this->change_amount,
            'created_at'     => $this->created_at?->toIso8601String(),
            'paid_at'        => $this->paid_at?->toIso8601String(),
            'kitchen_status' => $this->kitchen_status,
            'taken_at'       => $this->taken_at?->toIso8601String(),
            'cooking_at'     => $this->cooking_at?->toIso8601String(),
            'serving_at'     => $this->serving_at?->toIso8601String(),
            'done_at'        => $this->done_at?->toIso8601String(),
            'items'          => $this->items->map(fn ($i) => [
                'name'     => $i->name,
                'qty'      => $i->qty,
                'price'    => $i->price,
                'subtotal' => $i->subtotal,
            ])->values()->all(),
        ];
    }
}
