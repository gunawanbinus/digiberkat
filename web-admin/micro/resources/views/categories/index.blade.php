@extends('admin')

@section('title', 'Kategori Produk')

@section('content')
<div class="container-fluid py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0 fw-bold">Kategori Produk</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-2"></i>Tambah Kategori
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            @forelse ($categories as $category)
                <div class="category-item border-bottom">
                    <a href="{{ route('categories.show', $category['id']) }}" class="d-flex justify-content-between align-items-center p-4 text-decoration-none text-dark hover-bg">
                        <div class="d-flex align-items-center">
                            <div class="category-icon bg-light-primary rounded-3 p-3 me-3">
                                <i class="bi bi-tag text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-semibold">{{ $category['name'] }}</h5>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-folder-x fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">Tidak ada kategori ditemukan</p>
                    <button class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Kategori
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Add Category Modal (placeholder - you'll need to implement this) -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="addCategoryModalLabel">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control rounded-3" id="categoryName" placeholder="Masukkan nama kategori">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .category-item:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .category-icon {
        transition: all 0.2s ease;
    }

    .category-item:hover .category-icon {
        background-color: #e9f5ff !important;
        transform: scale(1.05);
    }

    .card {
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.05);
    }
</style>
@endsection
