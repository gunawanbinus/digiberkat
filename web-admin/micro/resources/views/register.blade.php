<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(-45deg, #74ebd5, #ACB6E5, #fbc2eb, #a6c1ee);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      overflow: hidden;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .register-card {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      z-index: 10;
    }
  </style>
</head>
<body>

  <div class="register-card position-relative">
    <h4 class="text-center mb-4">Register</h4>

    {{-- Menampilkan error dari Laravel --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Menampilkan pesan sukses --}}
    @if (session('success'))
        <div class="alert alert-success">
            Registration successful!
        </div>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="btn btn-primary">Login Sekarang</a>
        </div>
    @endif

    <form action="{{ route('register.do') }}" method="POST">
      @csrf

      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" value="{{ old('name') }}" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" value="{{ old('email') }}" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="agreeCheck" required>
        <label class="form-check-label" for="agreeCheck">I agree to the terms</label>
      </div>

      <button type="submit" class="btn btn-success w-100">Register</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Menunggu DOM selesai dimuat
    document.addEventListener("DOMContentLoaded", function() {
      // Cek jika ada alert
      const alert = document.querySelector('.alert');
      if (alert) {
        // Set timeout untuk menghilangkan alert setelah 3 detik
        setTimeout(function() {
          alert.style.display = 'none';
        }, 3000); // 3000 milidetik = 3 detik
      }
    });
  </script>


</body>
</html>
