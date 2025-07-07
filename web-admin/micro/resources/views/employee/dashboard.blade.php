@extends('employee')

@section('title', 'Dashboard Karyawan')

@section('content')
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">Dashboard</h2>
      </div>

<div class="row">
    <!-- Pending Orders -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Pesanan Belum Diproses</h6>
            <a href="{{ route('orders.status', 'pending') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
            @foreach($pendingOrders as $item)
            <a href="/orders/{{ $item['order']['id'] }}" class="list-group-item list-group-item-action border-0 py-3">
                <div class="d-flex align-items-center">
                <img src="{{ $item['sample_item']['thumbnail'] }}"
                        class="rounded-2 me-3" width="50" height="50"
                        style="object-fit: cover; border: 1px solid #eee;">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                    <h6 class="mb-1">#{{ $item['order']['id'] }}</h6>
                    <span class="text-primary fw-bold">Rp{{ number_format($item['order']['total_price']) }}</span>
                    </div>
                    <small class="text-muted">{{ $item['sample_item']['product_name'] }}</small>
                    <div class="mt-1">
                    <span class="badge bg-light text-dark">
                        <i class="far fa-clock me-1"></i>
                        {{ \Carbon\Carbon::parse($item['order']['created_at'])->format('d M Y') }}
                    </span>
                    </div>
                </div>
                </div>
            </a>
            @endforeach
            </div>
        </div>
        </div>
    </div>

    {{-- Card untuk Pemindai QR --}}
    <div class="col-12 col-md-6 col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex">
                <i class="fas fa-qrcode me-2"></i>
                <h6 class="mb-0 fw-bold">Pindai Kode QR Pesanan</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <label for="cameraSelection" class="form-label">Pilih Kamera:</label>
                    <select id="cameraSelection" class="form-select"></select>
                </div>
                <div id="qr-reader" style="width:100%;"></div>
                <div id="qr-reader-results" class="mt-3"></div>
                <button id="stopScannerBtn" class="btn btn-danger mt-3" style="display:none;">Stop Scanner</button>
                <button id="startScannerBtn" class="btn btn-primary mt-3" style="display:none;">Mulai Pemindai</button>
            </div>
            <div class="card-body text-center">
                <p class="text-muted">atau</p>
            </div>
            <div class="card-body text-center">
                <label class="form-label">Pilih kode QR dari perangkat:</label>
                <input type="file" class="form-control" id="qr-image-file" accept="image/*">
                <div id="qr-image-results" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
    let html5QrCode; // Variabel global untuk instance Html5Qrcode
    let currentCameraId = null; // Menyimpan ID kamera yang sedang digunakan

    // Fungsi callback saat QR code berhasil dipindai (dari kamera atau gambar)
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code matched = ${decodedText}`, decodedResult);
        document.getElementById('qr-reader-results').innerHTML = `
            <div class="alert alert-success">
                <strong>Kode Terpindai:</strong> ${decodedText}
            </div>
            <p>Mencari pesanan...</p>
        `;
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

    // Fungsi callback saat pemindaian gagal (biasanya diabaikan untuk live scan)
    function onScanFailure(error) {
        // console.warn(`QR Code scan error = ${error}`);
        // Anda bisa menambahkan pesan feedback jika tidak ada QR code terdeteksi untuk waktu yang lama
    }

    // Fungsi untuk memulai pemindai kamera
    const startCameraScanner = (cameraId) => {
        if (!cameraId) {
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-warning">Pilih kamera terlebih dahulu.</div>`;
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
            onScanSuccess,  // Callback untuk sukses scan
            onScanFailure   // Callback untuk gagal scan
        ).then(() => {
            console.log("Camera scanner started.");
            document.getElementById('stopScannerBtn').style.display = 'block';
            document.getElementById('startScannerBtn').style.display = 'none';
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-info">Pemindai kamera aktif. Arahkan ke QR Code.</div>`;
            document.getElementById('qr-image-results').innerHTML = '';
        }).catch(err => {
            console.error(`Unable to start scanning with camera: ${err}`);
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">Gagal memulai kamera: ${err}. Pastikan izin kamera diberikan dan situs menggunakan HTTPS.</div>`;
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
                document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">Tidak ada kamera terdeteksi di perangkat ini.</div>`;
                document.getElementById('stopScannerBtn').style.display = 'none';
                document.getElementById('startScannerBtn').style.display = 'none';
                cameraSelection.innerHTML = '<option value="">Tidak ada kamera</option>';
                cameraSelection.disabled = true; // Nonaktifkan dropdown jika tidak ada kamera
            }
        }).catch(err => {
            console.error(`Error getting camera devices: ${err}`);
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat mengakses perangkat kamera: ${err.message}.</div>`;
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
                    document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-info">Pemindai kamera dihentikan.</div>`;
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
                document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">Tidak ada kamera yang terdeteksi untuk memulai ulang.</div>`;
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

            qrImageResultsDiv.innerHTML = `<div class="alert alert-info">Sedang memindai gambar...</div>`;
            document.getElementById('qr-reader-results').innerHTML = ''; // Bersihkan hasil kamera

            // Fungsi pembantu untuk melakukan pemindaian gambar
            const performImageScan = () => {
                html5QrCode.scanFile(imageFile, true)
                    .then(decodedText => {
                        qrImageResultsDiv.innerHTML = `
                            <div class="alert alert-success">
                                <strong>Kode Terpindai dari Gambar:</strong> ${decodedText}
                            </div>
                            <p>Mencari pesanan...</p>
                        `;
                        sendOrderIdToBackend(decodedText);
                        qrImageFile.value = ''; // Reset input file setelah berhasil
                    })
                    .catch(err => {
                        qrImageResultsDiv.innerHTML = `<div class="alert alert-danger">Gagal memindai QR Code dari gambar. Alasan: ${err}</div>`;
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
                const successHtml = `
                    <div class="alert alert-success">
                        <strong>Pesanan Ditemukan!</strong><br>
                        ID: ${data.order.id}<br>
                        <br><a href="{{ url('/orders') }}/${data.order.id}/employee-detail" class="btn btn-sm btn-info mt-2">Lihat Detail Pesanan</a>
                    </div>
                `;
                document.getElementById('qr-reader-results').innerHTML = successHtml;
                document.getElementById('qr-image-results').innerHTML = successHtml;
            } else {
                const message = data.message || 'Pesanan tidak ditemukan atau terjadi kesalahan.';
                const errorHtml = `<div class="alert alert-danger">${message}</div>`;
                document.getElementById('qr-reader-results').innerHTML = errorHtml;
                document.getElementById('qr-image-results').innerHTML = errorHtml;
            }
        } catch (error) {
            console.error('Error sending order ID to backend:', error);
            const errorMessage = 'Terjadi kesalahan komunikasi dengan server.';
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">${errorMessage}</div>`;
            document.getElementById('qr-image-results').innerHTML = `<div class="alert alert-danger">${errorMessage}</div>`;
        }
    }
</script>
@endsection
