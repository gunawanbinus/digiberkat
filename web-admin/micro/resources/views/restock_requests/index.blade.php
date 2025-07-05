@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-boxes text-primary me-2"></i>Daftar Permintaan Restok
        </h2>
        <div class="d-flex">
            <button class="btn btn-outline-secondary me-2">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <button class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Buat Permintaan
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="restockTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="80">ID</th>
                            <th>Produk</th>
                            <th class="text-center" width="120">Jumlah</th>
                            <th class="text-center" width="150">Status</th>
                            <th class="text-center" width="180">Tanggal</th>
                            <th class="text-center" width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($restockRequests as $request)
                        <tr onclick="window.location.href='{{ route('products.edit', $request->product_id) }}'" style="cursor: pointer;">
                            <td class="text-center fw-bold">#{{ $request->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $request->product->image_url ?? asset('images/default-product.png') }}"
                                         class="rounded me-3" width="40" height="40"
                                         alt="{{ $request->product->name }}"
                                         onerror="this.src='{{ asset('images/default-product.png') }}'">
                                    <div>
                                        <div class="fw-semibold">{{ $request->product->name }}</div>
                                        <small class="text-muted">SKU: {{ $request->product->sku ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">{{ $request->quantity }} pcs</td>
                            <td class="text-center">
                                <span class="badge
                                    @if($request->status === 'pending') bg-warning text-dark
                                    @elseif($request->status === 'approved') bg-success
                                    @elseif($request->status === 'rejected') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div>{{ $request->created_at->format('d M Y') }}</div>
                                <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                @if($request->status !== 'read')
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="event.stopPropagation(); markAsRead({{ $request->id }})">
                                    <i class="fas fa-check me-1"></i> Tandai
                                </button>
                                @else
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i> Dibaca
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($restockRequests->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada permintaan restok</h5>
                <p class="text-muted">Tidak ada permintaan restok yang tercatat saat ini</p>
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Buat Permintaan Baru
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function markAsRead(id) {
    Swal.fire({
        title: 'Konfirmasi',
        text: "Anda yakin ingin menandai permintaan ini sebagai dibaca?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Tandai Dibaca',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
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
                Swal.fire(
                    'Berhasil!',
                    'Permintaan telah ditandai sebagai dibaca.',
                    'success'
                ).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error(error);
                Swal.fire(
                    'Gagal!',
                    'Terjadi kesalahan saat mengubah status.',
                    'error'
                );
            });
        }
    });
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.card {
    border-radius: 0.75rem;
    overflow: hidden;
}

.badge {
    padding: 0.5em 0.75em;
    font-size: 0.8em;
    font-weight: 500;
    letter-spacing: 0.05em;
}

.img-thumbnail {
    object-fit: cover;
}
</style>
@endsection
