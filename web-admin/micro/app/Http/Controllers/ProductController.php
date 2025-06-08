<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index()
    {
        $response = Http::timeout(10)->get('http://localhost:8081/api/v1/products');

        if ($response->successful()) {
            $products = $response->json()['data'];
        } else {
            $products = [];
        }

        return view('products.index', compact('products'));
    }
    public function index1()
    {
        $response = Http::timeout(10)->get('https://uts-ppt-digiberkat-production.up.railway.app/api/v1/products');

        if ($response->successful()) {
            $products = $response->json()['data'];
        } else {
            $products = [];
        }

        return view('products.index1', compact('products'));
    }
}


