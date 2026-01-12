<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle add vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $data = [
        'nomor_plat' => $_POST['nomor_plat'],
        'merk' => $_POST['merk'],
        'model' => $_POST['model'],
        'tahun' => $_POST['tahun'],
        'tipe_transmisi' => $_POST['tipe_transmisi'],
        'warna' => $_POST['warna'],
        'kapasitas_bahan_bakar' => $_POST['kapasitas_bahan_bakar'] ?? 0,
        'kondisi' => $_POST['kondisi'],
        'status_ketersediaan' => $_POST['status_ketersediaan'],
        'tanggal_pajak' => $_POST['tanggal_pajak'] ?? null,
        'tanggal_stnk' => $_POST['tanggal_stnk'] ?? null,
        'kilometer_terakhir' => $_POST['kilometer_terakhir'] ?? 0,
        'catatan' => $_POST['catatan'] ?? '',
    ];

    try {
        // Cek apakah nomor plat sudah ada
        $check_stmt = $db->prepare("SELECT id FROM kendaraan WHERE nomor_plat = ?");
        $check_stmt->execute([$data['nomor_plat']]);
        
        if ($check_stmt->fetch()) {
            $error = "Nomor plat sudah terdaftar!";
        } else {
            // Tambahkan kendaraan baru
            $stmt = $db->prepare("
                INSERT INTO kendaraan 
                (nomor_plat, merk, model, tahun, tipe_transmisi, warna, 
                 kapasitas_bahan_bakar, kondisi, status_ketersediaan,
                 tanggal_pajak, tanggal_stnk, kilometer_terakhir, catatan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $values = [
                $data['nomor_plat'],
                $data['merk'],
                $data['model'],
                $data['tahun'],
                $data['tipe_transmisi'],
                $data['warna'],
                $data['kapasitas_bahan_bakar'],
                $data['kondisi'],
                $data['status_ketersediaan'],
                $data['tanggal_pajak'],
                $data['tanggal_stnk'],
                $data['kilometer_terakhir'],
                $data['catatan']
            ];

            if ($stmt->execute($values)) {
                $success = "Kendaraan berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan kendaraan!";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle update vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $id = $_POST['id'];
    $data = [
        'nomor_plat' => $_POST['nomor_plat'],
        'merk' => $_POST['merk'],
        'model' => $_POST['model'],
        'tahun' => $_POST['tahun'],
        'tipe_transmisi' => $_POST['tipe_transmisi'],
        'warna' => $_POST['warna'],
        'kapasitas_bahan_bakar' => $_POST['kapasitas_bahan_bakar'] ?? 0,
        'kondisi' => $_POST['kondisi'],
        'status_ketersediaan' => $_POST['status_ketersediaan'],
        'tanggal_pajak' => $_POST['tanggal_pajak'] ?? null,
        'tanggal_stnk' => $_POST['tanggal_stnk'] ?? null,
        'kilometer_terakhir' => $_POST['kilometer_terakhir'] ?? 0,
        'catatan' => $_POST['catatan'] ?? '',
    ];

    try {
        // Cek apakah nomor plat sudah ada (selain kendaraan yang sedang diupdate)
        $check_stmt = $db->prepare("SELECT id FROM kendaraan WHERE nomor_plat = ? AND id != ?");
        $check_stmt->execute([$data['nomor_plat'], $id]);
        
        if ($check_stmt->fetch()) {
            $error = "Nomor plat sudah terdaftar untuk kendaraan lain!";
        } else {
            $stmt = $db->prepare("
                UPDATE kendaraan SET 
                nomor_plat = ?, merk = ?, model = ?, tahun = ?, tipe_transmisi = ?, 
                warna = ?, kapasitas_bahan_bakar = ?, kondisi = ?, status_ketersediaan = ?,
                tanggal_pajak = ?, tanggal_stnk = ?, kilometer_terakhir = ?, catatan = ?
                WHERE id = ?
            ");

            $values = [
                $data['nomor_plat'],
                $data['merk'],
                $data['model'],
                $data['tahun'],
                $data['tipe_transmisi'],
                $data['warna'],
                $data['kapasitas_bahan_bakar'],
                $data['kondisi'],
                $data['status_ketersediaan'],
                $data['tanggal_pajak'],
                $data['tanggal_stnk'],
                $data['kilometer_terakhir'],
                $data['catatan'],
                $id
            ];

            if ($stmt->execute($values)) {
                $success = "Kendaraan berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate kendaraan!";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle delete vehicle
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Cek apakah kendaraan sedang digunakan di jadwal
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM jadwal_kursus WHERE mobil_digunakan LIKE ?");
        $check_stmt->execute(['%' . $id . '%']);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = "Kendaraan tidak dapat dihapus karena masih digunakan dalam jadwal kursus!";
        } else {
            $stmt = $db->prepare("DELETE FROM kendaraan WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = "Kendaraan berhasil dihapus!";
            } else {
                $error = "Gagal menghapus kendaraan!";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$tipe_filter = $_GET['tipe'] ?? '';
$kondisi_filter = $_GET['kondisi'] ?? '';

// Query untuk data kendaraan
$query = "SELECT * FROM kendaraan WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND status_ketersediaan = ?";
    $params[] = $status_filter;
}

if ($tipe_filter) {
    $query .= " AND tipe_transmisi = ?";
    $params[] = $tipe_filter;
}

if ($kondisi_filter) {
    $query .= " AND kondisi = ?";
    $params[] = $kondisi_filter;
}

$query .= " ORDER BY merk, model";

$stmt = $db->prepare($query);
$stmt->execute($params);
$kendaraan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_kendaraan = $db->query("SELECT COUNT(*) as total FROM kendaraan")->fetch()['total'];
$tersedia = $db->query("SELECT COUNT(*) as total FROM kendaraan WHERE status_ketersediaan = 'tersedia'")->fetch()['total'];
$dipakai = $db->query("SELECT COUNT(*) as total FROM kendaraan WHERE status_ketersediaan = 'dipakai'")->fetch()['total'];
$servis = $db->query("SELECT COUNT(*) as total FROM kendaraan WHERE status_ketersediaan = 'servis'")->fetch()['total'];
$manual = $db->query("SELECT COUNT(*) as total FROM kendaraan WHERE tipe_transmisi = 'manual'")->fetch()['total'];
$matic = $db->query("SELECT COUNT(*) as total FROM kendaraan WHERE tipe_transmisi = 'matic'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kendaraan - Krishna Driving</title>
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
        
        /* Styling khusus untuk kendaraan */
        .vehicle-card {
            transition: all 0.3s ease;
        }
        
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        
        .transmission-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }
        
        /* Modal styling */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Progress bar for kilometer */
        .progress-bar {
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
    </style>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Kendaraan</h1>
                        <p class="text-gray-600">Manajemen armada kendaraan kursus mengemudi</p>
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

                <!-- Add Vehicle Button -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Manajemen Kendaraan</h3>
                                <p class="text-gray-600 mt-1">Tambah atau kelola kendaraan yang tersedia</p>
                            </div>
                            <button onclick="openAddModal()"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300 flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Kendaraan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-car text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Kendaraan</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_kendaraan ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Tersedia</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $tersedia ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-cogs text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Manual</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $manual ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-bolt text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Matic</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $matic ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status Ketersediaan</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="tersedia" <?= $status_filter === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="dipakai" <?= $status_filter === 'dipakai' ? 'selected' : '' ?>>Dipakai</option>
                                    <option value="servis" <?= $status_filter === 'servis' ? 'selected' : '' ?>>Servis</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Transmisi</label>
                                <select name="tipe" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Tipe</option>
                                    <option value="manual" <?= $tipe_filter === 'manual' ? 'selected' : '' ?>>Manual</option>
                                    <option value="matic" <?= $tipe_filter === 'matic' ? 'selected' : '' ?>>Matic</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi</label>
                                <select name="kondisi" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Kondisi</option>
                                    <option value="baik" <?= $kondisi_filter === 'baik' ? 'selected' : '' ?>>Baik</option>
                                    <option value="perbaikan" <?= $kondisi_filter === 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
                                    <option value="rusak" <?= $kondisi_filter === 'rusak' ? 'selected' : '' ?>>Rusak</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="kendaraan.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vehicles Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (count($kendaraan) > 0): ?>
                        <?php foreach ($kendaraan as $data): ?>
                            <?php
                            // Status badge colors
                            $status_colors = [
                                'tersedia' => 'bg-green-100 text-green-800',
                                'dipakai' => 'bg-yellow-100 text-yellow-800',
                                'servis' => 'bg-red-100 text-red-800'
                            ];
                            $status_color = $status_colors[$data['status_ketersediaan']] ?? 'bg-gray-100 text-gray-800';
                            
                            // Condition colors
                            $condition_colors = [
                                'baik' => 'text-green-600',
                                'perbaikan' => 'text-yellow-600',
                                'rusak' => 'text-red-600'
                            ];
                            $condition_color = $condition_colors[$data['kondisi']] ?? 'text-gray-600';
                            
                            // Transmission colors
                            $transmission_colors = [
                                'manual' => 'bg-yellow-100 text-yellow-800',
                                'matic' => 'bg-purple-100 text-purple-800'
                            ];
                            $transmission_color = $transmission_colors[$data['tipe_transmisi']] ?? 'bg-gray-100 text-gray-800';
                            
                            // Calculate days until tax/STNK expiry
                            $today = new DateTime();
                            $tax_due = null;
                            $stnk_due = null;
                            
                            if (!empty($data['tanggal_pajak']) && $data['tanggal_pajak'] != '0000-00-00') {
                                $tax_date = new DateTime($data['tanggal_pajak']);
                                $tax_due = $today->diff($tax_date)->days;
                            }
                            
                            if (!empty($data['tanggal_stnk']) && $data['tanggal_stnk'] != '0000-00-00') {
                                $stnk_date = new DateTime($data['tanggal_stnk']);
                                $stnk_due = $today->diff($stnk_date)->days;
                            }
                            ?>
                            
                            <div class="vehicle-card bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <!-- Vehicle Header -->
                                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <div class="transmission-icon <?= $transmission_color ?> mr-3">
                                                    <i class="fas <?= $data['tipe_transmisi'] == 'manual' ? 'fa-cogs' : 'fa-bolt' ?>"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($data['merk']) ?> <?= htmlspecialchars($data['model']) ?></h3>
                                                    <div class="flex items-center mt-1">
                                                        <span class="text-sm text-gray-600"><?= htmlspecialchars($data['nomor_plat']) ?></span>
                                                        <span class="mx-2">•</span>
                                                        <span class="text-sm text-gray-600"><?= $data['tahun'] ?></span>
                                                        <span class="mx-2">•</span>
                                                        <span class="text-sm font-medium <?= $condition_color ?>"><?= ucfirst($data['kondisi']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="status-badge font-semibold <?= $status_color ?>">
                                            <?= ucfirst($data['status_ketersediaan']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Vehicle Details -->
                                <div class="p-6">
                                    <div class="space-y-4">
                                        <!-- Vehicle Specs -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-600">Warna</p>
                                                <p class="font-medium text-gray-900"><?= htmlspecialchars($data['warna']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600">Bahan Bakar</p>
                                                <p class="font-medium text-gray-900"><?= $data['kapasitas_bahan_bakar'] ?> L</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Kilometer -->
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <p class="text-sm text-gray-600">Kilometer</p>
                                                <p class="text-sm font-medium text-gray-900"><?= number_format($data['kilometer_terakhir'], 0, ',', '.') ?> km</p>
                                            </div>
                                            <div class="progress-bar">
                                                <?php
                                                $km_percentage = min(($data['kilometer_terakhir'] / 100000) * 100, 100);
                                                $km_color = $km_percentage > 80 ? 'bg-red-500' : ($km_percentage > 60 ? 'bg-yellow-500' : 'bg-green-500');
                                                ?>
                                                <div class="progress-fill <?= $km_color ?>" style="width: <?= $km_percentage ?>%"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Document Expiry -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-600">Pajak</p>
                                                <p class="font-medium <?= $tax_due !== null && $tax_due < 30 ? 'text-red-600' : 'text-gray-900' ?>">
                                                    <?= !empty($data['tanggal_pajak']) && $data['tanggal_pajak'] != '0000-00-00' ? 
                                                        date('d M Y', strtotime($data['tanggal_pajak'])) : '-' ?>
                                                    <?php if ($tax_due !== null && $tax_due < 30): ?>
                                                        <br><span class="text-xs text-red-500">(<?= $tax_due ?> hari lagi)</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600">STNK</p>
                                                <p class="font-medium <?= $stnk_due !== null && $stnk_due < 30 ? 'text-red-600' : 'text-gray-900' ?>">
                                                    <?= !empty($data['tanggal_stnk']) && $data['tanggal_stnk'] != '0000-00-00' ? 
                                                        date('d M Y', strtotime($data['tanggal_stnk'])) : '-' ?>
                                                    <?php if ($stnk_due !== null && $stnk_due < 30): ?>
                                                        <br><span class="text-xs text-red-500">(<?= $stnk_due ?> hari lagi)</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Notes -->
                                        <?php if (!empty($data['catatan'])): ?>
                                        <div>
                                            <p class="text-sm text-gray-600">Catatan</p>
                                            <p class="text-sm text-gray-700 mt-1 line-clamp-2"><?= htmlspecialchars($data['catatan']) ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex justify-end space-x-2 mt-6 pt-4 border-t border-gray-200">
                                        <button onclick="viewVehicle(<?= $data['id'] ?>)"
                                            class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editVehicle(<?= $data['id'] ?>)"
                                            class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $data['id'] ?>)"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition"
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="md:col-span-3 bg-white rounded-lg shadow p-12 text-center">
                            <i class="fas fa-car text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-xl font-medium text-gray-900 mb-2">Belum ada kendaraan</h3>
                            <p class="text-gray-600 mb-6">Tambahkan kendaraan pertama Anda untuk memulai</p>
                            <button onclick="openAddModal()"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Tambah Kendaraan
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Vehicle Modal -->
    <div id="vehicleModal" class="fixed inset-0 modal-overlay overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-4 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white modal-content">
            <form method="POST" id="vehicleForm">
                <input type="hidden" name="id" id="vehicleId">
                <input type="hidden" name="add_vehicle" id="formType" value="1">

                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Tambah Kendaraan Baru</h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Informasi Dasar</h4>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Plat *</label>
                                <input type="text" name="nomor_plat" id="nomorPlat" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="B 1234 ABC">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Merk *</label>
                                    <input type="text" name="merk" id="merk" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Toyota">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
                                    <input type="text" name="model" id="model" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Avanza">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tahun *</label>
                                    <input type="number" name="tahun" id="tahun" required min="2000" max="<?= date('Y') + 1 ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="2023">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Warna *</label>
                                    <input type="text" name="warna" id="warna" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Putih">
                                </div>
                            </div>
                        </div>

                        <!-- Technical Information -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Informasi Teknis</h4>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Transmisi *</label>
                                <select name="tipe_transmisi" id="tipeTransmisi" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Tipe</option>
                                    <option value="manual">Manual</option>
                                    <option value="matic">Matic</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kapasitas Bahan Bakar (Liter)</label>
                                <input type="number" name="kapasitas_bahan_bakar" id="kapasitasBahanBakar" step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="40.00">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kilometer Terakhir</label>
                                    <input type="number" name="kilometer_terakhir" id="kilometerTerakhir"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- Status & Documents -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Status & Dokumen</h4>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi *</label>
                                    <select name="kondisi" id="kondisi" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Pilih Kondisi</option>
                                        <option value="baik">Baik</option>
                                        <option value="perbaikan">Perbaikan</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Ketersediaan *</label>
                                    <select name="status_ketersediaan" id="statusKetersediaan" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Pilih Status</option>
                                        <option value="tersedia">Tersedia</option>
                                        <option value="dipakai">Dipakai</option>
                                        <option value="servis">Servis</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pajak</label>
                                    <input type="date" name="tanggal_pajak" id="tanggalPajak"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal STNK</label>
                                    <input type="date" name="tanggal_stnk" id="tanggalStnk"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-4 md:col-span-2">
                            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Catatan</h4>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                                <textarea name="catatan" id="catatan" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Catatan khusus tentang kendaraan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-8 pt-6 border-t">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-300 font-medium">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Vehicle Modal -->
    <div id="viewModal" class="fixed inset-0 modal-overlay overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-4 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white modal-content">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Detail Kendaraan</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="viewContent" class="mt-6">
                    <!-- Detail content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Modal Functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kendaraan Baru';
            document.getElementById('vehicleId').value = '';
            document.getElementById('formType').value = 'add_vehicle';
            document.getElementById('formType').name = 'add_vehicle';
            
            // Reset form
            document.getElementById('vehicleForm').reset();
            
            // Show modal
            document.getElementById('vehicleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function editVehicle(id) {
            // Fetch vehicle data
            fetch(`get_kendaraan.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const vehicle = data.vehicle;
                        
                        document.getElementById('modalTitle').textContent = 'Edit Kendaraan';
                        document.getElementById('vehicleId').value = vehicle.id;
                        document.getElementById('formType').value = 'update_vehicle';
                        document.getElementById('formType').name = 'update_vehicle';
                        
                        // Fill form
                        document.getElementById('nomorPlat').value = vehicle.nomor_plat || '';
                        document.getElementById('merk').value = vehicle.merk || '';
                        document.getElementById('model').value = vehicle.model || '';
                        document.getElementById('tahun').value = vehicle.tahun || '';
                        document.getElementById('warna').value = vehicle.warna || '';
                        document.getElementById('tipeTransmisi').value = vehicle.tipe_transmisi || '';
                        document.getElementById('kapasitasBahanBakar').value = vehicle.kapasitas_bahan_bakar || '';
                        document.getElementById('kilometerTerakhir').value = vehicle.kilometer_terakhir || '';
                        document.getElementById('kondisi').value = vehicle.kondisi || '';
                        document.getElementById('statusKetersediaan').value = vehicle.status_ketersediaan || '';
                        document.getElementById('tanggalPajak').value = vehicle.tanggal_pajak || '';
                        document.getElementById('tanggalStnk').value = vehicle.tanggal_stnk || '';
                        document.getElementById('catatan').value = vehicle.catatan || '';
                        
                        // Show modal
                        document.getElementById('vehicleModal').classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    } else {
                        alert('Gagal memuat data kendaraan!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data!');
                });
        }

        function closeModal() {
            document.getElementById('vehicleModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // View Vehicle Function
        function viewVehicle(id) {
            // Show loading state
            document.getElementById('viewContent').innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-2"></i>
                        <p class="text-gray-600">Memuat detail kendaraan...</p>
                    </div>
                </div>
            `;

            document.getElementById('viewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            fetch(`kendaraan_detail.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('viewContent').innerHTML = `
                        <div class="flex justify-center items-center py-12">
                            <div class="text-center text-red-600">
                                <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                                <p>Gagal memuat detail kendaraan</p>
                            </div>
                        </div>
                    `;
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kendaraan ini?')) {
                window.location.href = `kendaraan.php?delete=${id}`;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const vehicleModal = document.getElementById('vehicleModal');
            const viewModal = document.getElementById('viewModal');

            if (event.target === vehicleModal) {
                closeModal();
            }
            if (event.target === viewModal) {
                closeViewModal();
            }
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeViewModal();
            }
        });
        
        // Form validation
        document.getElementById('vehicleForm').addEventListener('submit', function(e) {
            const nomorPlat = document.getElementById('nomorPlat').value.trim();
            const tahun = parseInt(document.getElementById('tahun').value);
            const currentYear = new Date().getFullYear();
            
            if (nomorPlat.length < 3) {
                e.preventDefault();
                alert('Nomor plat harus minimal 3 karakter!');
                document.getElementById('nomorPlat').focus();
                return false;
            }
            
            if (tahun < 2000 || tahun > currentYear + 1) {
                e.preventDefault();
                alert(`Tahun harus antara 2000 dan ${currentYear + 1}!`);
                document.getElementById('tahun').focus();
                return false;
            }
            
            return true;
        });
    </script>
</body>

</html>