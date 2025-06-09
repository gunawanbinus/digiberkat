<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function index()
    {
        $token = session('token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = Http::withToken($token)->get(env('GOLANG_API_URL') . 'orders/all');

        if ($response->successful()) {
            $orders = $response->json()['data'];
            return view('orders.index', compact('orders'));
        }

        return back()->with('error', 'Gagal mengambil data pesanan');
    }

}


