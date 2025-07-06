@extends('admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="mb-0 fw-bold">{{ $data['name'] }}</h2>
                    <div>
                        <a href="{{ route('products.create') }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> Edit
                        </a>
                        <a href="{{ route('products.create') }}" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i> Hapus
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <!-- Product Images Section -->
                        <div class="col-lg-6">
                            <div class="position-relative">
                                <div class="swiper product-image-slider rounded-3 overflow-hidden shadow-sm">
                                    <div class="swiper-wrapper">
                                        @foreach($data['images'] as $image)
                                        <div class="swiper-slide">
                                            <img src="{{ $image }}"
                                                 alt="{{ $data['name'] }}"
                                                 class="img-fluid w-100"
                                                 style="height: 500px; object-fit: contain; background-color: #f8f9fa;">
                                        </div>
                                        @endforeach
                                    </div>
                                    @if(count($data['images']) > 1)
                                    <div class="swiper-button-next"></div>
                                    <div class="swiper-button-prev"></div>
                                    <div class="swiper-pagination"></div>
                                    @endif
                                </div>

                                @if(count($data['images']) > 1)
                                <div class="swiper product-thumb-slider mt-3">
                                    <div class="swiper-wrapper">
                                        @foreach($data['images'] as $image)
                                        <div class="swiper-slide">
                                            <img src="{{ $image }}"
                                                 alt="{{ $data['name'] }}"
                                                 class="img-fluid rounded-2"
                                                 style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent;">
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Product Details Section -->
                        <div class="col-lg-6">
                            <div class="product-details">
                                <div class="d-flex align-items-center mb-4">
                                    <!--<span class="badge bg-primary rounded-pill px-3 py-2 me-3">
                                        Kategori: {{ $data['category_id'] }}
                                    </span>-->
                                    <span class="badge {{ $data['is_varians'] ? 'bg-info' : ($data['stock'] > 0 ? 'bg-success' : 'bg-danger') }} rounded-pill px-3 py-2">
                                        {{ $data['is_varians'] ? 'Multi Varian' : ($data['stock'] > 0 ? 'Stok: '.$data['stock'] : 'Stok Habis') }}
                                    </span>
                                </div>

                                <div class="mb-4">
                                    <h5 class="text-muted mb-3">Deskripsi Produk</h5>
                                    <p class="lead">{{ $data['description'] }}</p>
                                </div>

                                @if(!$data['is_varians'])
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            @if($data['is_discounted'])
                                            <div class="me-4">
                                                <span class="text-danger fw-bold fs-4">Rp{{ number_format($data['discount_price']) }}</span>
                                                <span class="text-muted text-decoration-line-through ms-2">Rp{{ number_format($data['price']) }}</span>
                                                <span class="badge bg-danger ms-2">Diskon</span>
                                            </div>
                                            @else
                                            <div>
                                                <span class="text-dark fw-bold fs-4">Rp{{ number_format($data['price']) }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Varian Produk</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama Varian</th>
                                                        <th class="text-end">Harga</th>
                                                        <th class="text-center">Stok</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data['variants'] as $variant)
                                                    <tr>
                                                        <td>{{ $variant['name'] }}</td>
                                                        <td class="text-end">
                                                            @if($variant['is_discounted'] && $variant['discount_price'])
                                                            <span class="text-danger fw-bold">Rp{{ number_format($variant['discount_price']) }}</span>
                                                            <span class="text-muted text-decoration-line-through ms-2">Rp{{ number_format($variant['price']) }}</span>
                                                            @else
                                                            <span class="text-dark">Rp{{ number_format($variant['price']) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge rounded-pill {{ $variant['stock'] > 0 ? 'bg-success' : 'bg-danger' }}">
                                                                {{ $variant['stock'] > 0 ? $variant['stock'] : 'Habis' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Swiper JS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

<style>
    .product-image-slider {
        border: 1px solid #eee;
    }
    .product-thumb-slider .swiper-slide-thumb-active {
        border-color: #0d6efd !important;
    }
    .swiper-button-next, .swiper-button-prev {
        color: #0d6efd;
        background: rgba(255,255,255,0.8);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .swiper-button-next:after, .swiper-button-prev:after {
        font-size: 1.2rem;
    }
    .product-details .lead {
        color: #495057;
        line-height: 1.8;
    }
    .card {
        border-radius: 12px;
    }
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
</style>

<script>
    // Initialize Swiper
    document.addEventListener('DOMContentLoaded', function() {
        const imageSlider = new Swiper('.product-image-slider', {
            spaceBetween: 10,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            thumbs: {
                swiper: {
                    el: '.product-thumb-slider',
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
            },
        });
    });
</script>
@endsection
