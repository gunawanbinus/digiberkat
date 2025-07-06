@extends('admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">Dashboard</h2>
        <!--<div class="d-flex">
          <div class="dropdown me-2">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-calendar-alt me-2"></i>Bulan Ini
            </button>
            <ul class="dropdown-menu" aria-labelledby="timeRangeDropdown">
              <li><a class="dropdown-item" href="#">Hari Ini</a></li>
              <li><a class="dropdown-item" href="#">Minggu Ini</a></li>
              <li><a class="dropdown-item" href="#">Bulan Ini</a></li>
              <li><a class="dropdown-item" href="#">Tahun Ini</a></li>
            </ul>
          </div>
          <button class="btn btn-primary" onclick="window.location.href = '{{ url('admin/dashboard') }}'">
            <i class="fas fa-sync-alt me-2"></i>Refresh
          </button>
        </div>-->
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-start border-primary border-4 shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col">
                  <div class="text-xs fw-bold text-primary text-uppercase mb-1">Penjualan Bln. Ini</div>
                  <div class="h5 mb-0 fw-bold text-gray-800">Rp65.000</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-wallet fa-2x text-primary"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-start border-success border-4 shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col">
                  <div class="text-xs fw-bold text-success text-uppercase mb-1">Pesanan Bln. Ini</div>
                  <div class="h5 mb-0 fw-bold text-gray-800">1 Pesanan</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-shopping-cart fa-2x text-success"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-start border-warning border-4 shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col">
                  <div class="text-xs fw-bold text-warning text-uppercase mb-1">Perlu Restok</div>
                  <div class="h5 mb-0 fw-bold text-gray-800">2 Produk</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-box-open fa-2x text-warning"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-start border-danger border-4 shadow-sm h-100 py-2">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col">
                  <div class="text-xs fw-bold text-danger text-uppercase mb-1">Belum Diproses</div>
                  <div class="h5 mb-0 fw-bold text-gray-800">2 Pesanan</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-clock fa-2x text-danger"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="row">
        <!-- Sales Chart -->
        <div class="col-lg-8 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold">Penjualan Per Bulan</h6>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartDropdown" data-bs-toggle="dropdown">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="#">Export Data</a></li>
                  <li><a class="dropdown-item" href="#">Print</a></li>
                </ul>
              </div>
            </div>
            <div class="card-body">
              <canvas id="salesChart" height="300"></canvas>
            </div>
          </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-lg-4 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold">Stok Rendah</h6>
              <a href="/products/lowstocks" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                @foreach($lowStocks as $prod)
                <a href="/products/{{ $prod['product_id'] }}" class="list-group-item list-group-item-action border-0 py-3">
                  <div class="d-flex align-items-center">
                    <div class="position-relative me-3">
                      <img src="{{ $prod['thumbnail'] ?: asset('images/default-product.png') }}"
                           class="rounded-2" width="50" height="50"
                           style="object-fit: cover; border: 1px solid #eee;">
                      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $prod['stock'] }}
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1">{{ $prod['product_name'] }}</h6>
                      <small class="text-muted">
                        @if (!empty($prod['variant_name']))
                            {{ $prod['variant_name'] }}
                        @else
                            <span class="badge bg-secondary text-white">Tanpa Varian</span>
                        @endif
                      </small>

                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                  </div>
                </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Pending Orders -->
        <div class="col-lg-6 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold">Pesanan Belum Diproses</h6>
              <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                @foreach($pendingOrders as $item)
                <a href="/orders/{{ $item['order']['id'] }}" class="list-group-item list-group-item-action border-0 py-3">
                  <div class="d-flex align-items-center">
                    <img src="{{ $item['sample_item']['thumbnail'] }}"
                         class="rounded-2 me-3" width="50" height="50"
                         style="object-fit: cover; border: 1px solid #eee;">
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between">
                        <h6 class="mb-1">#{{ $item['order']['id'] }}</h6>
                        <span class="text-primary fw-bold">Rp{{ number_format($item['order']['total_price']) }}</span>
                      </div>
                      <small class="text-muted">{{ $item['sample_item']['product_name'] }}</small>
                      <div class="mt-1">
                        <span class="badge bg-light text-dark">
                          <i class="far fa-clock me-1"></i>
                          {{ \Carbon\Carbon::parse($item['order']['created_at'])->format('d M Y') }}
                        </span>
                      </div>
                    </div>
                  </div>
                </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>

        <!-- Restock Requests -->
        <div class="col-lg-6 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-bold">Permintaan Restok</h6>
              <a href="/restock-requests" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                @foreach($restockRequests as $item)
                <a href="/products/{{ $item['product_id'] }}" class="list-group-item list-group-item-action border-0 py-3">
                  <div class="d-flex align-items-center">
                    <div class="position-relative me-3">
                      <img src="{{ $item['thumbnail'] ?: asset('images/default-product.png') }}"
                           class="rounded-2" width="50" height="50"
                           style="object-fit: cover; border: 1px solid #eee;">
                      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                        {{ $item['stock'] }}
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1">{{ $item['product_name'] }}</h6>
                      <small class="text-muted">
                        @if (!empty($item['variant_name']))
                            {{ $item['variant_name'] }}
                        @else
                            <span class="badge bg-secondary text-white">Tanpa Varian</span>
                        @endif
                      </small>
                    </div>
                    <button class="btn btn-sm btn-success">
                      <i class="fas fa-check me-1"></i> Dibaca
                    </button>
                  </div>
                </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Bootstrap Bundle with Popper -->
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> --}}

<script>
  // Sales Chart
  const sales = @json($sales);
  const labels = sales.map(d => d.month);
  const values = sales.map(d => d.total_sales);

  if (labels.length > 0) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Total Penjualan',
          data: values,
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          borderColor: 'rgba(13, 110, 253, 1)',
          borderWidth: 2,
          tension: 0.3,
          fill: true,
          pointBackgroundColor: 'rgba(13, 110, 253, 1)',
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Rp' + context.raw.toLocaleString();
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return 'Rp' + value.toLocaleString();
              }
            }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });
  }
</script>
@endsection

@section('styles')
<style>
  .card {
    border-radius: 12px;
    border: none;
  }
  .card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,.05);
  }
  .list-group-item {
    transition: all 0.2s ease;
    border-left: 0;
    border-right: 0;
  }
  .list-group-item:first-child {
    border-top: 0;
  }
  .list-group-item:last-child {
    border-bottom: 0;
  }
  .list-group-item:hover {
    background-color: rgba(13, 110, 253, 0.05);
  }
  .badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
  }
  .page-header {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,.05);
  }
</style>
@endsection
