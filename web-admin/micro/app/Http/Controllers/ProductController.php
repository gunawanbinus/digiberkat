<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index()
    {
        $response = Http::timeout(10)->get(env('GOLANG_API_URL') . 'products');

        if ($response->successful()) {
            $products = $response->json()['data'];
        } else {
            $products = [];
        }

        return view('products.index', compact('products'));
    }
}


