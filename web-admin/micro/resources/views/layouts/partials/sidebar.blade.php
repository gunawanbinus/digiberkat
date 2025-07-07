@php $user = currentUser(); @endphp {{--Lihat bagian app/Helpers/helper.php--}}
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Utama</div>
                <a class="nav-link" href="{{ url('admin/dashboard') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Manajemen</div>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProduk">
                    <div class="sb-nav-link-icon"><i class="fas fa-box-open"></i></div>
                    Produk
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProduk">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('products.index') }}">Semua Produk</a>
                        <a class="nav-link" href="{{ route('categories.index') }}">Berdasarkan Kategori</a>
                        <a class="nav-link" href="{{ route('products.create') }}">Tambah Produk</a>
                    </nav>
                </div>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseKategori">
                    <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                    Kategori
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseKategori">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('categories.index') }}">Semua Kategori</a>
                        <a class="nav-link" href="#">Tambah Kategori</a>
                    </nav>
                </div>

                <a class="nav-link" href="{{ route('orders.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-shopping-bag"></i></div>
                    Pesanan
                </a>
                <a class="nav-link" href="{{ route('restock.requests') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-arrow-rotate-left"></i></div>
                    Permintaan Restok
                </a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseEmployee">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-tie"></i></div>
                    Karyawan
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseEmployee">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('employee.index') }}">Semua Karyawan</a>
                        <a class="nav-link" href="{{ route('employee.register')}}">Tambah Karyawan</a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Masuk sebagai:</div>
            {{ currentUser('username') ?? 'Guest' }} {{--Lihat bagian app/Helpers/helper.php--}}
        </div>
    </nav>
</div>

<style>
    .sb-sidenav {
        background: linear-gradient(180deg, #2c3e50, #1a252f);
        color: rgba(255, 255, 255, 0.8);
    }
    .sb-sidenav .sb-sidenav-menu-heading {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1.5rem 1rem 0.5rem;
    }
    .sb-sidenav .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem 1rem;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    .sb-sidenav .nav-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.05);
    }
    .sb-sidenav .nav-link.active {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border-left: 3px solid #0d6efd;
    }
    .sb-sidenav .sb-nav-link-icon {
        color: rgba(255, 255, 255, 0.5);
    }
    .sb-sidenav .sb-sidenav-collapse-arrow {
        color: rgba(255, 255, 255, 0.5);
    }
    .sb-sidenav .nav-link.collapsed .sb-sidenav-collapse-arrow {
        transform: rotate(0deg);
    }
    .sb-sidenav .nav-link:not(.collapsed) .sb-sidenav-collapse-arrow {
        transform: rotate(180deg);
    }
    .sb-sidenav-footer {
        background: rgba(0, 0, 0, 0.2);
        padding: 1rem;
    }
</style>
