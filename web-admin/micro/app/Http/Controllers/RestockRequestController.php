<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RestockRequestController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.golang_api.url'), '/');
    }

    public function index()
    {
        $token = session('api_token');
        if (!$token) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$this->baseUrl}/restock-requests");

            $restockRequests = $response->successful()
                ? $response->json('data') ?? []
                : [];

            // Urutkan berdasarkan product_id
            usort($restockRequests, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

            return view('admin.restock-requests', compact('restockRequests'));

        } catch (\Exception $e) {
            return view('admin.restock-requests')->withErrors([
                'API error: ' . $e->getMessage()
            ]);
        }
    }

    public function markAsRead($id)
    {
        $token = session('api_token');
        if (!$token) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->put("{$this->baseUrl}/restock-requests/{$id}/read");

            if ($response->successful()) {
                return redirect()->route('restock.requests')->with('success', 'Berhasil menandai sebagai sudah dibaca.');
            }

            return back()->with('error', 'Gagal menandai permintaan');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
