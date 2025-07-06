<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim(config('services.golang_api.url'), '/');
        $response = Http::timeout(10)->get("$baseUrl/products");

        $products = $response->successful() ? $response->json()['data'] : [];

        return view('products.index', compact('products'));
    }

    public function show($id)
    {
        $baseUrl = rtrim(config('services.golang_api.url'), '/');
        $response = Http::timeout(10)->get("$baseUrl/products/id/$id");

        if ($response->failed()) {
            abort(404, 'Produk tidak ditemukan');
        }

        $data = $response->json('data');
        return view('products.show', compact('data'));
    }

    public function create()
    {
        $baseUrl = rtrim(config('services.golang_api.url'), '/');
        $response = Http::timeout(10)->get("$baseUrl/categories");

        $categories = $response->successful() ? $response->json()['data'] : [];
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string',
            'description'   => 'required|string',
            'category_id'   => 'required|integer',
            'images.*'      => 'required|image',
        ]);

        // Upload gambar ke ImageKit
        $imageUrls = [];
        foreach ($request->file('images') as $image) {
            $imgPath = $image->getPathname();
            $fileName = $image->getClientOriginalName();

            $res = Http::withBasicAuth(env('IMAGEKIT_PRIVATE_KEY'), '')
                ->attach('file', file_get_contents($imgPath), $fileName)
                ->post('https://upload.imagekit.io/api/v1/files/upload', [
                    'fileName' => $fileName,
                    'folder' => '/products'
                ]);

            if (!$res->successful()) {
                return back()->with('error', 'Upload gambar ke ImageKit gagal.');
            }

            $imageUrls[] = $res['url'];
        }

        // Susun data produk
        $payload = [
            'name'        => $request->name,
            'description' => $request->description,
            'category_id' => (int) $request->category_id,
            'is_varians'  => $request->has('is_varians'),
            'images'      => $imageUrls,
        ];

        if (!$payload['is_varians']) {
            $payload['price']          = (int) $request->price;
            $payload['discount_price'] = $request->discount_price ? (int) $request->discount_price : null;
            $payload['is_discounted']  = $request->filled('discount_price');
            $payload['stock']          = (int) $request->stock;
        } else {
            $payload['variants'] = array_map(function ($v) {
                return [
                    'name'           => $v['name'],
                    'price'          => (int) $v['price'],
                    'discount_price' => isset($v['discount_price']) && $v['discount_price'] !== null ? (int) $v['discount_price'] : null,
                    'stock'          => (int) $v['stock']
                ];
            }, $request->input('variants', []));
        }

        // Kirim ke API Golang
        $baseUrl = rtrim(config('services.golang_api.url'), '/');
        $goResponse = Http::post("$baseUrl/products", $payload);

        if (!$goResponse->successful()) {
            return back()->with('error', 'Gagal menyimpan produk ke sistem utama.');
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan');
    }
}

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;
// use App\Models\Category;

// class ProductController extends Controller
// {
//     public function index()
//     {
//         $response = Http::timeout(10)->get(config('services.golang_api.url') . 'products');

//         if ($response->successful()) {
//             $products = $response->json()['data'];
//         } else {
//             $products = [];
//         }

//         return view('products.index', compact('products'));
//     }

//     public function show($id)
//     {
//         $response = Http::get(env('GOLANG_API_URL') . "products/id/$id");

//         if ($response->failed()) {
//             abort(404, 'Produk tidak ditemukan');
//         }

//         $data = $response->json('data');

//         return view('products.show', compact('data'));
//     }

//     public function store(Request $request)
//     {
//         $request->validate([
//             'name'          => 'required|string',
//             'description'   => 'required|string',
//             'category_id'   => 'required|integer',
//             'images.*'      => 'required|image',
//         ]);

//         // Upload gambar ke ImageKit
//         $imageUrls = [];
//         foreach ($request->file('images') as $image) {
//             $imgPath = $image->getPathname();
//             $fileName = $image->getClientOriginalName();

//             $res = Http::withBasicAuth(env('IMAGEKIT_PRIVATE_KEY'), '')
//                 ->attach('file', file_get_contents($imgPath), $fileName)
//                 ->post('https://upload.imagekit.io/api/v1/files/upload', [
//                     'fileName' => $fileName,
//                     'folder' => '/products'
//                 ]);

//             if (!$res->successful()) {
//                 return back()->with('error', 'Upload gambar ke ImageKit gagal.');
//             }

//             $imageUrls[] = $res['url'];
//         }

//         // Susun data produk untuk dikirim ke API Go
//         $payload = [
//             'name'          => $request->name,
//             'description'   => $request->description,
//             'category_id'   => (int) $request->category_id,
//             'is_varians'    => $request->has('is_varians'),
//             'images'        => $imageUrls,
//         ];

//         if (!$payload['is_varians']) {
//             $payload['price']          = (int) $request->price;
//             $payload['discount_price'] = $request->discount_price ? (int) $request->discount_price : null;
//             $payload['is_discounted']  = $request->filled('discount_price');
//             $payload['stock']          = (int) $request->stock;
//         } else {
//             $payload['variants'] = array_map(function ($v) {
//                 return [
//                     'name'          => $v['name'],
//                     'price'         => (int) $v['price'],
//                     'discount_price'=> isset($v['discount_price']) && $v['discount_price'] !== null ? (int) $v['discount_price'] : null,
//                     'stock'         => (int) $v['stock']
//                 ];
//             }, $request->input('variants', []));
//         }

//         // Kirim ke API Go
//         $goResponse = Http::post(env('GOLANG_API_URL') . 'products', $payload);

//         if (!$goResponse->successful()) {
//             return back()->with('error', 'Gagal menyimpan produk ke sistem utama.');
//         }

//         return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan');
//     }

//     public function create()
//     {
//         $response = Http::timeout(10)->get(env('GOLANG_API_URL') . 'categories');

//         if ($response->successful()) {
//             $categories = $response->json()['data'];
//         } else {
//             $categories = [];
//         }

//         return view('products.create', compact('categories'));
//     }
// }
