<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/'); // arahkan ke halaman awal
})->name('logout');

Route::get('/admin/charts', function () {
    return view('admin.charts');
});
