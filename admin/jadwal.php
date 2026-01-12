<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle add schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $data = [
        'pendaftaran_id' => $_POST['pendaftaran_id'],
        'instruktur_id' => $_POST['instruktur_id'],
        'tanggal_jadwal' => $_POST['tanggal_jadwal'],
        'jam_mulai' => $_POST['jam_mulai'],
        'jam_selesai' => $_POST['jam_selesai'],
        'tipe_sesi' => $_POST['tipe_sesi'],
        'lokasi' => $_POST['lokasi'] ?? '',
        'kendaraan_id' => $_POST['kendaraan_id'] ?? null, // PERUBAHAN DI SINI
        'status' => 'terjadwal'
    ];

    // Simpan data form untuk ditampilkan kembali jika error
    $form_data = $data;
    $form_data['student_name'] = $_POST['student_name'] ?? '';
    $form_data['student_nomor'] = $_POST['student_nomor'] ?? '';
    $form_data['student_paket'] = $_POST['student_paket'] ?? '';
    $form_data['vehicle_display'] = $_POST['vehicle_display'] ?? ''; // Tambah ini

    try {
        // Cek apakah instruktur sudah memiliki jadwal pada tanggal dan waktu yang sama
        $check_stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM jadwal_kursus 
            WHERE instruktur_id = ? 
            AND tanggal_jadwal = ? 
            AND status NOT IN ('dibatalkan', 'selesai')
            AND (
                (jam_mulai < ? AND jam_selesai > ?) OR
                (jam_mulai < ? AND jam_selesai > ?) OR
                (jam_mulai >= ? AND jam_selesai <= ?) OR
                (? BETWEEN jam_mulai AND jam_selesai) OR
                (? BETWEEN jam_mulai AND jam_selesai)
            )
        ");
        
        $check_stmt->execute([
            $data['instruktur_id'],
            $data['tanggal_jadwal'],
            $data['jam_selesai'],
            $data['jam_mulai'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $data['jam_mulai'],
            $data['jam_selesai']
        ]);
        
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = "Instruktur sudah memiliki jadwal pada tanggal dan waktu tersebut! Silakan pilih waktu lain.";
        } else {
            // Cek apakah siswa sudah memiliki jadwal pada tanggal dan waktu yang sama
            $check_student_stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM jadwal_kursus 
                WHERE pendaftaran_id = ? 
                AND tanggal_jadwal = ? 
                AND status NOT IN ('dibatalkan', 'selesai')
                AND (
                    (jam_mulai < ? AND jam_selesai > ?) OR
                    (jam_mulai < ? AND jam_selesai > ?) OR
                    (jam_mulai >= ? AND jam_selesai <= ?) OR
                    (? BETWEEN jam_mulai AND jam_selesai) OR
                    (? BETWEEN jam_mulai AND jam_selesai)
                )
            ");
            
            $check_student_stmt->execute([
                $data['pendaftaran_id'],
                $data['tanggal_jadwal'],
                $data['jam_selesai'],
                $data['jam_mulai'],
                $data['jam_mulai'],
                $data['jam_selesai'],
                $data['jam_mulai'],
                $data['jam_selesai'],
                $data['jam_mulai'],
                $data['jam_selesai']
            ]);
            
            $student_result = $check_student_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student_result['count'] > 0) {
                $error = "Siswa sudah memiliki jadwal pada tanggal dan waktu tersebut!";
            } else {
                // Jika tidak ada konflik, tambahkan jadwal
                $stmt = $db->prepare("
                    INSERT INTO jadwal_kursus 
                    (pendaftaran_id, instruktur_id, tanggal_jadwal, jam_mulai, jam_selesai, tipe_sesi, lokasi, kendaraan_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute(array_values($data))) {
                    $success = "Jadwal berhasil ditambahkan!";
                    // Reset form data setelah sukses
                    $form_data = null;
                } else {
                    $error = "Gagal menambahkan jadwal!";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle update schedule status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $kehadiran_siswa = $_POST['kehadiran_siswa'] ?? null;
    $catatan_instruktur = $_POST['catatan_instruktur'] ?? '';

    $stmt = $db->prepare("UPDATE jadwal_kursus SET status = ?, kehadiran_siswa = ?, catatan_instruktur = ? WHERE id = ?");
    if ($stmt->execute([$status, $kehadiran_siswa, $catatan_instruktur, $id])) {
        $success = "Status jadwal berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate status jadwal!";
    }
}

// Handle delete schedule
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM jadwal_kursus WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Jadwal berhasil dihapus!";
    } else {
        $error = "Gagal menghapus jadwal!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$tipe_filter = $_GET['tipe'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';

// Query untuk data jadwal - JOIN dengan kendaraan untuk mendapatkan detail kendaraan
$query = "SELECT jk.*, ps.nama_lengkap, ps.nomor_pendaftaran, 
                 i.nama_lengkap as nama_instruktur,
                 k.nomor_plat, k.merk, k.model, k.tahun
          FROM jadwal_kursus jk 
          JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id 
          JOIN instruktur i ON jk.instruktur_id = i.id 
          LEFT JOIN kendaraan k ON jk.kendaraan_id = k.id  -- PERUBAHAN: LEFT JOIN untuk kendaraan
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND jk.status = ?";
    $params[] = $status_filter;
}

if ($tipe_filter) {
    $query .= " AND jk.tipe_sesi = ?";
    $params[] = $tipe_filter;
}

if ($tanggal_filter) {
    $query .= " AND jk.tanggal_jadwal = ?";
    $params[] = $tanggal_filter;
}

$query .= " ORDER BY jk.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_jadwal = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus")->fetch()['total'];
$jadwal_terjadwal = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE status = 'terjadwal'")->fetch()['total'];
$jadwal_selesai = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE status = 'selesai'")->fetch()['total'];
$jadwal_hari_ini = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE tanggal_jadwal = CURDATE()")->fetch()['total'];

// Get data for forms - untuk search siswa
$active_registrations = $db->query("
    SELECT ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, ps.telepon, pk.nama_paket 
    FROM pendaftaran_siswa ps 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE ps.status_pendaftaran IN ('dikonfirmasi', 'diproses')
    ORDER BY ps.nama_lengkap
")->fetchAll(PDO::FETCH_ASSOC);

// Query untuk instruktur
$instrukturs = $db->query("SELECT id, nama_lengkap, spesialisasi FROM instruktur ORDER BY nama_lengkap")->fetchAll(PDO::FETCH_ASSOC);

// ========== TAMBAHAN: Query untuk kendaraan ==========
// Ambil data kendaraan yang tersedia (status = 'tersedia')
$kendaraan = $db->query("
    SELECT id, nomor_plat, merk, model, tahun, tipe_transmisi, warna, status_ketersediaan
    FROM kendaraan 
    WHERE status_ketersediaan = 'tersedia' OR status_ketersediaan = 'dipakai'
    ORDER BY merk, model
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Krishna Driving</title>
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
        
        /* Search Results Styling */
        .search-container {
            position: relative;
        }
        
        #studentSearchResults {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 9999;
            margin-top: 2px;
        }

        #studentSearchResults::-webkit-scrollbar {
            width: 6px;
        }

        #studentSearchResults::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }

        #studentSearchResults::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 3px;
        }

        .student-search-result {
            transition: all 0.2s ease;
        }

        .student-search-result:hover {
            background-color: #f8fafc !important;
            transform: translateX(2px);
        }

        .student-search-result:last-child {
            border-bottom: none;
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Time conflict warning */
        .time-conflict {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .conflict-message {
            font-size: 0.875rem;
            color: #dc2626;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
        }
        
        .conflict-message i {
            margin-right: 0.25rem;
        }
        
        /* Vehicle dropdown styling */
        .vehicle-option {
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }
        
        .vehicle-option:hover {
            background-color: #f8fafc;
        }
        
        .vehicle-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f1f5f9;
        }
        
        .vehicle-option.selected {
            background-color: #dbeafe;
            border-left: 3px solid #3b82f6;
        }
        
        .vehicle-status-badge {
            font-size: 0.7rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        
        .vehicle-status-tersedia {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .vehicle-status-dipakai {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .vehicle-status-servis {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .vehicle-transmission-badge {
            font-size: 0.7rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        
        .vehicle-transmission-manual {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .vehicle-transmission-matic {
            background-color: #e9d5ff;
            color: #7c3aed;
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Jadwal Kursus</h1>
                        <p class="text-gray-600">Atur jadwal teori dan praktik mengemudi</p>
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

                <!-- Add Schedule Form - Hidden by default, tapi tetap terbuka jika ada error -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-4">
                        <!-- Toggle Button -->
                        <div class="flex justify-between items-center">
                            <div class="px-4 py-3">
                                <h3 class="text-lg font-medium text-gray-900">Tambah Jadwal Baru</h3>
                            </div>
                            <button id="toggleScheduleFormBtn"
                                onclick="toggleScheduleForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle form">
                                <i id="toggleScheduleFormIcon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Form - Tetap terbuka jika ada error atau form data tersimpan -->
                        <div id="scheduleFormContainer" class="mt-6 p-6 <?= (isset($error) && isset($form_data)) ? '' : 'hidden' ?>">
                            <form method="POST" id="addScheduleForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <input type="hidden" name="add_schedule" value="1">
                                <input type="hidden" name="student_name" id="studentNameField" value="<?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?>">
                                <input type="hidden" name="student_nomor" id="studentNomorField" value="<?= isset($form_data['student_nomor']) ? htmlspecialchars($form_data['student_nomor']) : '' ?>">
                                <input type="hidden" name="student_paket" id="studentPaketField" value="<?= isset($form_data['student_paket']) ? htmlspecialchars($form_data['student_paket']) : '' ?>">

                                <!-- Search Student -->
                                <div class="search-container md:col-span-2 lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cari Siswa *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                        <input type="text" 
                                               id="studentSearch" 
                                               placeholder="Ketik nama, no. pendaftaran, atau telepon..."
                                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               autocomplete="off"
                                               value="<?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?>">
                                        
                                        <!-- Hidden input untuk menyimpan ID siswa -->
                                        <input type="hidden" name="pendaftaran_id" id="pendaftaranId" required value="<?= isset($form_data['pendaftaran_id']) ? htmlspecialchars($form_data['pendaftaran_id']) : '' ?>">
                                    </div>
                                    
                                    <!-- Search Results Dropdown -->
                                    <div id="studentSearchResults" class="hidden bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                                    
                                    <!-- Student Info Display -->
                                    <div id="selectedStudentInfo" class="mt-2 <?= (isset($form_data['pendaftaran_id']) && $form_data['pendaftaran_id']) ? '' : 'hidden' ?>">
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-user text-blue-500 mr-2"></i>
                                                        <span class="font-medium text-blue-900" id="infoNama">
                                                            <?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-sm text-gray-600 mt-1">
                                                        <div><span class="text-blue-700">No. Pendaftaran:</span> <span id="infoNoPendaftaran" class="font-medium">
                                                            <?= isset($form_data['student_nomor']) ? htmlspecialchars($form_data['student_nomor']) : '' ?>
                                                        </span></div>
                                                        <div><span class="text-blue-700">Paket:</span> <span id="infoPaket" class="font-medium">
                                                            <?= isset($form_data['student_paket']) ? htmlspecialchars($form_data['student_paket']) : '' ?>
                                                        </span></div>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="clearStudentSelection()" class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Instruktur *</label>
                                    <select name="instruktur_id" id="instrukturId" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Pilih Instruktur</option>
                                        <?php foreach ($instrukturs as $instruktur): ?>
                                            <option value="<?= $instruktur['id'] ?>" 
                                                <?= (isset($form_data['instruktur_id']) && $form_data['instruktur_id'] == $instruktur['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($instruktur['nama_lengkap']) ?> - <?= $instruktur['spesialisasi'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal *</label>
                                    <input type="date" name="tanggal_jadwal" id="tanggalJadwal" required min="<?= date('Y-m-d') ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value="<?= isset($form_data['tanggal_jadwal']) ? htmlspecialchars($form_data['tanggal_jadwal']) : date('Y-m-d') ?>">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai *</label>
                                    <div class="relative">
                                        <input type="time" name="jam_mulai" id="jamMulai" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            value="<?= isset($form_data['jam_mulai']) ? htmlspecialchars($form_data['jam_mulai']) : '08:00' ?>">
                                        <div id="timeConflictMessage" class="conflict-message hidden">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Instruktur sudah memiliki jadwal pada waktu ini</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai *</label>
                                    <div class="relative">
                                        <input type="time" name="jam_selesai" id="jamSelesai" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            value="<?= isset($form_data['jam_selesai']) ? htmlspecialchars($form_data['jam_selesai']) : '10:00' ?>">
                                        <div id="timeConflictMessage2" class="conflict-message hidden">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Waktu selesai harus setelah waktu mulai</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Sesi *</label>
                                    <select name="tipe_sesi" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="teori" <?= (isset($form_data['tipe_sesi']) && $form_data['tipe_sesi'] == 'teori') ? 'selected' : '' ?>>Teori</option>
                                        <option value="praktik" <?= (isset($form_data['tipe_sesi']) && $form_data['tipe_sesi'] == 'praktik') ? 'selected' : '' ?>>Praktik</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                                    <input type="text" name="lokasi"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Lokasi kursus"
                                        value="<?= isset($form_data['lokasi']) ? htmlspecialchars($form_data['lokasi']) : '' ?>">
                                </div>

                                <!-- ========== MODIFIKASI: Dropdown untuk Kendaraan (kendaraan_id) ========== -->
                                <div class="search-container">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kendaraan Digunakan</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-car text-gray-400"></i>
                                        </div>
                                        <input type="text" 
                                               id="vehicleSearch" 
                                               placeholder="Cari mobil berdasarkan plat, merk, atau model..."
                                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               autocomplete="off"
                                               value="<?= isset($form_data['vehicle_display']) ? htmlspecialchars($form_data['vehicle_display']) : '' ?>">
                                        
                                        <!-- PERUBAHAN DI SINI: ganti name="mobil_digunakan" menjadi name="kendaraan_id" -->
                                        <input type="hidden" name="kendaraan_id" id="kendaraanId" value="<?= isset($form_data['kendaraan_id']) ? htmlspecialchars($form_data['kendaraan_id']) : '' ?>">
                                        
                                        <!-- Hidden field untuk menyimpan display text -->
                                        <input type="hidden" name="vehicle_display" id="vehicleDisplay" value="<?= isset($form_data['vehicle_display']) ? htmlspecialchars($form_data['vehicle_display']) : '' ?>">
                                    </div>
                                    
                                    <!-- Vehicle Search Results Dropdown -->
                                    <div id="vehicleSearchResults" class="hidden bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto mt-1">
                                        <?php foreach ($kendaraan as $kendaraan_item): ?>
                                            <?php
                                            $status_badges = [
                                                'tersedia' => 'vehicle-status-tersedia',
                                                'dipakai' => 'vehicle-status-dipakai',
                                                'servis' => 'vehicle-status-servis'
                                            ];
                                            $transmission_badges = [
                                                'manual' => 'vehicle-transmission-manual',
                                                'matic' => 'vehicle-transmission-matic'
                                            ];
                                            $status_class = $status_badges[$kendaraan_item['status_ketersediaan']] ?? '';
                                            $transmission_class = $transmission_badges[$kendaraan_item['tipe_transmisi']] ?? '';
                                            $is_disabled = $kendaraan_item['status_ketersediaan'] === 'servis';
                                            $display_text = "{$kendaraan_item['nomor_plat']} - {$kendaraan_item['merk']} {$kendaraan_item['model']} ({$kendaraan_item['tahun']})";
                                            ?>
                                            <div class="vehicle-option p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer <?= $is_disabled ? 'disabled' : '' ?>"
                                                 data-id="<?= $kendaraan_item['id'] ?>"
                                                 data-display="<?= htmlspecialchars($display_text) ?>"
                                                 onclick="<?= $is_disabled ? '' : "selectVehicle(this)" ?>"
                                                 title="<?= $is_disabled ? 'Kendaraan sedang dalam servis' : '' ?>">
                                                <div class="flex justify-between items-center">
                                                    <div class="flex-1">
                                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($kendaraan_item['merk']) ?> <?= htmlspecialchars($kendaraan_item['model']) ?></div>
                                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($kendaraan_item['nomor_plat']) ?> • <?= $kendaraan_item['tahun'] ?> • <?= htmlspecialchars($kendaraan_item['warna']) ?></div>
                                                    </div>
                                                    <div class="flex space-x-1">
                                                        <span class="vehicle-status-badge <?= $status_class ?>"><?= ucfirst($kendaraan_item['status_ketersediaan']) ?></span>
                                                        <span class="vehicle-transmission-badge <?= $transmission_class ?>"><?= ucfirst($kendaraan_item['tipe_transmisi']) ?></span>
                                                    </div>
                                                </div>
                                                <?php if ($is_disabled): ?>
                                                    <div class="text-xs text-red-500 mt-1 flex items-center">
                                                        <i class="fas fa-wrench mr-1"></i>
                                                        <span>Sedang dalam servis</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Vehicle Info Display -->
                                    <div id="selectedVehicleInfo" class="mt-2 <?= (isset($form_data['vehicle_display']) && $form_data['vehicle_display']) ? '' : 'hidden' ?>">
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-car text-blue-500 mr-2"></i>
                                                        <span class="font-medium text-blue-900" id="infoMobil">
                                                            <?= isset($form_data['vehicle_display']) ? htmlspecialchars($form_data['vehicle_display']) : '' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="clearVehicleSelection()" class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading indicator -->
                                <div id="loadingIndicator" class="md:col-span-3 flex justify-center hidden">
                                    <div class="spinner mr-2"></div>
                                    <span class="text-gray-600">Memeriksa ketersediaan instruktur...</span>
                                </div>

                                <!-- Available schedules for selected instructor -->
                                <div id="instructorSchedules" class="md:col-span-3 hidden">
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <h4 class="font-medium text-gray-700 mb-2">Jadwal Instruktur pada Tanggal Tersebut:</h4>
                                        <div id="scheduleList" class="text-sm text-gray-600">
                                            <!-- Schedule list will be populated here -->
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-3 flex justify-end space-x-3">
                                    <button type="button"
                                        onclick="toggleScheduleForm()"
                                        class="bg-gray-600 text-white px-4 py-2.5 rounded-lg font-medium hover:bg-gray-700 transition">
                                        Batal
                                    </button>
                                    <button type="submit" id="submitBtn"
                                        class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-plus mr-2"></i>Tambah Jadwal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Jadwal</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_jadwal ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Terjadwal</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $jadwal_terjadwal ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Selesai</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $jadwal_selesai ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Hari Ini</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $jadwal_hari_ini ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="terjadwal" <?= $status_filter === 'terjadwal' ? 'selected' : '' ?>>Terjadwal</option>
                                    <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $status_filter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Sesi</label>
                                <select name="tipe" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Tipe</option>
                                    <option value="teori" <?= $tipe_filter === 'teori' ? 'selected' : '' ?>>Teori</option>
                                    <option value="praktik" <?= $tipe_filter === 'praktik' ? 'selected' : '' ?>>Praktik</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="jadwal.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Schedule Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Daftar Jadwal (<?= count($jadwal) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($jadwal) ?> jadwal
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instruktur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th> <!-- Ganti nama kolom -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($jadwal) > 0): ?>
                                    <?php foreach ($jadwal as $data): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
                                                <div class="text-sm text-gray-500"><?= $data['nomor_pendaftaran'] ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($data['nama_instruktur']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= date('d M Y', strtotime($data['tanggal_jadwal'])) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= date('H:i', strtotime($data['jam_mulai'])) ?> - <?= date('H:i', strtotime($data['jam_selesai'])) ?>
                                                </div>
                                                <?php if ($data['lokasi']): ?>
                                                    <div class="text-xs text-gray-400"><?= $data['lokasi'] ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $tipe_badges = [
                                                    'teori' => 'bg-blue-100 text-blue-800',
                                                    'praktik' => 'bg-green-100 text-green-800'
                                                ];
                                                $tipe_class = $tipe_badges[$data['tipe_sesi']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $tipe_class ?> capitalize">
                                                    <?= $data['tipe_sesi'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($data['nomor_plat']): ?>
                                                    <div class="text-sm text-gray-900">
                                                        <?= htmlspecialchars($data['merk'] . ' ' . $data['model']) ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <?= htmlspecialchars($data['nomor_plat']) ?> • <?= $data['tahun'] ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status_badges = [
                                                    'terjadwal' => 'bg-yellow-100 text-yellow-800',
                                                    'selesai' => 'bg-green-100 text-green-800',
                                                    'dibatalkan' => 'bg-red-100 text-red-800',
                                                    'diubah' => 'bg-purple-100 text-purple-800'
                                                ];
                                                $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                                    <?= ucfirst($data['status']) ?>
                                                </span>
                                                <?php if ($data['kehadiran_siswa']): ?>
                                                    <div class="text-xs text-gray-500 mt-1 capitalize">
                                                        <?= str_replace('_', ' ', $data['kehadiran_siswa']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <!-- View Details Button -->
                                                    <button onclick="viewSchedule(<?= $data['id'] ?>)"
                                                        class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition duration-200"
                                                        title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <!-- Update Status Button -->
                                                    <button onclick="updateSchedule(<?= $data['id'] ?>, '<?= $data['status'] ?>', '<?= $data['kehadiran_siswa'] ?>', `<?= htmlspecialchars($data['catatan_instruktur'] ?? '') ?>`)"
                                                        class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition duration-200"
                                                        title="Update Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

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
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            Tidak ada jadwal yang ditemukan.
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

    <!-- Update Schedule Modal -->
    <div id="updateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <form method="POST" id="updateForm">
                <input type="hidden" name="id" id="updateId">
                <input type="hidden" name="update_schedule" value="1">

                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Update Status Jadwal</h3>
                    <button type="button" onclick="closeUpdateModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="updateStatus" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="terjadwal">Terjadwal</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                            <option value="diubah">Diubah</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kehadiran Siswa</label>
                        <select name="kehadiran_siswa" id="updateKehadiran"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Kehadiran</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                            <option value="izin">Izin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Instruktur</label>
                        <textarea name="catatan_instruktur" id="updateCatatan" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Catatan perkembangan siswa..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeUpdateModal()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-300">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Schedule Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-4 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Detail Jadwal Kursus</h3>
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

    <!-- JavaScript -->
    <script>
        // Data kendaraan dari PHP
        let vehicles = <?php 
            echo json_encode($kendaraan);
        ?>;

        // Toggle schedule form visibility
        function toggleScheduleForm() {
            const container = document.getElementById('scheduleFormContainer');
            const icon = document.getElementById('toggleScheduleFormIcon');

            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
                resetFormValidation();
            } else {
                container.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
                clearStudentSelection();
                clearVehicleSelection();
                resetFormValidation();
            }
        }
        
        // Data siswa dari PHP
        let students = <?php 
            echo json_encode($active_registrations);
        ?>;

        // Search functionality for students
        const studentSearch = document.getElementById('studentSearch');
        const studentSearchResults = document.getElementById('studentSearchResults');
        const pendaftaranId = document.getElementById('pendaftaranId');
        const selectedStudentInfo = document.getElementById('selectedStudentInfo');

        // Search functionality for vehicles
        const vehicleSearch = document.getElementById('vehicleSearch');
        const vehicleSearchResults = document.getElementById('vehicleSearchResults');
        const kendaraanId = document.getElementById('kendaraanId'); // PERUBAHAN: ganti mobilDigunakan menjadi kendaraanId
        const vehicleDisplay = document.getElementById('vehicleDisplay'); // Input hidden untuk display text
        const selectedVehicleInfo = document.getElementById('selectedVehicleInfo');

        // Initialize form with existing data if any
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($form_data) && $form_data): ?>
                // Check instructor availability with existing data
                setTimeout(checkInstructorAvailability, 500);
            <?php endif; ?>
            
            // Initialize vehicle search
            setupVehicleSearch();
        });

        studentSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            if (searchTerm.length < 1) {
                studentSearchResults.classList.add('hidden');
                return;
            }
            
            // Filter students
            const filtered = students.filter(student => {
                const searchFields = [
                    student.nama_lengkap?.toLowerCase() || '',
                    student.nomor_pendaftaran?.toLowerCase() || '',
                    student.telepon || ''
                ];
                
                return searchFields.some(field => field.includes(searchTerm));
            });
            
            // Display results
            if (filtered.length > 0) {
                let html = '';
                filtered.forEach(student => {
                    html += `
                        <div class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer student-search-result" 
                             data-id="${student.id}"
                             data-nama="${student.nama_lengkap}"
                             data-nomor="${student.nomor_pendaftaran}"
                             data-paket="${student.nama_paket}"
                             onclick="selectScheduleStudent(this)">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${student.nama_lengkap}</div>
                                        <div class="text-sm text-gray-600">${student.nomor_pendaftaran}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-blue-600">${student.nama_paket}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                studentSearchResults.innerHTML = html;
                studentSearchResults.classList.remove('hidden');
            } else {
                studentSearchResults.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-user-slash text-xl mb-2"></i>
                        <p>Siswa tidak ditemukan</p>
                        <p class="text-xs mt-1">Coba cari dengan nama atau nomor pendaftaran</p>
                    </div>
                `;
                studentSearchResults.classList.remove('hidden');
            }
        });

        // Setup vehicle search functionality
        function setupVehicleSearch() {
            vehicleSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length < 1) {
                    vehicleSearchResults.classList.add('hidden');
                    return;
                }
                
                // Filter vehicles
                const filtered = vehicles.filter(vehicle => {
                    const searchFields = [
                        vehicle.nomor_plat?.toLowerCase() || '',
                        vehicle.merk?.toLowerCase() || '',
                        vehicle.model?.toLowerCase() || '',
                        vehicle.tahun?.toString() || '',
                        vehicle.warna?.toLowerCase() || ''
                    ];
                    
                    return searchFields.some(field => field.includes(searchTerm));
                });
                
                // Show all vehicles if no search term
                const displayVehicles = searchTerm.length > 0 ? filtered : vehicles;
                
                if (displayVehicles.length > 0) {
                    vehicleSearchResults.classList.remove('hidden');
                } else {
                    vehicleSearchResults.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-car text-xl mb-2"></i>
                            <p>Kendaraan tidak ditemukan</p>
                        </div>
                    `;
                    vehicleSearchResults.classList.remove('hidden');
                }
            });
        }

        // Select student for schedule
        function selectScheduleStudent(element) {
            const id = element.getAttribute('data-id');
            const nama = element.getAttribute('data-nama');
            const nomor = element.getAttribute('data-nomor');
            const paket = element.getAttribute('data-paket');
            
            // Set hidden input
            pendaftaranId.value = id;
            
            // Update hidden fields for form submission
            document.getElementById('studentNameField').value = nama;
            document.getElementById('studentNomorField').value = nomor;
            document.getElementById('studentPaketField').value = paket;
            
            // Hide search
            studentSearch.value = nama;
            studentSearchResults.classList.add('hidden');
            
            // Show student info
            document.getElementById('infoNama').textContent = nama;
            document.getElementById('infoNoPendaftaran').textContent = nomor;
            document.getElementById('infoPaket').textContent = paket;
            
            selectedStudentInfo.classList.remove('hidden');
            
            // Check for time conflicts if other fields are filled
            checkInstructorAvailability();
        }

        // Select vehicle for schedule
        function selectVehicle(element) {
            const vehicleId = element.getAttribute('data-id');
            const displayText = element.getAttribute('data-display');
            
            // Set hidden input untuk ID kendaraan
            kendaraanId.value = vehicleId;
            
            // Set hidden input untuk display text
            vehicleDisplay.value = displayText;
            
            // Update search field
            vehicleSearch.value = displayText;
            vehicleSearchResults.classList.add('hidden');
            
            // Show vehicle info
            document.getElementById('infoMobil').textContent = displayText;
            selectedVehicleInfo.classList.remove('hidden');
            
            // Mark selected option
            const options = vehicleSearchResults.querySelectorAll('.vehicle-option');
            options.forEach(opt => {
                opt.classList.remove('selected');
                if (opt.getAttribute('data-id') === vehicleId) {
                    opt.classList.add('selected');
                }
            });
        }

        // Clear student selection
        function clearStudentSelection() {
            pendaftaranId.value = '';
            document.getElementById('studentNameField').value = '';
            document.getElementById('studentNomorField').value = '';
            document.getElementById('studentPaketField').value = '';
            studentSearch.value = '';
            selectedStudentInfo.classList.add('hidden');
            studentSearchResults.classList.add('hidden');
            resetFormValidation();
        }

        // Clear vehicle selection
        function clearVehicleSelection() {
            kendaraanId.value = '';
            vehicleDisplay.value = '';
            vehicleSearch.value = '';
            selectedVehicleInfo.classList.add('hidden');
            vehicleSearchResults.classList.add('hidden');
            
            // Remove selected class from all options
            const options = vehicleSearchResults.querySelectorAll('.vehicle-option');
            options.forEach(opt => opt.classList.remove('selected'));
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchContainer = document.querySelector('.search-container');
            if (searchContainer && !searchContainer.contains(event.target)) {
                studentSearchResults.classList.add('hidden');
                vehicleSearchResults.classList.add('hidden');
            }
        });

        // Tambah event listener untuk enter key (student search)
        studentSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstResult = studentSearchResults.querySelector('.student-search-result');
                if (firstResult) {
                    selectScheduleStudent(firstResult);
                }
            }
        });

        // Tambah event listener untuk enter key (vehicle search)
        vehicleSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstEnabledResult = vehicleSearchResults.querySelector('.vehicle-option:not(.disabled)');
                if (firstEnabledResult) {
                    selectVehicle(firstEnabledResult);
                }
            }
        });

        // Show vehicle dropdown when clicking on search field
        vehicleSearch.addEventListener('focus', function() {
            vehicleSearchResults.classList.remove('hidden');
        });

        // Check instructor availability
        function checkInstructorAvailability() {
            const instrukturId = document.getElementById('instrukturId').value;
            const tanggalJadwal = document.getElementById('tanggalJadwal').value;
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesai').value;
            
            // Reset UI
            resetFormValidation();
            
            if (!instrukturId || !tanggalJadwal || !jamMulai || !jamSelesai) {
                return;
            }
            
            // Validate time
            if (jamMulai >= jamSelesai) {
                document.getElementById('jamSelesai').classList.add('time-conflict');
                document.getElementById('timeConflictMessage2').classList.remove('hidden');
                document.getElementById('submitBtn').disabled = true;
                return;
            }
            
            // Show loading
            document.getElementById('loadingIndicator').classList.remove('hidden');
            
            // Send AJAX request to check availability
            fetch('check_instructor_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `instruktur_id=${instrukturId}&tanggal_jadwal=${tanggalJadwal}&jam_mulai=${jamMulai}&jam_selesai=${jamSelesai}`
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingIndicator').classList.add('hidden');
                
                if (data.hasConflict) {
                    // Show conflict
                    document.getElementById('jamMulai').classList.add('time-conflict');
                    document.getElementById('jamSelesai').classList.add('time-conflict');
                    document.getElementById('timeConflictMessage').classList.remove('hidden');
                    document.getElementById('submitBtn').disabled = true;
                    
                    // Show existing schedules
                    if (data.schedules && data.schedules.length > 0) {
                        let scheduleHtml = '<div class="space-y-2">';
                        data.schedules.forEach(schedule => {
                            const waktu = `${schedule.jam_mulai} - ${schedule.jam_selesai}`;
                            const siswa = schedule.nama_siswa || 'Siswa';
                            const status = schedule.status === 'selesai' ? '(Selesai)' : schedule.status === 'dibatalkan' ? '(Dibatalkan)' : '';
                            scheduleHtml += `
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <div>
                                        <span class="font-medium">${waktu}</span>
                                        <span class="text-gray-600 ml-2">${siswa} ${status}</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded ${schedule.tipe_sesi === 'teori' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                                        ${schedule.tipe_sesi}
                                    </span>
                                </div>
                            `;
                        });
                        scheduleHtml += '</div>';
                        document.getElementById('scheduleList').innerHTML = scheduleHtml;
                        document.getElementById('instructorSchedules').classList.remove('hidden');
                    }
                } else {
                    // No conflict, enable submit button
                    document.getElementById('submitBtn').disabled = false;
                    
                    // Show available schedules if any
                    if (data.schedules && data.schedules.length > 0) {
                        document.getElementById('instructorSchedules').classList.remove('hidden');
                        let scheduleHtml = '<div class="space-y-2">';
                        data.schedules.forEach(schedule => {
                            const waktu = `${schedule.jam_mulai} - ${schedule.jam_selesai}`;
                            const siswa = schedule.nama_siswa || 'Siswa';
                            const status = schedule.status === 'selesai' ? '(Selesai)' : schedule.status === 'dibatalkan' ? '(Dibatalkan)' : '';
                            scheduleHtml += `
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <div>
                                        <span class="font-medium">${waktu}</span>
                                        <span class="text-gray-600 ml-2">${siswa} ${status}</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded ${schedule.tipe_sesi === 'teori' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                                        ${schedule.tipe_sesi}
                                    </span>
                                </div>
                            `;
                        });
                        scheduleHtml += '</div>';
                        document.getElementById('scheduleList').innerHTML = scheduleHtml;
                    } else {
                        document.getElementById('instructorSchedules').classList.add('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingIndicator').classList.add('hidden');
            });
        }

        // Reset form validation
        function resetFormValidation() {
            document.getElementById('jamMulai').classList.remove('time-conflict');
            document.getElementById('jamSelesai').classList.remove('time-conflict');
            document.getElementById('timeConflictMessage').classList.add('hidden');
            document.getElementById('timeConflictMessage2').classList.add('hidden');
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('loadingIndicator').classList.add('hidden');
            document.getElementById('instructorSchedules').classList.add('hidden');
        }

        // Add event listeners for time and date changes
        document.getElementById('instrukturId').addEventListener('change', checkInstructorAvailability);
        document.getElementById('tanggalJadwal').addEventListener('change', checkInstructorAvailability);
        document.getElementById('jamMulai').addEventListener('change', checkInstructorAvailability);
        document.getElementById('jamSelesai').addEventListener('change', checkInstructorAvailability);

        // Form validation
        document.getElementById('addScheduleForm').addEventListener('submit', function(e) {
            if (!pendaftaranId.value) {
                e.preventDefault();
                alert('Silakan pilih siswa terlebih dahulu!');
                studentSearch.focus();
                return false;
            }
            
            const instruktur = document.getElementById('instrukturId').value;
            if (!instruktur) {
                e.preventDefault();
                alert('Silakan pilih instruktur!');
                return false;
            }
            
            // Check for time conflict one more time before submit
            if (document.getElementById('submitBtn').disabled) {
                e.preventDefault();
                alert('Instruktur tidak tersedia pada waktu yang dipilih!');
                return false;
            }
            
            return true;
        });

        // View Schedule Function
        function viewSchedule(id) {
            // Show loading state
            document.getElementById('viewContent').innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-2"></i>
                        <p class="text-gray-600">Memuat detail jadwal...</p>
                    </div>
                </div>
            `;

            document.getElementById('viewModal').classList.remove('hidden');

            fetch(`jadwal_detail.php?id=${id}`)
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
                                <p>Gagal memuat detail jadwal</p>
                            </div>
                        </div>
                    `;
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // Update Schedule Function
        function updateSchedule(id, status, kehadiran, catatan) {
            document.getElementById('updateId').value = id;
            document.getElementById('updateStatus').value = status;
            document.getElementById('updateKehadiran').value = kehadiran || '';
            document.getElementById('updateCatatan').value = catatan || '';
            document.getElementById('updateModal').classList.remove('hidden');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
                window.location.href = `jadwal.php?delete=${id}`;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const updateModal = document.getElementById('updateModal');

            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === updateModal) {
                closeUpdateModal();
            }
        }
    </script>
</body>

</html>