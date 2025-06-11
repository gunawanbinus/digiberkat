@extends('admin')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Daftar Permintaan Restok</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ implode('', $errors->all(':message')) }}
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>Stok Saat Ini</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($restockRequests as $item)
                        <tr onclick="location.href='/products/{{ $item['product_id'] }}'" style="cursor: pointer;">
                            <td class="d-flex align-items-center">
                                <img src="{{ $item['thumbnail'] }}" width="40" height="40" class="me-2 rounded">
                                <span>{{ $item['product_name'] }}</span>
                            </td>
                            <td>{{ $item['variant_name'] }}</td>
                            <td><span class="text-danger">{{ $item['stock'] }}</span></td>
                            <td>
                                 <form action="{{ url('/restock-requests/' . $item['id'] . '/read') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">Tandai Dibaca</button>
                            </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada permintaan restok saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
