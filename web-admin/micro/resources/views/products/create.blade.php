@extends('admin')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Tambah Produk</h4>
        </div>

        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                @csrf

                {{-- These inputs will store the URL of the first uploaded image --}}
                <input type="hidden" name="image_url" id="image_url">
                <input type="hidden" name="thumbnail_url" id="thumbnail_url">
                <input type="hidden" name="search_vector" id="search_vector">

                <input type="hidden" name="all_image_urls" id="all_image_urls">

                <div class="card mb-4" id="step1">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">1. Informasi Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" name="name" id="productName" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_varians" id="is_varians" class="form-check-input">
                            <label class="form-check-label" for="is_varians">Produk memiliki varian?</label>
                        </div>

                        <div id="nonVariantFields">
                            <div class="card border mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="price" class="form-label">Harga</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" name="price_display" id="price" class="form-control price-input" required data-min="500">
                                                    <input type="hidden" name="price" id="price_hidden">
                                                </div>
                                                <div class="invalid-feedback" id="price-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="discount_price" class="form-label">Harga Diskon (opsional)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" name="discount_price_display" id="discount_price" class="form-control price-input" data-min="0">
                                                    <input type="hidden" name="discount_price" id="discount_price_hidden">
                                                </div>
                                                <div class="invalid-feedback" id="discount_price-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="stock" class="form-label">Stok</label>
                                                <input type="number" name="stock" id="stock" class="form-control" required min="0">
                                                <div class="invalid-feedback" id="stock-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="variantFields" style="display: none;">
                            <div class="card border-0 mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Varian Produk</h5>
                                </div>
                                <div class="card-body">
                                    <div id="variantContainer">
                                        </div>
                                    <button type="button" class="btn btn-outline-primary mt-2" onclick="addVariant()" id="addVariantBtn">
                                        <i class="fas fa-plus"></i> Tambah Varian
                                    </button>
                                    <small class="text-muted d-block mt-1">Maksimal 10 varian</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-end">
                        <button type="button" class="btn btn-primary" onclick="nextStep(1)">Lanjut <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="card mb-4" id="step2" style="display: none;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">2. Gambar Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Anda dapat mengupload maksimal 3 gambar produk. Format yang diterima: JPG/JPEG, PNG, SVG.
                        </div>

                        <div class="form-group mb-3">
                            <label for="productImages" class="form-label">Pilih Gambar</label>
                            <input type="file" class="form-control" id="productImages" accept="image/jpeg, image/png, image/svg+xml" multiple>
                            <div class="invalid-feedback" id="image-feedback"></div>
                            <small class="text-muted">Gambar pertama akan digunakan untuk menghasilkan deskripsi otomatis (opsional)</small>
                        </div>

                        <div class="row mb-3" id="imagePreviewContainer">
                            </div>

                        <button type="button" id="uploadImageBtn" class="btn btn-primary mb-3 d-none" onclick="uploadToImageKit()">
                            <i class="fas fa-upload"></i> Upload
                        </button>

                        <div id="imageProcessing" class="alert alert-info mb-3 d-none">
                            <i class="fas fa-spinner fa-spin"></i> Sedang mengupload gambar...
                        </div>

                        {{-- Hidden message when AI generation is successful --}}
                        <div id="aiSuccessMessage" class="alert alert-success mb-3 d-none">
                            <i class="fas fa-check-circle"></i> Deskripsi AI berhasil dibuat!
                        </div>

                        <button type="button" id="generateAIBtn" class="btn btn-info mb-3 d-none" onclick="generateAIDescription()">
                            <i class="fas fa-robot"></i> Hasilkan deskripsi AI (opsional)
                        </button>

                        <div id="aiProcessing" class="alert alert-info mb-3 d-none">
                            <i class="fas fa-spinner fa-spin"></i> Sedang membuat deskripsi otomatis...
                        </div>

                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                            <i class="fas fa-save"></i> Simpan Produk
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Wizard navigation
    function nextStep(currentStep) {
        // Basic validation for Step 1 before moving to Step 2
        if (currentStep === 1) {
            const productName = document.getElementById('productName').value.trim();
            const description = document.querySelector('textarea[name="description"]').value.trim();
            const categoryId = document.querySelector('select[name="category_id"]').value;
            const isVariansChecked = document.getElementById('is_varians').checked;

            if (!productName || !categoryId) {
                alert('Harap lengkapi Nama Produk, Deskripsi, dan Kategori.');
                return;
            }

            if (!isVariansChecked) { // If not variants, validate main price/stock
                const priceInput = document.getElementById('price');
                const stockInput = document.getElementById('stock');
                if (!validatePriceInput(priceInput) || stockInput.value.trim() === '' || parseInt(stockInput.value, 10) < 0) {
                    alert('Harap lengkapi Harga dan Stok dengan benar untuk produk non-varian.');
                    return;
                }
                // Also validate discount price if filled
                const discountPriceInput = document.getElementById('discount_price');
                if (discountPriceInput.value.trim() !== '' && !validatePriceInput(discountPriceInput)) {
                     alert('Harga Diskon tidak valid.');
                     return;
                }
            } else { // If variants, validate at least one variant
                const variantCards = document.querySelectorAll('#variantContainer .card');
                if (variantCards.length === 0) {
                    alert('Harap tambahkan setidaknya satu varian untuk produk bervarian.');
                    return;
                }
                let allVariantsValid = true;
                variantCards.forEach(card => {
                    const variantNameInput = card.querySelector('input[name$="[name]"]');
                    const variantPriceInput = card.querySelector('input[name$="[price_display]"]');
                    const variantStockInput = card.querySelector('input[name$="[stock]"]');
                    const variantDiscountPriceInput = card.querySelector('input[name$="[discount_price_display]"]');

                    if (!variantNameInput.value.trim() || !validatePriceInput(variantPriceInput) || variantStockInput.value.trim() === '' || parseInt(variantStockInput.value, 10) < 0) {
                        allVariantsValid = false;
                        alert('Harap lengkapi semua field Nama Varian, Harga, dan Stok dengan benar untuk setiap varian.');
                        return; // Exit foreach
                    }
                    if (variantDiscountPriceInput.value.trim() !== '' && !validatePriceInput(variantDiscountPriceInput)) {
                        allVariantsValid = false;
                        alert('Harga Diskon varian tidak valid.');
                        return;
                    }
                });
                if (!allVariantsValid) return;
            }
        }

        document.getElementById(`step${currentStep}`).style.display = 'none';
        document.getElementById(`step${currentStep + 1}`).style.display = 'block';
    }

    function prevStep(currentStep) {
        document.getElementById(`step${currentStep}`).style.display = 'none';
        document.getElementById(`step${currentStep - 1}`).style.display = 'block';
    }

    // Variant management
    const isVariansCheckbox = document.getElementById('is_varians');
    const variantFields = document.getElementById('variantFields');
    const nonVariantFields = document.getElementById('nonVariantFields');
    const addVariantBtn = document.getElementById('addVariantBtn');
    let variantIndex = 0;
    const MAX_VARIANTS = 10;

    isVariansCheckbox.addEventListener('change', function() {
        variantFields.style.display = this.checked ? 'block' : 'none';
        nonVariantFields.style.display = this.checked ? 'none' : 'block';

        // Set required attribute for non-variant fields (handled in submit validation as well)
        // document.getElementById('price').required = !this.checked;
        // document.getElementById('stock').required = !this.checked;

        if (this.checked && variantIndex === 0) {
            addVariant();
        } else if (!this.checked) {
            // Clear all variants if checkbox is unchecked
            document.getElementById('variantContainer').innerHTML = '';
            variantIndex = 0;
            addVariantBtn.disabled = false;
        }
    });

    function addVariant() {
        if (variantIndex >= MAX_VARIANTS) {
            addVariantBtn.disabled = true;
            alert('Maksimal 10 varian telah tercapai.');
            return;
        }

        const container = document.getElementById('variantContainer');
        const variantId = `variant-${variantIndex}`;

        container.insertAdjacentHTML('beforeend', `
            <div class="card border mb-3" id="${variantId}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>Nama Varian</label>
                                <input type="text" name="variants[${variantIndex}][name]" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-3">
                                <label>Harga</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="variants[${variantIndex}][price_display]" class="form-control price-input" required data-min="500">
                                    <input type="hidden" name="variants[${variantIndex}][price]" class="price-hidden">
                                </div>
                                <div class="invalid-feedback variant-price-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-3">
                                <label>Harga Diskon</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="variants[${variantIndex}][discount_price_display]" class="form-control price-input" data-min="0">
                                    <input type="hidden" name="variants[${variantIndex}][discount_price]" class="price-hidden">
                                </div>
                                <div class="invalid-feedback variant-price-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-3">
                                <label>Stok</label>
                                <input type="number" name="variants[${variantIndex}][stock]" class="form-control" required min="0" value="0">
                                <div class="invalid-feedback variant-stock-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariant('${variantId}')">
                                <i class="fas fa-trash"></i> Hapus Varian
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        // Add event listeners for new price inputs
        const newPriceInputs = container.querySelectorAll(`#${variantId} .price-input`);
        newPriceInputs.forEach(input => {
            input.addEventListener('input', formatRupiahInput);
            input.addEventListener('blur', (event) => validatePriceInput(event.target));
        });

        variantIndex++;
        if (variantIndex >= MAX_VARIANTS) {
            addVariantBtn.disabled = true;
        }
    }

    function removeVariant(id) {
        const element = document.getElementById(id);
        if (element) {
            element.remove();
            variantIndex--;
            addVariantBtn.disabled = false;
        }
        // If all variants are removed and is_varians is checked, add one back
        if (isVariansCheckbox.checked && variantIndex === 0) {
            addVariant();
        }
    }

    // --- Image handling and Validation ---
    let selectedFiles = []; // Stores actual File objects
    let uploadedImageUrls = []; // Stores ImageKit URLs for ALL uploaded images
    const productImagesInput = document.getElementById('productImages');
    const uploadImageBtn = document.getElementById('uploadImageBtn');
    const generateAIBtn = document.getElementById('generateAIBtn');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imageFeedback = document.getElementById('image-feedback');
    const submitBtn = document.getElementById('submitBtn'); // Get submit button reference
    const aiSuccessMessage = document.getElementById('aiSuccessMessage'); // Get AI success message

    productImagesInput.addEventListener('change', function(e) {
        imagePreviewContainer.innerHTML = ''; // Clear existing previews
        imageFeedback.style.display = 'none'; // Hide previous error
        imageFeedback.textContent = '';
        uploadImageBtn.classList.add('d-none');
        generateAIBtn.classList.add('d-none');
        aiSuccessMessage.classList.add('d-none'); // Hide AI success message
        submitBtn.disabled = true; // Disable submit button until upload

        const files = Array.from(this.files);
        selectedFiles = []; // Reset selected files

        if (files.length > 3) {
            alert('Anda hanya dapat mengupload maksimal 3 gambar.');
            this.value = ''; // Clear selected files from input
            return;
        }

        let allFilesValid = true;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];

        files.forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                allFilesValid = false;
                alert(`Format gambar ${file.name} tidak didukung. Harap gunakan JPG/JPEG, PNG, atau SVG.`);
                this.value = ''; // Clear selected files from input
                return; // Exit forEach early
            }
            selectedFiles.push(file); // Add valid files to selectedFiles
        });

        if (!allFilesValid) {
            return;
        }

        if (selectedFiles.length > 0) {
            uploadImageBtn.classList.remove('d-none');
            selectedFiles.forEach((file) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'col-md-4 mb-3';
                    preview.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: contain;">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    imagePreviewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }
    });

    async function uploadToImageKit() {
        const processingDiv = document.getElementById('imageProcessing');
        uploadedImageUrls = []; // Clear previous uploads

        try {
            processingDiv.classList.remove('d-none');
            uploadImageBtn.classList.add('d-none');
            generateAIBtn.classList.add('d-none'); // Hide AI button until first image is uploaded
            aiSuccessMessage.classList.add('d-none'); // Hide AI success message
            submitBtn.disabled = true; // Disable submit during upload

            if (selectedFiles.length === 0) {
                throw new Error('Tidak ada gambar yang dipilih.');
            }

            const productName = document.getElementById('productName').value;
            if (!productName.trim()) {
                throw new Error('Harap isi Nama Produk terlebih dahulu di Langkah 1.');
            }

            // Loop through all selected files to upload them
            for (let i = 0; i < selectedFiles.length; i++) {
                const file = selectedFiles[i];
                const uploadForm = new FormData();
                uploadForm.append('file', file);
                uploadForm.append('product_name', productName);

                const uploadResponse = await fetch("{{ route('products.upload') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: uploadForm
                });

                const uploadResult = await uploadResponse.json();

                if (!uploadResult.success) {
                    throw new Error(`Upload gambar ${file.name} gagal: ${uploadResult.message || uploadResult.details}`);
                }

                uploadedImageUrls.push({
                    image_url: uploadResult.image_url,
                    thumbnail_url: uploadResult.thumbnail_url
                });

                // Update UI for uploaded image
                const correspondingPreviewCard = imagePreviewContainer.children[i].querySelector('.card');
                if (correspondingPreviewCard) {
                    correspondingPreviewCard.querySelector('.card-body').innerHTML += `
                        <div class="badge bg-primary mt-1">Uploaded</div>
                    `;
                }
            }

            // Store the first image's URL for AI processing in hidden inputs
            if (uploadedImageUrls.length > 0) {
                document.getElementById('image_url').value = uploadedImageUrls[0].image_url;
                document.getElementById('thumbnail_url').value = uploadedImageUrls[0].thumbnail_url;
            } else {
                // Clear hidden inputs if no images were successfully uploaded
                document.getElementById('image_url').value = '';
                document.getElementById('thumbnail_url').value = '';
            }


            // Store all uploaded image URLs as JSON string for Laravel to process
            document.getElementById('all_image_urls').value = JSON.stringify(uploadedImageUrls);

            // Once images are uploaded, enable submit button and show AI generate button
            submitBtn.disabled = false;
            generateAIBtn.classList.remove('d-none');

        } catch (error) {
            console.error('Error during image upload:', error);
            alert('Error upload gambar: ' + error.message);
            // Re-enable upload button if an error occurs
            uploadImageBtn.classList.remove('d-none');
            submitBtn.disabled = true; // Disable submit on error
        } finally {
            processingDiv.classList.add('d-none');
        }
    }

    async function generateAIDescription() {
        const aiProcessingDiv = document.getElementById('aiProcessing');
        const submitBtn = document.getElementById('submitBtn'); // Ensure submitBtn is accessible
        const aiSuccessMessage = document.getElementById('aiSuccessMessage');

        try {
            aiProcessingDiv.classList.remove('d-none');
            generateAIBtn.classList.add('d-none');
            aiSuccessMessage.classList.add('d-none'); // Hide previous success message

            const imageUrl = document.getElementById('image_url').value;
            if (!imageUrl) {
                throw new Error('Gambar belum diupload atau URL gambar utama tidak tersedia.');
            }

            const productName = document.getElementById('productName').value;

            const aiResponse = await fetch("{{ route('products.search_vector') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    image_url: imageUrl,
                    product_name: productName
                })
            });

            const aiResult = await aiResponse.json();

            if (!aiResult.success) {
                throw new Error(aiResult.message || 'Gagal membuat deskripsi AI');
            }

            document.getElementById('search_vector').value = aiResult.caption;
            aiSuccessMessage.classList.remove('d-none'); // Show AI success message

            // Mark first image as AI processed
            const firstImageCard = imagePreviewContainer.querySelector('.col-md-4:first-child .card');
            if (firstImageCard) {
                firstImageCard.querySelector('.card-body').innerHTML += `
                    <div class="badge bg-success mt-1">AI Generated</div>
                `;
            }

            // Submit button should already be enabled from upload. No change needed here.

        } catch (error) {
            console.error('Error:', error);
            alert('Error: ' + error.message);
            // If AI generation fails, re-enable the AI button so user can retry
            generateAIBtn.classList.remove('d-none');
        } finally {
            aiProcessingDiv.classList.add('d-none');
        }
    }

    // --- Price Formatting and Validation ---
    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(input => {
        input.addEventListener('input', formatRupiahInput);
        input.addEventListener('blur', (event) => validatePriceInput(event.target));
        // Initialize format on page load if values exist
        if (input.value) {
            formatRupiahInput.call(input);
        }
    });

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    function parseRupiah(rupiahString) {
        // Remove "Rp" and any dots, then parse as integer
        return parseInt(rupiahString.replace(/[^0-9]/g, ''), 10);
    }

    function formatRupiahInput(event) {
        let input = event.target;
        let value = input.value.replace(/[^0-9]/g, ''); // Remove non-numeric characters

        // Prevent leading zeros unless it's just '0'
        if (value.length > 1 && value.startsWith('0')) {
            value = parseInt(value, 10).toString();
        }

        if (value) {
            input.value = formatRupiah(value);
        } else {
            input.value = '';
        }

        // Update the corresponding hidden input
        let hiddenInput;
        if (input.id && input.id.endsWith('_display')) {
            hiddenInput = document.getElementById(input.id.replace('_display', '_hidden'));
        } else { // For variant price inputs
            hiddenInput = input.closest('.input-group').querySelector('.price-hidden');
        }

        if (hiddenInput) {
            hiddenInput.value = value;
        }
    }

    function validatePriceInput(inputElement) {
        const rawValue = parseRupiah(inputElement.value);
        const minPrice = parseInt(inputElement.dataset.min || '0', 10);
        // Determine feedback element based on its position (next to input-group or direct ID)
        let feedbackElement;
        if (inputElement.closest('.input-group')) {
            feedbackElement = inputElement.closest('.input-group').nextElementSibling;
        } else {
            feedbackElement = document.getElementById(`${inputElement.id}-feedback`);
        }


        inputElement.classList.remove('is-invalid', 'is-valid');
        if (feedbackElement) {
            feedbackElement.style.display = 'none';
            feedbackElement.textContent = '';
        }

        if (inputElement.value.trim() === '') {
            // Only mark as invalid if the field is required
            // For now, `required` attribute is handled by nextStep validation
            // If you want inline validation for required prices, add `inputElement.required` check here
            if (inputElement.hasAttribute('required') && inputElement.value.trim() === '') {
                inputElement.classList.add('is-invalid');
                if (feedbackElement) {
                    feedbackElement.textContent = 'Harga wajib diisi.';
                    feedbackElement.style.display = 'block';
                }
                return false;
            }
            return true; // Optional field can be empty
        }

        if (isNaN(rawValue) || rawValue < minPrice) {
            inputElement.classList.add('is-invalid');
            if (feedbackElement) {
                feedbackElement.textContent = `Harga tidak valid. Minimum Rp ${formatRupiah(minPrice)}.`;
                feedbackElement.style.display = 'block';
            }
            return false;
        }

        inputElement.classList.add('is-valid');
        return true;
    }

    // --- General Form Validation on Submit ---
    document.getElementById('productForm').addEventListener('submit', function(event) {
        let isValid = true;

        // Re-validate Step 1 fields
        const productName = document.getElementById('productName');
        const description = document.querySelector('textarea[name="description"]');
        const categoryId = document.querySelector('select[name="category_id"]');

        if (!productName.value.trim()) { isValid = false; productName.classList.add('is-invalid'); alert('Nama Produk wajib diisi.'); } else { productName.classList.remove('is-invalid'); }
        if (!description.value.trim()) { isValid = false; description.classList.add('is-invalid'); alert('Deskripsi wajib diisi.'); } else { description.classList.remove('is-invalid'); }
        if (!categoryId.value) { isValid = false; categoryId.classList.add('is-invalid'); alert('Kategori wajib diisi.'); } else { categoryId.classList.remove('is-invalid'); }

        const isVariansChecked = document.getElementById('is_varians').checked;
        if (!isVariansChecked) {
            // Validate non-variant fields
            const priceInput = document.getElementById('price');
            const stockInput = document.getElementById('stock');
            const discountPriceInput = document.getElementById('discount_price');

            if (!validatePriceInput(priceInput)) { isValid = false; }
            if (discountPriceInput.value.trim() !== '' && !validatePriceInput(discountPriceInput)) { isValid = false; }
            if (stockInput.value.trim() === '' || parseInt(stockInput.value, 10) < 0 || isNaN(parseInt(stockInput.value, 10))) {
                isValid = false;
                stockInput.classList.add('is-invalid');
                document.getElementById('stock-feedback').textContent = 'Stok wajib diisi dan harus angka positif atau nol.';
                document.getElementById('stock-feedback').style.display = 'block';
            } else {
                stockInput.classList.remove('is-invalid');
                document.getElementById('stock-feedback').style.display = 'none';
            }

        } else {
            // Validate variant fields
            const variantCards = document.querySelectorAll('#variantContainer .card');
            if (variantCards.length === 0) {
                isValid = false;
                alert('Harap tambahkan setidaknya satu varian untuk produk bervarian.');
            } else {
                variantCards.forEach(card => {
                    const variantNameInput = card.querySelector('input[name$="[name]"]');
                    const variantPriceInput = card.querySelector('input[name$="[price_display]"]');
                    const variantStockInput = card.querySelector('input[name$="[stock]"]');
                    const variantDiscountPriceInput = card.querySelector('input[name$="[discount_price_display]"]');

                    if (!variantNameInput.value.trim()) { isValid = false; variantNameInput.classList.add('is-invalid'); } else { variantNameInput.classList.remove('is-invalid'); }
                    if (!validatePriceInput(variantPriceInput)) { isValid = false; }
                    if (variantDiscountPriceInput.value.trim() !== '' && !validatePriceInput(variantDiscountPriceInput)) { isValid = false; }

                    if (variantStockInput.value.trim() === '' || parseInt(variantStockInput.value, 10) < 0 || isNaN(parseInt(variantStockInput.value, 10))) {
                        isValid = false;
                        variantStockInput.classList.add('is-invalid');
                        card.querySelector('.variant-stock-feedback').textContent = 'Stok wajib diisi dan harus angka positif atau nol.';
                        card.querySelector('.variant-stock-feedback').style.display = 'block';
                    } else {
                        variantStockInput.classList.remove('is-invalid');
                        card.querySelector('.variant-stock-feedback').style.display = 'none';
                    }
                });
            }
        }

        // Validate image upload
        if (document.getElementById('step2').style.display === 'block') {
            const imageUrlHidden = document.getElementById('image_url').value;

            if (!imageUrlHidden) { // Check if at least one image has been uploaded
                isValid = false;
                alert('Harap upload gambar produk terlebih dahulu.');
                productImagesInput.classList.add('is-invalid');
                imageFeedback.textContent = 'Gambar produk wajib diupload.';
                imageFeedback.style.display = 'block';
            } else {
                 productImagesInput.classList.remove('is-invalid');
                 imageFeedback.style.display = 'none';
            }
        }

        if (!isValid) {
            event.preventDefault(); // Stop form submission
            alert('Harap perbaiki kesalahan input sebelum menyimpan produk.');
            // Go back to step 1 if primary product info is invalid
            if (
                !productName.value.trim() ||
                !description.value.trim() ||
                !categoryId.value ||
                (!isVariansChecked && (!document.getElementById('price').value.trim() || !document.getElementById('stock').value.trim() || !validatePriceInput(document.getElementById('price')))) ||
                (isVariansChecked && document.querySelectorAll('#variantContainer .card').length === 0)
            ) {
                prevStep(2); // Go back to step 1 if current step is 2 and validation failed in step 1 related fields
            }
        }
    });

</script>
@endsection

@section('styles')
<style>
    .card {
        border-radius: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 0.375rem;
    }
    #variantContainer .card {
        transition: all 0.3s ease;
    }
    #variantContainer .card:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    .invalid-feedback {
        display: none; /* Hidden by default */
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545; /* Bootstrap red for invalid feedback */
    }
    .form-control.is-invalid + .invalid-feedback,
    .form-control.is-invalid ~ .input-group .invalid-feedback {
        display: block; /* Show when input is invalid */
    }
</style>
@endsection
