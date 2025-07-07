@php $user = currentUser(); @endphp {{--Lihat bagian app/Helpers/helper.php--}}

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary shadow-sm">
    <!-- Navbar Brand -->
    <a class="navbar-brand ps-2 d-flex align-items-center") }}">
        <i class="fas fa-shield-alt me-2"></i>
        <span class="fw-bold">Digiberkat</span>
    </a>

    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
        <i class="fas fa-bars text-white"></i>
    </button>

    <!-- Search Form -->
    <!--<form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <input class="form-control border-0 shadow-none" type="text" placeholder="Cari..." aria-label="Search">
            <button class="btn btn-light" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>-->

    <!-- Navbar Right -->
    <ul class="navbar-nav ms-auto me-0 me-md-3 my-2 my-md-0">
        <!--<li class="nav-item dropdown ms-2">
            <a class="nav-link" href="#" id="themeToggle">
                <i class="fas fa-moon"></i>
            </a>
        </li>-->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="position-relative">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(currentUser('username') ?? 'Guest') }}&background=random"
     class="rounded-circle me-2" width="32" height="32">
                    <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-white rounded-circle"></span>
                </div>
                <span class="d-none d-lg-inline ms-1">{{ currentUser('username') ?? 'Guest' }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdown">
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item d-flex align-items-center w-100">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<style>
    .sb-topnav {
        padding: 0.5rem 1rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
    }
    .sb-topnav .navbar-brand {
        font-weight: 600;
        font-size: 1.2rem;
    }
    .sb-topnav .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.8);
        transition: all 0.2s;
    }
    .sb-topnav .navbar-nav .nav-link:hover {
        color: rgba(255, 255, 255, 1);
    }
    .sb-topnav .dropdown-menu {
        border: none;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
    .sb-topnav .input-group .form-control {
        border-radius: 2rem 0 0 2rem;
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }
    .sb-topnav .input-group .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    .sb-topnav .input-group .btn {
        border-radius: 0 2rem 2rem 0;
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }
</style>
