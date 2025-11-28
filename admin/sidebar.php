<!-- sidebar.php -->
<?php
// Dapatkan nama file yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar bg-white shadow-lg w-64 flex flex-col">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200 bg-white">
        <div class="flex items-center">
            <img src="../assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
            <span class="text-xl font-bold text-gray-800 sidebar-text">Krishna Driving</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 bg-white">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'index.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-tachometer-alt w-6 mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="pendaftaran.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'pendaftaran.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-users w-6 mr-3"></i>
                    <span class="sidebar-text">Pendaftaran</span>
                </a>
            </li>
            
            <li>
                <a href="pembayaran.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'pembayaran.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-credit-card w-6 mr-3"></i>
                    <span class="sidebar-text">Pembayaran</span>
                </a>
            </li>

            <!-- Jadwal & Kemajuan Dropdown -->
            <li class="relative">
                <a href="#" onclick="toggleSubmenu(this)" class="flex items-center justify-between px-4 py-3 rounded-lg font-medium <?= in_array($current_page, ['jadwal.php', 'kemajuan.php', 'evaluasi.php', 'kehadiran.php']) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt w-6 mr-3"></i>
                        <span class="sidebar-text">Jadwal & Kemajuan</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
                </a>
                <ul class="ml-6 mt-2 space-y-1 submenu hidden">
                    <li>
                        <a href="jadwal.php" class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'jadwal.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                            <i class="fas fa-clock w-5 mr-3 text-sm"></i>
                            <span class="sidebar-text text-sm">Kelola Jadwal</span>
                        </a>
                    </li>
                    <li>
                        <a href="kemajuan.php" class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'kemajuan.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                            <i class="fas fa-chart-line w-5 mr-3 text-sm"></i>
                            <span class="sidebar-text text-sm">Kemajuan Belajar</span>
                        </a>
                    </li>
                    <li>
                        <a href="evaluasi.php" class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'evaluasi.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                            <i class="fas fa-clipboard-check w-5 mr-3 text-sm"></i>
                            <span class="sidebar-text text-sm">Evaluasi Skill</span>
                        </a>
                    </li>
                    <li>
                        <a href="kehadiran.php" class="flex items-center px-4 py-2 rounded-lg font-medium <?= $current_page == 'kehadiran.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                            <i class="fas fa-user-check w-5 mr-3 text-sm"></i>
                            <span class="sidebar-text text-sm">Kehadiran</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li>
                <a href="testimoni.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'testimoni.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-star w-6 mr-3"></i>
                    <span class="sidebar-text">Testimoni</span>
                </a>
            </li>
            
            <li>
                <a href="galeri.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'galeri.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-images w-6 mr-3"></i>
                    <span class="sidebar-text">Galeri</span>
                </a>
            </li>
            
            <li>
                <a href="paket.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'paket.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-box w-6 mr-3"></i>
                    <span class="sidebar-text">Paket Kursus</span>
                </a>
            </li>
            
            <li>
                <a href="instruktur.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'instruktur.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-chalkboard-teacher w-6 mr-3"></i>
                    <span class="sidebar-text">Instruktur</span>
                </a>
            </li>
            
            <li>
                <a href="export.php" class="flex items-center px-4 py-3 rounded-lg font-medium <?= $current_page == 'export.php' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fas fa-file-export w-6 mr-3"></i>
                    <span class="sidebar-text">Export Data</span>
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
        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium transition duration-300">
            <i class="fas fa-sign-out-alt w-6 mr-3"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</div>

<style>
.sidebar {
    transition: all 0.3s ease;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .sidebar-text {
    display: none;
}

.sidebar.collapsed .submenu {
    display: none !important;
}

.submenu {
    transition: all 0.3s ease;
    max-height: 0;
    overflow: hidden;
}

.submenu.open {
    max-height: 500px;
}

.fa-chevron-down.rotate-180 {
    transform: rotate(180deg);
}
</style>

<script>
// Function to toggle submenu
function toggleSubmenu(element) {
    event.preventDefault();
    
    const submenu = element.nextElementSibling;
    const chevron = element.querySelector('.fa-chevron-down');
    
    // Toggle submenu visibility
    submenu.classList.toggle('hidden');
    submenu.classList.toggle('open');
    
    // Rotate chevron icon
    chevron.classList.toggle('rotate-180');
}

// Auto-open submenu if current page is in the submenu items
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = '<?= $current_page ?>';
    const submenuPages = ['jadwal.php', 'kemajuan.php', 'evaluasi.php', 'kehadiran.php'];
    
    if (submenuPages.includes(currentPage)) {
        const jadwalMenu = document.querySelector('a[href="#"]');
        if (jadwalMenu) {
            const submenu = jadwalMenu.nextElementSibling;
            const chevron = jadwalMenu.querySelector('.fa-chevron-down');
            
            submenu.classList.remove('hidden');
            submenu.classList.add('open');
            chevron.classList.add('rotate-180');
        }
    }
});

// Sidebar toggle functionality (jika ada tombol toggle)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
});
</script>