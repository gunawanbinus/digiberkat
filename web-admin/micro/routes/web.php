<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EmployeeAccountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RestockRequestController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ImageKitUploadController;
use App\Http\Controllers\ProductWizardController;

/*
|--------------------------------------------------------------------------
| AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
| Halaman login, logout, dan default redirect root.
*/
Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| ACCOUNT ROUTE (khusus user yang sudah login)->masih blom jelas fungsinya apa karena view  account.blade.php blom ada
|--------------------------------------------------------------------------
*/
Route::middleware(['check.login', 'check.token'])->group(function () {
    Route::get('/account', [DashboardController::class, 'account'])->name('account');
});

/*
|--------------------------------------------------------------------------
| ADMIN PANEL ROUTES (hanya untuk admin) ADMIN ONLY
|--------------------------------------------------------------------------
| Middleware: check.login, check.token, check.role:admin
| Prefix: /admin untuk dashboard
*/
//untuk midlleware check.login, check.token, dan check.role:admin lihat di bootstrap/app.php dan app/Http/Middleware
Route::middleware(['check.login', 'check.token', 'check.role:admin'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/account', [DashboardController::class, 'account'])->name('admin.account');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products/upload/wizard', [ProductWizardController::class, 'uploadImage'])->name('products.upload');
        Route::post('/products/search-vector/wizard', [ProductWizardController::class, 'generateSearchVector'])->name('products.search_vector');
        Route::post('/products', [ProductWizardController::class, 'storeProduct'])->name('products.store');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE MANAGEMENT ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/employees', [EmployeeAccountController::class, 'index'])->name('employee.index');
    Route::get('/employee/register', [LoginController::class, 'employeeRegister'])->name('employee.register');
    Route::post('/employee/register', [LoginController::class, 'doEmployeeRegister'])->name('employee.register.do');

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

    /*
    |--------------------------------------------------------------------------
    | CATEGORY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

    /*
    |--------------------------------------------------------------------------
    | RESTOCK-REQUEST ROUTES
    |--------------------------------------------------------------------------
    */

    Route::get('/restock-requests', [RestockRequestController::class, 'index'])->name('restock.requests');
    Route::post('/restock-requests/{id}/read', [RestockRequestController::class, 'markAsRead'])->name('requests.read');
});
Route::middleware(['check.login', 'check.token', 'check.role:employee'])->group(function () {
        /*
    |--------------------------------------------------------------------------
    | EMPLOYEE DASHBOARD ROUTE
    |--------------------------------------------------------------------------
    */
    Route::prefix('employee')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'employeeindex'])->name('employee.dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| ORDER ROUTES (Admin, Employee)
|--------------------------------------------------------------------------
*/
Route::middleware(['check.login', 'check.token', 'check.role:admin,employee'])->group(function () {
    Route::get('/orders/all', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/employee', [OrderController::class, 'showemployee'])->name('orders.showemployee');
    Route::get('/orders/status/{status}', [OrderController::class, 'getByStatus'])->name('orders.status');

    Route::post('/orders/{id}/finish', function ($id) {
        $token = session('token');
        $response = Http::withToken($token)->put(env('GOLANG_API_URL') . "orders/{$id}/finish");

        if ($response->successful()) {
            return redirect()->route('orders.index')->with('success', 'Pesanan selesai.');
        }

        return back()->with('error', 'Gagal menyelesaikan pesanan');
    })->name('orders.finish');

     // Rute POST untuk menerima ID pesanan dari pemindaian QR
    Route::post('/orders/scan', [OrderController::class, 'scanOrder'])->name('orders.scan');
    // Rute untuk menampilkan detail pesanan di halaman employee (jika Anda ingin mengarahkan langsung setelah scan)
    Route::get('/orders/{id}/employee-detail', [OrderController::class, 'showemployee'])->name('orders.showemployee');
});
