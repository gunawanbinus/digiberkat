@extends('admin')

@section('title', 'Semua Produk')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Semua Produk</h2>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Tambah Produk</a>
      </div>
      <p class="text-muted">Klik pada kepala tabel untuk mengurutkan berdasarkan kolom. Klik pada produk untuk mengedit.</p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="productTable">
            <thead class="table-light">
                <tr>
                    <th >ID</th>
                    <th>Gambar</th>
                    <th >Nama</th>
                    <th >Varian</th>
                    <th >Harga Normal</th>
                    <th >Harga Diskon</th>
                    <th >Stok</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    @if($product['is_varians'])
                        @foreach($product['variants'] as $variant)
                            <tr onclick="location.href='/products/{{ $product['id'] }}'" style="cursor: pointer;">
                                <td>{{ $product['id'] }}</td>
                                <td>
                                    <img src="{{ $product['thumbnails'][0] ?? '' }}" width="50">
                                </td>
                                <td>{{ $product['name'] }}</td>
                                <td>{{ $variant['name'] }}</td>
                                <td>Rp{{ number_format($variant['price'], 0, ',', '.') }}</td>
                                <td>
                                    {{ $variant['discount_price'] ? 'Rp' . number_format($variant['discount_price'], 0, ',', '.') : '-' }}
                                </td>
                                <td>{{ $variant['stock'] }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr onclick="location.href='/products/{{ $product['id'] }}'" style="cursor: pointer;">
                            <td>{{ $product['id'] }}</td>
                            <td>
                                <img src="{{ $product['thumbnails'][0] ?? '' }}" width="50">
                            </td>
                            <td>{{ $product['name'] }}</td>
                            <td>-</td>
                            <td>Rp{{ number_format($product['price'], 0, ',', '.') }}</td>
                            <td>
                                {{ $product['discount_price'] ? 'Rp' . number_format($product['discount_price'], 0, ',', '.') : '-' }}
                            </td>
                            <td>{{ $product['stock'] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function sortTable(n) {
    const table = document.getElementById("productTable");
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
    sortTable(6); // sort by stock
}
</script>

    <!-- jQuery dan DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#productTable').DataTable({
                order: [[6, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });
        });
    </script>

@endsection
