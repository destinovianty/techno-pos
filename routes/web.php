<?php

use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DapurController;
use App\Http\Controllers\KasirOrderController;
use App\Http\Controllers\PicController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', fn () => view('splash'));

// ================================
// LOGIN
// ================================

Route::get('/login', function () {

    $role = request('role', 'kasir');

    return view('login', compact('role'));

})->name('login');

Route::post('/login', function () {

    $data = request()->validate([
        'username' => 'required',
        'password' => 'required',
        'role'     => 'required|in:kasir,pic,dapur',
    ]);

    $user = User::where('username', $data['username'])->first();

    if (! $user || ! Hash::check($data['password'], $user->password)) {
        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->onlyInput('username');
    }

    if ($user->role !== $data['role']) {
        return back()
            ->withErrors(['username' => 'Akun ini bukan untuk peran ' . $data['role'] . '.'])
            ->onlyInput('username');
    }

    Auth::login($user);
    request()->session()->regenerate();

    return redirect()->route($user->role);

})->name('login.process');


// ================================
// REGISTER
// ================================

Route::get('/register', function () {

    $role = request('role', 'kasir');
    $outlets = array_keys(config('outlets.list'));

    return view('register', compact('role', 'outlets'));

})->name('register');

Route::post('/register', function () {

    $data = request()->validate([
        'name'     => 'required|string|max:255',
        'username' => 'required|string|max:50|unique:users,username',
        'password' => 'required|min:6|confirmed',
        'role'     => 'required|in:kasir,pic,dapur',
        'outlet'   => ['required_if:role,kasir,dapur', 'nullable', 'string', Rule::in(array_keys(config('outlets.list')))],
    ]);

    User::create([
        'name'     => $data['name'],
        'username' => $data['username'],
        'password' => Hash::make($data['password']),
        'role'     => $data['role'],
        'outlet'   => $data['outlet'] ?? null,
    ]);

    // setelah daftar, arahkan ke halaman login sesuai role
    return redirect()
        ->route('login', ['role' => $data['role']])
        ->with('success', 'Pendaftaran berhasil, silakan login.');

})->name('register.process');


// ================================
// CUSTOMER (scan QR di meja, tanpa login)
// ================================

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/order/status/{code}', [CustomerOrderController::class, 'status'])->name('customer.status');
    Route::post('/order/{code}/bayar', [CustomerOrderController::class, 'payAtTable'])->name('customer.bayar');
    Route::get('/order/{outlet}/{table}', [CustomerOrderController::class, 'menu'])->whereNumber('table')->name('customer.menu');
    Route::post('/order/{outlet}/{table}', [CustomerOrderController::class, 'store'])->whereNumber('table')->name('customer.store');
});


// ================================
// HALAMAN SETELAH LOGIN
// ================================

Route::middleware('auth')->group(function () {

    // Kasir
    Route::get('/kasir', [KasirOrderController::class, 'page'])->name('kasir');
    Route::get('/kasir/qr', [KasirOrderController::class, 'qr'])->name('kasir.qr');
    Route::get('/kasir/data', [KasirOrderController::class, 'feed'])->name('kasir.data');
    Route::post('/kasir/pesanan', [KasirOrderController::class, 'store'])->name('kasir.store');
    Route::post('/kasir/pesanan/{order}/bayar', [KasirOrderController::class, 'pay'])->name('kasir.bayar');
    Route::post('/kasir/pesanan/{order}/batal', [KasirOrderController::class, 'cancel'])->name('kasir.batal');

    // Kelola menu (tambah, ubah, hapus)
    Route::post('/kasir/menu', [KasirOrderController::class, 'storeMenu'])->name('kasir.menu');
    Route::put('/kasir/menu/{product}', [KasirOrderController::class, 'updateMenu'])->whereNumber('product')->name('kasir.menu.update');
    Route::delete('/kasir/menu/{product}', [KasirOrderController::class, 'destroyMenu'])->whereNumber('product')->name('kasir.menu.destroy');

    // PIC
    Route::get('/pic', [PicController::class, 'page'])->name('pic');
    Route::get('/pic/data', [PicController::class, 'data'])->name('pic.data');
    Route::post('/pic/produk', [PicController::class, 'updateProduk'])->name('pic.produk');
    Route::post('/pic/transfer', [PicController::class, 'transfer'])->name('pic.transfer');
    Route::post('/pic/pesanan', [PicController::class, 'buatPesanan'])->name('pic.pesanan');
    Route::post('/pic/pesanan/{id}/terima', [PicController::class, 'terimaPesanan'])->whereNumber('id')->name('pic.terima');

    // Dapur
    Route::get('/dapur', [DapurController::class, 'page'])->name('dapur');
    Route::get('/dapur/data', [DapurController::class, 'feed'])->name('dapur.data');
    Route::post('/dapur/pesanan/{order}/maju', [DapurController::class, 'advance'])->name('dapur.maju');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

});
