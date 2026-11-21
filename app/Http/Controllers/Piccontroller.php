<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PicController extends Controller
{
    private const HARI_MINGGU = 7;
    private const HARI_BULAN = 30;

    /**
     * Halaman dashboard PIC.
     */
    public function page()
    {
        $this->authorizePic();

        return view('pic');
    }

    /**
     * Semua data dashboard (dipanggil berkala oleh browser).
     * Penjualan dihitung dari pesanan berstatus "lunas", pada waktu pembayaran (paid_at).
     */
    public function data()
    {
        $this->authorizePic();

        $outlets = $this->outlets();
        $idx = array_flip($outlets);
        // Pencocokan nama outlet tidak peka huruf besar/kecil ("Needs mart" = "Needs Mart").
        $idxLower = array_change_key_case($idx, CASE_LOWER);

        $hariIni = today()->toDateString();
        $mulaiMinggu = today()->subDays(self::HARI_MINGGU - 1);
        $mulaiBulan = today()->subDays(self::HARI_BULAN - 1);

        $kosong = fn () => ['jual' => 0, 'hpp' => 0, 'trx' => 0];
        $jamKosong = fn () => ['jual' => array_fill(0, 24, 0), 'trx' => array_fill(0, 24, 0)];

        $lap = ['harian' => [], 'mingguan' => [], 'bulanan' => []];
        $jamHari = [];
        $jamMinggu = [];

        foreach ($outlets as $o) {
            foreach (['harian', 'mingguan', 'bulanan'] as $k) {
                $lap[$k][$o] = $kosong();
            }
            $jamHari[$o] = $jamKosong();
            $jamMinggu[$o] = $jamKosong();
        }

        $tren = [];
        for ($i = self::HARI_MINGGU - 1; $i >= 0; $i--) {
            $tren[today()->subDays($i)->toDateString()] = 0;
        }

        // Satu baris per pesanan lunas, lengkap dengan total HPP-nya.
        $rows = DB::table('orders')
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'lunas')
            ->whereNotNull('orders.paid_at')
            ->where('orders.paid_at', '>=', $mulaiBulan)
            ->groupBy('orders.id', 'orders.outlet', 'orders.total', 'orders.paid_at')
            ->selectRaw('orders.id, orders.outlet, orders.total, orders.paid_at, COALESCE(SUM(order_items.cost * order_items.qty), 0) as hpp')
            ->get();

        foreach ($rows as $r) {
            $oi = $idxLower[mb_strtolower((string) $r->outlet)] ?? null;
            if ($oi === null) {
                continue;
            }

            $o = $outlets[$oi];
            $t = Carbon::parse($r->paid_at);
            $total = (int) $r->total;
            $hpp = (int) $r->hpp;
            $jam = (int) $t->format('G');

            $this->tambah($lap['bulanan'][$o], $total, $hpp);

            if ($t->gte($mulaiMinggu)) {
                $this->tambah($lap['mingguan'][$o], $total, $hpp);
                $jamMinggu[$o]['jual'][$jam] += $total;
                $jamMinggu[$o]['trx'][$jam]++;

                $tgl = $t->toDateString();
                if (isset($tren[$tgl])) {
                    $tren[$tgl] += $total;
                }
            }

            if ($t->toDateString() === $hariIni) {
                $this->tambah($lap['harian'][$o], $total, $hpp);
                $jamHari[$o]['jual'][$jam] += $total;
                $jamHari[$o]['trx'][$jam]++;
            }
        }

        $format = fn (array $per) => collect($outlets)->map(fn ($o) => ['nama' => $o] + $per[$o])->values();

        $perjam = [
            'hari'  => $format($jamHari),
            'rata7' => collect($outlets)->map(fn ($o) => [
                'nama' => $o,
                'jual' => array_map(fn ($v) => (int) round($v / self::HARI_MINGGU), $jamMinggu[$o]['jual']),
                'trx'  => array_map(fn ($v) => round($v / self::HARI_MINGGU, 1), $jamMinggu[$o]['trx']),
            ])->values(),
        ];

        // Stok per outlet dan harga per produk.
        $stok = [];
        $produk = [];
        $produkSupplier = [];

        $produkRows = DB::table('products')
            ->whereIn('outlet', $outlets)
            ->orderBy('name')
            ->get(['outlet', 'name', 'price', 'cost', 'stock', 'track_stock', 'supplier_id']);

        foreach ($produkRows as $p) {
            $pi = $idxLower[mb_strtolower((string) $p->outlet)] ?? null;
            if ($pi === null) {
                continue;   // outlet tidak dikenal, lewati
            }

            if (! isset($produk[$p->name])) {
                $produk[$p->name] = ['hpp' => (int) $p->cost, 'harga' => (int) $p->price];
            }

            if (! $p->track_stock) {
                $produk[$p->name]['jasa'] = true;   // paket / sewa: tanpa stok
                continue;
            }

            if (! isset($stok[$p->name])) {
                $stok[$p->name] = array_fill(0, count($outlets), null);   // null = outlet tidak punya menu ini
            }
            $stok[$p->name][$pi] = (int) $p->stock;

            if ($p->supplier_id && ! isset($produkSupplier[$p->name])) {
                $produkSupplier[$p->name] = (int) $p->supplier_id;
            }
        }

        // Produk terlaris semua outlet, 30 hari terakhir.
        $laris = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'lunas')
            ->whereIn('orders.outlet', $outlets)
            ->where('orders.paid_at', '>=', $mulaiBulan)
            ->groupBy('order_items.name')
            ->selectRaw('order_items.name as nama, SUM(order_items.qty) as qty')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [$r->nama, (int) $r->qty])
            ->values();

        // 30 transaksi lunas terakhir.
        $metode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'debit' => 'Debit / Transfer'];
        $trx = Order::with('items')
            ->where('status', 'lunas')
            ->whereNotNull('paid_at')
            ->whereIn('outlet', $outlets)
            ->orderByDesc('paid_at')
            ->limit(30)
            ->get()
            ->map(fn ($o) => [
                'no'     => '#' . $o->id,
                'outlet' => $o->outlet,
                'jam'    => $o->paid_at->isToday() ? $o->paid_at->format('H:i') : $o->paid_at->format('d/m H:i'),
                'metode' => $metode[$o->payment_method] ?? '-',
                'total'  => (int) $o->total,
                'isi'    => $o->items->map(fn ($i) => $i->name . ' ×' . $i->qty)->implode(', '),
            ])
            ->values();

        $suppliers = DB::table('suppliers')->orderBy('id')->get()
            ->map(fn ($s) => ['id' => (int) $s->id, 'nama' => $s->name, 'ket' => $s->note ?? '', 'wa' => $s->whatsapp ?? ''])
            ->values();

        $pesanan = DB::table('supplier_orders')->orderByDesc('id')->limit(50)->get()
            ->map(fn ($p) => [
                'id'       => (int) $p->id,
                'no'       => $p->code,
                'supplier' => (int) $p->supplier_id,
                'outlet'   => $p->outlet,
                'produk'   => $p->product_name,
                'qty'      => (int) $p->qty,
                'status'   => $p->status === 'diterima' ? 'Diterima' : 'Dipesan',
            ])
            ->values();

        ksort($produk);
        ksort($stok);

        return response()->json([
            'outlets'         => $format($lap['harian']),
            'laporan'         => [
                'harian'   => $format($lap['harian']),
                'mingguan' => $format($lap['mingguan']),
                'bulanan'  => $format($lap['bulanan']),
            ],
            'perjam'          => $perjam,
            'tren'            => array_map(fn ($v) => round($v / 1000000, 2), array_values($tren)),
            'laris'           => $laris,
            'stok'            => $stok,
            'produk'          => $produk,
            'suppliers'       => $suppliers,
            'produk_supplier' => $produkSupplier,
            'pesanan'         => $pesanan,
            'trx'             => $trx,
        ]);
    }

    /**
     * Ubah HPP dan harga jual. Berlaku untuk semua outlet yang menjual produk itu.
     * Transaksi lama tidak berubah, karena harga dan HPP tersimpan per transaksi.
     */
    public function updateProduk(Request $request)
    {
        $this->authorizePic();

        $data = $request->validate([
            'nama'  => 'required|string|max:255',
            'hpp'   => 'required|integer|min:0',
            'harga' => 'required|integer|min:1',
        ]);

        $query = DB::table('products')->where('name', $data['nama'])->whereIn('outlet', $this->outlets());
        abort_unless($query->exists(), 404, 'Produk tidak ditemukan.');

        $query->update(['cost' => $data['hpp'], 'price' => $data['harga'], 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Pindahkan stok antar outlet.
     */
    public function transfer(Request $request)
    {
        $user = $this->user();
        $outlets = $this->outlets();

        $data = $request->validate([
            'dari'   => ['required', Rule::in($outlets)],
            'ke'     => ['required', Rule::in($outlets), 'different:dari'],
            'produk' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1|max:100000',
        ]);

        DB::transaction(function () use ($data, $user) {
            $rows = DB::table('products')
                ->where('name', $data['produk'])
                ->whereIn('outlet', [$data['dari'], $data['ke']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('outlet');

            $asal = $rows->get($data['dari']);
            $tujuan = $rows->get($data['ke']);

            abort_unless($asal, 422, "{$data['dari']} tidak punya menu {$data['produk']}.");
            abort_unless($tujuan, 422, "{$data['ke']} belum punya menu {$data['produk']}.");
            abort_unless($asal->track_stock && $tujuan->track_stock, 422, 'Produk ini tidak memakai stok.');
            abort_if($asal->stock < $data['jumlah'], 422, "Stok {$data['produk']} di {$data['dari']} hanya {$asal->stock}.");

            DB::table('products')->where('id', $asal->id)->decrement('stock', $data['jumlah']);
            DB::table('products')->where('id', $tujuan->id)->increment('stock', $data['jumlah']);

            DB::table('stock_transfers')->insert([
                'from_outlet'  => $data['dari'],
                'to_outlet'    => $data['ke'],
                'product_name' => $data['produk'],
                'qty'          => $data['jumlah'],
                'user_id'      => $user->id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Catat pesanan ke supplier.
     */
    public function buatPesanan(Request $request)
    {
        $this->authorizePic();

        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'outlet'      => ['required', Rule::in($this->outlets())],
            'produk'      => 'required|string|max:255',
            'jumlah'      => 'required|integer|min:1|max:100000',
            'catatan'     => 'nullable|string|max:255',
        ]);

        $ada = DB::table('products')->where('outlet', $data['outlet'])->where('name', $data['produk'])->exists();
        abort_unless($ada, 422, "{$data['outlet']} tidak punya menu {$data['produk']}.");

        $id = DB::table('supplier_orders')->insertGetId([
            'code'         => 'TMP-' . Str::random(10),
            'supplier_id'  => $data['supplier_id'],
            'outlet'       => $data['outlet'],
            'product_name' => $data['produk'],
            'qty'          => $data['jumlah'],
            'note'         => $data['catatan'] ?? null,
            'status'       => 'dipesan',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $kode = 'PO-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
        DB::table('supplier_orders')->where('id', $id)->update(['code' => $kode]);

        return response()->json(['ok' => true, 'kode' => $kode], 201);
    }

    /**
     * Tandai pesanan supplier diterima dan tambahkan stoknya.
     */
    public function terimaPesanan(int $id)
    {
        $this->authorizePic();

        DB::transaction(function () use ($id) {
            $po = DB::table('supplier_orders')->where('id', $id)->lockForUpdate()->first();
            abort_unless($po, 404, 'Pesanan tidak ditemukan.');
            abort_if($po->status !== 'dipesan', 409, 'Pesanan ini sudah diterima.');

            $produk = DB::table('products')
                ->where('outlet', $po->outlet)
                ->where('name', $po->product_name)
                ->lockForUpdate()
                ->first();
            abort_unless($produk, 422, 'Produk tidak ada di outlet tujuan.');

            if ($produk->track_stock) {
                DB::table('products')->where('id', $produk->id)->increment('stock', $po->qty);
            }

            DB::table('supplier_orders')->where('id', $id)->update([
                'status'      => 'diterima',
                'received_at' => now(),
                'updated_at'  => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    private function tambah(array &$a, int $total, int $hpp): void
    {
        $a['jual'] += $total;
        $a['hpp'] += $hpp;
        $a['trx']++;
    }

    /** Daftar outlet resmi, urutannya tetap (config/outlets.php). */
    private function outlets(): array
    {
        return array_keys(config('outlets.list'));
    }

    private function user(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->role === 'pic', 403);

        return $user;
    }

    private function authorizePic(): void
    {
        $this->user();
    }
}
