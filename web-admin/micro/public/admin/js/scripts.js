document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        // Check localStorage for sidebar state
        if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
            document.body.classList.add('sb-sidenav-toggled');
        }

        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));

            // Dispatch custom event for other components
            const event = new Event('sidebarToggled');
            document.dispatchEvent(event);
        });
    }

    // Active menu item highlighting
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sb-sidenav .nav-link');

    navLinks.forEach(link => {
        if (link.href && currentPath.startsWith(link.getAttribute('href'))) {
            link.classList.add('active');

            // Expand parent collapse if exists
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                const trigger = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                if (trigger) {
                    trigger.classList.remove('collapsed');
                }
            }
        }
    });

    // Responsive adjustments
    function handleResize() {
        if (window.innerWidth < 768) {
            document.body.classList.add('sb-sidenav-toggled');
        } else {
            // Only restore if user hasn't explicitly toggled
            if (localStorage.getItem('sb|sidebar-toggle') !== 'true') {
                document.body.classList.remove('sb-sidenav-toggled');
            }
        }
    }

    // Initial check
    handleResize();

    // Listen for resize events
    window.addEventListener('resize', handleResize);

    // Custom event listener for other components
    document.addEventListener('sidebarToggled', function() {
        // You can add additional functionality here
        console.log('Sidebar toggled');
    });
});

// Fungsi untuk toggle tema
function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('themeToggle').querySelector('i');

    if (html.getAttribute('data-bs-theme') === 'light') {
        // Switch ke dark mode
        html.setAttribute('data-bs-theme', 'dark');
        icon.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('theme', 'dark');
    } else {
        // Switch ke light mode
        html.setAttribute('data-bs-theme', 'light');
        icon.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('theme', 'light');
    }
}

// Fungsi untuk menginisialisasi tema dari localStorage
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'dark'; // Default dark theme

    document.documentElement.setAttribute('data-bs-theme', savedTheme);

    const icon = document.getElementById('themeToggle').querySelector('i');
    if (savedTheme === 'light') {
        icon.classList.replace('fa-moon', 'fa-sun');
    } else {
        icon.classList.replace('fa-sun', 'fa-moon');
    }
}

// Event listener untuk tombol toggle
document.getElementById('themeToggle').addEventListener('click', function(e) {
    e.preventDefault();
    toggleTheme();
});

// Inisialisasi tema saat halaman dimuat
document.addEventListener('DOMContentLoaded', initializeTheme);
