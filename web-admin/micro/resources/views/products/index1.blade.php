<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Produk</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Daftar Produk</h1>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        @php
            $availableProducts = collect($products)->filter(function ($p) {
                return $p['stock'] > 0;
            });
        @endphp

        @forelse ($availableProducts as $product)
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product['name'] }}</h5>
                        <p class="card-text">{{ $product['description'] }}</p>

                        @if ($product['is_discounted'] && $product['discount_price'])
                            <p>
                                <span class="text-danger fw-bold">Rp {{ number_format($product['discount_price'], 0, ',', '.') }}</span><br>
                                <small class="text-muted text-decoration-line-through">Rp {{ number_format($product['price'], 0, ',', '.') }}</small>
                            </p>
                        @else
                            <p class="fw-bold">Rp {{ number_format($product['price'], 0, ',', '.') }}</p>
                        @endif

                        <p class="text-muted">Stok: {{ $product['stock'] }}</p>

                        <a href="#" class="btn btn-primary w-100">Tambahkan ke Keranjang</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning text-center">Semua produk sedang habis.</div>
            </div>
        @endforelse
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
