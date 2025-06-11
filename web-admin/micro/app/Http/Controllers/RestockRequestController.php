<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use Illuminate\Http\Request;

class RestockRequestController extends Controller
{
    // Tampilkan daftar permintaan restok
    public function index()
    {
        $restockRequests = RestockRequest::with('product')->orderBy('created_at', 'desc')->get();
        return view('restock_requests.index', compact('restockRequests'));
    }

    // Update status menjadi "read"
    public function markAsRead($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:read'
        ]);

        $restockRequest = RestockRequest::findOrFail($id);
        $restockRequest->status = $request->status;
        $restockRequest->save();

        return response()->json(['message' => 'Status diperbarui']);
    }
}
