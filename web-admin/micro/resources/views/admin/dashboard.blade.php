@extends('admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <h2 class="mb-4">Dashboard Admin</h2>

      <div class="row">
        <!-- Pending Orders -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
              <span>Pesanan Belum Diproses</span>
              <a href="/orders?status=pending">Lihat semua</a>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto">
              <ul class="list-group list-group-flush">
                @foreach($pendingOrders as $item)
                  <li class="list-group-item list-group-item-action" onclick="location.href='/orders/{{ $item['order']['id'] }}'">
                    <div class="d-flex align-items-center">
                      <img src="{{ $item['sample_item']['thumbnail'] }}" width="40" height="40" class="me-2 rounded">
                      <div>
                        <div>#{{ $item['order']['id'] }} - Rp{{ number_format($item['order']['total_price']) }}</div>
                        <small class="text-muted">{{ $item['sample_item']['product_name'] }} - {{ \Carbon\Carbon::parse($item['order']['created_at'])->format('d M Y') }}</small>
                      </div>
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
              <span>Stok Rendah</span>
              <a href="/products">Lihat semua</a>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto">
              <ul class="list-group list-group-flush">
                @foreach($lowStocks as $prod)
                  <li class="list-group-item list-group-item-action" onclick="location.href='/products/{{ $prod['product_id'] }}'">
                    <div class="d-flex align-items-center">
                      <img src="{{ $prod['thumbnail'] }}" width="40" height="40" class="me-2 rounded">
                      <div>
                        <div>{{ $prod['product_name'] }} - {{ $prod['variant_name'] }}</div>
                        <small class="text-danger">Stok: {{ $prod['stock'] }}</small>
                      </div>
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales Chart (full width) -->
      <div class="row">
        <div class="col-md-12">
          <div class="card mb-4">
            <div class="card-header">Penjualan per Bulan</div>
            <div class="card-body">
              <canvas id="salesChart" style="height: 300px;"></canvas>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const sales = @json($sales);
  const labels = sales.map(d => d.month);
  const values = sales.map(d => d.total_sales);

  if (labels.length > 0) {
    new Chart(document.getElementById('salesChart'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Total Penjualan',
          data: values,
          backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          title: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }
</script>
@endsection
