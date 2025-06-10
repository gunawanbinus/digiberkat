<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <a class="nav-link" href="{{ url('admin/dashboard') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProduk" aria-expanded="false">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Produk
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProduk" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('products.index') }}">Semua</a>
                        <a class="nav-link" href="{{ route('categories.index') }}">Berdasarkan Kategori</a>
                        <a class="nav-link" href="#">Stok Rendah dan Habis</a>
                        <a class="nav-link" href="{{ route('products.create') }}">Tambahkan Produk Baru</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseKategori" aria-expanded="false">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Kategori
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseKategori" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('categories.index') }}">Semua</a>
                        <a class="nav-link" href="{{ route('categories.index') }}">Tambahkan Kategori Baru</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePesanan" aria-expanded="false">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Pesanan
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePesanan" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="{{ route('orders.index') }}">Semua</a>
                        <a class="nav-link" href="{{ route('orders.index') }}">Belum Diproses</a>
                    </nav>
                </div>
                <a class="nav-link" href="#">
                    <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                    Permintaan Restok
                </a>

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Masuk sebagai:</div>
            {{ currentUser('username') ?? 'Guest' }}
        </div>
    </nav>
</div>
