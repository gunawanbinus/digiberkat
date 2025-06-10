<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    public function index()
    {
        $response = Http::timeout(10)->get(env('GOLANG_API_URL') . 'categories');

        if ($response->successful()) {
            $categories = $response->json()['data'];
        } else {
            $categories = [];
        }

        return view('categories.index', compact('categories'));
    }


    public function show($id)
    {
        // Ambil semua kategori, cari yang sesuai ID
        $categoryRes = Http::timeout(10)->get(env('GOLANG_API_URL') . 'categories');
        $category = collect($categoryRes->json()['data'])->firstWhere('id', (int)$id);

        // Ambil produk dalam kategori tersebut
        $productRes = Http::timeout(10)->get(env('GOLANG_API_URL') . "products/$id");

        if ($productRes->successful()) {
            $products = $productRes->json()['data'];
        } else {
            $products = [];
        }

        return view('categories.show', compact('category', 'products'));
    }

}
