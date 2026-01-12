<?php
// Dapatkan nama file yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Container -->
<div class="sidebar-container">
    <!-- Sidebar -->
    <div id="sidebar"
        class="sidebar bg-white shadow-lg w-64 flex flex-col fixed lg:relative h-screen z-50 left-0 top-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Logo dengan close button untuk mobile -->
        <div class="p-6 border-b border-gray-200 bg-white flex items-center justify-between">
            <div class="flex items-center">
                <img src="../assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                <span class="text-xl font-bold text-gray-800 sidebar-text">Krishna Kursus</span>
            </div>
            <!-- Tombol Close untuk Mobile -->
            <button id="close-sidebar"
                class="lg:hidden text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-2 rounded-lg transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 bg-white overflow-y-auto">
            <ul class="space-y-2">
                <!-- Dashboard -->
                <li>
                    <a href="index.php"
                        class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'index.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                        <i class="fas fa-tachometer-alt w-6 mr-3"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>

                <!-- Kelompok Siswa -->
                <li class="border-b border-gray-200 pb-1">
                    <button type="button"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-lg font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50 accordion-button"
                        onclick="toggleAccordion('siswa-menu')">
                        <div class="flex items-center">
                            <i class="fas fa-users w-6 mr-3"></i>
                            <span class="sidebar-text">Siswa</span>
                        </div>
                        <i class="fas fa-chevron-right text-sm transition-transform duration-300"></i>
                    </button>

                    <ul id="siswa-menu" class="pl-8 mt-1 space-y-1 hidden">
                        <li>
                            <a href="pendaftaran.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'pendaftaran.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-user-plus w-5 mr-2"></i>
                                <span class="sidebar-text">Pendaftaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="pembayaran.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'pembayaran.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-credit-card w-5 mr-2"></i>
                                <span class="sidebar-text">Pembayaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="jadwal.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'jadwal.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-calendar-alt w-5 mr-2"></i>
                                <span class="sidebar-text">Jadwal & Kehadiran</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Kelompok Konten -->
                <li class="border-b border-gray-200 pb-1">
                    <button type="button"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-lg font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50 accordion-button"
                        onclick="toggleAccordion('konten-menu')">
                        <div class="flex items-center">
                            <i class="fas fa-folder-open w-6 mr-3"></i>
                            <span class="sidebar-text">Konten</span>
                        </div>
                        <i class="fas fa-chevron-right text-sm transition-transform duration-300"></i>
                    </button>

                    <ul id="konten-menu" class="pl-8 mt-1 space-y-1 hidden">
                        <li>
                            <a href="paket.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'paket.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-box w-5 mr-2"></i>
                                <span class="sidebar-text">Paket Kursus</span>
                            </a>
                        </li>
                        <li>
                            <a href="instruktur.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'instruktur.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-chalkboard-teacher w-5 mr-2"></i>
                                <span class="sidebar-text">Instruktur</span>
                            </a>
                        </li>
                        <li>
                            <a href="kendaraan.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'kendaraan.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-car w-5 mr-2"></i>
                                <span class="sidebar-text">Kendaraan</span>
                            </a>
                        </li>
                        <li>
                            <a href="testimoni.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'testimoni.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-star w-5 mr-2"></i>
                                <span class="sidebar-text">Testimoni</span>
                            </a>
                        </li>
                        <li>
                            <a href="galeri.php"
                                class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'galeri.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                                <i class="fas fa-images w-5 mr-2"></i>
                                <span class="sidebar-text">Galeri</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Tentang & Kontak -->
                <li>
                    <a href="tentang-kontak.php"
                        class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'tentang-kontak.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                        <i class="fas fa-info-circle w-6 mr-3"></i>
                        <span class="sidebar-text">Tentang & Kontak</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User Info & Logout -->
        <div class="p-4 border-t border-gray-200 bg-white mt-auto">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <div class="ml-3 sidebar-text">
                    <p class="text-sm font-medium text-gray-900"><?= $_SESSION['admin_username'] ?? 'Admin' ?></p>
                    <p class="text-xs text-gray-500">Administrator</p>
                </div>
            </div>
            <a href="logout.php"
                class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium transition duration-300">
                <i class="fas fa-sign-out-alt w-6 mr-3"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
</div>

<style>
    /* Sidebar Animation */
    #sidebar {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Overlay Animation */
    #sidebar-overlay {
        transition: opacity 0.3s ease;
    }

    /* Mobile Button Styles - STANDARD UNTUK SEMUA HALAMAN */
    .sidebar-toggle-btn {
        padding: 0.75rem;
        border-radius: 0.75rem;
        background-color: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        color: #4B5563;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
    }

    .sidebar-toggle-btn:hover {
        background-color: #F3F4F6;
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .sidebar-toggle-btn i {
        font-size: 1.25rem;
    }

    /* Accordion Styling */
    .accordion-button.active {
        @apply text-blue-600 bg-blue-50;
    }

    .accordion-button .fa-chevron-right {
        transition: transform 0.3s ease;
    }

    .accordion-button.active .fa-chevron-right {
        transform: rotate(90deg);
    }

    /* Responsive */
    @media (max-width: 1023px) {
        body.sidebar-open {
            overflow: hidden;
        }
    }

    @media (min-width: 1024px) {
        #sidebar {
            position: relative;
            transform: translateX(0) !important;
        }

        #sidebar-overlay {
            display: none !important;
        }

        .sidebar-toggle-btn {
            display: none !important;
        }
    }

    /* Floating Mobile Menu Button */
    #mobile-menu-button {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 3.5rem;
        height: 3.5rem;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 40;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    #mobile-menu-button:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    }

    #mobile-menu-button i {
        font-size: 1.25rem;
    }

    /* Desktop Layout */
    @media (min-width: 1024px) {
        .main-content-with-sidebar {
            margin-left: 16rem;
            /* 64 * 4 = 256px = 16rem */
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const closeBtn = document.getElementById('close-sidebar');

    // Function to open sidebar
    function openSidebar() {
        sidebar.style.transform = 'translateX(0)';
        overlay.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
    }

    // Function to close sidebar
    function closeSidebar() {
        sidebar.style.transform = 'translateX(-100%)';
        overlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
    }

    // UNIVERSAL TOGGLE FUNCTION
    function setupSidebarToggle() {
        const toggleButtons = document.querySelectorAll('.sidebar-toggle-btn, #sidebar-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.stopPropagation();
                if (sidebar.style.transform === 'translateX(0px)' || sidebar.style.transform === '') {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        });
    }

    // Setup tombol close sidebar
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    // Setup overlay untuk close
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    setupSidebarToggle();

    // Auto-close sidebar on mobile when clicking a link
    if (window.innerWidth < 1024) {
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }

    // Create floating mobile menu button if needed
    function ensureMobileButton() {
        if (window.innerWidth < 1024) {
            if (!document.getElementById('mobile-menu-button')) {
                const mobileButton = document.createElement('button');
                mobileButton.id = 'mobile-menu-button';
                mobileButton.className = 'lg:hidden';
                mobileButton.innerHTML = '<i class="fas fa-bars"></i>';
                mobileButton.addEventListener('click', openSidebar);
                document.body.appendChild(mobileButton);
            }
        } else {
            const btn = document.getElementById('mobile-menu-button');
            if (btn) btn.remove();
        }
    }

    ensureMobileButton();
    window.addEventListener('resize', ensureMobileButton);

    // === ACCORDION LOGIC ===
    window.toggleAccordion = function(menuId) {
        const menu = document.getElementById(menuId);
        const button = menu.previousElementSibling;

        // Toggle active class on button
        button.classList.toggle('active');

        // Toggle visibility
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    };

    // Auto-open accordion if current page is inside it
    const currentPage = window.location.pathname.split('/').pop();
    const siswaPages = ['pendaftaran.php', 'pembayaran.php', 'jadwal.php'];
    const kontenPages = ['testimoni.php', 'galeri.php', 'paket.php', 'instruktur.php'];

    if (siswaPages.includes(currentPage)) {
        toggleAccordion('siswa-menu');
    } else if (kontenPages.includes(currentPage)) {
        toggleAccordion('konten-menu');
    }
});
</script>