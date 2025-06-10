@extends('admin')

@section('content')
<div class="container">
    <h2>Tambah Produk</h2>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>

        <div class="form-group">
            <label for="category_id">Kategori</label>
            <select name="category_id" class="form-control" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
            @endforeach
        </select>
        </div>
        <div class="form-check">
            <input type="checkbox" name="is_varians" id="is_varians" class="form-check-input">
            <label class="form-check-label" for="is_varians">Produk memiliki varian?</label>
        </div>

        <div id="nonVariantFields">
            <div class="form-group">
                <label for="price">Harga</label>
                <input type="number" name="price" class="form-control">
            </div>

            <div class="form-group">
                <label for="discount_price">Harga Diskon (opsional)</label>
                <input type="number" name="discount_price" class="form-control">
            </div>

            <div class="form-group">
                <label for="stock">Stok</label>
                <input type="number" name="stock" class="form-control">
            </div>
        </div>

        <div id="variantFields" style="display: none">
            <h4>Varian Produk</h4>
            <div id="variantContainer"></div>
            <button type="button" class="btn btn-secondary" onclick="addVariant()">Tambah Varian</button>
        </div>

        <div class="form-group">
            <label for="images">Gambar Produk</label>
            <input type="file" name="images[]" class="form-control" multiple required>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Produk</button>
    </form>
</div>

<script>
    const isVariansCheckbox = document.getElementById('is_varians');
    const variantFields = document.getElementById('variantFields');
    const nonVariantFields = document.getElementById('nonVariantFields');

    isVariansCheckbox.addEventListener('change', function() {
        variantFields.style.display = this.checked ? 'block' : 'none';
        nonVariantFields.style.display = this.checked ? 'none' : 'block';
    });

    let variantIndex = 0;
    function addVariant() {
        const container = document.getElementById('variantContainer');
        container.insertAdjacentHTML('beforeend', `
            <div class="card mb-2 p-2">
                <label>Nama Varian</label>
                <input type="text" name="variants[${variantIndex}][name]" class="form-control" required>
                <label>Harga</label>
                <input type="number" name="variants[${variantIndex}][price]" class="form-control" required>
                <label>Harga Diskon (opsional)</label>
                <input type="number" name="variants[${variantIndex}][discount_price]" class="form-control">
                <label>Stok</label>
                <input type="number" name="variants[${variantIndex}][stock]" class="form-control" required>
            </div>
        `);
        variantIndex++;
    }
</script>
@endsection
