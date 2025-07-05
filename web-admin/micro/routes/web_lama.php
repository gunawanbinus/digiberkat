<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RestockRequestController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

// Route::middleware(['check.login', 'check.token'])->group(function () {
//     Route::get('/account', [DashboardController::class, 'account'])->name('account');
// });

// Dashboard routes
//untuk midlleware check.login, check.token, dan check.role:admin lihat di bootstrap/app.php dan app/Http/Middleware
Route::middleware(['check.login', 'check.token', 'check.role:admin'])->group(function() {
    Route::prefix('admin')->group(function() {
        Route::get('/dashboard', [DashboardController::class, 'index'])
             ->name('admin.dashboard');

        Route::get('/account', [DashboardController::class, 'account'])
             ->name('admin.account');
    });
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products', [ProductController::class, 'index'], function () {
        $token = session('token');
        if (!$token) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
        }
    })->name('products.index');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/orders/all', [OrderController::class, 'index'], function () {

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
    Route::get('/orders/status/{status}', [OrderController::class, 'getByStatus'])->name('orders.status');
});

// Route ini hanya bisa diakses oleh admin
Route::middleware(['check.login', 'check.token', 'check.role:admin'])->group(function () {
    Route::get('/employee/register', [LoginController::class, 'employeeRegister'])->name('employee.register');
    Route::post('/employee/register', [LoginController::class, 'doEmployeeRegister'])->name('employee.register.do');
});

Route::post('/products', [ProductController::class, 'store'])->name('products.store');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/restock-requests', function () {
    $token = session('api_token');
    if (!$token) {
        return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
    }

    $baseUrl = rtrim(env('GOLANG_API_URL'), '/');
    $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ];

    try {
        $restockRequests = Http::withHeaders($headers)->get("{$baseUrl}/restock-requests")->json('data') ?? [];
        usort($restockRequests, fn($a, $b) => $a['product_id'] <=> $b['product_id']);
    } catch (\Exception $e) {
        return view('admin.restock-requests')->withErrors(['API error: ' . $e->getMessage()]);
    }

    return view('admin.restock-requests', compact('restockRequests'));
})->name('restock.requests');

Route::post('/restock-requests/{id}/read', function ($id) {
    $token = session('api_token');

    $response = Http::withToken($token)->put(env('GOLANG_API_URL') . "restock-requests/{$id}/read");

    if ($response->successful()) {
        return redirect()->route('restock.requests')->with('success', 'Berhasil.');
    }

    return back()->with('error', 'Gagal');
})->name('requests.read');

// Route::get('/login', [LoginController::class, 'index'])->name('login');
// Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
// Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/admin/charts', function () {
    return view('admin.charts');
});
