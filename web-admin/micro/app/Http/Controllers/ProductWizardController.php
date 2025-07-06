<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductWizardController extends Controller
{
    /**
     * Handles image upload to ImageKit.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:20480', // Max 20MB
            'product_name' => 'required|string|max:255'
        ]);

        $file = $request->file('file');
        // Generate a unique file name based on product name and timestamp
        $fileName = $this->generateImageName($request->product_name, $file->getClientOriginalExtension());

        try {
            $response = Http::withBasicAuth(config('services.imagekit.private_key'), '')
                ->attach('file', file_get_contents($file->getRealPath()), $fileName)
                ->asMultipart()
                ->post(config('services.imagekit.upload_url'), [
                    ['name' => 'fileName', 'contents' => $fileName],
                    ['name' => 'useUniqueFileName', 'contents' => 'false'], // Set to true if you want ImageKit to generate a unique name
                    ['name' => 'folder', 'contents' => '/products'], // Specify the folder in ImageKit
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return response()->json([
                    'success' => true,
                    'image_url' => $responseData['url'],
                    'thumbnail_url' => $responseData['thumbnailUrl'],
                ]);
            }

            Log::error('ImageKit Upload Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload gambar gagal.',
                'details' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Image Upload Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengunggah gambar.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generates a unique image name.
     *
     * @param string $productName
     * @param string $extension
     * @return string
     */
    protected function generateImageName(string $productName, string $extension): string
    {
        // Clean product name for URL/filename friendly string
        $cleanName = preg_replace('/[^a-zA-Z0-9-_]/', '-', strtolower($productName));
        $cleanName = substr($cleanName, 0, 50); // Limit length to 50 characters for readability
        return $cleanName . '-' . time() . '.' . $extension;
    }

    /**
     * Generates a search vector (description) for an image using Hugging Face AI.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSearchVector(Request $request)
    {
        $request->validate([
            'image_url' => [
                'required',
                'url',
                // Loosened validation: just check if it starts with ImageKit domain
                // The full domain match was causing issues with specific ImageKit subdomains/paths.
                function ($attribute, $value, $fail) {
                    if (!str_starts_with($value, 'https://ik.imagekit.io/')) {
                        $fail('URL gambar harus berasal dari ImageKit (harus dimulai dengan https://ik.imagekit.io/)');
                    }
                }
            ],
            'product_name' => 'sometimes|string|max:255'
        ]);

        try {
            $response = Http::withToken(config('services.huggingface.token'))
                ->timeout(30) // Set a timeout for the AI API request
                ->post(config('services.huggingface.endpoint'), [
                    'model' => config('services.huggingface.model'),
                    'stream' => false, // Do not stream the response
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $this->generatePrompt($request->product_name)],
                                ['type' => 'image_url', 'image_url' => ['url' => $request->image_url]],
                            ],
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $caption = $responseData['choices'][0]['message']['content'] ?? 'Tidak ada hasil.';

                return response()->json([
                    'success' => true,
                    'caption' => $caption,
                ]);
            }

            Log::error('AI Processing Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses AI.',
                'details' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('AI Processing Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses gambar.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stores product data to the Go API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProduct(Request $request)
    {
        $token = session('api_token');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        // Initialize base payload with common fields
        $payload = [
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => (int)$request->category_id, // Ensure it's an integer
            'is_varians' => (bool)$request->is_varians,   // Ensure it's a boolean
            'search_vector' => $request->search_vector,
            // Images array, even if only one image is used for AI processing
            'images' => [
                [
                    'image_url' => $request->image_url,
                    'thumbnail_url' => $request->thumbnail_url,
                ]
            ],
            'variants' => [], // Initialize as an empty array, will be populated if is_varians is true
        ];

        // Handle product pricing and stock based on whether it has variants
        if (!(bool)$request->is_varians) {
            // Product without variants: price, discount_price, stock are at the top level
            $payload['price'] = (int)$request->price;
            // Set discount_price to null if it's empty or not provided
            $payload['discount_price'] = isset($request->discount_price) && $request->discount_price !== '' ? (int)$request->discount_price : null;
            $payload['stock'] = (int)$request->stock;
            // Determine if the product is discounted
            $payload['is_discounted'] = ($payload['discount_price'] !== null && $payload['discount_price'] < $payload['price']);
        } else {
            // Product with variants: price, discount_price, stock at top level are null/0 (depending on Go model)
            // Go's struct uses *int for price/discount_price if nullable, but int for stock.
            // Assuming for top-level product with variants, these can be null/0.
            $payload['price'] = null;
            $payload['discount_price'] = null;
            $payload['stock'] = null; // Or 0 if Go expects non-null int
            $payload['is_discounted'] = false; // Main product not discounted if it has variants

            // Process variants
            $variantsPayload = [];
            foreach ($request->variants ?? [] as $variant) {
                $variantsPayload[] = [
                    'name' => $variant['name'],
                    'price' => (int)$variant['price'], // Ensure int, defaults to 0 if invalid
                    'discount_price' => isset($variant['discount_price']) && $variant['discount_price'] !== '' ? (int)$variant['discount_price'] : null, // Handle nullable
                    'stock' => (int)$variant['stock'], // Ensure int, defaults to 0 if invalid
                ];
            }
            $payload['variants'] = $variantsPayload;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10) // Set a timeout for the Go API request
                // Ensure no double slash: config('services.golang_api.url') should not end with '/'
                // and '/products' starts with '/'.
                ->post(rtrim(config('services.golang_api.url'), '/') . '/products', $payload);

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }

            // Log detailed error from Go API
            Log::error('Failed to store product to Go API', [
                'status' => $response->status(),
                'response_body' => $response->body(),
                'payload_sent' => $payload // Log the payload sent for debugging
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan ke API.',
                'details' => $response->body(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Product Store Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan produk.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generates AI prompt based on product name.
     *
     * @param string|null $productName
     * @return string
     */
    protected function generatePrompt(?string $productName): string
    {
        $basePrompt = 'Buatkan deskripsi singkat produk ini dalam bahasa Indonesia untuk keperluan pencarian.';

        if ($productName) {
            return "Produk ini bernama $productName. $basePrompt";
        }

        return $basePrompt;
    }
}
