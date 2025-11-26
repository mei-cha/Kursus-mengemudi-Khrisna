<!-- Sidebar -->
<div class="sidebar bg-white shadow-lg w-64 flex flex-col">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
            <i class="fas fa-car text-2xl text-blue-600 mr-3"></i>
            <span class="sidebar-text text-xl font-bold text-gray-800">Krishna Driving</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-tachometer-alt w-6 mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="pendaftaran.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-users w-6 mr-3"></i>
                    <span class="sidebar-text">Pendaftaran</span>
                </a>
            </li>
            <li>
                <a href="testimoni.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-star w-6 mr-3"></i>
                    <span class="sidebar-text">Testimoni</span>
                </a>
            </li>
            <li>
                <a href="galeri.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-images w-6 mr-3"></i>
                    <span class="sidebar-text">Galeri</span>
                </a>
            </li>
            <li>
                <a href="paket.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-box w-6 mr-3"></i>
                    <span class="sidebar-text">Paket Kursus</span>
                </a>
            </li>
            <li>
                <a href="instruktur.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-chalkboard-teacher w-6 mr-3"></i>
                    <span class="sidebar-text">Instruktur</span>
                </a>
            </li>
            <li>
                <a href="pembayaran.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-credit-card w-6 mr-3"></i>
                    <span class="sidebar-text">Pembayaran</span>
                </a>
            </li>
            <li>
                <a href="export.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg font-medium">
                    <i class="fas fa-file-export w-6 mr-3"></i>
                    <span class="sidebar-text">Export Data</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- User Info & Logout -->
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-blue-600"></i>
            </div>
            <div class="ml-3 sidebar-text">
                <p class="text-sm font-medium text-gray-900"><?= $_SESSION['admin_username'] ?></p>
                <p class="text-xs text-gray-500">Administrator</p>
            </div>
        </div>
        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium">
            <i class="fas fa-sign-out-alt w-6 mr-3"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</div>