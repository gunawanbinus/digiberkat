<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.golang_api.url');
    }
    public function index()
    {
        $token = session('api_token');
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


    /**
     * Get orders by status
     */
    public function getByStatus($status)
    {
        // Check authentication
        if (!session('api_token')) {
            return redirect()->route('login')
                   ->with('error', 'Session expired. Please login again.');
        }

        // Validate status
        $validStatuses = ['pending', 'expired', 'done', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Invalid order status requested');
        }

        try {
            $response = Http::withToken(session('api_token'))
                         ->timeout(10)
                         ->get($this->apiBaseUrl . "orders/all/{$status}");

            if ($response->successful()) {
                $responseData = $response->json();

                return view('orders.by_status', [
                    'orders' => $responseData['data'] ?? [],
                    'status' => $status,
                    'statusLabel' => $this->getStatusLabel($status)
                ]);
            }

            return back()->with('error', 'Failed to fetch orders: ' . $response->body());

        } catch (\Exception $e) {
            return back()->with('error',
                'Service unavailable. Please try again later. ' . $e->getMessage());
        }
    }

    /**
     * Get translated status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Belum Diproses',
            'expired' => 'Kadaluarsa',
            'done' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    public function show($id)
    {
        $token = session('api_token');

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

    public function showemployee($id)
    {
        $token = session('api_token');

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

            return view('orders.showemployee', compact('items', 'total', 'status', 'created_at'))->with('orderId', $id);
        }

        return back()->with('error', 'Gagal mengambil detail pesanan');
    }


}
