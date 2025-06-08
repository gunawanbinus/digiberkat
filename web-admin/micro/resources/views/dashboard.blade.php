<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

  <div class="container mt-5">
    <div class="row">
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Welcome, {{ currentUser('username') ?? 'Guest' }}</h5>
            <p class="card-text">Your account dashboard</p>
            <a href="{{ route('account') }}" class="btn btn-primary">Go to Account</a>
          </div>
        </div>
      </div>

      <div class="col-md-9">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">About Me</h4>
            <p>Welcome to your dashboard. This is where you can update your profile and personal information.</p>
            <a href="{{ route('account') }}" class="btn btn-info">Update Account Info</a>
            <a href="{{ url('/productss') }}" class="btn btn-success mt-3">Go to Products</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
