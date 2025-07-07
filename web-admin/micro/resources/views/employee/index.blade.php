@extends('admin')

@section('title', 'Semua Akun Karyawan')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-user-tie me-2"></i>Semua Akun Karyawan</h2>
        <a href="{{ route('employee.register') }}" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>Tambah
        </a>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="employeeTable">
              <thead class="table-light">
                <tr>
                  <th class="text-center">ID</th>
                  <th class="text-center">Foto</th>
                  <th>Username</th>
                  <th class="text-center">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                @forelse($employees as $employee)
                  <tr>
                    <td class="text-center">{{ $employee['id'] }}</td>
                    <td class="text-center">
                      <img src="{{ $employee['thumbnail_url'] }}"
                            class="img-thumbnail rounded" width="50"
                            alt="{{ $employee['username'] }}">
                    </td>
                    <td>{{ $employee['username'] }}</td>
                    <td class="d-flex align-items-center gap-2">
                      <a href="/employees]/{{ $employee['id'] }}" class="btn btn-sm btn-outline-primary" title="Detail">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="/employees/{{ $employee['id'] }}"
                          class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="/employees/{{ $employee['id'] }}" class="btn btn-sm btn-outline-danger" title="Hapus">
                          <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center py-5">
                      <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted mb-2">Tidak ada akun karyawan</h5>
                        <a href="{{ route('employee.register') }}" class="btn btn-sm btn-primary mt-2">
                          <i class="fas fa-plus me-1"></i> Tambah
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
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables only if table has data
    if ($('#employeeTable tbody tr').not('.empty-row').length > 0) {
        $('#employeeTable').DataTable({
            order: [[0, 'asc']], // Default sort by Employee ID
            pageLength: 10, // Show 10 entries by default
            lengthMenu: [10, 25, 50, 100], // Entries per page options
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                search: "_INPUT_",
                searchPlaceholder: "Cari...",
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
                        columns: [0, 2],
                        format: {
                            body: function (data, row, column, node) {
                                const $node = $(node);
                                const html = $node.html() || '';

                                // Fungsi untuk menghapus semua tag HTML dan ambil teks bersih
                                const cleanText = html
                                    .replace(/<[^>]*>/g, '')     // Hapus semua tag HTML
                                    .replace(/\s+/g, ' ')         // Normalisasi spasi
                                    .trim();
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
                        columns: [0, 2]
                    },
                    customize: function(win) {
                        $(win.document.body).find('table').addClass('table-bordered');
                        $(win.document.body).find('h1').css('text-align','center').text('Daftar Akun Karyawan');
                    }
                },
            ],
            columnDefs: [
                {
                    orderable: true,
                    targets: [0, 2]
                },
                {
                    orderable: false,
                    targets: [1, 3]
                },
                {
                    className: 'text-center',
                    targets: [0]
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
