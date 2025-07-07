<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['check.login', 'check.token', 'check.role:admin']);
    }


    public function index()
    {
        try {
            $token = session('api_token');
            $baseUrl = config('services.golang_api.url');

            // Menggunakan Http::pool untuk parallel requests
            $responses = Http::pool(fn ($pool) => [
                $pool->withToken($token)->get("{$baseUrl}orders/all/pending"),
                $pool->withToken($token)->get("{$baseUrl}restock-requests"),
                $pool->withToken($token)->get("{$baseUrl}stats/sales"),
                $pool->withToken($token)->get("{$baseUrl}stats/lowstocks")
            ]);

            // Proses data
            $pendingOrders = $responses[0]->json('data') ?? [];
            $restockRequests = $responses[1]->json('data') ?? [];
            $sales = $responses[2]->json('data') ?? [];
            $lowStocks = $responses[3]->json('data') ?? [];

            // Sorting data
            usort($pendingOrders, fn($a, $b) => $a['order']['id'] <=> $b['order']['id']);
            usort($restockRequests, fn($a, $b) => $a['product_id'] <=> $b['product_id']);
            usort($sales, fn($a, $b) => strtotime($a['month']) <=> strtotime($b['month']));
            usort($lowStocks, fn($a, $b) => $a['stock'] <=> $b['stock']);

            // Batasi jumlah data yang ditampilkan
            $pendingOrders = array_slice($pendingOrders, 0, 15);
            $restockRequests = array_slice($restockRequests, 0, 15);
            $lowStocks = array_slice($lowStocks, 0, 15);

            return view('admin.dashboard', compact(
                'pendingOrders',
                'restockRequests',
                'sales',
                'lowStocks'
            ));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat dashboard: ' . $e->getMessage());
        }
    }
    public function account()
    {
        return view('account', [
            'user' => currentUser() // Pastikan helper currentUser() tersedia
        ]);
    }

    public function employeeindex()
    {
        try {
            $token = session('api_token');
            $baseUrl = config('services.golang_api.url');

            // Menggunakan Http::pool untuk parallel requests
            $responses = Http::pool(fn ($pool) => [
                $pool->withToken($token)->get("{$baseUrl}orders/all/pending"),
            ]);

            // Proses data
            $pendingOrders = $responses[0]->json('data') ?? [];

            // Sorting data
            usort($pendingOrders, fn($a, $b) => $a['order']['id'] <=> $b['order']['id']);

            // Batasi jumlah data yang ditampilkan
            $pendingOrders = array_slice($pendingOrders, 0, 15);

            return view('employee.dashboard', compact('pendingOrders'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memuat dashboard: ' . $e->getMessage());
        }
     }
}
