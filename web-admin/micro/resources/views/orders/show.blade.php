@extends('admin')

@section('title', 'Detail Pesanan')

@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-md-12">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="mb-0">Pesanan #{{ $orderId }}</h2>
          @php
              $statusBadge = [
                  'pending' => ['label' => 'Belum Diproses', 'class' => 'bg-warning text-dark'],
                  'done' => ['label' => 'Selesai', 'class' => 'bg-success'],
                  'expired' => ['label' => 'Kadaluarsa', 'class' => 'bg-secondary'],
                  'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-secondary'],
              ];
              $badge = $statusBadge[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-light text-dark'];
          @endphp
          <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
        </div>
        <div class="text-muted">
          {{ \Carbon\Carbon::parse($created_at)->translatedFormat('d F Y H:i') }}
        </div>
      </div>

      {{-- List Produk --}}
      <div class="list-group mb-4">
        @foreach ($items as $item)
          <div class="list-group-item d-flex justify-content-between align-items-start">
            <div class="d-flex">
              <img
                src="{{ $item['thumbnails'][0] ?? '#' }}"
                width="60" height="60"
                class="me-3"
                style="object-fit:cover; border-radius:8px; aspect-ratio: 1 / 1;">
              <div>
                <div class="text-muted">ID #{{ $item['product_id'] }}</div>
                <div><strong>{{ $item['name'] }} &nbsp x{{ $item['quantity'] }}</strong></div>
                @if(isset($item['variants']) && count($item['variants']) > 0)
                  <div class="text-muted small">{{ $item['variants'][0]['name'] }}</div>
                @endif
              </div>
            </div>
            <div class="text-end">
              <strong>Rp{{ number_format($item['price_at_purchase'], 0, ',', '.') }}</strong>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Tombol dan total --}}
      <div class="d-flex justify-content-between align-items-center">
        @if(!in_array($status, ['done', 'expired', 'cancelled']))
          <form action="{{ url('/orders/' . $orderId . '/finish') }}" method="POST" onsubmit="return confirm('Tandai pesanan sebagai selesai?')">
            @csrf
            <button type="submit" class="btn btn-success">Pesanan Selesai</button>
          </form>
        @else
          <div></div>
        @endif

        <h4 class="text-end">Total: Rp{{ number_format($total, 0, ',', '.') }}</h4>
      </div>

    </div>
  </div>
</div>
@endsection
