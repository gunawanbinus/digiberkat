@extends('employee') {{-- Pastikan ini mengarah ke layout employee Anda, misalnya: resources/views/employee.blade.php --}}

@section('title', 'Dashboard Employee')

@section('content')
<h1 class="mt-4 mb-4">Dashboard Employee</h1>

<div class="row">
    {{-- Card untuk Pemindai Kamera Langsung --}}
    <div class="col-12 col-md-6 col-lg-6 mb-4"> {{-- Tambah mb-4 untuk margin bawah --}}
        <div class="card shadow h-100"> {{-- Tambah h-100 untuk tinggi seragam jika perlu --}}
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="fas fa-qrcode me-2"></i>
                <h5 class="mb-0">Pindai Kode QR Pesanan (Kamera)</h5>
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <label for="cameraSelection" class="form-label visually-hidden">Pilih Kamera:</label>
                        <select id="cameraSelection" class="form-select form-select-sm"></select>
                    </div>
                    <div id="qr-reader" style="width:100%; max-width: 350px; margin: 0 auto;"></div>
                    <div id="qr-reader-results" class="mt-3 fs-6"></div>
                </div>
                <div class="mt-3">
                    <button id="stopScannerBtn" class="btn btn-danger btn-sm me-2" style="display:none;">
                        <i class="fas fa-stop me-1"></i> Stop Scanner
                    </button>
                    <button id="startScannerBtn" class="btn btn-primary btn-sm" style="display:none;">
                        <i class="fas fa-play me-1"></i> Mulai Pemindai
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card untuk Pemindai dari Gambar --}}
    <div class="col-12 col-md-6 col-lg-6 mb-4"> {{-- Kelas responsif untuk tata letak yang lebih baik --}}
        <div class="card shadow h-100"> {{-- Tambah h-100 untuk tinggi seragam jika perlu --}}
            <div class="card-header bg-secondary text-white d-flex align-items-center">
                <i class="fas fa-upload me-2"></i>
                <h5 class="mb-0">Pindai Kode QR dari Gambar</h5>
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted mb-3">Pilih gambar yang berisi kode QR dari perangkat Anda.</p>
                    <input type="file" class="form-control" id="qr-image-file" accept="image/*">
                    <div id="qr-image-results" class="mt-3 fs-6"></div>
                </div>
                {{-- Placeholder untuk menjaga tinggi card jika konten di atasnya pendek --}}
                <div style="min-height: 50px;"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-6 mb-4"> {{-- Tambah mb-4 untuk margin bawah --}}
        <div class="card shadow h-100"> {{-- Tambah h-100 untuk tinggi seragam jika perlu --}}
            <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list-alt me-2"></i>
                    Pesanan Belum Diproses
                </h5>
                {{-- Pastikan route 'orders.status' sudah terdefinisi di web.php Anda --}}
                <a href="{{ route('orders.index.employee') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i> Lihat Semua
                </a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    {{-- Pastikan variabel $pendingOrders dilewatkan dari controller --}}
                    @forelse($pendingOrders as $item)
                    <a href="/orders/{{ $item['order']['id'] }}/employee-detail" class="list-group-item list-group-item-action border-0 py-3">
                        <div class="d-flex align-items-center">
                            <img src="{{ $item['sample_item']['thumbnail'] }}"
                                class="rounded-2 me-3 shadow-sm" width="50" height="50"
                                style="object-fit: cover; border: 1px solid #eee;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark">#{{ $item['order']['id'] }}</h6>
                                    <span class="text-primary fw-bold fs-6">Rp{{ number_format($item['order']['total_price']) }}</span>
                                </div>
                                <small class="text-muted d-block text-truncate mb-1" style="max-width: 90%;">
                                    {{ $item['sample_item']['product_name'] }}
                                </small>
                                <div>
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-1">
                                        <i class="far fa-clock me-1"></i>
                                        {{ \Carbon\Carbon::parse($item['order']['created_at'])->format('d M Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item border-0 py-4 text-center text-muted">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        Tidak ada pesanan yang perlu diproses saat ini.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Card Informasi Tambahan --}}
    <div class="col-12 col-md-6 col-lg-6 mb-4"> {{-- Kelas responsif untuk tata letak yang lebih baik --}}
        <div class="card shadow h-100"> {{-- Tambah h-100 untuk tinggi seragam jika perlu --}}
            <div class="card-header bg-info text-white d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <h5 class="mb-0">Informasi dan Panduan</h5>
            </div>
            <div class="card-body">
                <p class="lead text-center mb-4">Selamat datang di dashboard karyawan!</p>
                <p class="text-muted">Gunakan pemindai QR untuk memproses pesanan dengan cepat dan efisien.</p>
                <ul class="list-group list-group-flush border-bottom mb-3">
                    <li class="list-group-item">
                        <i class="fas fa-camera me-2 text-primary"></i>
                        Gunakan <strong>"Pindai Kode QR Pesanan (Kamera)"</strong> untuk pemindaian langsung.
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-image me-2 text-secondary"></i>
                        Gunakan <strong>"Pindai Kode QR dari Gambar"</strong> untuk mengunggah gambar.
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-barcode me-2 text-info"></i>
                        QR Code harus berisi ID pesanan dalam format angka.
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-wifi me-2 text-warning"></i>
                        Pastikan koneksi internet stabil untuk validasi pesanan.
                    </li>
                </ul>
                <p class="text-center small text-success mt-4">
                    <i class="fas fa-check-circle me-1"></i>
                    Siap melayani pelanggan Anda!
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- Pastikan Anda memuat library html5-qrcode --}}
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
    let html5QrCode; // Variabel global untuk instance Html5Qrcode
    let currentCameraId = null; // Menyimpan ID kamera yang sedang digunakan

    // Fungsi untuk menampilkan pesan alert di lokasi yang sesuai
    function displayScanResult(containerId, type, message, orderId = null) {
        const container = document.getElementById(containerId);
        let html = '';
        if (type === 'success') {
            html = `
                <div class="alert alert-success">
                    <strong>${message}</strong>
                    ${orderId ? `<br><a href="{{ url('/orders') }}/${orderId}/employee-detail" class="btn btn-sm btn-info mt-2">Lihat Detail Pesanan</a>` : ''}
                </div>
            `;
        } else if (type === 'warning') {
            html = `<div class="alert alert-warning">${message}</div>`;
        } else if (type === 'error') {
            html = `<div class="alert alert-danger">${message}</div>`;
        } else { // info or default
            html = `<div class="alert alert-info">${message}</div>`;
        }
        container.innerHTML = html;
    }

    // Fungsi callback saat QR code berhasil dipindai (dari kamera atau gambar)
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code matched = ${decodedText}`, decodedResult);
        displayScanResult('qr-reader-results', 'info', `<strong>Kode Terpindai:</strong> ${decodedText}<br>Mencari pesanan...`);
        document.getElementById('qr-image-results').innerHTML = ''; // Bersihkan hasil scan gambar jika ini dari kamera

        // Hentikan pemindai kamera setelah pemindaian berhasil (jika aktif)
        if (html5QrCode.isScanning) {
            html5QrCode.stop().then((ignore) => {
                console.log("QR Code scanner stopped after successful scan.");
                document.getElementById('stopScannerBtn').style.display = 'none';
                document.getElementById('startScannerBtn').style.display = 'block'; // Tampilkan tombol Start
            }).catch((err) => {
                console.error("Failed to stop QR Code scanner.", err);
            });
        }

        // Kirim ID pesanan ke backend Laravel
        sendOrderIdToBackend(decodedText);
    }

    // Fungsi callback saat pemindaian gagal (biasanya diabaikan untuk live scan, kecuali untuk feedback visual)
    function onScanFailure(error) {
        // console.warn(`QR Code scan error = ${error}`); // Terlalu banyak log jika aktif
        // Anda bisa menambahkan pesan feedback jika tidak ada QR code terdeteksi untuk waktu yang lama,
        // namun untuk live scan ini bisa sangat berisik. Lebih baik biarkan Html5Qrcode menangani UI error-nya.
    }

    // Fungsi untuk memulai pemindai kamera
    const startCameraScanner = (cameraId) => {
        if (!cameraId) {
            displayScanResult('qr-reader-results', 'warning', 'Pilih kamera terlebih dahulu.');
            return;
        }

        // Hentikan pemindai yang mungkin aktif sebelum memulai yang baru
        if (html5QrCode.isScanning) {
            html5QrCode.stop().then(() => {
                console.log("Existing camera scanner stopped before starting a new one.");
                startCameraExecution(cameraId);
            }).catch(err => {
                console.error("Error stopping existing scanner before starting new one:", err);
                startCameraExecution(cameraId); // Coba tetap mulai meskipun gagal menghentikan
            });
        } else {
            startCameraExecution(cameraId);
        }
    };

    const startCameraExecution = (cameraId) => {
        html5QrCode.start(
            cameraId,
            {
                fps: 10,    // Frames per second
                qrbox: { width: 250, height: 250 }, // Ukuran kotak pemindaian
                aspectRatio: 1.777778 // 16:9 - Opsional, untuk rasio aspek video
            },
            onScanSuccess,   // Callback untuk sukses scan
            onScanFailure    // Callback untuk gagal scan
        ).then(() => {
            console.log("Camera scanner started.");
            document.getElementById('stopScannerBtn').style.display = 'block';
            document.getElementById('startScannerBtn').style.display = 'none';
            displayScanResult('qr-reader-results', 'info', 'Pemindai kamera aktif. Arahkan ke QR Code.');
            document.getElementById('qr-image-results').innerHTML = '';
        }).catch(err => {
            console.error(`Unable to start scanning with camera: ${err}`);
            displayScanResult('qr-reader-results', 'error', `Gagal memulai kamera: ${err}. Pastikan izin kamera diberikan dan situs menggunakan HTTPS.`);
            document.getElementById('stopScannerBtn').style.display = 'none';
            document.getElementById('startScannerBtn').style.display = 'block';
        });
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        html5QrCode = new Html5Qrcode("qr-reader");
        const cameraSelection = document.getElementById('cameraSelection');

        // --- Logika Deteksi dan Pemilihan Kamera ---
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                cameraSelection.innerHTML = ''; // Bersihkan pilihan yang ada
                devices.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.id;
                    option.text = device.label || `Camera ${device.id}`;
                    cameraSelection.appendChild(option);
                });

                // Pilih kamera belakang secara default jika ada, atau kamera pertama
                let defaultCameraId = devices[0].id;
                const rearCamera = devices.find(device =>
                    device.label.toLowerCase().includes('back') ||
                    device.label.toLowerCase().includes('environment')
                );
                if (rearCamera) {
                    defaultCameraId = rearCamera.id;
                }
                cameraSelection.value = defaultCameraId;
                currentCameraId = defaultCameraId; // Atur kamera default saat inisialisasi

                // Mulai pemindai dengan kamera default saat halaman dimuat
                startCameraScanner(currentCameraId);

            } else {
                displayScanResult('qr-reader-results', 'error', 'Tidak ada kamera terdeteksi di perangkat ini.');
                document.getElementById('stopScannerBtn').style.display = 'none';
                document.getElementById('startScannerBtn').style.display = 'none';
                cameraSelection.innerHTML = '<option value="">Tidak ada kamera</option>';
                cameraSelection.disabled = true; // Nonaktifkan dropdown jika tidak ada kamera
            }
        }).catch(err => {
            console.error(`Error getting camera devices: ${err}`);
            displayScanResult('qr-reader-results', 'error', `Terjadi kesalahan saat mengakses perangkat kamera: ${err.message}.`);
            document.getElementById('stopScannerBtn').style.display = 'none';
            document.getElementById('startScannerBtn').style.display = 'none';
            cameraSelection.innerHTML = '<option value="">Gagal mendeteksi kamera</option>';
            cameraSelection.disabled = true;
        });

        // Event listener saat pilihan kamera berubah
        cameraSelection.addEventListener('change', (event) => {
            currentCameraId = event.target.value;
            // Jika kamera sedang berjalan, hentikan dan mulai ulang dengan kamera baru
            if (html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Scanner stopped due to camera change.");
                    startCameraScanner(currentCameraId);
                }).catch(err => {
                    console.error("Error stopping scanner on camera change:", err);
                    // Jika gagal menghentikan, coba tetap mulai dengan kamera baru
                    startCameraScanner(currentCameraId);
                });
            } else {
                // Jika tidak sedang berjalan, cukup mulai dengan kamera baru
                startCameraScanner(currentCameraId);
            }
        });

        // Event listener untuk tombol Stop Scanner (kamera)
        document.getElementById('stopScannerBtn').addEventListener('click', () => {
            if (html5QrCode.isScanning) {
                html5QrCode.stop().then((ignore) => {
                    console.log("QR Code scanner stopped by user.");
                    displayScanResult('qr-reader-results', 'info', 'Pemindai kamera dihentikan.');
                    document.getElementById('stopScannerBtn').style.display = 'none';
                    document.getElementById('startScannerBtn').style.display = 'block'; // Tampilkan tombol Start
                }).catch((err) => {
                    console.error("Failed to stop QR Code scanner.", err);
                });
            }
        });

        // Event listener untuk tombol Start Scanner (kamera)
        document.getElementById('startScannerBtn').addEventListener('click', () => {
            if (currentCameraId) {
                startCameraScanner(currentCameraId);
            } else {
                displayScanResult('qr-reader-results', 'error', 'Tidak ada kamera yang terdeteksi untuk memulai ulang.');
            }
        });

        // --- Logika Pemindai dari Gambar ---
        const qrImageFile = document.getElementById('qr-image-file');
        const qrImageResultsDiv = document.getElementById('qr-image-results');

        qrImageFile.addEventListener('change', e => {
            if (e.target.files.length === 0) {
                qrImageResultsDiv.innerHTML = '';
                return;
            }

            const imageFile = e.target.files[0];

            displayScanResult('qr-image-results', 'info', 'Sedang memindai gambar...');
            document.getElementById('qr-reader-results').innerHTML = ''; // Bersihkan hasil kamera

            // Fungsi pembantu untuk melakukan pemindaian gambar
            const performImageScan = () => {
                html5QrCode.scanFile(imageFile, true)
                    .then(decodedText => {
                        displayScanResult('qr-image-results', 'info', `<strong>Kode Terpindai dari Gambar:</strong> ${decodedText}<br>Mencari pesanan...`);
                        sendOrderIdToBackend(decodedText);
                        qrImageFile.value = ''; // Reset input file setelah berhasil
                    })
                    .catch(err => {
                        displayScanResult('qr-image-results', 'error', `Gagal memindai QR Code dari gambar. Pastikan gambar berisi QR Code yang jelas dan valid. Alasan: ${err}`);
                        console.error(`Error scanning file: ${err}`);
                        qrImageFile.value = ''; // Reset input file bahkan jika ada error
                    });
            };

            // --- PERBAIKAN KRUSIAL: Pastikan kamera berhenti SEBELUM mencoba memindai file ---
            if (html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Camera scanner stopped for image scan.");
                    document.getElementById('stopScannerBtn').style.display = 'none';
                    document.getElementById('startScannerBtn').style.display = 'block'; // Tampilkan tombol Start
                    performImageScan(); // Eksekusi pemindaian gambar HANYA SETELAH kamera dipastikan berhenti
                }).catch(err => {
                    console.error("Error stopping camera for image scan, attempting file scan anyway (might fail):", err);
                    performImageScan();
                });
            } else {
                performImageScan();
            }
        });
    });

    // Fungsi untuk mengirim ID pesanan ke backend
    async function sendOrderIdToBackend(orderId) {
        // Validasi input: pastikan orderId adalah angka
        if (!/^\d+$/.test(orderId)) {
            displayScanResult('qr-reader-results', 'warning', `QR Code "${orderId}" tidak valid. Harap pindai QR Code yang berisi ID pesanan dalam format angka.`);
            displayScanResult('qr-image-results', 'warning', `QR Code "${orderId}" tidak valid. Harap unggah gambar dengan QR Code yang berisi ID pesanan dalam format angka.`);
            return; // Hentikan proses jika tidak valid
        }

        try {
            const response = await fetch('{{ route('orders.scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                displayScanResult('qr-reader-results', 'success', `<strong>Pesanan Ditemukan!</strong><br>ID: ${data.order.id}`, data.order.id);
                displayScanResult('qr-image-results', 'success', `<strong>Pesanan Ditemukan!</strong><br>ID: ${data.order.id}`, data.order.id);

                // Opsional: Muat ulang bagian "Pending Orders" jika ada
                // Anda bisa membuat fungsi terpisah untuk memuat ulang daftar ini via AJAX
                // atau cukup refresh halaman untuk kesederhanaan.
                // window.location.reload();
            } else {
                const message = data.message || 'Pesanan tidak ditemukan atau terjadi kesalahan.';
                displayScanResult('qr-reader-results', 'error', message);
                displayScanResult('qr-image-results', 'error', message);
            }
        } catch (error) {
            console.error('Error sending order ID to backend:', error);
            const errorMessage = 'Terjadi kesalahan komunikasi dengan server. Pastikan koneksi internet Anda stabil.';
            displayScanResult('qr-reader-results', 'error', errorMessage);
            displayScanResult('qr-image-results', 'error', errorMessage);
        }
    }
</script>
@endsection
