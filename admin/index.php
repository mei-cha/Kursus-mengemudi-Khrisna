
<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// ==================== FUNGSI NOTIFIKASI ====================
function getSiswaBelumLunas($db, $limit = 10) {
    $query = "
        SELECT 
            ps.id,
            ps.nomor_pendaftaran,
            ps.nama_lengkap,
            ps.telepon,
            ps.email,
            ps.status_pendaftaran,
            pk.nama_paket,
            pk.harga,
            pk.durasi_jam,
            (
                SELECT COUNT(*) 
                FROM jadwal_kursus jk 
                WHERE jk.pendaftaran_id = ps.id
                AND jk.status IN ('terjadwal', 'selesai')
            ) as total_jadwal,
            (
                SELECT COUNT(*) 
                FROM jadwal_kursus jk 
                WHERE jk.pendaftaran_id = ps.id 
                AND jk.status = 'selesai'
            ) as jadwal_selesai,
            COALESCE((
                SELECT SUM(jumlah)
                FROM pembayaran 
                WHERE pendaftaran_id = ps.id
                AND status = 'terverifikasi'
            ), 0) as total_dibayar,
            (
                SELECT COUNT(*)
                FROM pembayaran 
                WHERE pendaftaran_id = ps.id
                AND tipe_pembayaran = 'lunas'
                AND status = 'terverifikasi'
            ) as ada_pembayaran_lunas,
            COALESCE((
                SELECT SUM(jumlah)
                FROM pembayaran 
                WHERE pendaftaran_id = ps.id
                AND status = 'terverifikasi'
            ), 0) >= pk.harga as sudah_bayar_penuh
        FROM pendaftaran_siswa ps
        JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
        WHERE ps.status_pendaftaran IN ('diproses', 'dikonfirmasi')
        HAVING 
            total_jadwal > 0 
            AND jadwal_selesai > 0 
            AND ada_pembayaran_lunas = 0
            AND sudah_bayar_penuh = 0
            AND (jadwal_selesai * 100.0 / total_jadwal) >= 70
        ORDER BY (jadwal_selesai * 100.0 / total_jadwal) DESC, total_dibayar ASC
        LIMIT :limit
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk menghitung total notifikasi (HANYA siswa yang perlu ditagih)
function countSiswaPerluDitagih($db) {
    $query = "
        SELECT COUNT(*) as total
        FROM (
            SELECT 
                ps.id,
                (
                    SELECT COUNT(*) 
                    FROM jadwal_kursus jk 
                    WHERE jk.pendaftaran_id = ps.id
                    AND jk.status IN ('terjadwal', 'selesai')
                ) as total_jadwal,
                (
                    SELECT COUNT(*) 
                    FROM jadwal_kursus jk 
                    WHERE jk.pendaftaran_id = ps.id 
                    AND jk.status = 'selesai'
                ) as jadwal_selesai,
                (
                    SELECT COUNT(*)
                    FROM pembayaran 
                    WHERE pendaftaran_id = ps.id
                    AND tipe_pembayaran = 'lunas'
                    AND status = 'terverifikasi'
                ) as ada_pembayaran_lunas,
                COALESCE((
                    SELECT SUM(jumlah)
                    FROM pembayaran 
                    WHERE pendaftaran_id = ps.id
                    AND status = 'terverifikasi'
                ), 0) as total_dibayar,
                pk.harga
            FROM pendaftaran_siswa ps
            JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
            WHERE ps.status_pendaftaran IN ('diproses', 'dikonfirmasi')
        ) as subquery
        WHERE total_jadwal > 0 
        AND jadwal_selesai > 0 
        AND ada_pembayaran_lunas = 0
        AND total_dibayar < harga
        AND (jadwal_selesai * 100.0 / total_jadwal) >= 70
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Get statistics
$total_pendaftaran = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa")->fetch()['total'];
$pendaftaran_baru = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa WHERE status_pendaftaran = 'baru'")->fetch()['total'];
$total_testimoni = $db->query("SELECT COUNT(*) as total FROM testimoni")->fetch()['total'];
$testimoni_menunggu = $db->query("SELECT COUNT(*) as total FROM testimoni WHERE status = 'menunggu'")->fetch()['total'];

// Get recent registrations
$recent_pendaftaran = $db->query("SELECT * FROM pendaftaran_siswa ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Ambil siswa yang perlu ditagih untuk notifikasi
$notif_siswa_taghihan = getSiswaBelumLunas($db, 10);
$total_notifications = countSiswaPerluDitagih($db);
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
        
        /* Styling untuk notifikasi */
        .progress-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 6px;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Notifikasi Bell */
        .notification-bell {
            position: relative;
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc2626;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* Modal Notifikasi */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .notification-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        /* Efek untuk notifikasi penting */
        .urgent-notification {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { 
                transform: scale(1); 
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); 
            }
            70% { 
                box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); 
            }
            100% { 
                transform: scale(1.02); 
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); 
            }
        }
        
        /* Efek untuk badge count */
        .badge-pulse {
            animation: badgePulse 1.5s infinite;
        }
        
        @keyframes badgePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Tab styling untuk modal */
        .notification-tab.active {
            border-color: #dc2626 !important;
            color: #dc2626 !important;
        }
        
        .notification-tab-content {
            display: none;
        }
        
        .notification-tab-content.active {
            display: block;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Modal Notifikasi -->
    <div id="notificationModal" class="notification-modal">
        <div class="notification-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-bell mr-2 text-red-600"></i>
                        Notifikasi Tagihan
                    </h3>
                    <button onclick="closeNotificationModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <!-- Daftar Notifikasi -->
                <?php if (!empty($notif_siswa_taghihan)): ?>
                <div id="notificationList" class="space-y-4">
                    <?php foreach ($notif_siswa_taghihan as $siswa): 
                        $progress = ($siswa['total_jadwal'] > 0) ? ($siswa['jadwal_selesai'] / $siswa['total_jadwal']) * 100 : 0;
                        $progress = round($progress, 0);
                        $sisa_tagihan = $siswa['harga'] - $siswa['total_dibayar'];
                    ?>
                    <div class="border border-red-200 rounded-lg p-4 hover:bg-red-50 transition duration-200">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center mb-3">
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-red-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900"><?= htmlspecialchars($siswa['nama_lengkap']) ?></h4>
                                        <p class="text-sm text-gray-500">
                                            <?= $siswa['nomor_pendaftaran'] ?> • 
                                            Status: <span class="font-medium"><?= $siswa['status_pendaftaran'] ?></span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-red-600 text-lg">
                                            Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?>
                                        </div>
                                        <div class="text-xs text-gray-500">Sisa Tagihan</div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 text-sm">
                                    <div>
                                        <span class="text-gray-600">Paket:</span>
                                        <span class="font-medium block"><?= htmlspecialchars($siswa['nama_paket']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Progress:</span>
                                        <span class="font-medium block">
                                            <?= $progress ?>% (<?= $siswa['jadwal_selesai'] ?>/<?= $siswa['total_jadwal'] ?> sesi)
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Total:</span>
                                        <span class="font-medium block">Rp <?= number_format($siswa['harga'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="text-sm text-gray-600 mb-1">Progress Pembayaran:</div>
                                    <div class="progress-container mb-2">
                                        <div class="progress-bar bg-red-500" 
                                             style="width: <?= min(100, ($siswa['total_dibayar'] / $siswa['harga']) * 100) ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span>Terbayar: Rp <?= number_format($siswa['total_dibayar'], 0, ',', '.') ?></span>
                                        <span>Sisa: Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3 mt-4">
                                    <a href="pembayaran.php?siswa_id=<?= $siswa['id'] ?>" 
                                       class="text-sm bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
                                        <i class="fas fa-money-bill-wave mr-1"></i> Tindak Lanjuti
                                    </a>
                                    <a href="jadwal.php?siswa_id=<?= $siswa['id'] ?>" 
                                       class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
                                        <i class="fas fa-calendar-alt mr-1"></i> Lihat Jadwal
                                    </a>
                                    <a href="pendaftaran_detail.php?id=<?= $siswa['id'] ?>" 
                                       class="text-sm bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
                                        <i class="fas fa-eye mr-1"></i> Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Tidak Ada Notifikasi</h4>
                    <p class="text-gray-600">Semua pembayaran sudah terkonfirmasi atau progress masih di bawah 70%</p>
                </div>
                <?php endif; ?>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">
                            Total <strong><?= count($notif_siswa_taghihan) ?></strong> siswa memerlukan perhatian
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex h-screen absolute inset-0">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden relative">
            <!-- Top Header DENGAN BELL NOTIFIKASI -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                        <p class="text-gray-600">Selamat datang di panel admin Krishna Driving</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Bell Notification dengan count -->
                        <button onclick="openNotificationModal()" class="notification-bell p-2 relative hover:bg-gray-100 rounded-lg transition duration-200">
                            <i class="fas fa-bell text-gray-600 text-xl"></i>
                            <?php if ($total_notifications > 0): ?>
                            <span class="notification-count badge-pulse">
                                <?= $total_notifications > 99 ? '99+' : $total_notifications ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- BANNER NOTIFIKASI UTAMA (Hanya tampil jika ada) -->
                <?php if (!empty($notif_siswa_taghihan)): ?>
                <div class="bg-gradient-to-r from-red-50 to-orange-50 border-l-4 border-red-500 p-4 mb-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-red-800 mb-1">
                                    <i class="fas fa-bell mr-2"></i>PERINGATAN: <?= count($notif_siswa_taghihan) ?> SISWA PERLU DITAGIH!
                                </h3>
                                <p class="text-red-700">
                                    Ada siswa yang sudah menyelesaikan minimal <strong>70% kursus</strong> namun 
                                    <strong>BELUM MELUNASI PEMBAYARAN</strong>.
                                </p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="openNotificationModal()" 
                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-eye mr-2"></i> Lihat Detail (<?= $total_notifications ?>)
                            </button>
                            <button onclick="hideBanner()" 
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-times mr-2"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition duration-300">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-users" style="color: #2563eb; font-size: 1.5rem;"></i>
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
                                <i class="fas fa-user-plus" style="color: #d97706; font-size: 1.5rem;"></i>
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
                                <i class="fas fa-star" style="color: #059669; font-size: 1.5rem;"></i>
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
                                <i class="fas fa-clock" style="color: #dc2626; font-size: 1.5rem;"></i>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        No. Pendaftaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Telepon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_pendaftaran as $pendaftaran): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= $pendaftaran['nomor_pendaftaran'] ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?= $pendaftaran['telepon'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($pendaftaran['nama_lengkap']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?= $pendaftaran['email'] ?></div>
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
                                <a href="pendaftaran.php"
                                    class="inline-flex items-center text-blue-600 hover:text-blue-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-users" style="color: #2563eb; font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Testimoni</h4>
                                <p class="text-gray-600 text-sm mb-4">Approve testimoni dari siswa</p>
                                <a href="testimoni.php"
                                    class="inline-flex items-center text-green-600 hover:text-green-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-star" style="color: #059669; font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Galeri</h4>
                                <p class="text-gray-600 text-sm mb-4">Upload foto aktivitas</p>
                                <a href="galeri.php"
                                    class="inline-flex items-center text-purple-600 hover:text-purple-900 font-medium">
                                    Kelola <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-images" style="color: #7c3aed; font-size: 2rem;"></i>
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
        // Fungsi untuk modal notifikasi
        function openNotificationModal() {
            document.getElementById('notificationModal').style.display = 'flex';
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar konten
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotificationModal();
            }
        });

        // Sembunyikan banner
        function hideBanner() {
            const banner = document.querySelector('.bg-gradient-to-r.from-red-50.to-orange-50');
            if (banner) {
                banner.style.display = 'none';
                // Simpan status di localStorage agar tidak muncul lagi di session ini
                localStorage.setItem('hide_notification_banner', 'true');
            }
        }

        // Cek apakah banner sudah disembunyikan
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('hide_notification_banner') === 'true') {
                const banner = document.querySelector('.bg-gradient-to-r.from-red-50.to-orange-50');
                if (banner) {
                    banner.style.display = 'none';
                }
            }
        });

        // Auto-refresh notifikasi setiap 1 menit
        setInterval(function() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const currentTotal = <?= $total_notifications ?>;
                    const newTotal = data.total || 0;
                    
                    // Update count bell jika ada perubahan
                    updateBellCount(newTotal);
                    
                    // Refresh halaman jika ada perubahan signifikan (lebih dari 2 notifikasi)
                    if (Math.abs(newTotal - currentTotal) >= 2) {
                        setTimeout(() => {
                            location.reload();
                        }, 5000); // Refresh dalam 5 detik
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }, 60000); // 1 menit

        // Fungsi untuk update bell count
        function updateBellCount(count) {
            const bell = document.querySelector('.notification-bell');
            let badge = bell.querySelector('.notification-count');
            
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notification-count badge-pulse';
                    bell.appendChild(badge);
                }
                badge.textContent = count > 99 ? '99+' : count;
                
                // Tambahkan efek urgent jika count > 0
                badge.classList.add('badge-pulse');
            } else {
                // Hapus badge jika tidak ada notifikasi
                if (badge) {
                    badge.remove();
                }
            }
        }

        // Update current time (opsional)
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
            const timeElement = document.querySelector('header .text-xs');
            if (timeElement) {
                timeElement.textContent = now.toLocaleDateString('id-ID', options);
            }
        }

        // Initialize
        setInterval(updateTime, 60000);
        updateTime();

        // Hotkey untuk membuka modal notifikasi (Ctrl+Shift+N)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'N') {
                e.preventDefault();
                openNotificationModal();
            }
        });
    </script>
</body>
</html>



	












