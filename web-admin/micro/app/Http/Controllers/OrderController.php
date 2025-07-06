<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Import Log Facade

class OrderController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        // Pastikan ini mengarah ke base URL API GoLang Anda
        $this->baseUrl = rtrim(config('services.golang_api.url'), '/');
    }

    public function index()
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/all");

        if ($response->successful()) {
            $orders = $response->json()['data'] ?? [];
            return view('orders.index', compact('orders'));
        }

        return back()->with('error', 'Gagal mengambil data pesanan');
    }

    public function getByStatus($status)
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $validStatuses = ['pending', 'expired', 'done', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Status pesanan tidak valid.');
        }

        try {
            $response = Http::withToken($token)
                            ->timeout(10)
                            ->get("{$this->baseUrl}/orders/all/{$status}");

            if ($response->successful()) {
                $responseData = $response->json();
                return view('orders.by_status', [
                    'orders' => $responseData['data'] ?? [],
                    'status' => $status,
                    'statusLabel' => $this->getStatusLabel($status),
                ]);
            }

            return back()->with('error', 'Gagal mengambil data pesanan: ' . $response->body());

        } catch (\Exception $e) {
            return back()->with('error', 'Server tidak tersedia: ' . $e->getMessage());
        }
    }

    // Fungsi scanOrder yang baru ditambahkan/dimodifikasi
    public function scanOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|numeric|min:1', // Validasi bahwa ini adalah angka positif
        ]);

        $orderId = (int) $request->input('order_id'); // Konversi ke integer

        $token = session('api_token');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi berakhir. Silakan login kembali.'
            ], 401); // Unauthorized
        }

        try {
            // Panggil API GoLang untuk mendapatkan detail pesanan berdasarkan ID
            $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/{$orderId}");

            if ($response->successful()) {
                $data = $response->json();
                // Pastikan struktur respons dari GoLang API sesuai
                // Misalnya, jika API mengembalikan { "data": { "id": 1, "status": "pending", ... } }
                // maka $data['data'] akan berisi detail pesanan.
                // Jika API hanya mengembalikan { "id": 1, "status": "pending", ... }
                // maka Anda bisa langsung menggunakan $data.
                $orderData = $data['data'] ?? $data; // Sesuaikan dengan struktur respons API GoLang Anda

                if (!empty($orderData)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Pesanan ditemukan.',
                        'order' => [
                            'id' => $orderData['id'] ?? $orderId, // Ambil ID dari data atau gunakan orderId input
                            // 'status' => $orderData['status'] ?? 'N/A',
                            // 'total_amount' => $orderData['total_order_price'] ?? 0, // Sesuaikan nama field
                            // Tambahkan detail lain yang ingin Anda tampilkan di frontend
                            // Misalnya: 'customer_name' => $orderData['customer_name'] ?? 'Guest',
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Detail pesanan dengan ID ' . $orderId . ' tidak ditemukan di API GoLang.'
                    ], 404);
                }

            } else {
                Log::error('API GoLang error on scanOrder for ID: ' . $orderId, [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil detail pesanan dari API GoLang: ' . ($response->json()['message'] ?? 'Unknown error')
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception during QR scan order lookup: ' . $e->getMessage(), ['order_id' => $orderId]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. Mohon coba lagi nanti.'
            ], 500);
        }
    }


    public function show($id)
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/{$id}");

        if ($response->successful()) {
            $data = $response->json();
            $items = $data['data'] ?? [];
            $total = $data['total_order_price'] ?? 0;
            $status = $data['status'] ?? 'pending';
            $created_at = $data['created_at'] ?? now();

            return view('orders.show', compact('items', 'total', 'status', 'created_at'))
                       ->with('orderId', $id);
        }

        return back()->with('error', 'Gagal mengambil detail pesanan');
    }
    public function showemployee($id)
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Panggil API GoLang untuk mendapatkan detail pesanan berdasarkan ID
        $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/{$id}");

        if ($response->successful()) {
            $data = $response->json();

            // --- LANGKAH PENTING UNTUK DEBUGGING: Log respons API yang sebenarnya ---
            // Ini akan mencatat struktur JSON lengkap yang diterima dari API GoLang
            // Anda bisa melihatnya di file storage/logs/laravel.log
            Log::info('API Response for showemployee', ['order_id' => $id, 'response_data' => $data]);

            // Asumsi struktur API GoLang sama dengan yang berhasil ditangani oleh fungsi 'show':
            // {
            //     "data": [ {item1}, {item2}, ... ], // Ini adalah array dari item pesanan
            //     "total_order_price": 12345,
            //     "status": "done",
            //     "created_at": "2023-10-26T10:00:00Z"
            // }
            // Dengan asumsi ini, 'items' ada langsung di bawah kunci 'data', dan 'total_order_price',
            // 'status', 'created_at' berada di level root dari respons API.

            $items = $data['data'] ?? []; // Ekstrak array item langsung dari kunci 'data'
            $total = $data['total_order_price'] ?? 0; // Ekstrak total dari level root
            $status = $data['status'] ?? 'pending'; // Ekstrak status dari level root
            $created_at = $data['created_at'] ?? now(); // Ekstrak created_at dari level root

            return view('orders.showemployee', compact('items', 'total', 'status', 'created_at'))
                         ->with('orderId', $id);
        }

        // Log error jika panggilan API gagal
        Log::error('Failed to fetch order detail for showemployee', [
            'order_id' => $id,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return back()->with('error', 'Gagal mengambil detail pesanan');
    }


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
}



