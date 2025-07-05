@extends('employee')

@section('title', 'Pesanan ' . $statusLabel)

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Pesanan {{ $statusLabel }}</h2>
        <div class="btn-group">
          <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            Filter Status
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('orders.status', 'pending') }}">
              <span class="badge bg-warning me-2"></span> Belum Diproses
            </a></li>
            <li><a class="dropdown-item" href="{{ route('orders.status', 'expired') }}">
              <span class="badge bg-info me-2"></span> Kadaluarsa
            </a></li>
            <li><a class="dropdown-item" href="{{ route('orders.status', 'done') }}">
              <span class="badge bg-success me-2"></span> Selesai
            </a></li>
            <li><a class="dropdown-item" href="{{ route('orders.status', 'cancelled') }}">
              <span class="badge bg-danger me-2"></span> Dibatalkan
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('orders.index') }}">
              <i class="fas fa-list me-2"></i> Semua Pesanan
            </a></li>
          </ul>
        </div>
      </div>
      <p class="text-muted small">Klik pada kepala tabel untuk mengurutkan berdasarkan kolom. Klik pada pesanan untuk melihat detailnya.</p>

      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="orderTable">
          <thead class="table-light">
            <tr>
              <th onclick="sortTable(0)">ID <i class="fas fa-sort"></i></th>
              <th>Gambar</th>
              <th onclick="sortTable(2)">Produk <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(3)">Status <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(4)">Total <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(5)">Tanggal <i class="fas fa-sort"></i></th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $entry)
              @php
                $order = $entry['order'];
                $item = $entry['sample_item'];
              @endphp
              <tr onclick="window.location='{{ route('orders.showemployee', $order['id']) }}'" style="cursor: pointer;">
                <td>{{ $order['id'] }}</td>
                <td>
                  <img src="{{ $item['thumbnail'] }}" width="50" class="img-thumbnail">
                </td>
                <td>
                  {{ $item['product_name'] }}
                  @if(isset($item['variant']) && $item['variant'])
                    <br><small class="text-muted">{{ $item['variant']['name'] }}</small>
                  @endif
                </td>
                <td>
                  @if($order['status'] === 'pending')
                    <span class="badge bg-warning text-dark">Belum Diproses</span>
                  @elseif($order['status'] === 'expired')
                    <span class="badge bg-info text-white">Kadaluarsa</span>
                  @elseif($order['status'] === 'done')
                    <span class="badge bg-success text-white">Selesai</span>
                  @elseif($order['status'] === 'cancelled')
                    <span class="badge bg-danger text-white">Dibatalkan</span>
                  @else
                    <span class="badge bg-secondary">{{ ucfirst($order['status']) }}</span>
                  @endif
                </td>
                <td>Rp{{ number_format($order['total_price'], 0, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d M Y H:i') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">Tidak ada pesanan</h5>
                    <p class="text-muted small">Belum ada pesanan dengan status {{ $statusLabel }}</p>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-primary mt-2">
                      <i class="fas fa-arrow-left me-1"></i> Kembali ke Semua Pesanan
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
<!-- DataTables CSS dengan tema Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<!-- DataTables JS dengan ekstensi -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
// Fungsi sorting custom (jika masih diperlukan)
function sortTable(n) {
    const table = document.getElementById("orderTable");
    let switching = true, dir = "asc", switchcount = 0;

    while (switching) {
        switching = false;
        const rows = table.rows;

        for (let i = 1; i < (rows.length - 1); i++) {
            let shouldSwitch = false;
            const x = rows[i].getElementsByTagName("TD")[n];
            const y = rows[i + 1].getElementsByTagName("TD")[n];

            let xContent = x.innerText || x.textContent;
            let yContent = y.innerText || y.textContent;

            const xNum = parseFloat(xContent.replace(/[^\d.]/g, '')) || 0;
            const yNum = parseFloat(yContent.replace(/[^\d.]/g, '')) || 0;

            const compareResult = (!isNaN(xNum) && !isNaN(yNum)) ?
                (dir === "asc" ? xNum > yNum : xNum < yNum) :
                (dir === "asc" ? xContent.toLowerCase() > yContent.toLowerCase() : xContent.toLowerCase() < yContent.toLowerCase());

            if (compareResult) {
                shouldSwitch = true;
                break;
            }
        }

        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else if (switchcount === 0 && dir === "asc") {
            dir = "desc";
            switching = true;
        }
    }
}

// Inisialisasi DataTables dengan konfigurasi lengkap
$(document).ready(function () {
    $('#orderTable').DataTable({
        order: [[5, 'desc']], // Default sorting by date descending
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            search: "_INPUT_",
            searchPlaceholder: "Cari pesanan..."
        },
        dom: '<"row mb-3"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row mt-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: 'print',
                text: '<i class="fas fa-print me-2"></i> Print',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5]
                }
            }
        ],
        columnDefs: [
            {
                orderable: false,
                targets: [1],
                className: 'text-center' // Kolom gambar di-center
            },
            {
                targets: [4], // Kolom total
                className: 'text-end' // Rata kanan untuk nominal
            },
            {
                targets: '_all',
                className: 'align-middle' // Vertikal middle untuk semua sel
            }
        ],
        responsive: true,
        initComplete: function() {
            // Custom styling untuk search box
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-select form-select-sm');
        },
        drawCallback: function() {
            // Tooltip untuk ikon
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Hilangkan fungsi sortTable jika menggunakan DataTables sorting
    // sortTable(5); // Dapat dihapus karena sudah dihandle DataTables
});
</script>

<style>
/* Custom Styling */
#orderTable thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
}

#orderTable tbody tr {
    transition: all 0.2s ease;
}

#orderTable tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.card {
    border-radius: 0.5rem;
    overflow: hidden;
}

.dataTables_filter {
    float: right !important;
    text-align: right !important;
}

.dt-buttons {
    display: flex;
    gap: 5px;
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

@media (max-width: 768px) {
    .dataTables_wrapper .row {
        flex-direction: column;
        gap: 10px;
    }

    .dataTables_filter {
        float: none !important;
        text-align: left !important;
    }

    .dt-buttons {
        justify-content: flex-start;
    }
}
</style>
@endsection
