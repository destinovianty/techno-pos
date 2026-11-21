<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DapurController extends Controller
{
    /** Tahap sekarang => [tahap berikutnya, kolom waktu yang diisi]. */
    private const NEXT = [
        'antrian' => ['diambil', 'taken_at'],
        'diambil' => ['dimasak', 'cooking_at'],
        'dimasak' => ['serving', 'serving_at'],
        'serving' => ['selesai', 'done_at'],
    ];

    /**
     * Halaman dapur.
     */
    public function page()
    {
        $this->authorizeDapur();
        $cfg = $this->cfg();
        abort_unless($cfg['kitchen'], 404, 'Outlet ini tidak memakai dapur.');

        return view('dapur', [
            'outletName' => $this->outlet(),
            'userName'   => $this->user()->name,
            'color'      => $cfg['color'],
            'label'      => $cfg['label'],
        ]);
    }

    /**
     * Data terbaru untuk halaman dapur (dipanggil berkala oleh browser):
     * pesanan aktif + riwayat pesanan selesai.
     */
    public function feed(Request $request)
    {
        $this->authorizeDapur();
        $outlet = $this->outlet();
        $hari = $request->query('hari') === '7' ? 7 : 1;

        $aktif = Order::with('items')
            ->where('outlet', $outlet)
            ->where('status', '!=', 'batal')
            ->where('kitchen_status', '!=', 'selesai')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($order) => $order->toPayload())
            ->values();

        $riwayat = Order::with('items')
            ->where('outlet', $outlet)
            ->where('status', '!=', 'batal')
            ->where('kitchen_status', 'selesai')
            ->whereNotNull('done_at')
            ->where('done_at', '>=', today()->subDays($hari - 1))
            ->orderByDesc('done_at')
            ->limit(100)
            ->get()
            ->map(fn ($order) => $order->toPayload())
            ->values();

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'aktif'       => $aktif,
            'riwayat'     => $riwayat,
        ]);
    }

    /**
     * Majukan pesanan ke tahap berikutnya dan catat waktunya.
     * "from" wajib dikirim supaya klik ganda atau dua perangkat tidak melompati tahap.
     */
    public function advance(Request $request, Order $order)
    {
        $this->authorizeDapur();
        abort_unless($order->outlet === $this->outlet(), 403);

        $data = $request->validate([
            'from' => 'required|in:antrian,diambil,dimasak,serving',
        ]);

        $order = DB::transaction(function () use ($order, $data) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status === 'batal') {
                abort(409, 'Pesanan ini sudah dibatalkan.');
            }

            if ($order->kitchen_status !== $data['from']) {
                abort(409, 'Status pesanan sudah berubah. Data diperbarui.');
            }

            [$next, $kolom] = self::NEXT[$data['from']];

            $order->update([
                'kitchen_status' => $next,
                $kolom           => now(),
            ]);

            return $order;
        });

        return response()->json(['order' => $order->toPayload()]);
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
        $outlet = $this->user()->outlet;
        abort_unless($outlet, 403, 'Akun dapur ini belum punya outlet.');

        return $outlet;
    }

    /** Pengaturan outlet dari config/outlets.php. */
    private function cfg(): array
    {
        return config('outlets.list.' . $this->outlet(), config('outlets.default'));
    }

    private function authorizeDapur(): void
    {
        abort_unless($this->user()->role === 'dapur', 403);
    }
}
