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
        'kapasitas_bahan_bakar' => !empty($_POST['kapasitas_bahan_bakar']) ? $_POST['kapasitas_bahan_bakar'] : null,
        'kondisi' => $_POST['kondisi'] ?? 'baik',
        'status_ketersediaan' => $_POST['status_ketersediaan'] ?? 'tersedia',
        'tanggal_pajak' => !empty($_POST['tanggal_pajak']) ? $_POST['tanggal_pajak'] : null,
        'tanggal_stnk' => !empty($_POST['tanggal_stnk']) ? $_POST['tanggal_stnk'] : null,
        'kilometer_terakhir' => $_POST['kilometer_terakhir'] ?? 0,
        'catatan' => !empty($_POST['catatan']) ? $_POST['catatan'] : null,
        'foto' => null,
    ];

    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/kendaraan/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['foto']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['foto']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
                $data['foto'] = $fileName;
            } else {
                $error = "Gagal mengupload foto!";
            }
        } else {
            $error = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
        }
    }

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
                 tanggal_pajak, tanggal_stnk, kilometer_terakhir, catatan, foto) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $data['foto']
            ];

            if ($stmt->execute($values)) {
                $success = "Kendaraan berhasil ditambahkan!";
                header("Location: kendaraan.php?success=" . urlencode($success));
                exit;
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
        'kapasitas_bahan_bakar' => !empty($_POST['kapasitas_bahan_bakar']) ? $_POST['kapasitas_bahan_bakar'] : null,
        'kondisi' => $_POST['kondisi'] ?? 'baik',
        'status_ketersediaan' => $_POST['status_ketersediaan'] ?? 'tersedia',
        'tanggal_pajak' => !empty($_POST['tanggal_pajak']) ? $_POST['tanggal_pajak'] : null,
        'tanggal_stnk' => !empty($_POST['tanggal_stnk']) ? $_POST['tanggal_stnk'] : null,
        'kilometer_terakhir' => $_POST['kilometer_terakhir'] ?? 0,
        'catatan' => !empty($_POST['catatan']) ? $_POST['catatan'] : null,
    ];

    try {
        // Cek apakah nomor plat sudah ada (selain kendaraan yang sedang diupdate)
        $check_stmt = $db->prepare("SELECT id FROM kendaraan WHERE nomor_plat = ? AND id != ?");
        $check_stmt->execute([$data['nomor_plat'], $id]);
        
        if ($check_stmt->fetch()) {
            $error = "Nomor plat sudah terdaftar untuk kendaraan lain!";
        } else {
            // Handle file upload if new photo provided
            $foto_field = "";
            $foto_value = null;
            
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/kendaraan/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['foto']['name']);
                $uploadFile = $uploadDir . $fileName;
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($_FILES['foto']['tmp_name']);
                
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
                        // Delete old photo if exists
                        $stmt_old = $db->prepare("SELECT foto FROM kendaraan WHERE id = ?");
                        $stmt_old->execute([$id]);
                        $old_foto = $stmt_old->fetchColumn();
                        
                        if ($old_foto && file_exists($uploadDir . $old_foto)) {
                            unlink($uploadDir . $old_foto);
                        }
                        
                        $foto_field = ", foto = ?";
                        $foto_value = $fileName;
                    } else {
                        $error = "Gagal mengupload foto!";
                    }
                } else {
                    $error = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
                }
            }

            $stmt = $db->prepare("
                UPDATE kendaraan SET 
                nomor_plat = ?, merk = ?, model = ?, tahun = ?, tipe_transmisi = ?, 
                warna = ?, kapasitas_bahan_bakar = ?, kondisi = ?, status_ketersediaan = ?,
                tanggal_pajak = ?, tanggal_stnk = ?, kilometer_terakhir = ?, catatan = ?
                $foto_field
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
                $data['catatan']
            ];
            
            if ($foto_value) {
                $values[] = $foto_value;
            }
            
            $values[] = $id;

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

// Handle delete vehicle - PERBAIKAN DISINI
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Cek apakah kendaraan sedang digunakan di jadwal
        // PERBAIKAN: Menggunakan kendaraan_id bukan mobil_digunakan
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM jadwal_kursus WHERE kendaraan_id = ?");
        $check_stmt->execute([$id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = "Kendaraan tidak dapat dihapus karena masih digunakan dalam jadwal kursus!";
        } else {
            // Delete photo file if exists
            $stmt_foto = $db->prepare("SELECT foto FROM kendaraan WHERE id = ?");
            $stmt_foto->execute([$id]);
            $foto = $stmt_foto->fetchColumn();
            
            if ($foto) {
                $uploadDir = '../uploads/kendaraan/';
                if (file_exists($uploadDir . $foto)) {
                    unlink($uploadDir . $foto);
                }
            }
            
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

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kendaraan - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        /* Form styling */
        .error-input {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .success-input {
            border-color: #10b981 !important;
            background-color: #f0fdf4 !important;
        }
        .error-label {
            color: #ef4444 !important;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .info-message {
            color: #3b82f6;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .valid-indicator {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            display: none;
        }
        .input-wrapper {
            position: relative;
        }
        .cursor-not-allowed {
            cursor: not-allowed;
        }
        input[readonly], select[readonly] {
            background-color: #f9fafb !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            user-select: none !important;
        }
        input[readonly]:focus {
            border-color: #d1d5db !important;
            box-shadow: none !important;
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
        
        /* Photo preview */
        .photo-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px dashed #d1d5db;
        }
        .photo-preview.has-photo {
            border-style: solid;
            border-color: #3b82f6;
        }
        .photo-remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden relative">
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
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

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

                <!-- Tambah Kendaraan Button -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    <i class="fas fa-car text-blue-600 mr-2"></i>
                                    Manajemen Kendaraan
                                </h3>
                                <p class="text-gray-600">Tambah atau kelola kendaraan yang tersedia</p>
                            </div>
                            <button onclick="toggleTambahForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle form tambah kendaraan">
                                <i id="toggle-icon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Form Tambah Kendaraan (Hidden by default) -->
                        <div id="tambahForm" class="mt-6 hidden">
                            <form method="POST" class="space-y-7" id="formKendaraan" novalidate enctype="multipart/form-data">
                                <input type="hidden" name="id" id="vehicleId">
                                <input type="hidden" name="current_foto" id="currentFoto">

                                <!-- Informasi Dasar -->
                                <div class="space-y-5">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-car text-blue-600"></i> Informasi Dasar
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Nomor Plat -->
                                        <div class="input-wrapper">
                                            <label for="nomor_plat" class="block text-sm text-gray-700 mb-1">Nomor Plat *</label>
                                            <input type="text" id="nomor_plat" name="nomor_plat" required
                                                placeholder="B 1234 ABC"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="nomor_plat_error">Nomor plat wajib diisi (minimal 3 karakter)</div>
                                        </div>
                                        
                                        <!-- Tahun -->
                                        <div class="input-wrapper">
                                            <label for="tahun" class="block text-sm text-gray-700 mb-1">Tahun *</label>
                                            <input type="number" id="tahun" name="tahun" required 
                                                placeholder="<?= date('Y') ?>"
                                                min="2000"
                                                max="<?= date('Y') + 1 ?>"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="tahun_error">Tahun harus antara 2000 dan <?= date('Y') + 1 ?></div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Merk -->
                                        <div class="input-wrapper">
                                            <label for="merk" class="block text-sm text-gray-700 mb-1">Merk *</label>
                                            <input type="text" id="merk" name="merk" required 
                                                placeholder="Toyota"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="merk_error">Merk wajib diisi</div>
                                        </div>
                                        
                                        <!-- Model -->
                                        <div class="input-wrapper">
                                            <label for="model" class="block text-sm text-gray-700 mb-1">Model *</label>
                                            <input type="text" id="model" name="model" required 
                                                placeholder="Avanza"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="model_error">Model wajib diisi</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Warna -->
                                        <div class="input-wrapper">
                                            <label for="warna" class="block text-sm text-gray-700 mb-1">Warna *</label>
                                            <input type="text" id="warna" name="warna" required 
                                                placeholder="Putih"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="warna_error">Warna wajib diisi</div>
                                        </div>
                                        
                                        <!-- Tipe Transmisi -->
                                        <div class="input-wrapper">
                                            <label for="tipe_transmisi" class="block text-sm text-gray-700 mb-1">Tipe Transmisi *</label>
                                            <select id="tipe_transmisi" name="tipe_transmisi" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                                <option value="">Pilih Tipe</option>
                                                <option value="manual">Manual</option>
                                                <option value="matic">Matic</option>
                                            </select>
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="tipe_transmisi_error">Pilih tipe transmisi</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Foto Kendaraan -->
                                <div class="space-y-5 pt-4 border-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-camera text-blue-600"></i> Foto Kendaraan
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Foto Preview -->
                                        <div>
                                            <label class="block text-sm text-gray-700 mb-2">Preview Foto</label>
                                            <div class="relative">
                                                <img id="fotoPreview" src="" alt="Preview foto kendaraan" 
                                                    class="photo-preview">
                                                <button type="button" id="removeFotoBtn" 
                                                    class="photo-remove-btn hidden"
                                                    onclick="removeFoto()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2" id="currentFotoName"></p>
                                        </div>
                                        
                                        <!-- Upload Foto -->
                                        <div>
                                            <label for="foto" class="block text-sm text-gray-700 mb-2">Upload Foto Baru</label>
                                            <input type="file" id="foto" name="foto" accept="image/*"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                                                onchange="previewFoto(this)">
                                            <div class="error-message" id="foto_error">Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.</div>
                                            <div class="info-message">Maksimum ukuran file: 2MB. Format: JPG, PNG, GIF, WebP</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informasi Teknis -->
                                <div class="space-y-5 pt-4 border-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-cogs text-blue-600"></i> Informasi Teknis
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Kapasitas Bahan Bakar -->
                                        <div class="input-wrapper">
                                            <label for="kapasitas_bahan_bakar" class="block text-sm text-gray-700 mb-1">Kapasitas Bahan Bakar (Liter)</label>
                                            <input type="number" id="kapasitas_bahan_bakar" name="kapasitas_bahan_bakar" step="0.01"
                                                placeholder="40.00"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <div class="error-message" id="kapasitas_bahan_bakar_error">Kapasitas bahan bakar harus angka positif</div>
                                        </div>
                                        
                                        <!-- Kilometer Terakhir -->
                                        <div class="input-wrapper">
                                            <label for="kilometer_terakhir" class="block text-sm text-gray-700 mb-1">Kilometer Terakhir</label>
                                            <input type="number" id="kilometer_terakhir" name="kilometer_terakhir"
                                                placeholder="0"
                                                min="0"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <div class="error-message" id="kilometer_terakhir_error">Kilometer harus angka positif</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Dokumen -->
                                <div class="space-y-5 pt-4 border-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-clipboard-check text-blue-600"></i> Status & Dokumen
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Kondisi -->
                                        <div class="input-wrapper">
                                            <label for="kondisi" class="block text-sm text-gray-700 mb-1">Kondisi *</label>
                                            <select id="kondisi" name="kondisi" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                                <option value="">Pilih Kondisi</option>
                                                <option value="baik">Baik</option>
                                                <option value="perbaikan">Perbaikan</option>
                                                <option value="rusak">Rusak</option>
                                            </select>
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="kondisi_error">Pilih kondisi kendaraan</div>
                                        </div>
                                        
                                        <!-- Status Ketersediaan -->
                                        <div class="input-wrapper">
                                            <label for="status_ketersediaan" class="block text-sm text-gray-700 mb-1">Status Ketersediaan *</label>
                                            <select id="status_ketersediaan" name="status_ketersediaan" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                                <option value="">Pilih Status</option>
                                                <option value="tersedia">Tersedia</option>
                                                <option value="dipakai">Dipakai</option>
                                                <option value="servis">Servis</option>
                                            </select>
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="status_ketersediaan_error">Pilih status ketersediaan</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Tanggal Pajak -->
                                        <div class="input-wrapper">
                                            <label for="tanggal_pajak" class="block text-sm text-gray-700 mb-1">Tanggal Pajak</label>
                                            <input type="date" id="tanggal_pajak" name="tanggal_pajak"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <div class="info-message">Format: YYYY-MM-DD</div>
                                        </div>
                                        
                                        <!-- Tanggal STNK -->
                                        <div class="input-wrapper">
                                            <label for="tanggal_stnk" class="block text-sm text-gray-700 mb-1">Tanggal STNK</label>
                                            <input type="date" id="tanggal_stnk" name="tanggal_stnk"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <div class="info-message">Format: YYYY-MM-DD</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Catatan -->
                                <div class="space-y-5 pt-4 border-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-sticky-note text-blue-600"></i> Catatan
                                    </h3>

                                    <!-- Catatan Tambahan -->
                                    <div>
                                        <label for="catatan" class="block text-sm text-gray-700 mb-1">Catatan Tambahan</label>
                                        <textarea id="catatan" name="catatan" rows="3"
                                            placeholder="Catatan khusus tentang kendaraan..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-4">
                                    <button type="submit" id="submitButton"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition duration-300 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                                        <i class="fas fa-save"></i>
                                        <span id="buttonText">Simpan Kendaraan Baru</span>
                                    </button>
                                </div>
                                
                                <!-- Form Status -->
                                <div id="formStatus" class="hidden p-4 rounded-lg text-center"></div>
                            </form>
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
                            
                            // Foto path
                            $foto_path = !empty($data['foto']) ? '../uploads/kendaraan/' . $data['foto'] : '../assets/images/no-car-image.jpg';
                            
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
                                
                                <!-- Vehicle Image -->
                                <div class="h-48 overflow-hidden">
                                    <img src="<?= $foto_path ?>" 
                                         alt="<?= htmlspecialchars($data['merk'] . ' ' . $data['model']) ?>" 
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                                         onerror="this.src='../assets/images/no-car-image.jpg'">
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
                                                <p class="font-medium text-gray-900"><?= $data['kapasitas_bahan_bakar'] ? $data['kapasitas_bahan_bakar'] . ' L' : '-' ?></p>
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
                            <button onclick="toggleTambahForm()"
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Tambah Kendaraan
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- View Vehicle Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Detail Kendaraan</h3>
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

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // ==================== FUNGSI UTAMA ====================
        
        // Toggle form tambah kendaraan
        function toggleTambahForm() {
            const form = document.getElementById('tambahForm');
            const icon = document.getElementById('toggle-icon');

            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
                
                resetForm();
                
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }

        // Reset form
        function resetForm() {
            const form = document.getElementById('formKendaraan');
            if (form) {
                form.reset();
                
                // Reset hidden inputs
                document.getElementById('vehicleId').value = '';
                document.getElementById('currentFoto').value = '';
                
                // Reset button text
                document.getElementById('buttonText').textContent = 'Simpan Kendaraan Baru';
                
                // Reset foto preview
                document.getElementById('fotoPreview').src = '';
                document.getElementById('fotoPreview').classList.remove('has-photo');
                document.getElementById('removeFotoBtn').classList.add('hidden');
                document.getElementById('currentFotoName').textContent = '';
                document.getElementById('foto').value = '';
                
                // Reset error messages
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Reset input styling
                document.querySelectorAll('#tambahForm input, #tambahForm select, #tambahForm textarea').forEach(el => {
                    el.classList.remove('error-input', 'success-input');
                });
                
                // Reset labels
                document.querySelectorAll('#tambahForm label').forEach(el => {
                    el.classList.remove('error-label');
                });
                
                // Reset valid indicators
                document.querySelectorAll('.valid-indicator').forEach(el => {
                    el.style.display = 'none';
                });
            }
        }

        // Preview foto
        function previewFoto(input) {
            const preview = document.getElementById('fotoPreview');
            const removeBtn = document.getElementById('removeFotoBtn');
            const fotoName = document.getElementById('currentFotoName');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('has-photo');
                    removeBtn.classList.remove('hidden');
                    fotoName.textContent = input.files[0].name;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Remove foto
        function removeFoto() {
            const preview = document.getElementById('fotoPreview');
            const removeBtn = document.getElementById('removeFotoBtn');
            const fotoInput = document.getElementById('foto');
            const fotoName = document.getElementById('currentFotoName');
            const currentFoto = document.getElementById('currentFoto');
            
            preview.src = '';
            preview.classList.remove('has-photo');
            removeBtn.classList.add('hidden');
            fotoInput.value = '';
            fotoName.textContent = currentFoto.value ? 'Foto saat ini akan dihapus' : '';
        }

        // ==================== VALIDASI FUNGSI ====================
        
        // Tampilkan/sembunyikan error message
        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorElement = document.getElementById(inputId + '_error');
            const validIndicator = input.parentElement.querySelector('.valid-indicator');
            
            if (input) {
                input.classList.remove('success-input');
                input.classList.add('error-input');
                
                const label = input.parentElement.querySelector('label');
                if (label) {
                    label.classList.add('error-label');
                }
            }
            
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
            
            if (validIndicator) {
                validIndicator.style.display = 'none';
            }
        }

        function showSuccess(inputId) {
            const input = document.getElementById(inputId);
            const errorElement = document.getElementById(inputId + '_error');
            const validIndicator = input.parentElement.querySelector('.valid-indicator');
            
            if (input) {
                input.classList.remove('error-input');
                input.classList.add('success-input');
                
                const label = input.parentElement.querySelector('label');
                if (label) {
                    label.classList.remove('error-label');
                }
            }
            
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            
            if (validIndicator) {
                validIndicator.style.display = 'block';
            }
        }

        function resetValidation(input) {
            if (!input) return;
            
            input.classList.remove('error-input', 'success-input');
            
            const errorElement = document.getElementById(input.id + '_error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            
            const label = input.parentElement.querySelector('label');
            if (label) {
                label.classList.remove('error-label');
            }
            
            const validIndicator = input.parentElement.querySelector('.valid-indicator');
            if (validIndicator) {
                validIndicator.style.display = 'none';
            }
        }

        // Validasi select element
        function validateSelect(selectElement) {
            if (!selectElement.value) {
                showError(selectElement.id, 'Pilihan ini wajib diisi');
                return false;
            } else {
                showSuccess(selectElement.id);
                return true;
            }
        }

        // Validasi nomor plat
        function validateNomorPlat(plat) {
            return plat.length >= 3;
        }

        // Validasi tahun
        function validateTahun(tahun) {
            const currentYear = new Date().getFullYear();
            return tahun >= 2000 && tahun <= currentYear + 1;
        }

        // Validasi file
        function validateFile(fileInput) {
            if (!fileInput.files || !fileInput.files[0]) {
                return true; // File tidak wajib
            }
            
            const file = fileInput.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!allowedTypes.includes(file.type)) {
                showError('foto', 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.');
                return false;
            }
            
            if (file.size > maxSize) {
                showError('foto', 'Ukuran file terlalu besar. Maksimum 2MB.');
                return false;
            }
            
            resetValidation(fileInput);
            return true;
        }

        // Validasi form sebelum submit
        function validateForm() {
            let isValid = true;
            
            // Nomor Plat
            const nomorPlatInput = document.getElementById('nomor_plat');
            if (!nomorPlatInput.value.trim() || !validateNomorPlat(nomorPlatInput.value.trim())) {
                showError('nomor_plat', 'Nomor plat wajib diisi (minimal 3 karakter)');
                isValid = false;
            } else {
                showSuccess('nomor_plat');
            }
            
            // Tahun
            const tahunInput = document.getElementById('tahun');
            const tahun = parseInt(tahunInput.value);
            if (!tahunInput.value || !validateTahun(tahun)) {
                showError('tahun', `Tahun harus antara 2000 dan ${new Date().getFullYear() + 1}`);
                isValid = false;
            } else {
                showSuccess('tahun');
            }
            
            // Merk
            const merkInput = document.getElementById('merk');
            if (!merkInput.value.trim()) {
                showError('merk', 'Merk wajib diisi');
                isValid = false;
            } else {
                showSuccess('merk');
            }
            
            // Model
            const modelInput = document.getElementById('model');
            if (!modelInput.value.trim()) {
                showError('model', 'Model wajib diisi');
                isValid = false;
            } else {
                showSuccess('model');
            }
            
            // Warna
            const warnaInput = document.getElementById('warna');
            if (!warnaInput.value.trim()) {
                showError('warna', 'Warna wajib diisi');
                isValid = false;
            } else {
                showSuccess('warna');
            }
            
            // Tipe Transmisi
            const tipeTransmisiSelect = document.getElementById('tipe_transmisi');
            if (!validateSelect(tipeTransmisiSelect)) {
                isValid = false;
            }
            
            // File
            const fotoInput = document.getElementById('foto');
            if (!validateFile(fotoInput)) {
                isValid = false;
            }
            
            // Kapasitas Bahan Bakar (jika diisi)
            const kapasitasInput = document.getElementById('kapasitas_bahan_bakar');
            if (kapasitasInput.value && (isNaN(kapasitasInput.value) || parseFloat(kapasitasInput.value) < 0)) {
                showError('kapasitas_bahan_bakar', 'Kapasitas bahan bakar harus angka positif');
                isValid = false;
            } else {
                resetValidation(kapasitasInput);
            }
            
            // Kilometer Terakhir (jika diisi)
            const kilometerInput = document.getElementById('kilometer_terakhir');
            if (kilometerInput.value && (isNaN(kilometerInput.value) || parseInt(kilometerInput.value) < 0)) {
                showError('kilometer_terakhir', 'Kilometer harus angka positif');
                isValid = false;
            } else {
                resetValidation(kilometerInput);
            }
            
            // Kondisi
            const kondisiSelect = document.getElementById('kondisi');
            if (!validateSelect(kondisiSelect)) {
                isValid = false;
            }
            
            // Status Ketersediaan
            const statusSelect = document.getElementById('status_ketersediaan');
            if (!validateSelect(statusSelect)) {
                isValid = false;
            }
            
            return isValid;
        }

        // ==================== EVENT LISTENERS ====================
        
        document.addEventListener('DOMContentLoaded', function() {
            // Validasi Nomor Plat
            const nomorPlatInput = document.getElementById('nomor_plat');
            if (nomorPlatInput) {
                nomorPlatInput.addEventListener('blur', function() {
                    if (!validateNomorPlat(this.value.trim())) {
                        showError('nomor_plat', 'Nomor plat minimal 3 karakter');
                    } else {
                        showSuccess('nomor_plat');
                    }
                });
            }

            // Validasi Tahun
            const tahunInput = document.getElementById('tahun');
            if (tahunInput) {
                tahunInput.addEventListener('blur', function() {
                    const tahun = parseInt(this.value);
                    if (!validateTahun(tahun)) {
                        showError('tahun', `Tahun harus antara 2000 dan ${new Date().getFullYear() + 1}`);
                    } else {
                        showSuccess('tahun');
                    }
                });
            }

            // Validasi Merk
            const merkInput = document.getElementById('merk');
            if (merkInput) {
                merkInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        showError('merk', 'Merk wajib diisi');
                    } else {
                        showSuccess('merk');
                    }
                });
            }

            // Validasi Model
            const modelInput = document.getElementById('model');
            if (modelInput) {
                modelInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        showError('model', 'Model wajib diisi');
                    } else {
                        showSuccess('model');
                    }
                });
            }

            // Validasi Warna
            const warnaInput = document.getElementById('warna');
            if (warnaInput) {
                warnaInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        showError('warna', 'Warna wajib diisi');
                    } else {
                        showSuccess('warna');
                    }
                });
            }

            // Validasi Tipe Transmisi
            const tipeTransmisiSelect = document.getElementById('tipe_transmisi');
            if (tipeTransmisiSelect) {
                tipeTransmisiSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Validasi Kondisi
            const kondisiSelect = document.getElementById('kondisi');
            if (kondisiSelect) {
                kondisiSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Validasi Status Ketersediaan
            const statusSelect = document.getElementById('status_ketersediaan');
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Validasi Kapasitas Bahan Bakar
            const kapasitasInput = document.getElementById('kapasitas_bahan_bakar');
            if (kapasitasInput) {
                kapasitasInput.addEventListener('blur', function() {
                    if (this.value && (isNaN(this.value) || parseFloat(this.value) < 0)) {
                        showError('kapasitas_bahan_bakar', 'Kapasitas bahan bakar harus angka positif');
                    } else {
                        resetValidation(this);
                    }
                });
            }

            // Validasi Kilometer
            const kilometerInput = document.getElementById('kilometer_terakhir');
            if (kilometerInput) {
                kilometerInput.addEventListener('blur', function() {
                    if (this.value && (isNaN(this.value) || parseInt(this.value) < 0)) {
                        showError('kilometer_terakhir', 'Kilometer harus angka positif');
                    } else {
                        resetValidation(this);
                    }
                });
            }

            // Validasi File
            const fotoInput = document.getElementById('foto');
            if (fotoInput) {
                fotoInput.addEventListener('change', function() {
                    validateFile(this);
                });
            }

            // Form submission dengan pencegahan double submit
            const form = document.getElementById('formKendaraan');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Validasi form
                    if (!validateForm()) {
                        const firstError = document.querySelector('.error-input, .error-message[style*="block"]');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }
                    
                    // Set nama form berdasarkan mode (add/edit)
                    const vehicleId = document.getElementById('vehicleId').value;
                    const formAction = document.createElement('input');
                    formAction.type = 'hidden';
                    formAction.name = vehicleId ? 'update_vehicle' : 'add_vehicle';
                    this.appendChild(formAction);
                    
                    // Submit form
                    this.submit();
                });
            }
        });

        // ==================== FUNGSI LAINNYA ====================
        
        // Edit Vehicle Function
        function editVehicle(id) {
            // Fetch vehicle data
            fetch(`get_kendaraan.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const vehicle = data.vehicle;
                        
                        // Buka form jika belum terbuka
                        const form = document.getElementById('tambahForm');
                        if (form.classList.contains('hidden')) {
                            toggleTambahForm();
                        }
                        
                        // Set form untuk edit
                        document.getElementById('vehicleId').value = vehicle.id;
                        document.getElementById('buttonText').textContent = 'Update Kendaraan';
                        
                        // Fill form
                        document.getElementById('nomor_plat').value = vehicle.nomor_plat || '';
                        document.getElementById('tahun').value = vehicle.tahun || '';
                        document.getElementById('merk').value = vehicle.merk || '';
                        document.getElementById('model').value = vehicle.model || '';
                        document.getElementById('warna').value = vehicle.warna || '';
                        document.getElementById('tipe_transmisi').value = vehicle.tipe_transmisi || '';
                        document.getElementById('kapasitas_bahan_bakar').value = vehicle.kapasitas_bahan_bakar || '';
                        document.getElementById('kilometer_terakhir').value = vehicle.kilometer_terakhir || '';
                        document.getElementById('kondisi').value = vehicle.kondisi || '';
                        document.getElementById('status_ketersediaan').value = vehicle.status_ketersediaan || '';
                        document.getElementById('tanggal_pajak').value = vehicle.tanggal_pajak || '';
                        document.getElementById('tanggal_stnk').value = vehicle.tanggal_stnk || '';
                        document.getElementById('catatan').value = vehicle.catatan || '';
                        
                        // Handle foto
                        const currentFoto = document.getElementById('currentFoto');
                        const fotoPreview = document.getElementById('fotoPreview');
                        const currentFotoName = document.getElementById('currentFotoName');
                        
                        if (vehicle.foto) {
                            currentFoto.value = vehicle.foto;
                            fotoPreview.src = '../uploads/kendaraan/' + vehicle.foto;
                            fotoPreview.classList.add('has-photo');
                            currentFotoName.textContent = 'Foto saat ini: ' + vehicle.foto;
                        }
                        
                        // Scroll ke form
                        form.scrollIntoView({ behavior: 'smooth' });
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal memuat data kendaraan'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memuat data!'
                    });
                });
        }

        // View Vehicle Function
        function viewVehicle(id) {
            fetch(`kendaraan_detail.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewContent').innerHTML = html;
                    document.getElementById('viewModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat detail kendaraan'
                    });
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data kendaraan akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `kendaraan.php?delete=${id}`;
                }
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');

            if (event.target === viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>