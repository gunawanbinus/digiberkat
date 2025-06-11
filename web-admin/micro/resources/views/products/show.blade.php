@extends('admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>{{ $data['name'] }}</h2>
            <a href="{{ route('products.create') }}" class="btn btn-primary">Edit</a>
        </div>

    <div class="row mb-4">
        <div class="col-md-6 text-center">
            <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                @if (count($data['images']) > 1)
                    <button onclick="showPrevImage()" class="btn btn-light me-2" style="font-size: 2rem;">&lt;</button>
                @endif

                <img id="product-image"
                     src="{{ $data['images'][0] }}"
                     alt="{{ $data['name'] }}"
                     class="rounded shadow"
                     style="width: 400px; height: 400px; object-fit: contain; border: 1px solid #ddd;">

                @if (count($data['images']) > 1)
                    <button onclick="showNextImage()" class="btn btn-light ms-2" style="font-size: 2rem;">&gt;</button>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <p><strong>Kategori:</strong> {{ $data['category_id'] }}</p>
            <p><strong>Deskripsi:</strong> {{ $data['description'] }}</p>

            @if (!$data['is_varians'])
                @if ($data['is_discounted'])
                    <p>
                        <del class="text-danger">Rp{{ number_format($data['price']) }}</del>
                        <strong class="text-success">Rp{{ number_format($data['discount_price']) }}</strong>
                    </p>
                @else
                    <p><strong>Harga:</strong> Rp{{ number_format($data['price']) }}</p>
                @endif
                <p><strong>Stok:</strong> {{ $data['stock'] }}</p>
            @else
                <h5>Varian Produk:</h5>
                <ul class="list-group">
                    @foreach ($data['variants'] as $variant)
                        <li class="list-group-item">
                            <strong>{{ $variant['name'] }}</strong><br>
                            @if ($variant['is_discounted'] && $variant['discount_price'])
                                <del class="text-danger">Rp{{ number_format($variant['price']) }}</del>
                                <span class="text-success">Rp{{ number_format($variant['discount_price']) }}</span>
                            @else
                                Harga: Rp{{ number_format($variant['price']) }}
                            @endif
                            <br>
                            Stok: {{ $variant['stock'] > 0 ? $variant['stock'] : 'Habis' }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<script>
    const images = @json($data['images']);
    let currentImageIndex = 0;

    function showNextImage() {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        updateImage();
    }

    function showPrevImage() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        updateImage();
    }

    function updateImage() {
        document.getElementById('product-image').src = images[currentImageIndex];
    }
</script>
@endsection
