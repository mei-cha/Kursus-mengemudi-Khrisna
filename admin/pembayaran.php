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

// Build query - PERBAIKAN: ORDER BY id DESC (bukan tanggal_pembayaran)
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

// PERBAIKAN: ORDER BY id DESC untuk urutan yang benar
$query .= " ORDER BY p.id DESC";

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
    ORDER BY ps.id DESC
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

        /* PERBAIKAN: Menggunakan styling default browser untuk select */
        select {
            /* Hanya gunakan styling dasar, biarkan browser menampilkan ikon default */
            padding-right: 2.5rem;
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
        
        /* Custom styles for consistent design */
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
            color: #4a5568;
            border: 1px solid #cbd5e0;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        }
        
        .status-badge-new {
            background: linear-gradient(135deg, #f6e05e 0%, #d69e2e 100%);
            color: #744210;
        }
        
        .status-badge-confirmed {
            background: linear-gradient(135deg, #68d391 0%, #38a169 100%);
            color: white;
        }
        
        .status-badge-processed {
            background: linear-gradient(135deg, #d6bcfa 0%, #9f7aea 100%);
            color: white;
        }
        
        .status-badge-completed {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
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

                <!-- Tambah Siswa & Quick Actions -->
                <div class="bg-white rounded-xl shadow mb-6">
                    <div class="pt-6 px-6 pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Tambah Pembayaran</h3>
                                <p class="text-gray-600">Untuk pembayaran langsung di kantor</p>
                            </div>
                            <button onclick="toggleManualPayment()" 
                                    class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Toggle manual payment form">
                                <i id="toggle-payment-icon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Manual Payment Form (Hidden by default) -->
                        <div id="manualPaymentForm" class="hidden mt-6">
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="hidden" name="add_payment" value="1">
                                
                                <div class="space-y-4">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Data Pembayaran</h4>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Siswa *</label>
                                        <select name="pendaftaran_id" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                id="studentSelect">
                                            <option value="">Pilih siswa...</option>
                                            <?php 
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
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembayaran *</label>
                                            <input type="date" name="tanggal_pembayaran" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Rp) *</label>
                                            <input type="number" name="jumlah" required min="1" id="paymentAmount"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="1500000">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran *</label>
                                            <select name="metode_pembayaran" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Pembayaran *</label>
                                            <select name="tipe_pembayaran" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="dp">DP (Down Payment)</option>
                                                <option value="pelunasan">Pelunasan</option>
                                                <option value="full">Lunas</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                        <select name="status" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="terverifikasi">Terverifikasi</option>
                                            <option value="menunggu">Menunggu</option>
                                            <option value="ditolak">Ditolak</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                                        <textarea name="catatan" rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Catatan tambahan mengenai pembayaran..."></textarea>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Informasi Siswa Terpilih</h4>
                                    
                                    <div id="studentInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
                                        <div class="grid grid-cols-1 gap-2 text-sm">
                                            <div>
                                                <span class="text-blue-700">Nama:</span>
                                                <span id="infoNama" class="text-blue-900 font-medium block"></span>
                                            </div>
                                            <div>
                                                <span class="text-blue-700">No. Pendaftaran:</span>
                                                <span id="infoNoPendaftaran" class="text-blue-900 font-medium block"></span>
                                            </div>
                                            <div>
                                                <span class="text-blue-700">Paket:</span>
                                                <span id="infoPaket" class="text-blue-900 block"></span>
                                            </div>
                                            <div>
                                                <span class="text-blue-700">Harga Paket:</span>
                                                <span id="infoHarga" class="text-blue-900 font-semibold block"></span>
                                            </div>
                                            <div>
                                                <span class="text-blue-700">Total Dibayar:</span>
                                                <span id="infoDibayar" class="text-green-600 font-semibold block"></span>
                                            </div>
                                            <div>
                                                <span class="text-blue-700">Sisa Bayar:</span>
                                                <span id="infoSisa" class="text-orange-600 font-semibold block"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex space-x-3 pt-4">
                                        <button type="button" onclick="toggleManualPayment()" 
                                                class="flex-1 bg-gray-600 text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                                            Batal
                                        </button>
                                        <button type="submit" 
                                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                                            <i class="fas fa-save mr-2"></i>Simpan Pembayaran
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-blue-100 mr-4">
                                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Pembayaran</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $total_pembayaran ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-yellow-100 mr-4">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Menunggu Verifikasi</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $pembayaran_menunggu ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-green-100 mr-4">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Terverifikasi</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $pembayaran_terverifikasi ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-purple-100 mr-4">
                                <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Nominal</p>
                                <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($total_nominal ?: 0, 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow mb-6">
                    <div class="p-6">
                        <!-- PERBAIKAN: Form harus memiliki id dan method="GET" -->
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4" id="filterForm">
                            <!-- Search Input -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Nama, no. kwitansi, telepon..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="terverifikasi" <?= $status_filter === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                    <option value="ditolak" <?= $status_filter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            
                            <!-- Tipe Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                                <select name="tipe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <option value="">Semua Tipe</option>
                                    <option value="dp" <?= $tipe_filter === 'dp' ? 'selected' : '' ?>>DP</option>
                                    <option value="pelunasan" <?= $tipe_filter === 'pelunasan' ? 'selected' : '' ?>>Pelunasan</option>
                                    <option value="full" <?= $tipe_filter === 'full' ? 'selected' : '' ?>>Lunas</option>
                                </select>
                            </div>
                            
                            <!-- Metode Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Metode</label>
                                <select name="metode" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                    <option value="">Semua Metode</option>
                                    <option value="transfer" <?= $metode_filter === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                                    <option value="tunai" <?= $metode_filter === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                                </select>
                            </div>
                        </form>
                        
                        <div class="flex space-x-2 mt-4">
                            <!-- PERBAIKAN: Button Filter harus di dalam form -->
                            <button type="submit" form="filterForm"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <!-- PERBAIKAN: Link reset harus benar -->
                            <a href="pembayaran.php" 
                               class="bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                                <i class="fas fa-refresh mr-2"></i>Reset
                            </a>
                        </div>
                        
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
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-800">
                                    <i class="fas fa-filter mr-1"></i>
                                    Status: <?= ucfirst($status_filter) ?>
                                    <a href="<?= remove_filter('status') ?>" class="ml-1 text-red-500 hover:text-green-800">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($tipe_filter): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-800">
                                    <i class="fas fa-money-bill-wave mr-1"></i>
                                    Tipe: <?= ucfirst($tipe_filter) ?>
                                    <a href="<?= remove_filter('tipe') ?>" class="ml-1 text-red-500 hover:text-purple-800">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($metode_filter): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-800">
                                    <i class="fas fa-credit-card mr-1"></i>
                                    Metode: <?= ucfirst($metode_filter) ?>
                                    <a href="<?= remove_filter('metode') ?>" class="ml-1 text-red-500 hover:text-orange-800">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <?php
                    $payment_status_info = [
                        'total' => ['color' => 'blue', 'icon' => 'credit-card', 'label' => 'Total', 'count' => $total_pembayaran],
                        'menunggu' => ['color' => 'yellow', 'icon' => 'clock', 'label' => 'Menunggu', 'count' => $pembayaran_menunggu],
                        'terverifikasi' => ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Terverifikasi', 'count' => $pembayaran_terverifikasi],
                        'ditolak' => ['color' => 'red', 'icon' => 'times', 'label' => 'Ditolak', 'count' => $pembayaran_ditolak],
                        'nominal' => ['color' => 'purple', 'icon' => 'money-bill-wave', 'label' => 'Nominal', 'count' => 'Rp ' . number_format($total_nominal ?: 0, 0, ',', '.')]
                    ];

                    foreach ($payment_status_info as $status => $info):
                    ?>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <div class="p-2 bg-<?= $info['color'] ?>-100 rounded-lg inline-block mb-2">
                                <i class="fas fa-<?= $info['icon'] ?> text-<?= $info['color'] ?>-600"></i>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?= $info['count'] ?></div>
                            <div class="text-sm text-gray-600"><?= $info['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-xl shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Data Pembayaran (<?= count($pembayaran) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($pembayaran) ?> pembayaran
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Kwitansi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe & Metode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($pembayaran) > 0): ?>
                                    <?php foreach ($pembayaran as $data): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= $data['nomor_kwitansi'] ?></div>
                                            <div class="text-xs text-gray-500"><?= $data['metode_pembayaran'] === 'transfer' ? 'Transfer' : 'Tunai' ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
                                            <div class="text-sm text-gray-500"><?= $data['nomor_pendaftaran'] ?></div>
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
                                                'menunggu' => 'bg-yellow-100 text-yellow-800',
                                                'terverifikasi' => 'bg-green-100 text-green-800',
                                                'ditolak' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
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
                                                        class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50"
                                                        title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Verify Button (only for pending) -->
                                                <?php if ($data['status'] === 'menunggu'): ?>
                                                    <button onclick="verifyPayment(<?= $data['id'] ?>)" 
                                                            class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
                                                            title="Verifikasi">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            Tidak ada data pembayaran yang ditemukan.
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
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="terverifikasi">Terverifikasi</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="catatan" id="verifyCatatan" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Berikan catatan verifikasi..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeVerifyModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-300">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition duration-300">
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
            const icon = document.getElementById('toggle-payment-icon');
            
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }

        // Update student info when student is selected
        document.getElementById('studentSelect')?.addEventListener('change', function() {
            const studentInfo = document.getElementById('studentInfo');
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                studentInfo.classList.remove('hidden');
                
                // Extract info from option text and data attributes
                const optionText = selectedOption.text;
                const parts = optionText.split(' - ');
                
                document.getElementById('infoNama').textContent = parts[1];
                document.getElementById('infoNoPendaftaran').textContent = parts[0];
                document.getElementById('infoPaket').textContent = parts[2];
                
                const price = selectedOption.getAttribute('data-price');
                const paid = selectedOption.getAttribute('data-paid');
                const remaining = selectedOption.getAttribute('data-remaining');
                
                document.getElementById('infoHarga').textContent = 'Rp ' + parseInt(price).toLocaleString('id-ID');
                document.getElementById('infoDibayar').textContent = 'Rp ' + parseInt(paid).toLocaleString('id-ID');
                document.getElementById('infoSisa').textContent = 'Rp ' + parseInt(remaining).toLocaleString('id-ID');
                
                // Auto-fill payment amount with remaining amount
                if (parseInt(remaining) > 0) {
                    document.getElementById('paymentAmount').value = remaining;
                }
            } else {
                studentInfo.classList.add('hidden');
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