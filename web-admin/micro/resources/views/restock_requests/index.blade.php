@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Permintaan Restok</h2>

    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Produk</th>
                <th>Jumlah Diminta</th>
                <th>Status</th>
                <th>Dibuat Pada</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($restockRequests as $request)
            <tr style="cursor: pointer;" onclick="window.location.href='{{ route('products.edit', $request->product_id) }}'">
                <td>{{ $request->id }}</td>
                <td>{{ $request->product->name }}</td>
                <td>{{ $request->quantity }}</td>
                <td id="status-{{ $request->id }}">{{ $request->status }}</td>
                <td>{{ $request->created_at->format('d-m-Y H:i') }}</td>
                <td>
                    @if($request->status !== 'read')
                    <button class="btn btn-sm btn-outline-primary"
                        onclick="event.stopPropagation(); markAsRead({{ $request->id }})">Tandai Dibaca</button>
                    @else
                    <span class="badge badge-success">Sudah dibaca</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    function markAsRead(id) {
        fetch(`/restock-requests/${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: 'read' })
        })
        .then(response => {
            if (!response.ok) throw new Error("Gagal memperbarui status");
            return response.json();
        })
        .then(data => {
            document.getElementById('status-' + id).innerText = 'read';
            alert("Permintaan telah ditandai sebagai dibaca");
            location.reload(); // opsional, untuk refresh data
        })
        .catch(error => {
            console.error(error);
            alert("Terjadi kesalahan saat mengubah status");
        });
    }
</script>
@endsection
