<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;

Route::middleware(['check.login', 'check.token'])->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/account', [DashboardController::class, 'account'])->name('account');
    Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/productss', [ProductController::class, 'index1']);
});

// Route ini hanya bisa diakses oleh admin
Route::middleware(['check.login', 'check.token', 'check.role:admin'])->group(function () {
    Route::get('/employee/register', [LoginController::class, 'employeeRegister'])->name('employee.register');
    Route::post('/employee/register', [LoginController::class, 'doEmployeeRegister'])->name('employee.register.do');
});



Route::get('/', function () {
    return redirect()->route('login');
});
// Route::get('/login', 'LoginController@index')->name('login');
// Route::post('/login', 'LoginController@authenticate')->name('login.post');

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
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




