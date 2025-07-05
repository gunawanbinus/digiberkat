<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RestockRequestController extends Controller
{
    public function index()
    {
        $token = session('api_token');
        if (!$token) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $baseUrl = rtrim(env('GOLANG_API_URL'), '/');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ];

        try {
            $restockRequests = Http::withHeaders($headers)
                ->get("{$baseUrl}/restock-requests")
                ->json('data') ?? [];

            usort($restockRequests, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        } catch (\Exception $e) {
            return view('admin.restock-requests')
                ->withErrors(['API error: ' . $e->getMessage()]);
        }

        return view('admin.restock-requests', compact('restockRequests'));
    }

    public function markAsRead($id)
    {
        $token = session('api_token');

        $response = Http::withToken($token)
            ->put(env('GOLANG_API_URL') . "restock-requests/{$id}/read");

        if ($response->successful()) {
            return redirect()->route('restock.requests')->with('success', 'Berhasil.');
        }

        return back()->with('error', 'Gagal');
    }
}
