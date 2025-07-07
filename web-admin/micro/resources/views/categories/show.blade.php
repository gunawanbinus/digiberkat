@extends('admin')

@section('title', 'Kategori ' . ($category['name'] ?? 'Kategori Tidak Ditemukan'))

@section('content')
<div class="container py-4">
    {{-- Menampilkan pesan alert --}}
    @if (Session::has('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ Session::get('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (Session::has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            {{ Session::get('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><i class="fas fa-boxes me-2"></i>{{ $category['name'] ?? 'Kategori Tidak Ditemukan' }}</h2>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-edit me-2"></i>Edit Kategori
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-trash me-2"></i>Hapus Kategori
            </button>
        </div>
    </div>

    @isset($category['description'])
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted">{{ $category['description'] }}</p>
    </div>
    @endisset

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">ID Produk</th>
                            <th class="text-center">ID Varian</th>
                            <th class="text-center">Gambar</th>
                            <th>Nama Produk</th>
                            <th>Varian</th>
                            <th class="text-end">Harga Normal</th>
                            <th class="text-end">Harga Diskon</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @if($product['is_varians'] && !empty($product['variants']))
                                @foreach($product['variants'] as $variant)
                                    <tr>
                                        <td class="text-center">{{ $product['id'] }}</td>
                                        <td class="text-center">{{ $variant['id'] }}</td>
                                        <td class="text-center">
                                            <img src="{{ $product['thumbnails'][0] ?? asset('images/default-product.png') }}"
                                                 class="img-thumbnail rounded" width="50"
                                                 alt="{{ $product['name'] }}"
                                                 onerror="this.src='{{ asset('images/default-product.png') }}'">
                                        </td>
                                        <td>{{ $product['name'] }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $variant['name'] }}</span>
                                        </td>
                                        <td class="text-end" data-order="{{ $variant['price'] }}">
                                            Rp{{ number_format($variant['price'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end" data-order="{{ $variant['discount_price'] ?? 0 }}">
                                            @if($variant['is_discounted'] && $variant['discount_price'])
                                                <span class="text-danger fw-bold">Rp{{ number_format($variant['discount_price'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ $variant['stock'] }}">
                                            @if($variant['stock'] > 0)
                                                <span>{{ $variant['stock'] }}</span>
                                            @else
                                                <span class="badge bg-danger">Habis</span>
                                            @endif
                                        </td>
                                        <td class="d-flex align-items-center gap-2">
                                            <a href="/products/{{ $product['id'] }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/products/{{ $product['id'] }}?variant={{ $variant['id'] }}"
                                                 class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/products/{{ $product['id'] }}" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="text-center">{{ $product['id'] }}</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center">
                                        <img src="{{ $product['thumbnails'][0] ?? asset('images/default-product.png') }}"
                                             class="img-thumbnail rounded" width="50"
                                             alt="{{ $product['name'] }}"
                                             onerror="this.src='{{ asset('images/default-product.png') }}'">
                                    </td>
                                    <td>{{ $product['name'] }}</td>
                                    <td><span class="badge bg-secondary">-</span></td>
                                    <td class="text-end" data-order="{{ $product['price'] }}">
                                        Rp{{ number_format($product['price'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" data-order="{{ $product['discount_price'] ?? 0 }}">
                                        @if($product['is_discounted'] && $product['discount_price'])
                                            <span class="text-danger fw-bold">Rp{{ number_format($product['discount_price'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $product['stock'] }}">
                                        @if($product['stock'] > 0)
                                            <span>{{ $product['stock'] }}</span>
                                        @else
                                            <span class="badge bg-danger">Habis</span>
                                        @endif
                                    </td>
                                    <td class="d-flex align-items-center gap-2">
                                        <a href="/products/{{ $product['id'] }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/products/{{ $product['id'] }}"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/products/{{ $product['id'] }}" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            {{-- Add a unique class to the empty row --}}
                            <tr class="dataTables_empty_row">
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted mb-2">Tidak ada produk</h5>
                                        <p class="text-muted small">Belum ada produk yang terdaftar</p>
                                        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-plus me-1"></i> Tambah Produk Pertama
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Check if the empty row exists
    const isEmptyTable = $('#productTable tbody').find('.dataTables_empty_row').length > 0;

    if (!isEmptyTable) {
        // Initialize DataTables if the table is NOT empty
        $('#productTable').DataTable({
            order: [[6, 'asc']], // Default sort by stock
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                search: "_INPUT_",
                searchPlaceholder: "Cari produk...",
                buttons: {
                    excel: 'Excel',
                    print: 'Print'
                }
            },
            dom: '<"row mb-3"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row mt-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel me-2"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: [0, 2, 3, 4, 5],
                        format: {
                            body: function(data, row, column, node) {
                                const $node = $(node);
                                const rawHtml = $node.html() || '';
                                const cleanText = rawHtml.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
                                if (column === 0) {
                                    return cleanText.replace(/^#/, '');
                                }
                                if (column === 3 || column === 4) {
                                    const numeric = cleanText.replace(/[^\d]/g, '');
                                    return numeric ? parseInt(numeric) : '-';
                                }
                                if (column === 2 && (cleanText === '-' || cleanText === '')) {
                                    return '-';
                                }
                                return cleanText;
                            }
                        }
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print me-2"></i> Print',
                    className: 'btn btn-info btn-sm',
                    exportOptions: {
                        columns: [0, 2, 3, 4, 5]
                    },
                    customize: function(win) {
                        $(win.document.body).find('table').addClass('table-bordered');
                        $(win.document.body).find('h1').css('text-align','center');
                    }
                },
            ],
            columnDefs: [
                {
                    orderable: true,
                    targets: [0, 2, 3, 4, 5, 6]
                },
                {
                    orderable: false,
                    targets: [1]
                },
                {
                    className: 'text-center',
                    targets: [0, 1, 6]
                },
                {
                    className: 'text-end',
                    targets: [4, 5]
                },
                {
                    type: 'num',
                    targets: [4, 5],
                    render: function(data, type, row) {
                        if (type === 'sort') {
                            return data.replace('Rp', '').replace(/\./g, '');
                        }
                        return data;
                    }
                },
                {
                    className: 'align-middle',
                    targets: '_all'
                }
            ],
            responsive: true,
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-select form-select-sm');
            }
        });
    } else {
        // If table is empty, hide DataTables controls
        // This targets the wrapper div created by DataTables
        $('.dataTables_wrapper').find('.row').hide();
    }
});
</script>

<style>
/* Custom Styling */
#productTable thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
    vertical-align: middle;
}

#productTable tbody tr {
    transition: all 0.2s ease;
}

#productTable tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.card {
    border-radius: 0.5rem;
    overflow: hidden;
}

.img-thumbnail {
    border-radius: 0.375rem;
    padding: 0.25rem;
    background-color: #fff;
    border: 1px solid #dee2e6;
    max-width: 100%;
    height: auto;
}

.badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 600;
    letter-spacing: 0.05em;
}

/* Ensure DataTables controls are hidden when the table is explicitly empty */
.dataTables_wrapper .row {
    margin-left: 0;
    margin-right: 0;
}

.dataTables_wrapper .dataTables_filter {
    text-align: right;
}

.dataTables_wrapper .dataTables_length {
    text-align: left;
}

.dt-buttons {
    display: flex;
    gap: 5px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .dataTables_wrapper .row {
        flex-direction: column;
        gap: 10px;
    }

    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
        text-align: left;
    }

    .dt-buttons {
        justify-content: flex-start;
    }
}
</style>
@endsection
