<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

Route::middleware(['check.login', 'check.token'])->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/account', [DashboardController::class, 'account'])->name('account');
    // Route::get('/admin/dashboard', function () {
    // return view('admin.dashboard');
    // ;
});

// Route ini hanya bisa diakses oleh admin
Route::middleware(['check.login', 'check.token', 'check.role:admin'])->group(function () {
    Route::get('/employee/register', [LoginController::class, 'employeeRegister'])->name('employee.register');
    Route::post('/employee/register', [LoginController::class, 'doEmployeeRegister'])->name('employee.register.do');
});

Route::get('/products', [ProductController::class, 'index'], function () {
    $token = session('token');
    if (!$token) {
        return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
    }
})->name('products.index');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/orders/all', [OrderController::class, 'index'], function () {
    $token = session('token');
    if (!$token) {
        return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
    }
})->name('orders.index');

Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{id}/finish', function ($id) {
    $token = session('token');

    $response = Http::withToken($token)->put(env('GOLANG_API_URL') . "orders/{$id}/finish");

    if ($response->successful()) {
        return redirect()->route('orders.index')->with('success', 'Pesanan selesai.');
    }

    return back()->with('error', 'Gagal menyelesaikan pesanan');
})->name('orders.finish');

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
        'role' => 'required|in:admin,employee',
    ]);

    $payload = [
        'username' => $request->input('username'),
        'password' => $request->input('password'),
        'role' => $request->input('role'),
    ];

    try {
        $response = Http::post(env('GOLANG_API_URL') . 'login', $payload);

        if ($response->successful()) {
            $data = $response->json();

            // Simpan token dan info user ke session
            session([
                'token' => $data['token'],
                'user' => $data['user'],
                'role' => $data['role']
            ]);

            return redirect('/admin/dashboard')->with('success', 'Berhasil login sebagai ' . $data['role']);
        }

        return back()->withErrors(['Gagal login: ' . $response->json('error') ?? 'Tidak diketahui']);
    } catch (\Exception $e) {
        return back()->withErrors(['Gagal terhubung ke API: ' . $e->getMessage()]);
    }
})->name('login.authenticate');

Route::get('/admin/register', [LoginController::class, 'adminRegister'])->name('admin.register');
Route::post('/admin/register', [LoginController::class, 'doAdminRegister'])->name('admin.register.do');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// Route::get('/login', [LoginController::class, 'index'])->name('login');
// Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
// Route::get('/regis', [LoginController::class, 'register'])->name('register');
// Route::post('/register', [LoginController::class, 'doRegister'])->name('register.do');

// Route::get('/products', [ProductController::class, 'index']);
// Route::get('/productss', [ProductController::class, 'index1']);
// Route::get('/admin/dashboard', function () {
//     return view('admin.dashboard');
// });

// Route::post('/logout', function () {
//     Auth::logout();
//     return redirect('/'); // arahkan ke halaman awal
// })->name('logout');

Route::get('/admin/charts', function () {
    return view('admin.charts');
});

Route::get('/admin/dashboard', function () {
    $token = session('token');
    if (!$token) {
        return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
    }

    $baseUrl = rtrim(env('GOLANG_API_URL'), '/');
    $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ];

    try {
        $pendingOrders = Http::withHeaders($headers)->get("{$baseUrl}/orders/all/pending")->json('data') ?? [];
        usort($pendingOrders, fn($a, $b) => $a['order']['id'] <=> $b['order']['id']);

        $sales = Http::withHeaders($headers)->get("{$baseUrl}/stats/sales")->json('data') ?? [];
        usort($sales, fn($a, $b) => strtotime($a['month']) <=> strtotime($b['month']));

        $lowStocks = Http::withHeaders($headers)->get("{$baseUrl}/stats/lowstocks")->json('data') ?? [];
        usort($lowStocks, fn($a, $b) => $a['stock'] <=> $b['stock']);

        $restockRequests = Http::withHeaders($headers)->get("{$baseUrl}/restock-requests")->json('data') ?? [];
        usort($restockRequests, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

    } catch (\Exception $e) {
        return view('admin.dashboard')->withErrors(['API error: ' . $e->getMessage()]);
    }

    $pendingOrders = array_slice($pendingOrders, 0, 15);
        $restockRequests = array_slice($restockRequests, 0, 15);
        $lowStocks = array_slice($lowStocks, 0, 15);

    return view('admin.dashboard', compact('pendingOrders', 'sales', 'lowStocks', 'restockRequests'));
})->name('dashboard');


