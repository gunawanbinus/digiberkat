@extends('admin')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes me-2"></i>Permintaan Restok</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menandai permintaan restok ini sebagai sudah dibaca?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="confirmationForm" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Ya, Tandai Dibaca</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="restockTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Produk</th>
                            <th class="text-start">Varian</th>
                            <th class="text-center">Stok Saat Ini</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($restockRequests as $item)
                            <tr>
                                <td class="text-start">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $item['thumbnail'] }}" width="40" height="40" class="me-3 rounded"
                                             onerror="this.src='{{ asset('images/default-product.png') }}'">
                                        <span>{{ $item['product_name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-start">
                                    @if (!empty($item['variant_name']))
                                        {{ $item['variant_name'] }}
                                    @else
                                        <span class="badge bg-secondary text-white rounded-pill px-3">Tanpa Varian</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge rounded-pill px-3 py-2 bg-{{ $item['stock'] > 10 ? 'success' : ($item['stock'] > 0 ? 'warning' : 'danger') }}">
                                        {{ $item['stock'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-success mark-as-read"
                                            data-url="{{ url('/restock-requests/' . $item['id'] . '/read') }}">
                                        <i class="fas fa-check-circle me-1"></i> Tandai Dibaca
                                    </button>
                                    <a href="/products/{{ $item['product_id'] }}" class="btn btn-sm btn-primary ms-1">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted mb-2">Tidak ada permintaan restok</h5>
                                        <p class="text-muted small">Belum ada permintaan restok saat ini</p>
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

<!-- DataTables & Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<!-- FontAwesome untuk ikon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DataTables & Buttons JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#restockTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row mt-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-2"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function (data, row, column, node) {
                            const $node = $(node);

                            // Kolom 0: Produk -> ambil teks dari <span>
                            if (column === 0) {
                                return $node.find('span').text().trim();
                            }

                            // Kolom 1: Varian -> '-' jika tidak ada varian
                            if (column === 1) {
                                if (data.includes('Tanpa Varian') || $node.find('.badge').length > 0) {
                                    return '-';
                                }
                                return data.trim();
                            }

                            // Kolom 2: Stok -> ambil isi dari badge saja
                            if (column === 2) {
                                return $node.text().trim();
                            }

                            return data;
                        }
                    }
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print me-2"></i> Print',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 1 && (data.includes('Tanpa Varian') || $(node).find('.badge').length > 0)) {
                                return '-';
                            }
                            return data;
                        }
                    }
                },
                customize: function(win) {
                    $(win.document.body).find('table').addClass('table-bordered');
                    $(win.document.body).find('h1').css('text-align','center');
                }
            }
        ],
        columnDefs: [
            {
                targets: [2, 3],
                className: 'text-center'
            },
            {
                targets: [0, 1],
                className: 'text-start'
            },
            {
                targets: [3],
                orderable: false,
                searchable: false
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            search: "_INPUT_",
            searchPlaceholder: "Cari produk..."
        },
        responsive: true,
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-select form-select-sm');
        }
    });

    // Confirmation modal handler
    $('.mark-as-read').click(function() {
        var url = $(this).data('url');
        $('#confirmationForm').attr('action', url);
        $('#confirmationModal').modal('show');
    });
});
</script>

<style>
/* Custom Styling */
#restockTable thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
}

#restockTable tbody tr {
    transition: all 0.2s ease;
}

#restockTable tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.card {
    border-radius: 0.5rem;
    overflow: hidden;
}

.badge {
    padding: 0.35em 0.65em;
    font-weight: 600;
    min-width: 40px;
}

.dt-buttons {
    display: flex;
    gap: 5px;
}

@media (max-width: 768px) {
    .dataTables_wrapper .row {
        flex-direction: column;
        gap: 10px;
    }

    .dt-buttons {
        /* display: flex;
        flex-wrap: wrap;
        gap: 5px; */
        justify-content: flex-start;
    }

    .mark-as-read, .btn-primary {
        margin-bottom: 5px;
    }
}
</style>
@endsection
