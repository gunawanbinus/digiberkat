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

    /**
     * Menampilkan semua pesanan (biasanya untuk halaman admin/manajemen pesanan).
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/all");

            if ($response->successful()) {
                $orders = $response->json()['data'] ?? [];
                return view('orders.index', compact('orders'));
            }

            Log::error('Gagal mengambil semua pesanan dari API GoLang:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->with('error', 'Gagal mengambil data pesanan. Kode: ' . $response->status());

        } catch (\Exception $e) {
            Log::error('Pengecualian saat mengambil semua pesanan:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Server tidak tersedia atau terjadi kesalahan: ' . $e->getMessage());
        }
    }
    public function indexemployee()
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/all");

            if ($response->successful()) {
                $orders = $response->json()['data'] ?? [];
                return view('orders.allemployee', compact('orders'));
            }

            Log::error('Gagal mengambil semua pesanan dari API GoLang:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->with('error', 'Gagal mengambil data pesanan. Kode: ' . $response->status());

        } catch (\Exception $e) {
            Log::error('Pengecualian saat mengambil semua pesanan:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Server tidak tersedia atau terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan pesanan berdasarkan status tertentu (misalnya untuk halaman laporan admin).
     *
     * @param string $status
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
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

            Log::error("Gagal mengambil pesanan dengan status '{$status}' dari API GoLang:", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->with('error', 'Gagal mengambil data pesanan: ' . $response->body());

        } catch (\Exception $e) {
            Log::error("Pengecualian saat mengambil pesanan dengan status '{$status}':", ['error' => $e->getMessage()]);
            return back()->with('error', 'Server tidak tersedia: ' . $e->getMessage());
        }
    }

    /**
     * API Endpoint untuk mendapatkan daftar pesanan pending.
     * Ini akan dipanggil oleh JavaScript dari employee dashboard via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingOrders(Request $request)
    {
        $token = session('api_token');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi berakhir. Silakan login kembali.'
            ], 401); // Unauthorized
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/all/pending");

            if ($response->successful()) {
                $pendingOrders = $response->json('data') ?? [];

                // Sorting data berdasarkan ID pesanan
                usort($pendingOrders, fn($a, $b) => ($a['order']['id'] ?? 0) <=> ($b['order']['id'] ?? 0));

                // Batasi jumlah data yang ditampilkan (misalnya 15, bisa disesuaikan di frontend juga)
                $pendingOrders = array_slice($pendingOrders, 0, 15);

                return response()->json([
                    'success' => true,
                    'data' => $pendingOrders
                ]);
            }

            Log::error('Gagal mengambil pesanan pending dari API GoLang:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pesanan pending dari server.'
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Pengecualian saat mengambil pesanan pending:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal saat memuat pesanan pending.'
            ], 500);
        }
    }

    /**
     * Menangani permintaan pemindaian QR code dari dashboard karyawan.
     * Memvalidasi apakah ID pesanan valid dan ada dalam daftar pesanan pending.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scanOrder(Request $request)
    {
        $orderId = $request->input('order_id');

        // Validasi dasar input: harus ada, numerik, dan positif
        if (empty($orderId) || !is_numeric($orderId) || (int)$orderId < 1) {
            return response()->json([
                'success' => false,
                'message' => 'ID Pesanan tidak valid. Harap pindai ulang.'
            ], 400); // Bad Request
        }

        $orderId = (int) $orderId; // Pastikan menjadi integer

        $token = session('api_token');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi berakhir. Silakan login kembali.'
            ], 401); // Unauthorized
        }

        try {
            // 1. Ambil daftar pesanan pending dari GoLang API untuk validasi real-time
            $pendingResponse = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/all/pending");

            if (!$pendingResponse->successful()) {
                Log::error('Gagal mengambil pesanan pending saat cek scan:', [
                    'status' => $pendingResponse->status(),
                    'body' => $pendingResponse->body()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memeriksa daftar pesanan pending dari server. Silakan coba lagi.'
                ], 500); // Internal Server Error
            }

            $pendingOrders = $pendingResponse->json('data') ?? [];
            $isOrderPending = false;
            $foundOrderData = null; // Untuk menyimpan data pesanan yang ditemukan jika ada

            // Cari ID pesanan yang dipindai di daftar pending
            foreach ($pendingOrders as $item) {
                if (isset($item['order']['id']) && (int)$item['order']['id'] === $orderId) {
                    $isOrderPending = true;
                    $foundOrderData = $item['order']; // Asumsi detail pesanan ada di 'order' key
                    break;
                }
            }

            // 2. Cek apakah ID pesanan yang dipindai ada di daftar pending
            if (!$isOrderPending) {
                return response()->json([
                    'success' => false,
                    'message' => "Pesanan dengan ID #{$orderId} tidak ditemukan dalam daftar pesanan pending atau tidak valid. Pastikan ini adalah pesanan yang belum diproses."
                ], 404); // Not Found
            }

            // Jika sampai di sini, pesanan valid dan berstatus pending.
            // Kembalikan data pesanan yang ditemukan.
            return response()->json([
                'success' => true,
                'message' => 'Pesanan ditemukan dan berstatus pending.',
                'order' => [
                    'id' => $foundOrderData['id'] ?? $orderId,
                    'total_price' => $foundOrderData['total_amount'] ?? $foundOrderData['total_price'] ?? 0, // Sesuaikan dengan nama field di API GoLang
                    'status' => $foundOrderData['status'] ?? 'pending',
                    // Tambahkan detail lain dari $foundOrderData jika diperlukan di frontend
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pengecualian saat memproses scan QR order:', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. Mohon coba lagi nanti.'
            ], 500);
        }
    }

    /**
     * Menampilkan detail pesanan untuk halaman admin/pengguna umum.
     *
     * @param int $id The ID of the order.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        return $this->renderOrderDetail($id, 'orders.show');
    }

    public function showEmployee($id)
    {
        return $this->renderOrderDetail($id, 'orders.showemployee');
    }

    private function renderOrderDetail($id, $view)
    {
        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$this->baseUrl}/orders/{$id}");

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];
                $total = $data['total_order_price'] ?? 0;
                $status = $data['status'] ?? 'pending';
                $created_at = $data['created_at'] ?? now();

                return view($view, compact('items', 'total', 'status', 'created_at'))
                    ->with('orderId', $id);
            }

            Log::error('Gagal mengambil detail pesanan:', [
                'order_id' => $id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->with('error', 'Gagal mengambil detail pesanan. Kode: ' . $response->status());

        } catch (\Exception $e) {
            Log::error('Pengecualian saat mengambil detail pesanan:', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Server tidak tersedia: ' . $e->getMessage());
        }
    }


    /**
     * Helper function to get human-readable status labels.
     *
     * @param string $status
     * @return string
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
}
