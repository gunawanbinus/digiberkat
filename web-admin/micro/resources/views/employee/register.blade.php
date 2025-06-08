<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register Employee - Sistem Manajemen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    /* Your existing CSS styles */
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4895ef;
      --danger-color: #f72585;
      --light-color: #f8f9fa;
      --dark-color: #212529;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .register-container {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      padding: 20px;
    }

    .register-card {
      background: white;
      border-radius: 15px;
      padding: 2.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 450px;
      transition: all 0.3s ease;
      border: none;
      position: relative;
      overflow: hidden;
    }

    /* Rest of your CSS styles */
  </style>

    @if(session('delayed_redirect'))
    <meta http-equiv="refresh" content="4;url={{ session('redirect_url') }}">
    @endif
</head>
<body>
  <div class="register-container">
    <div class="register-card animate__animated animate__fadeIn">
      <div class="register-header">
        <h2>Daftar Karyawan Baru</h2>
        <p>Silakan isi formulir berikut untuk mendaftar</p>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger animate__animated animate__shakeX">
          <i class="fas fa-exclamation-circle me-2"></i>
          {{ $errors->first() }}
        </div>
      @endif

      {{-- @if(session('success'))
        <div class="alert alert-success animate__animated animate__fadeIn">
          <i class="fas fa-check-circle me-2"></i>
          {{ session('success') }}
        </div>
      @endif --}}

     @if(session('success'))
        <div class="alert alert-success animate__animated animate__fadeIn">
          <i class="fas fa-check-circle me-2"></i>
          {{ session('success') }}
        </div>

        <div class="text-center mt-3">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Mengarahkan ke halaman login...</p>
        </div>

        <script>
          // Optional: More precise JavaScript redirect
          setTimeout(function() {
            window.location.href = "{{ session('redirect_url') }}";
          }, {{ session('redirect_time', 4000) }});
        </script>
      @endif

      <form action="{{ route('employee.register.do') }}" method="POST" id="registerForm">
        @csrf

        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email"
                 placeholder="contoh@email.com" required
                 value="{{ old('email') }}">
          <div class="invalid-feedback">
            Harap masukkan email yang valid
          </div>
        </div>

        <div class="mb-3 password-toggle">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password"
                 name="password" placeholder="Masukkan password" required
                 minlength="6">
          <span class="password-toggle-icon" id="togglePassword">
            <i class="far fa-eye"></i>
          </span>
          <small class="text-muted">Password harus minimal 6 karakter</small>
        </div>

        <div class="mb-3">
          <label for="position_name" class="form-label">Posisi</label>
          <select class="form-select" id="position_name" name="position_name" required>
            <option value="" disabled selected>Pilih posisi</option>
            @foreach($positions as $position)
              <option value="{{ $position['position_name'] }}"
                {{ old('position_name') == $position['position_name'] ? 'selected' : '' }}>
                {{ $position['position_name'] }}
              </option>
            @endforeach
          </select>
          <div class="invalid-feedback">
            Harap pilih posisi
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
          <i class="fas fa-user-plus me-2"></i>Daftar
        </button>

        <div class="register-footer">
          Kembali ke <a href="{{ route('login') }}">Halaman Login</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Toggle password visibility
      const togglePassword = document.querySelector('#togglePassword');
      const password = document.querySelector('#password');

      togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
      });

      // Form validation
      const form = document.getElementById('registerForm');
      form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  </script>
</body>
</html>
