@extends('admin')

@section('title', 'Kategori Produk')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Kategori Produk</h2>

    <div class="list-group">
        @forelse ($categories as $category)
            <a href="{{ route('categories.show', $category['id']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span>{{ $category['name'] }}</span>
                <i class="bi bi-chevron-right"></i> {{-- Bootstrap Icons --}}
            </a>
        @empty
            <div class="list-group-item">Tidak ada kategori ditemukan.</div>
        @endforelse
    </div>
</div>
@endsection
