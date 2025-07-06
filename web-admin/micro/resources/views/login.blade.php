<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login Manajemen Digiberkat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
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

    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      padding: 20px;
    }

    .login-card {
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

    .login-card:hover {
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-header h2 {
      color: var(--dark-color);
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .login-header p {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .form-control {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s;
    }

    .form-control:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.25rem rgba(72, 149, 239, 0.25);
    }

    .form-label {
      font-weight: 600;
      color: var(--dark-color);
      margin-bottom: 0.5rem;
    }

    .btn-login {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      padding: 12px;
      font-weight: 600;
      letter-spacing: 0.5px;
      border-radius: 8px;
      transition: all 0.3s;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
    }

    .role-selector {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #e0e0e0;
    }

    .role-option {
      flex: 1;
      text-align: center;
      padding: 10px;
      cursor: pointer;
      transition: all 0.3s;
      background: white;
    }

    .role-option input {
      display: none;
    }

    .role-option label {
      display: block;
      cursor: pointer;
      padding: 8px;
      border-radius: 5px;
      transition: all 0.3s;
    }

    .role-option input:checked + label {
      background: var(--primary-color);
      color: white;
    }

    .login-footer {
      text-align: center;
      margin-top: 1.5rem;
      color: #6c757d;
      font-size: 0.9rem;
    }

    .login-footer a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
    }

    .login-footer a:hover {
      text-decoration: underline;
    }

    .alert {
      border-radius: 8px;
      padding: 12px 15px;
    }

    .password-input-container {
      position: relative;
    }

    .password-toggle-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6c757d;
      cursor: pointer;
      padding: 5px;
      z-index: 2;
    }

    .password-toggle-btn:focus {
      outline: none;
    }

    @media (max-width: 576px) {
      .login-card {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card animate__animated animate__fadeIn">
      <div class="login-header">
        <h2>Selamat Datang</h2>
        <p>Silakan masuk untuk mengakses sistem</p>
      </div>

      {{-- Menampilkan pesan error --}}
      @if ($errors->any())
        <div class="alert alert-danger animate__animated animate__shakeX">
          <i class="fas fa-exclamation-circle me-2"></i>
          {{ $errors->first() }}
        </div>
      @endif

      @if(session('success'))
        <div class="alert alert-success animate__animated animate__fadeIn">
          <i class="fas fa-check-circle me-2"></i>
          {{ session('success') }}
        </div>
      @endif

      <form action="{{ route('login.authenticate') }}" method="POST">
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

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="password-input-container">
            <input type="password" class="form-control" id="password"
                   name="password" placeholder="Masukkan password" required>
            <button type="button" class="password-toggle-btn" id="togglePassword">
              <i class="far fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Masuk Sebagai</label>
          <div class="role-selector">
            <div class="role-option">
              <input type="radio" id="role_admin" name="role" value="admin"
                     {{ old('role') == 'admin' ? 'checked' : '' }} required>
              <label for="role_admin">Admin</label>
            </div>
            <div class="role-option">
              <input type="radio" id="role_employee" name="role" value="employee"
                     {{ old('role') == 'employee' ? 'checked' : '' }}>
              <label for="role_employee">Karyawan</label>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
            <label class="form-check-label" for="rememberMe">Ingat saya</label>
          </div>
          {{-- <a href="{{ route('password.request') }}" class="text-decoration-none">Lupa password?</a> --}}
        </div>

        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
          <i class="fas fa-sign-in-alt me-2"></i>Masuk
        </button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Toggle password visibility
      const togglePassword = document.querySelector('#togglePassword');
      const passwordInput = document.querySelector('#password');

      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle eye icon
        if (type === 'password') {
          this.innerHTML = '<i class="far fa-eye"></i>';
        } else {
          this.innerHTML = '<i class="far fa-eye-slash"></i>';
        }
      });

      // Form validation
      const form = document.querySelector('form');
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
