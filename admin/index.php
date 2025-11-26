<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Get statistics
$total_pendaftaran = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa")->fetch()['total'];
$pendaftaran_baru = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa WHERE status_pendaftaran = 'baru'")->fetch()['total'];
$total_testimoni = $db->query("SELECT COUNT(*) as total FROM testimoni")->fetch()['total'];
$testimoni_menunggu = $db->query("SELECT COUNT(*) as total FROM testimoni WHERE status = 'menunggu'")->fetch()['total'];

// Get recent registrations
$recent_pendaftaran = $db->query("SELECT * FROM pendaftaran_siswa ORDER BY dibuat_pada DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .main-content {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen absolute inset-0">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden relative">
            <!-- Top Header -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                        <p class="text-gray-600">Selamat datang di panel admin Krishna Driving</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900"><?= $_SESSION['admin_username'] ?></p>
                            <p class="text-xs text-gray-500"><?= date('l, d F Y') ?></p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition duration-300">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Pendaftaran</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_pendaftaran ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition duration-300">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-user-plus text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pendaftaran Baru</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $pendaftaran_baru ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition duration-300">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-star text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Testimoni</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_testimoni ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition duration-300">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <i class="fas fa-clock text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Testimoni Menunggu</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $testimoni_menunggu ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Pendaftaran Terbaru</h3>
                            <a href="pendaftaran.php" class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                Lihat semua →
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Pendaftaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_pendaftaran as $pendaftaran): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $pendaftaran['nomor_pendaftaran'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($pendaftaran['nama_lengkap']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $pendaftaran['telepon'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d M Y', strtotime($pendaftaran['dibuat_pada'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_badges = [
                                            'baru' => 'bg-yellow-100 text-yellow-800',
                                            'dikonfirmasi' => 'bg-blue-100 text-blue-800',
                                            'diproses' => 'bg-purple-100 text-purple-800',
                                            'selesai' => 'bg-green-100 text-green-800',
                                            'dibatalkan' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_class = $status_badges[$pendaftaran['status_pendaftaran']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                            <?= ucfirst($pendaftaran['status_pendaftaran']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="pendaftaran_detail.php?id=<?= $pendaftaran['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="pendaftaran_edit.php?id=<?= $pendaftaran['id'] ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Kelola Pendaftaran</h4>
                                <p class="text-gray-600 text-sm mb-4">Lihat dan proses pendaftaran baru</p>
                                <a href="pendaftaran.php" class="inline-flex items-center text-blue-600 hover:text-blue-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Testimoni</h4>
                                <p class="text-gray-600 text-sm mb-4">Approve testimoni dari siswa</p>
                                <a href="testimoni.php" class="inline-flex items-center text-green-600 hover:text-green-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-star text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Galeri</h4>
                                <p class="text-gray-600 text-sm mb-4">Upload foto aktivitas</p>
                                <a href="galeri.php" class="inline-flex items-center text-purple-600 hover:text-purple-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-images text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.querySelector('header .text-xs').textContent = now.toLocaleDateString('id-ID', options);
        }

        // Update time every minute
        setInterval(updateTime, 60000);
        updateTime(); // Initial call
    </script>
</body>
</html>