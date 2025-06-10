@extends('admin')

@section('title', 'Semua Pesanan')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Semua Pesanan</h2>
      </div>
      <p class="text-muted small">Klik pada kepala tabel untuk mengurutkan berdasarkan kolom. Klik pada pesanan untuk melihat detailnya.</p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="orderTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Gambar</th>
                    <th>Produk Pertama</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $entry)
                    @php
                        $order = $entry['order'];
                        $item = $entry['sample_item'];
                    @endphp
                    <tr onclick="location.href='/orders/{{ $order['id'] }}'" style="cursor: pointer;">
                        <td>{{ $order['id'] }}</td>
                        <td>
                            <img src="{{ $item['thumbnail'] }}" width="50">
                        </td>
                        <td>{{ $item['product_name'] }}</td>
                        <td>
                            @if($order['status'] === 'pending')
                                <span class="badge bg-warning text-dark">Belum Diproses</span>
                            @elseif($order['status'] === 'done')
                                <span class="badge bg-success">Selesai</span>
                            @elseif($order['status'] === 'expired')
                                <span class="badge bg-secondary">Kadaluarsa</span>
                            @elseif($order['status'] === 'cancelled')
                                <span class="badge bg-secondary">Dibatalkan</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($order['status']) }}</span>
                            @endif
                        </td>
                        <td>Rp{{ number_format($order['total_price'], 0, ',', '.') }}</td>
                        <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
  </div>
</div>

<script>
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

window.onload = function () {
    sortTable(5); // sort by stock
}
</script>

    <!-- jQuery dan DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#orderTable').DataTable({
                order: [[5, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });
        });
    </script>
@endsection
