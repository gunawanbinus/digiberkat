@extends('admin')

@section('title', 'Semua Pesanan')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
          <h2><i class="fas fa-clipboard-list me-2"></i>Semua Pesanan</h2>
          <div class="ms-3">
            <select id="statusFilter" class="form-select form-select-sm">
              <option value="">Semua Status</option>
              <option value="Belum Diproses">Belum Diproses</option>
              <option value="Selesai">Selesai</option>
              <option value="Kadaluarsa">Kadaluarsa</option>
              <option value="Dibatalkan">Dibatalkan</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="orderTable">
              <thead class="table-light">
                <tr>
                  <th class="text-center">ID</th>
                  <th class="text-center">Gambar</th>
                  <th>Produk</th>
                  <th>Status</th>
                  <th class="text-end">Total</th>
                  <th class="text-center">Tanggal</th>
                  <th class="text-center">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $entry)
                  @php
                    $order = $entry['order'];
                    $item = $entry['sample_item'];
                    $variant = $item['variant'] ?? null;
                  @endphp
                  <tr>
                    <td class="text-center fw-bold">#{{ $order['id'] }}</td>
                    <td class="text-center">
                      <img src="{{ $item['thumbnail'] }}" class="img-thumbnail rounded" width="50" alt="{{ $item['product_name'] }}">
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $item['product_name'] }}</div>
                      @if($variant)
                        <small class="text-muted">{{ $variant['name'] }}</small>
                      @endif
                    </td>
                    <td>
                      @switch($order['status'])
                        @case('pending')
                          <span class="badge bg-warning text-dark">Belum Diproses</span>
                          @break
                        @case('done')
                          <span class="badge bg-success text-white">Selesai</span>
                          @break
                        @case('expired')
                          <span class="badge bg-secondary text-white">Kadaluarsa</span>
                          @break
                        @case('cancelled')
                          <span class="badge bg-danger text-white">Dibatalkan</span>
                          @break
                        @default
                          <span class="badge bg-secondary">{{ ucfirst($order['status']) }}</span>
                      @endswitch
                    </td>
                    <td class="text-end fw-bold" data-order="{{ $order['total_price'] }}">
                      Rp{{ number_format($order['total_price'], 0, ',', '.') }}
                    </td>
                    <td class="text-center" data-order="{{ strtotime($order['created_at']) }}">
                      <div>{{ \Carbon\Carbon::parse($order['created_at'])->translatedFormat('d M Y') }}</div>
                      <small class="text-muted">{{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}</small>
                    </td>
                    <td class="text-center">
                      <a href="/orders/{{ $order['id'] }}" class="btn btn-sm btn-outline-primary" title="Detail">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- DataTables Resources -->
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
    const table = $('#orderTable').DataTable({
        order: [[5, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            search: "_INPUT_",
            searchPlaceholder: "Cari pesanan...",
            buttons: {
                excel: 'Excel',
                print: 'Print'
            }
        },
        dom: '<"row mb-3"<"col-md-4"l><"col-md-4"B><"col-md-4"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-2"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5],
                    format: {
                        body: function(data, row, column, node) {
                            const html = $(node).html() || '';
                            const clean = html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
                            if (column === 0) return clean.replace(/^#/, '');
                            if (column === 4) return clean.replace(/[^\d]/g, '');
                            if (column === 5) {
                                const div = document.createElement('div');
                                div.innerHTML = html;
                                const date = div.querySelector('div')?.textContent?.trim() || '';
                                const time = div.querySelector('small')?.textContent?.trim() || '';
                                return `${date} ${time}`;
                            }
                            return clean;
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
            }
        ],
        columnDefs: [
            { orderable: true, targets: [0, 2, 3, 4, 5] },
            { orderable: false, targets: [1, 6] },
            { className: 'text-center', targets: [0, 1, 5, 6] },
            { className: 'text-end', targets: [4] },
            { className: 'align-middle', targets: '_all' }
        ],
        responsive: true,
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons button').removeClass('btn-secondary').addClass('btn-sm');

            $('#statusFilter').on('change', function () {
                const value = $(this).val();
                table.column(3).search(value).draw();
            });
        }
    });
});
</script>

<style>
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


{{-- @extends('admin')

@section('title', 'Semua Pesanan')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-clipboard-list me-2"></i>Semua Pesanan</h2>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="orderTable">
              <thead class="table-light">
                <tr>
                  <th class="text-center">ID</th>
                  <th class="text-center">Gambar</th>
                  <th>Produk</th>
                  <th>Status</th>
                  <th class="text-end">Total</th>
                  <th class="text-center">Tanggal</th>
                  <th class="text-center">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $entry)
                  @php
                    $order = $entry['order'];
                    $item = $entry['sample_item'];
                    $variant = $item['variant'] ?? null;
                  @endphp
                  <tr>
                    <td class="text-center fw-bold">#{{ $order['id'] }}</td>
                    <td class="text-center">
                      <img src="{{ $item['thumbnail'] }}" class="img-thumbnail rounded" width="50" alt="{{ $item['product_name'] }}">
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $item['product_name'] }}</div>
                      @if($variant)
                        <small class="text-muted">{{ $variant['name'] }}</small>
                      @endif
                    </td>
                    <td>
                      @switch($order['status'])
                        @case('pending')
                          <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i> Belum Diproses
                          </span>
                          @break
                        @case('done')
                          <span class="badge bg-success text-white">
                            <i class="fas fa-check-circle me-1"></i> Selesai
                          </span>
                          @break
                        @case('expired')
                          <span class="badge bg-secondary text-white">
                            <i class="fas fa-hourglass-end me-1"></i> Kadaluarsa
                          </span>
                          @break
                        @case('cancelled')
                          <span class="badge bg-danger text-white">
                            <i class="fas fa-times-circle me-1"></i> Dibatalkan
                          </span>
                          @break
                        @default
                          <span class="badge bg-secondary">
                            {{ ucfirst($order['status']) }}
                          </span>
                      @endswitch
                    </td>
                    <td class="text-end fw-bold" data-order="{{ $order['total_price'] }}">
                      Rp{{ number_format($order['total_price'], 0, ',', '.') }}
                    </td>
                    <td class="text-center" data-order="{{ strtotime($order['created_at']) }}">
                      <div>{{ \Carbon\Carbon::parse($order['created_at'])->translatedFormat('d M Y') }}</div>
                      <small class="text-muted">{{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}</small>
                    </td>
                    <td class="text-center">
                      <a href="/orders/{{ $order['id'] }}" class="btn btn-sm btn-outline-primary" title="Detail">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- DataTables Resources -->
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
    $('#orderTable').DataTable({
        order: [[5, 'desc']], // Default sort by date
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            search: "_INPUT_",
            searchPlaceholder: "Cari pesanan...",
            buttons: {
                excel: 'Excel',
                print: 'Print'
                // ,copy: 'Salin'
            }
        },
        dom: '<"row mb-3"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row mt-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-2"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 2, 3, 4, 5], // ID, Produk, Status, Total, Tanggal
                    format: {
                        body: function(data, row, column, node) {
                            const $node = $(node);
                            const html = $node.html() || '';

                            // Bersihkan tag HTML
                            const cleanText = html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();

                            // Kolom ID (0): Hilangkan "#"
                            if (column === 0) {
                                return cleanText.replace(/^#/, '');
                            }

                            // Kolom Total (4): Ambil angka dari Rp
                            if (column === 4) {
                                const angka = cleanText.replace(/[^\d]/g, '');
                                return angka ? parseInt(angka) : '-';
                            }

                            // Kolom Tanggal (5): Gabungkan tanggal dan waktu jadi satu baris
                            if (column === 5) {
                                const div = document.createElement('div');
                                div.innerHTML = html;
                                const datePart = div.querySelector('div')?.textContent?.trim() || '';
                                const timePart = div.querySelector('small')?.textContent?.trim() || '';
                                return datePart + ' ' + timePart;
                            }

                            // Default: teks bersih
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
            // {
            //     extend: 'copy',
            //     text: '<i class="fas fa-copy me-2"></i> Salin',
            //     className: 'btn btn-dark btn-sm',
            //     exportOptions: {
            //         columns: [0, 1, 3, 4, 5, 6, 7]
            //     }
            // }
        ],
        columnDefs: [
            {
                orderable: true,
                targets: [0, 2, 3, 4, 5]
            },
            {
                orderable: false,
                targets: [1, 6]
            },
            {
                className: 'text-center',
                targets: [0, 1, 5, 6]
            },
            {
                className: 'text-end',
                targets: [4]
            },
            {
                type: 'num',
                targets: 4,
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
            $('.dt-buttons button').removeClass('btn-secondary').addClass('btn-sm');
        }
    });
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
@endsection --}}
