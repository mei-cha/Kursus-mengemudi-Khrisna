<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Helper function to remove specific filter from URL
function remove_filter($filter_name) {
    $params = $_GET;
    unset($params[$filter_name]);
    return 'pembayaran.php?' . http_build_query($params);
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $catatan = $_POST['catatan'] ?? '';
    
    $verified_by = $_SESSION['admin_username'];
    $verified_at = date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("UPDATE pembayaran SET status = ?, catatan = ?, diverifikasi_oleh = ?, tanggal_verifikasi = ? WHERE id = ?");
    if ($stmt->execute([$status, $catatan, $verified_by, $verified_at, $id])) {
        $success = "Status pembayaran berhasil diupdate!";
        
        // If payment is verified, update registration status
        if ($status === 'terverifikasi') {
            // Get registration ID from payment
            $stmt = $db->prepare("SELECT pendaftaran_id FROM pembayaran WHERE id = ?");
            $stmt->execute([$id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                // Update registration status to confirmed
                $update_stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = 'dikonfirmasi' WHERE id = ?");
                $update_stmt->execute([$payment['pendaftaran_id']]);
            }
        }
    } else {
        $error = "Gagal mengupdate status pembayaran!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get payment data first to delete proof image
    $stmt = $db->prepare("SELECT bukti_bayar FROM pembayaran WHERE id = ?");
    $stmt->execute([$id]);
    $pembayaran = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("DELETE FROM pembayaran WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Delete proof image file if exists
        if ($pembayaran['bukti_bayar']) {
            $file_path = "../assets/images/bukti_bayar/" . $pembayaran['bukti_bayar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $success = "Data pembayaran berhasil dihapus!";
    } else {
        $error = "Gagal menghapus data pembayaran!";
    }
}

// Handle manual payment add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $pendaftaran_id = $_POST['pendaftaran_id'];
    $tanggal_pembayaran = $_POST['tanggal_pembayaran'];
    $jumlah = $_POST['jumlah'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $tipe_pembayaran = $_POST['tipe_pembayaran'];
    $status = $_POST['status'];
    $catatan = $_POST['catatan'] ?? '';
    
    // Generate receipt number
    $nomor_kwitansi = 'KW' . date('ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $diverifikasi_oleh = null;
    $tanggal_verifikasi = null;
    
    if ($status === 'terverifikasi') {
        $diverifikasi_oleh = $_SESSION['admin_username'];
        $tanggal_verifikasi = date('Y-m-d H:i:s');
        
        // Update registration status if payment is verified
        $update_stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = 'dikonfirmasi' WHERE id = ?");
        $update_stmt->execute([$pendaftaran_id]);
    }
    
    $stmt = $db->prepare("INSERT INTO pembayaran (pendaftaran_id, tanggal_pembayaran, jumlah, metode_pembayaran, tipe_pembayaran, nomor_kwitansi, status, catatan, diverifikasi_oleh, tanggal_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$pendaftaran_id, $tanggal_pembayaran, $jumlah, $metode_pembayaran, $tipe_pembayaran, $nomor_kwitansi, $status, $catatan, $diverifikasi_oleh, $tanggal_verifikasi])) {
        $success = "Data pembayaran berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan data pembayaran!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$tipe_filter = $_GET['tipe'] ?? '';
$metode_filter = $_GET['metode'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT p.*, ps.nama_lengkap, ps.nomor_pendaftaran, pk.nama_paket, pk.harga 
          FROM pembayaran p 
          JOIN pendaftaran_siswa ps ON p.pendaftaran_id = ps.id 
          JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}

if ($tipe_filter) {
    $query .= " AND p.tipe_pembayaran = ?";
    $params[] = $tipe_filter;
}

if ($metode_filter) {
    $query .= " AND p.metode_pembayaran = ?";
    $params[] = $metode_filter;
}

if ($search) {
    $query .= " AND (ps.nama_lengkap LIKE ? OR p.nomor_kwitansi LIKE ? OR ps.nomor_pendaftaran LIKE ? OR ps.telepon LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY p.tanggal_pembayaran DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$total_pembayaran = $db->query("SELECT COUNT(*) as total FROM pembayaran")->fetch()['total'];
$pembayaran_menunggu = $db->query("SELECT COUNT(*) as total FROM pembayaran WHERE status = 'menunggu'")->fetch()['total'];
$pembayaran_terverifikasi = $db->query("SELECT COUNT(*) as total FROM pembayaran WHERE status = 'terverifikasi'")->fetch()['total'];
$pembayaran_ditolak = $db->query("SELECT COUNT(*) as total FROM pembayaran WHERE status = 'ditolak'")->fetch()['total'];
$total_nominal = $db->query("SELECT SUM(jumlah) as total FROM pembayaran WHERE status = 'terverifikasi'")->fetch()['total'];

// Get pending registrations for manual payment add
$pending_registrations = $db->query("
    SELECT ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, pk.nama_paket, pk.harga 
    FROM pendaftaran_siswa ps 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE ps.status_pendaftaran = 'baru' 
    ORDER BY ps.dibuat_pada DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get payment type statistics
$payment_types = $db->query("
    SELECT tipe_pembayaran, COUNT(*) as count, SUM(jumlah) as total 
    FROM pembayaran 
    WHERE status = 'terverifikasi' 
    GROUP BY tipe_pembayaran
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Krishna Driving</title>
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
        .payment-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
        }
        /* Custom select styling */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Remove default arrow in IE */
select::-ms-expand {
    display: none;
}

/* Ensure buttons have consistent height */
.btn-filter {
    min-height: 48px;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        grid-column: 1;
    }
}
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Pembayaran</h1>
                        <p class="text-gray-600">Verifikasi dan kelola seluruh pembayaran siswa</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100 transition duration-200">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Notifications -->
                <?php if (isset($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                    <div>
                        <p class="text-green-800 font-medium">Berhasil!</p>
                        <p class="text-green-700 text-sm"><?= $success ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
                    <div>
                        <p class="text-red-800 font-medium">Error!</p>
                        <p class="text-red-700 text-sm"><?= $error ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg text-white p-6 payment-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Pembayaran</p>
                                <p class="text-3xl font-bold mt-2"><?= $total_pembayaran ?></p>
                                <p class="text-blue-100 text-xs mt-1">Semua transaksi</p>
                            </div>
                            <div class="bg-blue-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-credit-card text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg text-white p-6 payment-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-100 text-sm font-medium">Menunggu Verifikasi</p>
                                <p class="text-3xl font-bold mt-2"><?= $pembayaran_menunggu ?></p>
                                <p class="text-yellow-100 text-xs mt-1">Perlu tindakan</p>
                            </div>
                            <div class="bg-yellow-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg text-white p-6 payment-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Terverifikasi</p>
                                <p class="text-3xl font-bold mt-2"><?= $pembayaran_terverifikasi ?></p>
                                <p class="text-green-100 text-xs mt-1">Pembayaran valid</p>
                            </div>
                            <div class="bg-green-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-check-circle text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg text-white p-6 payment-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Total Nominal</p>
                                <p class="text-2xl font-bold mt-2">Rp <?= number_format($total_nominal ?: 0, 0, ',', '.') ?></p>
                                <p class="text-purple-100 text-xs mt-1">Dana terkumpul</p>
                            </div>
                            <div class="bg-purple-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-money-bill-wave text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Type Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-chart-pie text-blue-500 mr-3"></i>
                            Ringkasan Tipe Pembayaran
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php foreach ($payment_types as $type): 
                                $type_colors = [
                                    'dp' => 'from-orange-400 to-orange-500',
                                    'pelunasan' => 'from-green-400 to-green-500',
                                    'full' => 'from-blue-400 to-blue-500'
                                ];
                                $type_icons = [
                                    'dp' => 'fa-hand-holding-usd',
                                    'pelunasan' => 'fa-money-check',
                                    'full' => 'fa-receipt'
                                ];
                                $type_labels = [
                                    'dp' => 'Down Payment',
                                    'pelunasan' => 'Pelunasan',
                                    'full' => 'Lunas'
                                ];
                            ?>
                            <div class="bg-gradient-to-br <?= $type_colors[$type['tipe_pembayaran']] ?? 'from-gray-400 to-gray-500' ?> rounded-lg text-white p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white text-opacity-90 text-sm font-medium"><?= $type_labels[$type['tipe_pembayaran']] ?? ucfirst($type['tipe_pembayaran']) ?></p>
                                        <p class="text-xl font-bold mt-1"><?= $type['count'] ?> Transaksi</p>
                                        <p class="text-white text-opacity-80 text-sm mt-1">Rp <?= number_format($type['total'], 0, ',', '.') ?></p>
                                    </div>
                                    <div class="bg-white bg-opacity-20 p-2 rounded-full">
                                        <i class="fas <?= $type_icons[$type['tipe_pembayaran']] ?? 'fa-money-bill' ?>"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Manual Payment -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <!-- Quick Actions -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-bolt text-yellow-500 mr-3"></i>
                                    Quick Actions
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <button onclick="toggleManualPayment()" 
                                        class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-plus mr-3"></i>
                                    Tambah Pembayaran Manual
                                </button>
                                
                                <a href="pembayaran.php?status=menunggu" 
                                   class="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-yellow-600 hover:to-yellow-700 transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-clock mr-3"></i>
                                    Lihat Menunggu Verifikasi
                                </a>
                                
                                <a href="pembayaran.php" 
                                   class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-list mr-3"></i>
                                    Semua Pembayaran
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Payment Form -->
<!-- Manual Payment Form -->
<div class="lg:col-span-2">
    <div id="manualPaymentForm" class="bg-white rounded-xl shadow-sm border border-gray-200 hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-green-100">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-hand-holding-usd text-green-500 mr-3"></i>
                Tambah Pembayaran Manual
            </h3>
            <p class="text-green-700 text-sm mt-1">Untuk pembayaran langsung di kantor atau transfer manual</p>
        </div>
        <div class="p-6">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="add_payment" value="1">
                
                <div class="space-y-4">
                    <!-- Pilih Siswa yang Sudah Terdaftar -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Siswa *</label>
                        <select name="pendaftaran_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                id="studentSelect">
                            <option value="">Cari atau pilih siswa...</option>
                            <?php 
                            // Get all active students (not cancelled or completed)
                            $active_students = $db->query("
                                SELECT ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, ps.telepon, 
                                       pk.nama_paket, pk.harga,
                                       COALESCE(SUM(p.jumlah), 0) as total_dibayar
                                FROM pendaftaran_siswa ps 
                                JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
                                LEFT JOIN pembayaran p ON ps.id = p.pendaftaran_id AND p.status = 'terverifikasi'
                                WHERE ps.status_pendaftaran NOT IN ('dibatalkan', 'selesai')
                                GROUP BY ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, ps.telepon, pk.nama_paket, pk.harga
                                ORDER BY ps.nama_lengkap
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($active_students as $student): 
                                $sisa_bayar = $student['harga'] - $student['total_dibayar'];
                                $status_pembayaran = $student['total_dibayar'] == 0 ? 'Belum Bayar' : 
                                                   ($sisa_bayar > 0 ? 'Belum Lunas' : 'Lunas');
                            ?>
                            <option value="<?= $student['id'] ?>" 
                                    data-price="<?= $student['harga'] ?>"
                                    data-paid="<?= $student['total_dibayar'] ?>"
                                    data-remaining="<?= $sisa_bayar ?>">
                                <?= $student['nomor_pendaftaran'] ?> - <?= htmlspecialchars($student['nama_lengkap']) ?> 
                                (<?= htmlspecialchars($student['nama_paket']) ?> - Rp <?= number_format($student['harga'], 0, ',', '.') ?>)
                                - <?= $status_pembayaran ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih siswa yang melakukan pembayaran</p>
                    </div>
                    
                    <!-- Informasi Siswa Terpilih -->
                    <div id="studentInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Informasi Siswa</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-blue-700">Nama:</span>
                                <span id="infoNama" class="text-blue-900 font-medium"></span>
                            </div>
                            <div>
                                <span class="text-blue-700">No. Pendaftaran:</span>
                                <span id="infoNoPendaftaran" class="text-blue-900 font-medium"></span>
                            </div>
                            <div>
                                <span class="text-blue-700">Paket:</span>
                                <span id="infoPaket" class="text-blue-900"></span>
                            </div>
                            <div>
                                <span class="text-blue-700">Harga Paket:</span>
                                <span id="infoHarga" class="text-blue-900 font-semibold"></span>
                            </div>
                            <div>
                                <span class="text-blue-700">Total Dibayar:</span>
                                <span id="infoDibayar" class="text-green-600 font-semibold"></span>
                            </div>
                            <div>
                                <span class="text-blue-700">Sisa Bayar:</span>
                                <span id="infoSisa" class="text-orange-600 font-semibold"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembayaran *</label>
                        <input type="date" name="tanggal_pembayaran" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" required min="1" id="paymentAmount"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                               placeholder="1500000">
                        <p class="text-xs text-gray-500 mt-1">Masukkan jumlah pembayaran</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran *</label>
                        <select name="metode_pembayaran" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                id="paymentMethod">
                            <option value="transfer">Transfer Bank</option>
                            <option value="tunai">Tunai</option>
                        </select>
                    </div>
                    
                    <!-- Informasi Transfer (muncul hanya jika metode transfer) -->
                    <div id="transferInfo" class="hidden space-y-3 bg-gray-50 p-4 rounded-lg border">
                        <h4 class="font-medium text-gray-700">Informasi Transfer</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Bank</label>
                            <input type="text" name="bank_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Contoh: BCA, Mandiri, BNI">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Rekening</label>
                            <input type="text" name="account_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Nomor rekening pengirim">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pemilik Rekening</label>
                            <input type="text" name="account_holder"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Nama sesuai rekening">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Pembayaran *</label>
                        <select name="tipe_pembayaran" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                id="paymentType">
                            <option value="dp">DP (Down Payment)</option>
                            <option value="pelunasan">Pelunasan</option>
                            <option value="full">Lunas</option>
                            <option value="cicilan">Cicilan</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <span id="paymentTypeHint">Pembayaran pertama untuk mengkonfirmasi pendaftaran</span>
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="terverifikasi">Terverifikasi</option>
                            <option value="menunggu">Menunggu</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Status verifikasi pembayaran</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="catatan" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                  placeholder="Catatan tambahan mengenai pembayaran..."></textarea>
                    </div>
                    
                    <div class="flex space-x-3 pt-4">
                        <button type="button" onclick="toggleManualPayment()" 
                                class="flex-1 bg-gray-600 text-white py-3 rounded-xl font-semibold hover:bg-gray-700 transition duration-200">
                            Batal
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-green-700 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Simpan Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
                </div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Nama, no. kwitansi, telepon..."
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                </div>
            </div>
            
            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-filter text-gray-400"></i>
                    </div>
                    <select name="status" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 appearance-none bg-white">
                        <option value="">Semua Status</option>
                        <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="terverifikasi" <?= $status_filter === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                        <option value="ditolak" <?= $status_filter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Tipe Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-money-bill-wave text-gray-400"></i>
                    </div>
                    <select name="tipe" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 appearance-none bg-white">
                        <option value="">Semua Tipe</option>
                        <option value="dp" <?= $tipe_filter === 'dp' ? 'selected' : '' ?>>DP</option>
                        <option value="pelunasan" <?= $tipe_filter === 'pelunasan' ? 'selected' : '' ?>>Pelunasan</option>
                        <option value="full" <?= $tipe_filter === 'full' ? 'selected' : '' ?>>Lunas</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Metode Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Metode</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-credit-card text-gray-400"></i>
                    </div>
                    <select name="metode" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 appearance-none bg-white">
                        <option value="">Semua Metode</option>
                        <option value="transfer" <?= $metode_filter === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        <option value="tunai" <?= $metode_filter === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons - Now in separate row on mobile -->
            <div class="lg:col-span-5">
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="submit" 
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700 transition duration-200 flex items-center justify-center shadow-sm">
                        <i class="fas fa-filter mr-2"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="pembayaran.php" 
                       class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-gray-600 hover:to-gray-700 transition duration-200 flex items-center justify-center shadow-sm">
                        <i class="fas fa-refresh mr-2"></i>
                        <span>Reset Filter</span>
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Active Filters Badges -->
        <?php if ($status_filter || $tipe_filter || $metode_filter || $search): ?>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm text-gray-600">Filter aktif:</span>
                <?php if ($search): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-search mr-1"></i>
                    "<?= htmlspecialchars($search) ?>"
                    <a href="<?= remove_filter('search') ?>" class="ml-1 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                
                <?php if ($status_filter): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-filter mr-1"></i>
                    Status: <?= ucfirst($status_filter) ?>
                    <a href="<?= remove_filter('status') ?>" class="ml-1 text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                
                <?php if ($tipe_filter): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    <i class="fas fa-money-bill-wave mr-1"></i>
                    Tipe: <?= ucfirst($tipe_filter) ?>
                    <a href="<?= remove_filter('tipe') ?>" class="ml-1 text-purple-600 hover:text-purple-800">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                
                <?php if ($metode_filter): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    <i class="fas fa-credit-card mr-1"></i>
                    Metode: <?= ucfirst($metode_filter) ?>
                    <a href="<?= remove_filter('metode') ?>" class="ml-1 text-orange-600 hover:text-orange-800">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

                <!-- Payments Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Data Pembayaran</h3>
                                <p class="text-gray-600 text-sm mt-1">Total <?= count($pembayaran) ?> pembayaran ditemukan</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">Update: <?= date('H:i') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">No. Kwitansi</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tipe & Metode</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($pembayaran) > 0): ?>
                                    <?php foreach ($pembayaran as $data): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900"><?= $data['nomor_kwitansi'] ?></div>
                                            <div class="text-xs text-gray-500 mt-1"><?= $data['metode_pembayaran'] === 'transfer' ? 'Transfer' : 'Tunai' ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                                    <span class="text-white text-sm font-bold">
                                                        <?= strtoupper(substr($data['nama_lengkap'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= $data['nomor_pendaftaran'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d M Y', strtotime($data['tanggal_pembayaran'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></div>
                                            <div class="text-xs text-gray-500">Paket: <?= number_format($data['harga'], 0, ',', '.') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 capitalize"><?= $data['tipe_pembayaran'] ?></div>
                                            <div class="text-xs text-gray-500 capitalize"><?= $data['metode_pembayaran'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_badges = [
                                                'menunggu' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                                'terverifikasi' => 'bg-green-100 text-green-800 border border-green-200',
                                                'ditolak' => 'bg-red-100 text-red-800 border border-red-200'
                                            ];
                                            $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800 border border-gray-200';
                                            ?>
                                            <span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                                                <?php if ($data['status'] === 'menunggu'): ?>
                                                    <i class="fas fa-clock mr-1"></i>
                                                <?php elseif ($data['status'] === 'terverifikasi'): ?>
                                                    <i class="fas fa-check mr-1"></i>
                                                <?php elseif ($data['status'] === 'ditolak'): ?>
                                                    <i class="fas fa-times mr-1"></i>
                                                <?php endif; ?>
                                                <?= ucfirst($data['status']) ?>
                                            </span>
                                            <?php if ($data['diverifikasi_oleh']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    oleh <?= $data['diverifikasi_oleh'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <!-- View Details Button -->
                                                <button onclick="viewPayment(<?= $data['id'] ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition duration-200"
                                                        title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Verify Button (only for pending) -->
                                                <?php if ($data['status'] === 'menunggu'): ?>
                                                    <button onclick="verifyPayment(<?= $data['id'] ?>)" 
                                                            class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition duration-200"
                                                            title="Verifikasi">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition duration-200"
                                                        title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="text-gray-400">
                                                <i class="fas fa-receipt text-4xl mb-3"></i>
                                                <p class="text-lg font-medium text-gray-500">Tidak ada data pembayaran</p>
                                                <p class="text-sm text-gray-400 mt-1">Data pembayaran akan muncul di sini</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Payment Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Detail Pembayaran</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="viewContent" class="mt-4">
                    <!-- Detail content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Payment Modal -->
    <div id="verifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <form method="POST" id="verifyForm">
                <input type="hidden" name="id" id="verifyId">
                <input type="hidden" name="verify_payment" value="1">
                
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Verifikasi Pembayaran</h3>
                    <button type="button" onclick="closeVerifyModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Verifikasi *</label>
                        <select name="status" id="verifyStatus" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="terverifikasi">Terverifikasi</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="catatan" id="verifyCatatan" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Berikan catatan verifikasi..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeVerifyModal()" 
                            class="px-6 py-3 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition duration-200">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700 transition duration-200">
                        Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Toggle manual payment form
        function toggleManualPayment() {
            const form = document.getElementById('manualPaymentForm');
            form.classList.toggle('hidden');
        }

        // Auto-fill amount based on package when registration is selected
        document.querySelector('select[name="pendaftaran_id"]')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const price = selectedOption.getAttribute('data-price');
                if (price) {
                    document.getElementById('paymentAmount').value = price;
                }
            }
        });

        // View Payment Function
        function viewPayment(id) {
            fetch(`pembayaran_detail.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewContent').innerHTML = html;
                    document.getElementById('viewModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat detail pembayaran');
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // Verify Payment Function
        function verifyPayment(id) {
            document.getElementById('verifyId').value = id;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data pembayaran ini?')) {
                window.location.href = `pembayaran.php?delete=${id}`;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const verifyModal = document.getElementById('verifyModal');
            
            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === verifyModal) {
                closeVerifyModal();
            }
        }

        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successMessage = document.querySelector('.bg-green-50');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>