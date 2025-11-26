<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

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
            $stmt = $db->prepare("SELECT registration_id FROM pembayaran WHERE id = ?");
            $stmt->execute([$id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                // Update registration status to confirmed
                $update_stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = 'dikonfirmasi' WHERE id = ?");
                $update_stmt->execute([$payment['registration_id']]);
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

if ($search) {
    $query .= " AND (ps.nama_lengkap LIKE ? OR p.nomor_kwitansi LIKE ? OR ps.nomor_pendaftaran LIKE ?)";
    $search_term = "%$search%";
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
$total_nominal = $db->query("SELECT SUM(jumlah) as total FROM pembayaran WHERE status = 'terverifikasi'")->fetch()['total'];

// Get pending registrations for manual payment add
$pending_registrations = $db->query("
    SELECT ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, pk.nama_paket, pk.harga 
    FROM pendaftaran_siswa ps 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE ps.status_pendaftaran = 'baru' 
    ORDER BY ps.dibuat_pada DESC
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
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Pembayaran</h1>
                        <p class="text-gray-600">Verifikasi dan kelola pembayaran siswa</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $success ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Pembayaran</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_pembayaran ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Menunggu Verifikasi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $pembayaran_menunggu ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Terverifikasi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $pembayaran_terverifikasi ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Nominal</p>
                                <p class="text-2xl font-bold text-gray-900">Rp <?= number_format($total_nominal ?: 0, 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Payment Add Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Tambah Pembayaran Manual</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <input type="hidden" name="add_payment" value="1">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pendaftaran *</label>
                                <select name="pendaftaran_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Pendaftaran</option>
                                    <?php foreach ($pending_registrations as $reg): ?>
                                    <option value="<?= $reg['id'] ?>">
                                        <?= $reg['nomor_pendaftaran'] ?> - <?= htmlspecialchars($reg['nama_lengkap']) ?> (<?= htmlspecialchars($reg['nama_paket']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembayaran *</label>
                                <input type="date" name="tanggal_pembayaran" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Rp) *</label>
                                <input type="number" name="jumlah" required min="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="1500000">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Metode *</label>
                                <select name="metode_pembayaran" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="transfer">Transfer</option>
                                    <option value="tunai">Tunai</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe *</label>
                                <select name="tipe_pembayaran" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="dp">DP (Down Payment)</option>
                                    <option value="pelunasan">Pelunasan</option>
                                    <option value="full">Lunas</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="menunggu">Menunggu</option>
                                    <option value="terverifikasi">Terverifikasi</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                                <textarea name="catatan" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Catatan tambahan..."></textarea>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" 
                                        class="w-full bg-green-600 text-white py-2 rounded-lg font-bold hover:bg-green-700 transition duration-300">
                                    <i class="fas fa-plus mr-2"></i>Tambah Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Cari nama, no. kwitansi..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="terverifikasi" <?= $status_filter === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                    <option value="ditolak" <?= $status_filter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Pembayaran</label>
                                <select name="tipe" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Tipe</option>
                                    <option value="dp" <?= $tipe_filter === 'dp' ? 'selected' : '' ?>>DP</option>
                                    <option value="pelunasan" <?= $tipe_filter === 'pelunasan' ? 'selected' : '' ?>>Pelunasan</option>
                                    <option value="full" <?= $tipe_filter === 'full' ? 'selected' : '' ?>>Lunas</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="pembayaran.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-lg shadow">
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
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
                                            <div class="text-sm text-gray-500"><?= $data['metode_pembayaran'] ?></div>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                            <?= $data['tipe_pembayaran'] ?>
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
                                                        class="text-blue-600 hover:text-blue-900"
                                                        title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Verify Button (only for pending) -->
                                                <?php if ($data['status'] === 'menunggu'): ?>
                                                    <button onclick="verifyPayment(<?= $data['id'] ?>)" 
                                                            class="text-green-600 hover:text-green-900"
                                                            title="Verifikasi">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900"
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
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
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
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                        Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
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

        // Auto-fill amount based on package when registration is selected
        document.querySelector('select[name="pendaftaran_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                // Extract package price from option text (assuming format: ... (Rp XXXX))
                const match = selectedOption.text.match(/Rp\s*([\d.,]+)/);
                if (match) {
                    const price = match[1].replace(/\./g, '');
                    document.querySelector('input[name="jumlah"]').value = price;
                }
            }
        });
    </script>
</body>
</html>