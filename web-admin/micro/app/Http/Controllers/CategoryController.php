<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.golang_api.url'), '/');
    }

    public function index()
    {
        $response = Http::timeout(10)->get("{$this->baseUrl}/categories");

        $categories = $response->successful() ? $response->json()['data'] : [];

        return view('categories.index', compact('categories'));
    }

    public function show($id)
    {
        // Ambil semua kategori
        $categoryRes = Http::timeout(10)->get("{$this->baseUrl}/categories");
        $categoryData = $categoryRes->successful() ? $categoryRes->json()['data'] : [];

        // Temukan kategori berdasarkan ID
        $category = collect($categoryData)->firstWhere('id', (int)$id);
        if (!$category) {
            abort(404, 'Kategori tidak ditemukan');
        }

        // Ambil produk berdasarkan kategori ID
        $productRes = Http::timeout(10)->get("{$this->baseUrl}/products/$id");
        $products = $productRes->successful() ? $productRes->json()['data'] : [];

        return view('categories.show', compact('category', 'products'));
    }
}

// namespace App\Http\Controllers;

// use Illuminate\Support\Facades\Http;

// class CategoryController extends Controller
// {
//     public function index()
//     {
//         $response = Http::timeout(10)->get(env('GOLANG_API_URL') . 'categories');

//         if ($response->successful()) {
//             $categories = $response->json()['data'];
//         } else {
//             $categories = [];
//         }

//         return view('categories.index', compact('categories'));
//     }


//     public function show($id)
//     {
//         // Ambil semua kategori, cari yang sesuai ID
//         $categoryRes = Http::timeout(10)->get(env('GOLANG_API_URL') . 'categories');
//         $category = collect($categoryRes->json()['data'])->firstWhere('id', (int)$id);

//         // Ambil produk dalam kategori tersebut
//         $productRes = Http::timeout(10)->get(env('GOLANG_API_URL') . "products/$id");

//         if ($productRes->successful()) {
//             $products = $productRes->json()['data'];
//         } else {
//             $products = [];
//         }

//         return view('categories.show', compact('category', 'products'));
//     }

// }
