<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// ============================================
// FUNGSI OTOMATIS: Update status kendaraan
// ============================================
function updateVehicleStatusAutomatically($db) {
    try {
        // Update kendaraan berdasarkan jadwal aktif
        $query = "
            UPDATE kendaraan k
            LEFT JOIN (
                SELECT DISTINCT kendaraan_id
                FROM jadwal_kursus 
                WHERE kendaraan_id IS NOT NULL
                AND status NOT IN ('selesai', 'dibatalkan')
                AND (
                    tanggal_jadwal > CURDATE()
                    OR (tanggal_jadwal = CURDATE() AND jam_selesai > CURTIME())
                )
            ) active_schedules ON k.id = active_schedules.kendaraan_id
            SET k.status_ketersediaan = CASE 
                WHEN active_schedules.kendaraan_id IS NOT NULL THEN 'dipakai'
                ELSE 'tersedia'
            END
            WHERE k.status_ketersediaan IN ('tersedia', 'dipakai')
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $affected = $stmt->rowCount();
        
        error_log("Auto vehicle update: $affected kendaraan diupdate");
        return $affected;
        
    } catch (PDOException $e) {
        error_log("Error auto updating vehicles: " . $e->getMessage());
        return 0;
    }
}

// Panggil fungsi otomatis setiap kali halaman diakses
updateVehicleStatusAutomatically($db);
// ============================================

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
        'kendaraan_id' => $_POST['kendaraan_id'] ?? null,
        'status' => 'terjadwal'
    ];

    // Simpan data form untuk ditampilkan kembali jika error
    $form_data = $data;
    $form_data['student_name'] = $_POST['student_name'] ?? '';
    $form_data['student_nomor'] = $_POST['student_nomor'] ?? '';
    $form_data['student_paket'] = $_POST['student_paket'] ?? '';
    $form_data['vehicle_display'] = $_POST['vehicle_display'] ?? '';

    try {
        // ... validasi lainnya ...
        
        // Cek ketersediaan kendaraan untuk sesi praktik
        if ($data['tipe_sesi'] === 'praktik' && $data['kendaraan_id']) {
            $check_vehicle_stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM jadwal_kursus 
                WHERE kendaraan_id = ? 
                AND tanggal_jadwal = ? 
                AND tipe_sesi = 'praktik'
                AND status NOT IN ('dibatalkan', 'selesai')
                AND (
                    (jam_mulai < ? AND jam_selesai > ?) OR
                    (jam_mulai < ? AND jam_selesai > ?) OR
                    (jam_mulai >= ? AND jam_selesai <= ?)
                )
            ");
            
            $check_vehicle_stmt->execute([
                $data['kendaraan_id'],
                $data['tanggal_jadwal'],
                $data['jam_selesai'], $data['jam_mulai'],
                $data['jam_mulai'], $data['jam_selesai'],
                $data['jam_mulai'], $data['jam_selesai']
            ]);
            
            $vehicle_result = $check_vehicle_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($vehicle_result['count'] > 0) {
                $error = "Kendaraan sudah dipakai pada tanggal dan waktu tersebut!";
            }
        }
        
        // Jika tidak ada konflik, tambahkan jadwal
        if (!isset($error)) {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO jadwal_kursus 
                (pendaftaran_id, instruktur_id, tanggal_jadwal, jam_mulai, jam_selesai, tipe_sesi, lokasi, kendaraan_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute(array_values($data))) {
                // PERBAIKAN: Update status kendaraan menjadi dipakai jika sesi praktik
                if ($data['tipe_sesi'] === 'praktik' && $data['kendaraan_id']) {
                    $update_vehicle_stmt = $db->prepare("
                        UPDATE kendaraan 
                        SET status_ketersediaan = 'dipakai' 
                        WHERE id = ?
                    ");
                    $update_vehicle_stmt->execute([$data['kendaraan_id']]);
                    
                    // Cek apakah update berhasil
                    $affected_rows = $update_vehicle_stmt->rowCount();
                    error_log("DEBUG: Kendaraan ID {$data['kendaraan_id']} diubah menjadi dipakai (affected rows: $affected_rows)");
                }
                
                $db->commit();
                $success = "Jadwal berhasil ditambahkan!";
                // Reset form data setelah sukses
                $form_data = null;
                
            } else {
                $db->rollBack();
                $error = "Gagal menambahkan jadwal!";
            }
        }
        
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = "Error: " . $e->getMessage();
        error_log("Error add schedule: " . $e->getMessage());
    }
}

// Handle update schedule status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $kehadiran_siswa = $_POST['kehadiran_siswa'] ?? null;
    $catatan_instruktur = $_POST['catatan_instruktur'] ?? '';

    try {
        $db->beginTransaction();
        
        // Ambil data jadwal
        $get_schedule = $db->prepare("
            SELECT kendaraan_id, tipe_sesi 
            FROM jadwal_kursus 
            WHERE id = ?
        ");
        $get_schedule->execute([$id]);
        $schedule = $get_schedule->fetch(PDO::FETCH_ASSOC);
        
        // Update status jadwal
        $stmt = $db->prepare("
            UPDATE jadwal_kursus 
            SET status = ?, kehadiran_siswa = ?, catatan_instruktur = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $kehadiran_siswa, $catatan_instruktur, $id]);
        
        // LOGIKA SIMPLIFIED: Update status kendaraan
        if ($schedule['tipe_sesi'] === 'praktik' && $schedule['kendaraan_id']) {
            $kendaraan_id = $schedule['kendaraan_id'];
            
            // Jika status berubah menjadi 'selesai' atau 'dibatalkan'
            if ($status === 'selesai' || $status === 'dibatalkan') {
                // Update kendaraan menjadi tersedia
                $update_vehicle = $db->prepare("
                    UPDATE kendaraan 
                    SET status_ketersediaan = 'tersedia' 
                    WHERE id = ?
                ");
                $update_vehicle->execute([$kendaraan_id]);
                error_log("DEBUG: Kendaraan ID $kendaraan_id diubah menjadi tersedia (jadwal $id status: $status)");
            } 
            // Jika status berubah menjadi 'terjadwal' atau 'berlangsung'
            else if ($status === 'terjadwal' || $status === 'berlangsung') {
                // Update kendaraan menjadi dipakai
                $update_vehicle = $db->prepare("
                    UPDATE kendaraan 
                    SET status_ketersediaan = 'dipakai' 
                    WHERE id = ?
                ");
                $update_vehicle->execute([$kendaraan_id]);
                error_log("DEBUG: Kendaraan ID $kendaraan_id diubah menjadi dipakai (jadwal $id status: $status)");
            }
        }
        
        $db->commit();
        $success = "Status jadwal berhasil diupdate!";
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
        error_log("Error update schedule: " . $e->getMessage());
    }
}

// Handle delete schedule
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        // Ambil data jadwal untuk mengembalikan status kendaraan jika ada
        $get_schedule = $db->prepare("SELECT kendaraan_id, tipe_sesi FROM jadwal_kursus WHERE id = ?");
        $get_schedule->execute([$id]);
        $schedule = $get_schedule->fetch(PDO::FETCH_ASSOC);
        
        // Hapus jadwal
        $stmt = $db->prepare("DELETE FROM jadwal_kursus WHERE id = ?");
        $stmt->execute([$id]);
        
        // PERBAIKAN: Update status kendaraan kembali ke tersedia jika sesi praktik
        if ($schedule['tipe_sesi'] === 'praktik' && $schedule['kendaraan_id']) {
            $update_vehicle = $db->prepare("UPDATE kendaraan SET status_ketersediaan = 'tersedia' WHERE id = ?");
            $update_vehicle->execute([$schedule['kendaraan_id']]);
            error_log("DEBUG: Kendaraan ID {$schedule['kendaraan_id']} diubah menjadi tersedia (jadwal $id dihapus)");
        }
        
        $db->commit();
        $success = "Jadwal berhasil dihapus!";
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$tipe_filter = $_GET['tipe'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';

// Query untuk data jadwal - JOIN dengan kendaraan untuk mendapatkan detail kendaraan
$query = "SELECT jk.*, ps.nama_lengkap, ps.nomor_pendaftaran, 
                 i.nama_lengkap as nama_instruktur,
                 k.nomor_plat, k.merk, k.model, k.tahun,
                 k.status_ketersediaan as status_kendaraan
          FROM jadwal_kursus jk 
          JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id 
          JOIN instruktur i ON jk.instruktur_id = i.id 
          LEFT JOIN kendaraan k ON jk.kendaraan_id = k.id
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

$query .= " ORDER BY jk.tanggal_jadwal DESC, jk.jam_mulai ASC";

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

// Query untuk instruktur - diambil semua terlebih dahulu, akan difilter di JavaScript
$instrukturs = $db->query("SELECT id, nama_lengkap, spesialisasi FROM instruktur ORDER BY nama_lengkap")->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Query untuk kendaraan - Hanya yang status tersedia
$kendaraan_stmt = $db->prepare("
    SELECT id, nomor_plat, merk, model, tahun, tipe_transmisi, warna, status_ketersediaan
    FROM kendaraan 
    WHERE status_ketersediaan = 'tersedia'
    ORDER BY merk, model
");

if ($kendaraan_stmt->execute()) {
    $kendaraan = $kendaraan_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $kendaraan = [];
    error_log("Error query kendaraan: " . print_r($db->errorInfo(), true));
}
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
        
        /* Time slot styling */
        .time-slot {
            padding: 0.25rem 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin: 0.1rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        
        .time-slot.available {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        
        .time-slot.available:hover {
            background-color: #a7f3d0;
            transform: translateY(-1px);
        }
        
        .time-slot.occupied {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .time-slot.selected {
            background-color: #3b82f6;
            color: white;
            border-color: #2563eb;
        }
        
        /* Disabled form elements */
        select:disabled, 
        select:disabled option,
        input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f1f5f9;
        }
        
        .form-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* Durasi badge */
        .durasi-badge {
            font-size: 0.7rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            background-color: #e9d5ff;
            color: #7c3aed;
        }
        
        /* Compact time slots grid */
        .compact-time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.3rem;
            max-height: 200px;
            overflow-y: auto;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            font-size: 0.75rem;
        }
        
        /* Compact form controls */
        .compact-input {
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .compact-select {
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
        }
        
        /* Info panel compact */
        .info-panel {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.875rem;
            border: 1px solid #e2e8f0;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
            color: #4b5563;
        }
        
        /* Quick time buttons */
        .quick-time-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .quick-time-btn:hover {
            background-color: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .quick-time-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #2563eb;
        }
        
        /* Instruktur dropdown styling */
        .instruktur-option {
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }
        
        .instruktur-option:hover {
            background-color: #f8fafc;
        }
        
        .instruktur-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f1f5f9;
        }
        
        .instruktur-option.selected {
            background-color: #dbeafe;
            border-left: 3px solid #3b82f6;
        }
        
        .instruktur-busy {
            color: #dc2626;
            font-size: 0.7rem;
        }
        
        .instruktur-available {
            color: #059669;
            font-size: 0.7rem;
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
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Add Schedule Form -->
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

                        <!-- Form -->
                        <div id="scheduleFormContainer" class="mt-6 p-6 <?= (isset($error) && isset($form_data)) ? '' : 'hidden' ?>">
                            <form method="POST" id="addScheduleForm" class="space-y-6">
                                <input type="hidden" name="add_schedule" value="1">
                                <input type="hidden" name="student_name" id="studentNameField" value="<?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?>">
                                <input type="hidden" name="student_nomor" id="studentNomorField" value="<?= isset($form_data['student_nomor']) ? htmlspecialchars($form_data['student_nomor']) : '' ?>">
                                <input type="hidden" name="student_paket" id="studentPaketField" value="<?= isset($form_data['student_paket']) ? htmlspecialchars($form_data['student_paket']) : '' ?>">

                                <!-- Row 1: Siswa -->
                                <div class="search-container">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Siswa *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-gray-400 text-sm"></i>
                                        </div>
                                        <input type="text" 
                                               id="studentSearch" 
                                               placeholder="Ketik nama, no. pendaftaran, atau telepon..."
                                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                               autocomplete="off"
                                               value="<?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?>"
                                               required>
                                        
                                        <!-- Hidden input untuk menyimpan ID siswa -->
                                        <input type="hidden" name="pendaftaran_id" id="pendaftaranId" required value="<?= isset($form_data['pendaftaran_id']) ? htmlspecialchars($form_data['pendaftaran_id']) : '' ?>">
                                    </div>
                                    
                                    <!-- Search Results Dropdown -->
                                    <div id="studentSearchResults" class="hidden bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto mt-1"></div>
                                    
                                    <!-- Student Info Display -->
                                    <div id="selectedStudentInfo" class="mt-2 <?= (isset($form_data['pendaftaran_id']) && $form_data['pendaftaran_id']) ? '' : 'hidden' ?>">
                                        <div class="info-panel">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="info-item">
                                                        <div class="info-icon"><i class="fas fa-user"></i></div>
                                                        <div>
                                                            <div class="font-medium text-gray-900" id="infoNama"><?= isset($form_data['student_name']) ? htmlspecialchars($form_data['student_name']) : '' ?></div>
                                                            <div class="text-xs text-gray-600 mt-1">
                                                                No. Pendaftaran: <span id="infoNoPendaftaran" class="font-medium"><?= isset($form_data['student_nomor']) ? htmlspecialchars($form_data['student_nomor']) : '' ?></span>
                                                            </div>
                                                            <div class="text-xs text-gray-600">
                                                                Paket: <span id="infoPaket" class="font-medium"><?= isset($form_data['student_paket']) ? htmlspecialchars($form_data['student_paket']) : '' ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="clearStudentSelection()" class="text-gray-400 hover:text-gray-600 text-sm">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row 2: Tipe Sesi, Tanggal, Waktu -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Tipe Sesi -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Sesi *</label>
                                        <select name="tipe_sesi" id="tipeSesi" required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            onchange="checkTimeAvailability(); filterAvailableInstructors(); updateVehicleVisibility();">
                                            <option value="">Pilih Tipe Sesi</option>
                                            <option value="teori" <?= (isset($form_data['tipe_sesi']) && $form_data['tipe_sesi'] == 'teori') ? 'selected' : '' ?>>Teori</option>
                                            <option value="praktik" <?= (isset($form_data['tipe_sesi']) && $form_data['tipe_sesi'] == 'praktik') ? 'selected' : '' ?>>Praktik</option>
                                        </select>
                                    </div>

                                    <!-- Tanggal -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal *</label>
                                        <input type="date" name="tanggal_jadwal" id="tanggalJadwal" required min="<?= date('Y-m-d') ?>"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            value="<?= isset($form_data['tanggal_jadwal']) ? htmlspecialchars($form_data['tanggal_jadwal']) : date('Y-m-d') ?>"
                                            onchange="checkTimeAvailability(); filterAvailableInstructors();">
                                    </div>

                                    <!-- Waktu -->
                                    <div>
                                        <div class="flex items-center mb-2">
                                            <label class="text-sm font-medium text-gray-700">Waktu *</label>
                                            <span class="durasi-badge ml-2 text-xs">50 menit</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-1">
                                                <div class="relative">
                                                    <input type="time" name="jam_mulai" id="jamMulai" required
                                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        value="<?= isset($form_data['jam_mulai']) ? htmlspecialchars($form_data['jam_mulai']) : '08:00' ?>"
                                                        onchange="updateJamSelesai(); checkTimeAvailability(); filterAvailableInstructors();"
                                                        step="300">
                                                    <!-- Hidden input untuk jam selesai -->
                                                    <input type="hidden" name="jam_selesai" id="jamSelesaiHidden" value="<?= isset($form_data['jam_selesai']) ? htmlspecialchars($form_data['jam_selesai']) : '' ?>">
                                                </div>
                                            </div>
                                            <div class="text-gray-400">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="relative">
                                                    <input type="time" id="jamSelesaiDisplayInput" 
                                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-50"
                                                        value="<?= isset($form_data['jam_selesai']) ? htmlspecialchars($form_data['jam_selesai']) : '08:50' ?>"
                                                        readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-600">
                                            <span id="durasiInfo">Durasi: <span id="durasiValue" class="font-medium">50 menit</span></span>
                                            <div class="mt-1 flex space-x-1">
                                                <button type="button" onclick="applyCustomEndTime()" class="quick-time-btn" title="Terapkan jam selesai manual">
                                                    <i class="fas fa-check mr-1"></i>Terapkan
                                                </button>
                                                <button type="button" onclick="resetToDefaultDuration()" class="quick-time-btn" title="Reset ke 50 menit">
                                                    <i class="fas fa-redo-alt mr-1"></i>Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Time Slots -->
                                <div id="timeSlotsContainer" class="hidden">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-sm font-medium text-gray-700">Pilih Waktu Cepat</div>
                                        <button type="button" onclick="hideTimeSlots()" class="text-xs text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-times mr-1"></i>Tutup
                                        </button>
                                    </div>
                                    <div class="compact-time-slots" id="timeSlotsGrid">
                                        <!-- Time slots will be loaded here -->
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i> Durasi default: 50 menit
                                    </div>
                                </div>

                                <!-- Row 3: Instruktur, Lokasi, Kendaraan -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Instruktur -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Instruktur *</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user-tie text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="text" 
                                                   id="instrukturSearch" 
                                                   placeholder="Pilih instruktur yang tersedia"
                                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                   autocomplete="off"
                                                   readonly
                                                   onclick="showInstrukturDropdown()">
                                            
                                            <input type="hidden" name="instruktur_id" id="instrukturId" value="<?= isset($form_data['instruktur_id']) ? htmlspecialchars($form_data['instruktur_id']) : '' ?>">
                                            <input type="hidden" name="instruktur_display" id="instrukturDisplay" value="">
                                        </div>
                                        
                                        <!-- Instruktur Dropdown -->
                                        <div id="instrukturDropdown" class="hidden bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto mt-1 text-sm">
                                            <div class="p-2 border-b border-gray-200">
                                                <div class="flex items-center">
                                                    <i class="fas fa-search text-gray-400 mr-2 text-sm"></i>
                                                    <input type="text" 
                                                           id="instrukturFilter" 
                                                           placeholder="Cari instruktur..."
                                                           class="w-full border-0 focus:ring-0 text-sm"
                                                           autocomplete="off"
                                                           onkeyup="filterInstructors()">
                                                </div>
                                            </div>
                                            <div id="instrukturList" class="max-h-48 overflow-y-auto">
                                                <!-- Instruktur options will be loaded here -->
                                            </div>
                                        </div>
                                        
                                        <!-- Instruktur Info Display -->
                                        <div id="selectedInstrukturInfo" class="mt-2 <?= (isset($form_data['instruktur_id']) && $form_data['instruktur_id']) ? '' : 'hidden' ?>">
                                            <div class="info-panel">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="info-item">
                                                            <div class="info-icon"><i class="fas fa-user-tie"></i></div>
                                                            <div>
                                                                <div class="font-medium text-gray-900" id="infoInstruktur">
                                                                    <?php 
                                                                    if (isset($form_data['instruktur_id']) && $form_data['instruktur_id']) {
                                                                        foreach ($instrukturs as $instruktur) {
                                                                            if ($instruktur['id'] == $form_data['instruktur_id']) {
                                                                                echo htmlspecialchars($instruktur['nama_lengkap']);
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" onclick="clearInstrukturSelection()" class="text-gray-400 hover:text-gray-600 text-sm">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Lokasi -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                                        <input type="text" name="lokasi" id="lokasi"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Lokasi kursus"
                                            value="<?= isset($form_data['lokasi']) ? htmlspecialchars($form_data['lokasi']) : '' ?>">
                                    </div>

                                    <!-- Kendaraan -->
                                    <div class="search-container" id="vehicleContainer">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kendaraan</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-car text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="text" 
                                                   id="vehicleSearch" 
                                                   placeholder="Pilih kendaraan (praktik saja)"
                                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                   autocomplete="off"
                                                   readonly
                                                   onclick="showVehicleDropdown()">
                                            
                                            <input type="hidden" name="kendaraan_id" id="kendaraanId" value="<?= isset($form_data['kendaraan_id']) ? htmlspecialchars($form_data['kendaraan_id']) : '' ?>">
                                            <input type="hidden" name="vehicle_display" id="vehicleDisplay" value="<?= isset($form_data['vehicle_display']) ? htmlspecialchars($form_data['vehicle_display']) : '' ?>">
                                        </div>
                                        
                                        <!-- Vehicle Dropdown -->
                                        <div id="vehicleDropdown" class="hidden bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto mt-1 text-sm">
                                            <div class="p-2 border-b border-gray-200">
                                                <div class="flex items-center">
                                                    <i class="fas fa-search text-gray-400 mr-2 text-sm"></i>
                                                    <input type="text" 
                                                           id="vehicleFilter" 
                                                           placeholder="Cari kendaraan..."
                                                           class="w-full border-0 focus:ring-0 text-sm"
                                                           autocomplete="off"
                                                           onkeyup="filterVehicles()">
                                                </div>
                                            </div>
                                            <div id="vehicleList" class="max-h-48 overflow-y-auto">
                                                <!-- Vehicle options will be loaded here -->
                                            </div>
                                        </div>
                                        
                                        <!-- Vehicle Info Display -->
                                        <div id="selectedVehicleInfo" class="mt-2 hidden">
                                            <div class="info-panel">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="info-item">
                                                            <div class="info-icon"><i class="fas fa-car"></i></div>
                                                            <div>
                                                                <div class="font-medium text-gray-900" id="infoMobil">
                                                                    <?= isset($form_data['vehicle_display']) ? htmlspecialchars($form_data['vehicle_display']) : '' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" onclick="clearVehicleSelection()" class="text-gray-400 hover:text-gray-600 text-sm">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Availability Status -->
                                <div id="availabilityStatus" class="hidden">
                                    <div id="loadingIndicator" class="flex items-center justify-center p-3 bg-gray-50 rounded-lg text-sm">
                                        <div class="spinner mr-2"></div>
                                        <span class="text-gray-600">Memeriksa ketersediaan...</span>
                                    </div>
                                    
                                    <div id="availabilityResult" class="hidden"></div>
                                    
                                    <div id="conflictInfo" class="hidden p-3 bg-red-50 border border-red-200 rounded-lg text-sm">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 mr-2"></i>
                                            <div>
                                                <h4 class="font-medium text-red-800">Jadwal Bertabrakan</h4>
                                                <p class="text-red-600 text-xs mt-1" id="conflictDetails"></p>
                                                <div id="conflictList" class="mt-1 space-y-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="availableInfo" class="hidden p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                                        <div class="flex items-start">
                                            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                                            <div>
                                                <h4 class="font-medium text-green-800">Jadwal Tersedia</h4>
                                                <p class="text-green-600 text-xs mt-1">Waktu yang dipilih dapat digunakan.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing Schedules Info -->
                                <div id="existingSchedules" class="hidden">
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 text-sm">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-medium text-gray-700">Jadwal yang Sudah Ada</h4>
                                            <span class="text-xs text-gray-500" id="scheduleCount">0 jadwal</span>
                                        </div>
                                        <div id="scheduleList" class="space-y-1 max-h-32 overflow-y-auto">
                                            <!-- Existing schedules will be loaded here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Buttons -->
                                <div class="flex justify-end space-x-3 pt-4 border-t">
                                    <button type="button"
                                        onclick="toggleScheduleForm()"
                                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition text-sm">
                                        Batal
                                    </button>
                                    <button type="submit" id="submitBtn"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                                        disabled>
                                        <i class="fas fa-plus mr-2"></i>Tambah Jadwal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards Compact -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                <i class="fas fa-calendar text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600">Total Jadwal</p>
                                <p class="text-xl font-bold text-gray-900"><?= $total_jadwal ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600">Terjadwal</p>
                                <p class="text-xl font-bold text-gray-900"><?= $jadwal_terjadwal ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600">Selesai</p>
                                <p class="text-xl font-bold text-gray-900"><?= $jadwal_selesai ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg mr-3">
                                <i class="fas fa-calendar-day text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600">Hari Ini</p>
                                <p class="text-xl font-bold text-gray-900"><?= $jadwal_hari_ini ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Compact -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Filter Jadwal</h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($jadwal) ?> jadwal
                            </div>
                        </div>
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="terjadwal" <?= $status_filter === 'terjadwal' ? 'selected' : '' ?>>Terjadwal</option>
                                    <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $status_filter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tipe Sesi</label>
                                <select name="tipe" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Semua Tipe</option>
                                    <option value="teori" <?= $tipe_filter === 'teori' ? 'selected' : '' ?>>Teori</option>
                                    <option value="praktik" <?= $tipe_filter === 'praktik' ? 'selected' : '' ?>>Praktik</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 text-sm">
                                    <i class="fas fa-filter mr-1"></i>Filter
                                </button>
                                <a href="jadwal.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300 text-sm">
                                    <i class="fas fa-refresh mr-1"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Schedule Table Compact -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Daftar Jadwal
                            </h3>
                            <div class="text-sm text-gray-600">
                                <?= count($jadwal) ?> jadwal
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instruktur</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($jadwal) > 0): ?>
                                    <?php foreach ($jadwal as $data): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
                                                <div class="text-xs text-gray-500"><?= $data['nomor_pendaftaran'] ?></div>
                                                <?php if ($data['lokasi']): ?>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($data['lokasi']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($data['nomor_plat']): ?>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        <i class="fas fa-car mr-1"></i>
                                                        <?= htmlspecialchars($data['merk']) ?> <?= $data['model'] ?>
                                                        <?php if ($data['status_kendaraan']): ?>
                                                            <span class="ml-1 px-1 py-0.5 text-xs rounded-full <?= $data['status_kendaraan'] == 'tersedia' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                                <?= $data['status_kendaraan'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
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
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 text-sm">
                                                    <?= date('d M Y', strtotime($data['tanggal_jadwal'])) ?>
                                                </div>
                                                <div class="text-gray-500 text-sm">
                                                    <?= date('H:i', strtotime($data['jam_mulai'])) ?> - <?= date('H:i', strtotime($data['jam_selesai'])) ?>
                                                </div>
                                                <div class="text-xs text-gray-400">50 menit</div>
                                                <?php
                                                $status_badges = [
                                                    'terjadwal' => 'bg-yellow-100 text-yellow-800',
                                                    'selesai' => 'bg-green-100 text-green-800',
                                                    'dibatalkan' => 'bg-red-100 text-red-800'
                                                ];
                                                $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?> mt-1">
                                                    <?= ucfirst($data['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-gray-900"><?= htmlspecialchars($data['nama_instruktur']) ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex space-x-1">
                                                    <button onclick="viewSchedule(<?= $data['id'] ?>)"
                                                        class="text-blue-600 hover:text-blue-900 p-1.5 rounded hover:bg-blue-50 transition"
                                                        title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="updateSchedule(<?= $data['id'] ?>, '<?= $data['status'] ?>', '<?= $data['kehadiran_siswa'] ?>', `<?= htmlspecialchars($data['catatan_instruktur'] ?? '') ?>`)"
                                                        class="text-green-600 hover:text-green-900 p-1.5 rounded hover:bg-green-50 transition"
                                                        title="Update Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="confirmDelete(<?= $data['id'] ?>)"
                                                        class="text-red-600 hover:text-red-900 p-1.5 rounded hover:bg-red-50 transition"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 text-sm">
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
        // Data kendaraan dan instruktur dari PHP
        let vehicles = <?php echo json_encode($kendaraan); ?>;
        let instructors = <?php echo json_encode($instrukturs); ?>;
        let students = <?php echo json_encode($active_registrations); ?>;
        
        // Track selected vehicle and instructor
        let selectedVehicleId = null;
        let selectedVehicleDisplay = null;
        let selectedInstructorId = null;
        let selectedInstructorName = null;

        // Fungsi untuk menghitung jam selesai (50 menit setelah jam mulai)
        function calculateEndTime(startTime) {
            if (!startTime) return '';
            
            const [hours, minutes] = startTime.split(':').map(Number);
            let totalMinutes = hours * 60 + minutes + 50;
            
            // Handle overflow ke hari berikutnya
            const endHours = Math.floor(totalMinutes / 60) % 24;
            const endMinutes = totalMinutes % 60;
            
            return `${endHours.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}`;
        }

        // Fungsi untuk menghitung durasi dalam menit
        function calculateDuration(startTime, endTime) {
            if (!startTime || !endTime) return 0;
            
            const [startHours, startMinutes] = startTime.split(':').map(Number);
            const [endHours, endMinutes] = endTime.split(':').map(Number);
            
            const startTotal = startHours * 60 + startMinutes;
            const endTotal = endHours * 60 + endMinutes;
            
            return endTotal - startTotal;
        }

        // Update jam selesai saat jam mulai berubah
        function updateJamSelesai() {
            const jamMulai = document.getElementById('jamMulai').value;
            if (jamMulai) {
                const jamSelesai = calculateEndTime(jamMulai);
                document.getElementById('jamSelesaiHidden').value = jamSelesai;
                document.getElementById('jamSelesaiDisplayInput').value = jamSelesai;
                updateDurationInfo();
            }
        }

        // Update informasi durasi
        function updateDurationInfo() {
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiHidden').value;
            
            if (jamMulai && jamSelesai) {
                const duration = calculateDuration(jamMulai, jamSelesai);
                document.getElementById('durasiValue').textContent = `${duration} menit`;
                
                // Highlight jika durasi bukan 50 menit
                if (duration !== 50) {
                    document.getElementById('durasiValue').classList.add('text-yellow-600', 'font-bold');
                } else {
                    document.getElementById('durasiValue').classList.remove('text-yellow-600', 'font-bold');
                }
            }
        }

        // Terapkan jam selesai manual
        function applyCustomEndTime() {
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiDisplayInput').value;
            
            if (!jamMulai) {
                alert('Harap isi jam mulai terlebih dahulu');
                return;
            }
            
            if (!jamSelesai) {
                alert('Harap isi jam selesai');
                return;
            }
            
            // Validasi: jam selesai harus setelah jam mulai
            if (jamSelesai <= jamMulai) {
                alert('Jam selesai harus setelah jam mulai');
                return;
            }
            
            // Validasi: durasi maksimal (misal 180 menit = 3 jam)
            const duration = calculateDuration(jamMulai, jamSelesai);
            if (duration > 180) {
                alert('Durasi maksimal adalah 3 jam (180 menit)');
                return;
            }
            
            if (duration < 30) {
                alert('Durasi minimal adalah 30 menit');
                return;
            }
            
            // Simpan ke hidden input
            document.getElementById('jamSelesaiHidden').value = jamSelesai;
            
            // Update durasi info
            updateDurationInfo();
            
            // Check availability
            checkTimeAvailability();
            
            // Show feedback
            showToast('Jam selesai berhasil diterapkan', 'success');
        }

        // Reset ke durasi default (50 menit)
        function resetToDefaultDuration() {
            const jamMulai = document.getElementById('jamMulai').value;
            if (jamMulai) {
                const jamSelesai = calculateEndTime(jamMulai);
                document.getElementById('jamSelesaiHidden').value = jamSelesai;
                document.getElementById('jamSelesaiDisplayInput').value = jamSelesai;
                updateDurationInfo();
                checkTimeAvailability();
                showToast('Durasi direset ke 50 menit', 'info');
            }
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const colors = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            };
            
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}-circle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Toggle schedule form visibility
        function toggleScheduleForm() {
            const container = document.getElementById('scheduleFormContainer');
            const icon = document.getElementById('toggleScheduleFormIcon');

            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
                resetForm();
            } else {
                container.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
                resetForm();
            }
        }
        
        // Reset form
        function resetForm() {
            clearStudentSelection();
            clearVehicleSelection();
            clearInstrukturSelection();
            document.getElementById('tipeSesi').value = '';
            document.getElementById('tanggalJadwal').value = '<?= date('Y-m-d') ?>';
            document.getElementById('jamMulai').value = '08:00';
            updateJamSelesai(); // Update jam selesai saat reset
            document.getElementById('lokasi').value = '';
            document.getElementById('submitBtn').disabled = true;
            
            hideTimeSlots();
            hideAvailabilityStatus();
            hideExistingSchedules();
        }
        
        // Search functionality for students
        const studentSearch = document.getElementById('studentSearch');
        const studentSearchResults = document.getElementById('studentSearchResults');
        const pendaftaranId = document.getElementById('pendaftaranId');
        const selectedStudentInfo = document.getElementById('selectedStudentInfo');

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
                        <div class="p-2 border-b border-gray-100 hover:bg-gray-50 cursor-pointer student-search-result" 
                             data-id="${student.id}"
                             data-nama="${student.nama_lengkap}"
                             data-nomor="${student.nomor_pendaftaran}"
                             data-paket="${student.nama_paket}"
                             onclick="selectScheduleStudent(this)">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                        <i class="fas fa-user text-blue-500 text-xs"></i>
                                    </div>
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-900">${student.nama_lengkap}</div>
                                        <div class="text-gray-600">${student.nomor_pendaftaran}</div>
                                    </div>
                                </div>
                                <div class="text-right text-xs">
                                    <div class="font-medium text-blue-600">${student.nama_paket}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                studentSearchResults.innerHTML = html;
                studentSearchResults.classList.remove('hidden');
            } else {
                studentSearchResults.innerHTML = `
                    <div class="p-3 text-center text-gray-500 text-sm">
                        <i class="fas fa-user-slash text-lg mb-1"></i>
                        <p>Siswa tidak ditemukan</p>
                    </div>
                `;
                studentSearchResults.classList.remove('hidden');
            }
        });

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
            
            // Check availability
            checkTimeAvailability();
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
            
            // Disable submit button
            document.getElementById('submitBtn').disabled = true;
        }

        // Filter vehicles
        function filterVehicles() {
            const filter = document.getElementById('vehicleFilter').value.toLowerCase();
            const options = document.querySelectorAll('#vehicleList .vehicle-option');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(filter)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        // Select vehicle
        function selectVehicle(element) {
            const vehicleId = element.getAttribute('data-id');
            const displayText = element.getAttribute('data-display');
            
            selectedVehicleId = vehicleId;
            selectedVehicleDisplay = displayText;
            
            // Set hidden inputs
            document.getElementById('kendaraanId').value = vehicleId;
            document.getElementById('vehicleDisplay').value = displayText;
            
            // Update search field
            document.getElementById('vehicleSearch').value = displayText;
            
            // Hide dropdown
            document.getElementById('vehicleDropdown').classList.add('hidden');
            
            // Show vehicle info
            document.getElementById('infoMobil').textContent = displayText;
            document.getElementById('selectedVehicleInfo').classList.remove('hidden');
            
            // Check availability
            checkTimeAvailability();
        }

        // Clear vehicle selection
        function clearVehicleSelection() {
            selectedVehicleId = null;
            selectedVehicleDisplay = null;
            
            // Clear hidden inputs
            document.getElementById('kendaraanId').value = '';
            document.getElementById('vehicleDisplay').value = '';
            document.getElementById('vehicleSearch').value = '';
            document.getElementById('vehicleFilter').value = '';
            
            // Hide info and dropdown
            document.getElementById('selectedVehicleInfo').classList.add('hidden');
            document.getElementById('vehicleDropdown').classList.add('hidden');
            
            // Check availability
            checkTimeAvailability();
        }

        // Show vehicle dropdown
        function showVehicleDropdown() {
            const tipeSesi = document.getElementById('tipeSesi').value;
            
            // Only show for praktik sessions
            if (tipeSesi !== 'praktik') {
                showToast('Pilihan kendaraan hanya untuk sesi praktik', 'warning');
                return;
            }
            
            // Filter vehicles based on availability
            filterAvailableVehicles();
            
            // Show dropdown
            const dropdown = document.getElementById('vehicleDropdown');
            dropdown.classList.toggle('hidden');
            
            if (!dropdown.classList.contains('hidden')) {
                document.getElementById('vehicleFilter').focus();
            }
        }

        // Filter available vehicles
        function filterAvailableVehicles() {
            const tanggal = document.getElementById('tanggalJadwal').value;
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiHidden').value;
            
            let html = '';
            
            // Get vehicles that are available (tersedia)
            const availableVehicles = vehicles.filter(vehicle => 
                vehicle.status_ketersediaan === 'tersedia' || 
                vehicle.id == selectedVehicleId
            );
            
            if (availableVehicles.length === 0) {
                html = `
                    <div class="p-3 text-center text-gray-500 text-sm">
                        <i class="fas fa-car text-lg mb-1"></i>
                        <p>Tidak ada kendaraan tersedia</p>
                    </div>
                `;
            } else {
                availableVehicles.forEach(vehicle => {
                    const status_badges = {
                        'tersedia': 'vehicle-status-tersedia',
                        'dipakai': 'vehicle-status-dipakai',
                        'servis': 'vehicle-status-servis'
                    };
                    const transmission_badges = {
                        'manual': 'vehicle-transmission-manual',
                        'matic': 'vehicle-transmission-matic'
                    };
                    const status_class = status_badges[vehicle.status_ketersediaan] || '';
                    const transmission_class = transmission_badges[vehicle.tipe_transmisi] || '';
                    const display_text = `${vehicle.nomor_plat} - ${vehicle.merk} ${vehicle.model}`;
                    const is_selected = vehicle.id == selectedVehicleId;
                    const is_available = vehicle.status_ketersediaan === 'tersedia';
                    
                    html += `
                        <div class="vehicle-option p-2 border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${is_selected ? 'selected' : ''} ${!is_available ? 'disabled' : ''}"
                             ${is_available ? `data-id="${vehicle.id}" data-display="${display_text}" onclick="selectVehicle(this)"` : ''}>
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 text-xs">${vehicle.merk} ${vehicle.model}</div>
                                    <div class="text-gray-600 text-xs">${vehicle.nomor_plat} • ${vehicle.tahun}</div>
                                </div>
                                <div class="flex space-x-1">
                                    <span class="vehicle-status-badge ${status_class} text-xs">${vehicle.status_ketersediaan}</span>
                                    <span class="vehicle-transmission-badge ${transmission_class} text-xs">${vehicle.tipe_transmisi}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('vehicleList').innerHTML = html;
        }

        // Update vehicle visibility based on session type
        function updateVehicleVisibility() {
            const tipeSesi = document.getElementById('tipeSesi').value;
            const vehicleContainer = document.getElementById('vehicleContainer');
            
            if (tipeSesi === 'praktik') {
                vehicleContainer.classList.remove('hidden');
                // Clear vehicle selection if switching from teori to praktik
                if (!selectedVehicleId) {
                    clearVehicleSelection();
                }
            } else {
                vehicleContainer.classList.add('hidden');
                clearVehicleSelection();
            }
        }

        // Filter instructors
        function filterInstructors() {
            const filter = document.getElementById('instrukturFilter').value.toLowerCase();
            const options = document.querySelectorAll('#instrukturList .instruktur-option');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(filter)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        // Select instructor
        function selectInstructor(element) {
            const instructorId = element.getAttribute('data-id');
            const displayText = element.getAttribute('data-nama');
            
            selectedInstructorId = instructorId;
            selectedInstructorName = displayText;
            
            // Set hidden inputs
            document.getElementById('instrukturId').value = instructorId;
            document.getElementById('instrukturDisplay').value = displayText;
            
            // Update search field
            document.getElementById('instrukturSearch').value = displayText;
            
            // Hide dropdown
            document.getElementById('instrukturDropdown').classList.add('hidden');
            
            // Show instructor info
            document.getElementById('infoInstruktur').textContent = displayText;
            document.getElementById('selectedInstrukturInfo').classList.remove('hidden');
            
            // Check availability
            checkTimeAvailability();
        }

        // Clear instructor selection
        function clearInstrukturSelection() {
            selectedInstructorId = null;
            selectedInstructorName = null;
            
            // Clear hidden inputs
            document.getElementById('instrukturId').value = '';
            document.getElementById('instrukturDisplay').value = '';
            document.getElementById('instrukturSearch').value = '';
            document.getElementById('instrukturFilter').value = '';
            
            // Hide info and dropdown
            document.getElementById('selectedInstrukturInfo').classList.add('hidden');
            document.getElementById('instrukturDropdown').classList.add('hidden');
            
            // Disable submit button
            document.getElementById('submitBtn').disabled = true;
            
            // Check availability
            checkTimeAvailability();
        }

        // Show instructor dropdown
        function showInstrukturDropdown() {
            const tipeSesi = document.getElementById('tipeSesi').value;
            const tanggal = document.getElementById('tanggalJadwal').value;
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiHidden').value;
            
            // Check if required fields are filled
            if (!tipeSesi || !tanggal || !jamMulai || !jamSelesai) {
                showToast('Harap isi tipe sesi, tanggal, dan waktu terlebih dahulu', 'warning');
                return;
            }
            
            // Filter instructors based on availability
            filterAvailableInstructors();
            
            // Show dropdown
            const dropdown = document.getElementById('instrukturDropdown');
            dropdown.classList.toggle('hidden');
            
            if (!dropdown.classList.contains('hidden')) {
                document.getElementById('instrukturFilter').focus();
            }
        }

        // Filter available instructors berdasarkan waktu yang dipilih
        async function filterAvailableInstructors() {
            const tipeSesi = document.getElementById('tipeSesi').value;
            const tanggal = document.getElementById('tanggalJadwal').value;
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiHidden').value;
            
            // Reset UI
            document.getElementById('submitBtn').disabled = true;
            hideAvailabilityStatus();
            hideTimeSlots();
            hideExistingSchedules();
            
            // Basic validation
            if (!tipeSesi || !tanggal || !jamMulai || !jamSelesai) {
                // Jika belum lengkap, tampilkan semua instruktur
                displayAllInstructors();
                return;
            }
            
            // Validate time
            if (jamMulai >= jamSelesai) {
                displayAllInstructors();
                return;
            }
            
            // Validate duration
            const duration = calculateDuration(jamMulai, jamSelesai);
            if (duration > 180 || duration < 30) {
                displayAllInstructors();
                return;
            }
            
            // Show loading
            showLoading();
            
            try {
                // Check availability untuk setiap instruktur
                let html = '';
                let availableCount = 0;
                
                for (const instructor of instructors) {
                    // Prepare data for API call
                    const data = {
                        instruktur_id: instructor.id,
                        tanggal_jadwal: tanggal,
                        tipe_sesi: tipeSesi,
                        jam_mulai: jamMulai,
                        jam_selesai: jamSelesai
                    };
                    
                    // Call API untuk check availability
                    const response = await fetch('check_time_availability.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    const isAvailable = result.selectedTimeAvailable !== false;
                    const isSelected = instructor.id == selectedInstructorId;
                    
                    let className = 'instruktur-option p-2 border-b border-gray-100 cursor-pointer ';
                    if (isSelected) {
                        className += 'selected';
                    } else if (!isAvailable) {
                        className += 'disabled';
                    }
                    
                    let availabilityBadge = '';
                    if (isAvailable) {
                        availabilityBadge = '<span class="instruktur-available text-xs ml-2"><i class="fas fa-check-circle"></i> Tersedia</span>';
                        availableCount++;
                    } else {
                        availabilityBadge = '<span class="instruktur-busy text-xs ml-2"><i class="fas fa-times-circle"></i> Sibuk</span>';
                    }
                    
                    html += `
                        <div class="${className}"
                             ${isAvailable ? `data-id="${instructor.id}" data-nama="${instructor.nama_lengkap}" onclick="selectInstructor(this)"` : ''}>
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 text-xs">${instructor.nama_lengkap}</div>
                                    <div class="text-gray-600 text-xs">${instructor.spesialisasi || 'Instruktur'}</div>
                                </div>
                                ${availabilityBadge}
                            </div>
                        </div>
                    `;
                }
                
                if (availableCount === 0) {
                    html = `
                        <div class="p-3 text-center text-gray-500 text-sm">
                            <i class="fas fa-user-slash text-lg mb-1"></i>
                            <p>Tidak ada instruktur tersedia pada waktu ini</p>
                        </div>
                    `;
                }
                
                document.getElementById('instrukturList').innerHTML = html;
                hideLoading();
                
            } catch (error) {
                console.error('Error:', error);
                displayAllInstructors();
                hideLoading();
            }
        }

        // Display all instructors (without availability check)
        function displayAllInstructors() {
            let html = '';
            
            instructors.forEach(instructor => {
                const isSelected = instructor.id == selectedInstructorId;
                const className = `instruktur-option p-2 border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${isSelected ? 'selected' : ''}`;
                
                html += `
                    <div class="${className}"
                         data-id="${instructor.id}"
                         data-nama="${instructor.nama_lengkap}"
                         onclick="selectInstructor(this)">
                        <div class="flex justify-between items-center">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 text-xs">${instructor.nama_lengkap}</div>
                                <div class="text-gray-600 text-xs">${instructor.spesialisasi || 'Instruktur'}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('instrukturList').innerHTML = html;
        }

        // Check time availability
        async function checkTimeAvailability() {
            const tipeSesi = document.getElementById('tipeSesi').value;
            const tanggal = document.getElementById('tanggalJadwal').value;
            const jamMulai = document.getElementById('jamMulai').value;
            const jamSelesai = document.getElementById('jamSelesaiHidden').value;
            const instrukturId = selectedInstructorId;
            const kendaraanId = selectedVehicleId;
            const pendaftaranId = document.getElementById('pendaftaranId').value;
            
            // Update jam selesai terlebih dahulu
            updateJamSelesai();
            
            // Reset UI
            document.getElementById('submitBtn').disabled = true;
            hideAvailabilityStatus();
            hideTimeSlots();
            hideExistingSchedules();
            
            // Basic validation
            if (!tipeSesi || !tanggal || !jamMulai || !jamSelesai || !instrukturId || !pendaftaranId) {
                return;
            }
            
            // Validate time
            if (jamMulai >= jamSelesai) {
                showConflict('Waktu selesai harus setelah waktu mulai');
                return;
            }
            
            // Validate duration
            const duration = calculateDuration(jamMulai, jamSelesai);
            if (duration > 180) {
                showConflict('Durasi maksimal adalah 3 jam (180 menit)');
                return;
            }
            
            if (duration < 30) {
                showConflict('Durasi minimal adalah 30 menit');
                return;
            }
            
            // Show loading
            showLoading();
            
            try {
                // Prepare data for API call
                const data = {
                    instruktur_id: instrukturId,
                    pendaftaran_id: pendaftaranId,
                    tanggal_jadwal: tanggal,
                    tipe_sesi: tipeSesi,
                    jam_mulai: jamMulai,
                    jam_selesai: jamSelesai,
                    kendaraan_id: kendaraanId
                };
                
                // Call API
                const response = await fetch('check_time_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.error) {
                    showConflict('Error: ' + result.error);
                    return;
                }
                
                // Show time slots if available
                if (result.availableSlots && result.availableSlots.length > 0) {
                    showTimeSlots(result.availableSlots, result.occupiedSlots);
                }
                
                // Show existing schedules
                if (result.existingSchedules && result.existingSchedules.length > 0) {
                    showExistingSchedules(result.existingSchedules);
                }
                
                // Check if selected time is available
                if (result.selectedTimeAvailable) {
                    showAvailable();
                    document.getElementById('submitBtn').disabled = false;
                } else {
                    showConflictInfo(result.conflictInfo);
                }
                
            } catch (error) {
                hideLoading();
                console.error('Error:', error);
                showConflict('Terjadi kesalahan saat memeriksa ketersediaan');
            }
        }

        // Show loading
        function showLoading() {
            document.getElementById('availabilityStatus').classList.remove('hidden');
            document.getElementById('loadingIndicator').classList.remove('hidden');
            document.getElementById('conflictInfo').classList.add('hidden');
            document.getElementById('availableInfo').classList.add('hidden');
        }

        // Hide loading
        function hideLoading() {
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        // Show conflict message
        function showConflict(message) {
            document.getElementById('conflictInfo').classList.remove('hidden');
            document.getElementById('conflictDetails').textContent = message;
            document.getElementById('availabilityStatus').classList.remove('hidden');
        }

        // Show conflict info
        function showConflictInfo(conflictInfo) {
            let message = 'Jadwal bertabrakan dengan ';
            let details = '';
            
            if (conflictInfo) {
                if (conflictInfo.type === 'instruktur') {
                    message += `instruktur (${conflictInfo.with})`;
                } else if (conflictInfo.type === 'kendaraan') {
                    message += `kendaraan (${conflictInfo.with})`;
                } else if (conflictInfo.type === 'siswa') {
                    message += `siswa (${conflictInfo.with})`;
                }
                
                if (conflictInfo.schedule) {
                    const schedule = conflictInfo.schedule;
                    const duration = calculateDuration(schedule.jam_mulai, schedule.jam_selesai);
                    details = `
                        <div class="text-xs mt-1 p-1 bg-white rounded border">
                            <div class="font-medium">${schedule.jam_mulai} - ${schedule.jam_selesai} (${duration} menit)</div>
                            <div class="text-gray-600">${schedule.nama_siswa}</div>
                        </div>
                    `;
                }
            }
            
            document.getElementById('conflictInfo').classList.remove('hidden');
            document.getElementById('conflictDetails').textContent = message;
            document.getElementById('conflictList').innerHTML = details;
            document.getElementById('availabilityStatus').classList.remove('hidden');
        }

        // Show available message
        function showAvailable() {
            document.getElementById('availableInfo').classList.remove('hidden');
            document.getElementById('availabilityStatus').classList.remove('hidden');
        }

        // Hide availability status
        function hideAvailabilityStatus() {
            document.getElementById('availabilityStatus').classList.add('hidden');
        }

        // Show time slots
        function showTimeSlots(availableSlots, occupiedSlots) {
            let html = '';
            
            // Combine all slots
            const allSlots = [...availableSlots, ...occupiedSlots];
            
            // Sort by start time
            allSlots.sort((a, b) => a.start.localeCompare(b.start));
            
            // Display slots
            allSlots.forEach(slot => {
                const isAvailable = slot.is_available;
                const isOccupied = !isAvailable;
                const isCurrentSelection = 
                    slot.start === document.getElementById('jamMulai').value &&
                    slot.end === document.getElementById('jamSelesaiHidden').value;
                
                let className = 'time-slot ';
                if (isCurrentSelection) {
                    className += 'selected';
                } else if (isAvailable) {
                    className += 'available';
                } else {
                    className += 'occupied';
                }
                
                let conflictInfo = '';
                if (isOccupied && slot.conflict_with) {
                    conflictInfo = ` title="Terpakai oleh: ${slot.conflict_with}"`;
                }
                
                const duration = calculateDuration(slot.start, slot.end);
                
                html += `
                    <div class="${className}"${conflictInfo}
                         ${isAvailable ? `onclick="selectTimeSlot('${slot.start}', '${slot.end}')"` : ''}>
                        <div class="font-medium">${slot.start}</div>
                        <div class="text-xs text-gray-500">${slot.end}</div>
                    </div>
                `;
            });
            
            document.getElementById('timeSlotsGrid').innerHTML = html;
            document.getElementById('timeSlotsContainer').classList.remove('hidden');
        }

        // Select time slot
        function selectTimeSlot(start, end) {
            document.getElementById('jamMulai').value = start;
            document.getElementById('jamSelesaiHidden').value = end;
            document.getElementById('jamSelesaiDisplayInput').value = end;
            updateDurationInfo();
            checkTimeAvailability();
        }

        // Hide time slots
        function hideTimeSlots() {
            document.getElementById('timeSlotsContainer').classList.add('hidden');
        }

        // Show existing schedules
        function showExistingSchedules(schedules) {
            let html = '';
            let count = 0;
            
            schedules.forEach(schedule => {
                const status_badges = {
                    'terjadwal': 'bg-yellow-100 text-yellow-800',
                    'selesai': 'bg-green-100 text-green-800',
                    'dibatalkan': 'bg-red-100 text-red-800'
                };
                const tipe_badges = {
                    'teori': 'bg-blue-100 text-blue-800',
                    'praktik': 'bg-green-100 text-green-800'
                };
                
                const status_class = status_badges[schedule.status] || 'bg-gray-100 text-gray-800';
                const tipe_class = tipe_badges[schedule.tipe_sesi] || 'bg-gray-100 text-gray-800';
                
                html += `
                    <div class="p-1.5 bg-white rounded border border-gray-200 text-xs">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium">${schedule.jam_mulai} - ${schedule.jam_selesai}</div>
                                <div class="text-gray-600">${schedule.nama_siswa}</div>
                            </div>
                            <div class="flex space-x-1">
                                <span class="px-1 py-0.5 text-xs rounded-full ${tipe_class}">
                                    ${schedule.tipe_sesi.charAt(0)}
                                </span>
                                <span class="px-1 py-0.5 text-xs rounded-full ${status_class}">
                                    ${schedule.status.charAt(0)}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                count++;
            });
            
            document.getElementById('scheduleList').innerHTML = html;
            document.getElementById('scheduleCount').textContent = `${count} jadwal`;
            document.getElementById('existingSchedules').classList.remove('hidden');
        }

        // Hide existing schedules
        function hideExistingSchedules() {
            document.getElementById('existingSchedules').classList.add('hidden');
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('#studentSearchResults') && !event.target.closest('#studentSearch')) {
                document.getElementById('studentSearchResults').classList.add('hidden');
            }
            
            if (!event.target.closest('#vehicleDropdown') && !event.target.closest('#vehicleSearch')) {
                document.getElementById('vehicleDropdown').classList.add('hidden');
            }
            
            if (!event.target.closest('#instrukturDropdown') && !event.target.closest('#instrukturSearch')) {
                document.getElementById('instrukturDropdown').classList.add('hidden');
            }
        });

        // Handle enter key in student search
        studentSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstResult = document.querySelector('.student-search-result');
                if (firstResult) {
                    selectScheduleStudent(firstResult);
                }
            }
        });

        // Handle enter key in vehicle filter
        document.getElementById('vehicleFilter').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstEnabledResult = document.querySelector('#vehicleList .vehicle-option:not(.disabled)');
                if (firstEnabledResult) {
                    selectVehicle(firstEnabledResult);
                }
            }
        });

        // Handle enter key in instructor filter
        document.getElementById('instrukturFilter').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstEnabledResult = document.querySelector('#instrukturList .instruktur-option:not(.disabled)');
                if (firstEnabledResult) {
                    selectInstructor(firstEnabledResult);
                }
            }
        });

        // View Schedule Function
        function viewSchedule(id) {
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

        // Initialize form if there's error data
        <?php if (isset($error) && isset($form_data)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Set selected instructor if exists
                <?php if (isset($form_data['instruktur_id']) && $form_data['instruktur_id']): ?>
                    selectedInstructorId = <?= $form_data['instruktur_id'] ?>;
                    selectedInstructorName = '<?= addslashes($form_data['instruktur_display'] ?? '') ?>';
                    document.getElementById('instrukturSearch').value = selectedInstructorName;
                    document.getElementById('infoInstruktur').textContent = selectedInstructorName;
                <?php endif; ?>
                
                // Update jam selesai untuk data form yang error
                updateJamSelesai();
                updateDurationInfo();
                
                // Trigger availability check after a short delay
                setTimeout(checkTimeAvailability, 500);
                
                // Show vehicle info if exists
                <?php if (isset($form_data['vehicle_display']) && $form_data['vehicle_display']): ?>
                    document.getElementById('selectedVehicleInfo').classList.remove('hidden');
                    document.getElementById('infoMobil').textContent = '<?= addslashes($form_data['vehicle_display']) ?>';
                <?php endif; ?>
            });
        <?php endif; ?>
        
        // Initialize jam selesai saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateJamSelesai();
            updateDurationInfo();
            updateVehicleVisibility(); // Set initial visibility for vehicle field
        });
    </script>
</body>
</html>