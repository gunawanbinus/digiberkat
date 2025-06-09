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

    public function show($id)
    {
        $token = session('token');

        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = Http::withToken($token)->get(env('GOLANG_API_URL') . "orders/{$id}");

        if ($response->successful()) {
            $data = $response->json();
            $items = $data['data'];
            $total = $data['total_order_price'];
            $status = $data['status'] ?? 'pending';
            $created_at = $data['created_at'] ?? now();

            return view('orders.show', compact('items', 'total', 'status', 'created_at'))->with('orderId', $id);
        }

        return back()->with('error', 'Gagal mengambil detail pesanan');
    }


}
