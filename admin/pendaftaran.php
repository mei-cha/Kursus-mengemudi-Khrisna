<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// ==================== FUNGSI HELPER ====================
function cekStatusPembayaran($db, $pendaftaran_id) {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN status = 'terverifikasi' THEN jumlah ELSE 0 END) as total_dibayar,
            MAX(CASE WHEN tipe_pembayaran = 'lunas' AND status = 'terverifikasi' THEN 1 ELSE 0 END) as is_lunas
        FROM pembayaran 
        WHERE pendaftaran_id = ?
    ");
    $stmt->execute([$pendaftaran_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function cekJumlahPertemuan($db, $pendaftaran_id) {
    $stmt = $db->prepare("
        SELECT 
            ps.id,
            pk.durasi_jam,
            FLOOR(pk.durasi_jam / 50) as jumlah_pertemuan,
            COUNT(jk.id) as pertemuan_selesai
        FROM pendaftaran_siswa ps
        JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
        LEFT JOIN jadwal_kursus jk ON ps.id = jk.pendaftaran_id 
            AND jk.status = 'selesai'
        WHERE ps.id = ?
        GROUP BY ps.id
    ");
    $stmt->execute([$pendaftaran_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateStatusOtomatis($db, $pendaftaran_id) {
    $pembayaran = cekStatusPembayaran($db, $pendaftaran_id);
    $total_dibayar = $pembayaran['total_dibayar'] ?? 0;
    $is_lunas = $pembayaran['is_lunas'] ?? 0;
    
    $pertemuan = cekJumlahPertemuan($db, $pendaftaran_id);
    $jumlah_pertemuan = $pertemuan['jumlah_pertemuan'] ?? 0;
    $pertemuan_selesai = $pertemuan['pertemuan_selesai'] ?? 0;
    
    $stmt = $db->prepare("SELECT status_pendaftaran FROM pendaftaran_siswa WHERE id = ?");
    $stmt->execute([$pendaftaran_id]);
    $current_status = $stmt->fetchColumn();
    
    $new_status = $current_status;
    
    if ($current_status === 'dikonfirmasi' && $total_dibayar > 0) {
        $new_status = 'diproses';
    }
    
    if ($current_status === 'diproses' && 
        $pertemuan_selesai >= $jumlah_pertemuan && 
        $is_lunas == 1) {
        $new_status = 'selesai';
    }
    
    if ($new_status !== $current_status) {
        $update_stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $pendaftaran_id]);
        return $new_status;
    }
    
    return $current_status;
}
// ==================== END FUNGSI HELPER ====================

// Handle tambah siswa manual dengan validasi duplikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_siswa'])) {
    // Validasi email duplikat
    $email = $_POST['email'];
    $stmt_check = $db->prepare("SELECT COUNT(*) FROM pendaftaran_siswa WHERE email = ?");
    $stmt_check->execute([$email]);
    $email_count = $stmt_check->fetchColumn();
    
    if ($email_count > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        // Generate nomor pendaftaran yang unik
        $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Validasi nomor pendaftaran unik
        $stmt_check_nomor = $db->prepare("SELECT COUNT(*) FROM pendaftaran_siswa WHERE nomor_pendaftaran = ?");
        $stmt_check_nomor->execute([$nomor_pendaftaran]);
        $nomor_count = $stmt_check_nomor->fetchColumn();
        
        // Jika nomor sudah ada, generate ulang
        while ($nomor_count > 0) {
            $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt_check_nomor->execute([$nomor_pendaftaran]);
            $nomor_count = $stmt_check_nomor->fetchColumn();
        }

        $data = [
            'nomor_pendaftaran' => $nomor_pendaftaran,
            'nama_lengkap' => $_POST['nama_lengkap'],
            'email' => $_POST['email'],
            'telepon' => $_POST['telepon'],
            'alamat' => $_POST['alamat'],
            'tanggal_lahir' => $_POST['tanggal_lahir'],
            'jenis_kelamin' => $_POST['jenis_kelamin'],
            'paket_kursus_id' => $_POST['paket_kursus_id'],
            'tipe_mobil' => $_POST['tipe_mobil'],
            'jadwal_preferensi' => $_POST['jadwal_preferensi'],
            'pengalaman_mengemudi' => $_POST['pengalaman_mengemudi'],
            'kondisi_medis' => $_POST['kondisi_medis'],
            'kontak_darurat' => $_POST['kontak_darurat'],
            'nama_kontak_darurat' => $_POST['nama_kontak_darurat'],
            'status_pendaftaran' => 'baru',
            'catatan_admin' => $_POST['catatan_admin'] ?? 'Pendaftaran manual oleh admin'
        ];

        try {
            $stmt = $db->prepare("
                INSERT INTO pendaftaran_siswa 
                (nomor_pendaftaran, nama_lengkap, email, telepon, alamat, tanggal_lahir, 
                 jenis_kelamin, paket_kursus_id, tipe_mobil, jadwal_preferensi, 
                 pengalaman_mengemudi, kondisi_medis, kontak_darurat, nama_kontak_darurat,
                 status_pendaftaran, catatan_admin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute(array_values($data))) {
                $success = "Siswa berhasil ditambahkan dengan nomor pendaftaran: " . $nomor_pendaftaran;
                // Refresh halaman untuk mencegah resubmit
                header("Location: pendaftaran.php?success=" . urlencode($success));
                exit;
            } else {
                $error = "Gagal menambahkan siswa!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $catatan = $_POST['catatan_admin'] ?? '';

    $stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = ?, catatan_admin = ? WHERE id = ?");
    if ($stmt->execute([$status, $catatan, $id])) {
        $success = "Status pendaftaran berhasil diupdate!";
        updateStatusOtomatis($db, $id);
    } else {
        $error = "Gagal mengupdate status pendaftaran!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $db->beginTransaction();

        $stmt1 = $db->prepare("DELETE FROM evaluasi_kemajuan WHERE pendaftaran_id = ?");
        $stmt1->execute([$id]);

        $stmt2 = $db->prepare("DELETE FROM jadwal_kursus WHERE pendaftaran_id = ?");
        $stmt2->execute([$id]);

        $stmt3 = $db->prepare("DELETE FROM pembayaran WHERE pendaftaran_id = ?");
        $stmt3->execute([$id]);

        $stmt4 = $db->prepare("DELETE FROM pendaftaran_siswa WHERE id = ?");
        $stmt4->execute([$id]);

        $db->commit();
        $success = "Pendaftaran berhasil dihapus beserta data terkait!";
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Gagal menghapus pendaftaran! Error: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query dengan ORDER BY id DESC
$query = "SELECT ps.*, pk.nama_paket, pk.harga, pk.durasi_jam, pk.tipe_mobil as tipe_paket
          FROM pendaftaran_siswa ps 
          LEFT JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND ps.status_pendaftaran = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (ps.nama_lengkap LIKE ? OR ps.nomor_pendaftaran LIKE ? OR ps.email LIKE ? OR ps.telepon LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// PERBAIKAN: ORDER BY id DESC untuk urutan yang benar
$query .= " ORDER BY ps.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$status_counts = $db->query("
    SELECT status_pendaftaran, COUNT(*) as count 
    FROM pendaftaran_siswa 
    GROUP BY status_pendaftaran
")->fetchAll(PDO::FETCH_ASSOC);

// Get paket kursus
$paket_kursus = $db->query("SELECT id, nama_paket, harga, tipe_mobil, durasi_jam FROM paket_kursus")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Kelola Pendaftaran - Krishna Driving</title>
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
        /* Styling untuk validasi */
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
        /* Animasi untuk perubahan harga */
        @keyframes priceUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .price-update {
            animation: priceUpdate 0.5s ease;
        }
        select[readonly] {
            background-color: #f9fafb !important;
            cursor: not-allowed !important;
        }
        select[readonly]:focus {
            border-color: #d1d5db !important;
            box-shadow: none !important;
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola pendaftaran</h1>
                        <p class="text-gray-600">Kelola pendaftran siswa</p>
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

                <!-- Tambah Siswa Manual Button -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Tambah Siswa</h3>
                                <p class="text-gray-600">Untuk pendaftaran langsung di kantor</p>
                            </div>
                            <button onclick="toggleTambahForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle upload form">
                                <i id="toggle-icon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Form Tambah Siswa (Hidden by default) -->
                        <div id="tambahForm" class="mt-6 hidden">
                            <form method="POST" class="space-y-7" id="formPendaftaran" novalidate>
                                <input type="hidden" name="tambah_siswa" value="1">

                                <!-- Data Pribadi -->
                                <div class="space-y-5">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-user text-blue-600"></i> Data Pribadi
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Nama Lengkap -->
                                        <div class="input-wrapper">
                                            <label for="nama_lengkap" class="block text-sm text-gray-700 mb-1">Nama Lengkap *</label>
                                            <input type="text" id="nama_lengkap" name="nama_lengkap" required
                                                placeholder="Nama lengkap siswa"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="nama_lengkap_error">Nama lengkap wajib diisi (minimal 3 karakter)</div>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="input-wrapper">
                                            <label for="email" class="block text-sm text-gray-700 mb-1">Email *</label>
                                            <input type="email" id="email" name="email" required placeholder="email@contoh.com"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="email_error">Email tidak valid</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Telepon -->
                                        <div class="input-wrapper">
                                            <label for="telepon" class="block text-sm text-gray-700 mb-1">Telepon *</label>
                                            <input type="tel" id="telepon" name="telepon" required 
                                                placeholder="081234567890"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="telepon_error">Format nomor HP tidak valid (contoh: 081234567890)</div>
                                            <div class="info-message">Format: 08xxxxxxxxxx (10-13 digit)</div>
                                        </div>
                                        
                                        <!-- Tanggal Lahir -->
                                        <div class="input-wrapper">
                                            <label for="tanggal_lahir" class="block text-sm text-gray-700 mb-1">Tanggal Lahir *</label>
                                            <?php
                                            $today = new DateTime();
                                            $minDate = clone $today;
                                            $minDate->modify('-17 years');
                                            $minDateStr = $minDate->format('Y-m-d');
                                            $maxDate = clone $today;
                                            $maxDate->modify('-80 years');
                                            $maxDateStr = $maxDate->format('Y-m-d');
                                            ?>
                                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                                                max="<?= $minDateStr ?>"
                                                min="<?= $maxDateStr ?>"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="tanggal_lahir_error">Minimal usia 17 tahun</div>
                                            <div class="info-message">Minimal usia: 17 tahun</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <!-- Jenis Kelamin -->
                                        <div>
                                            <label class="block text-sm text-gray-700 mb-1">Jenis Kelamin *</label>
                                            <div class="flex gap-4 mt-1">
                                                <label class="flex items-center">
                                                    <input type="radio" id="jenis_kelamin_l" name="jenis_kelamin" value="L" required
                                                        class="text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-gray-700">Laki-laki</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="radio" id="jenis_kelamin_p" name="jenis_kelamin" value="P"
                                                        class="text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-gray-700">Perempuan</span>
                                                </label>
                                            </div>
                                            <div class="error-message" id="jenis_kelamin_error">Pilih jenis kelamin</div>
                                        </div>
                                        
                                        <!-- Pengalaman Mengemudi -->
                                        <div class="input-wrapper">
                                            <label for="pengalaman_mengemudi" class="block text-sm text-gray-700 mb-1">Pengalaman Mengemudi</label>
                                            <select id="pengalaman_mengemudi" name="pengalaman_mengemudi"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                                <option value="pemula">Pemula</option>
                                                <option value="pernah_kursus">Pernah kursus</option>
                                                <option value="pernah_ujian">Pernah ujian</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Alamat -->
                                        <div class="input-wrapper">
                                            <label for="alamat" class="block text-sm text-gray-700 mb-1">Alamat *</label>
                                            <textarea id="alamat" name="alamat" rows="1" required placeholder="Alamat lengkap"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="alamat_error">Alamat wajib diisi (minimal 10 karakter)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-5 pt-4 border-t border-gray-200">
    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
        <i class="fas fa-car text-blue-600"></i> Preferensi Kursus
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Paket Kursus -->
        <div class="input-wrapper">
            <label for="paket_kursus_id" class="block text-sm text-gray-700 mb-1">Paket Kursus *</label>
            <select id="paket_kursus_id" name="paket_kursus_id" required onchange="onPackageSelectChange()"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                <option value="">Pilih Paket</option>
                <?php foreach ($paket_kursus as $paket): ?>
                    <option value="<?= $paket['id'] ?>" 
                            data-harga="<?= $paket['harga'] ?>"
                            data-tipe-mobil="<?= $paket['tipe_mobil'] ?>">
                        <?= htmlspecialchars($paket['nama_paket']) ?> 
                        (<?= $paket['tipe_mobil_text'] ?? ucfirst($paket['tipe_mobil']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="valid-indicator">
                <i class="fas fa-check"></i>
            </span>
            <div class="error-message" id="paket_kursus_id_error">Pilih paket kursus</div>
            <p class="text-xs text-gray-500 mt-1">Pilih paket, tipe mobil akan otomatis terisi</p>
        </div>
        
        <!-- Tipe Mobil -->
<div class="input-wrapper">
    <label for="tipe_mobil" class="block text-sm text-gray-700 mb-1">Tipe Mobil *</label>
    <select id="tipe_mobil" name="tipe_mobil" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition bg-gray-100" 
        style="pointer-events: none; cursor: not-allowed;"> <!-- READONLY STYLE -->
        <option value="">Pilih Paket Dulu</option>
        <option value="manual">Manual</option>
        <option value="matic">Matic</option>
        <option value="keduanya">Keduanya</option>
    </select>
    <span class="valid-indicator">
        <i class="fas fa-check"></i>
    </span>
    <div class="error-message" id="tipe_mobil_error">Tipe mobil wajib diisi</div>
    <div id="tipeMobilNote" class="text-xs text-blue-600 mt-1 hidden">
        <i class="fas fa-info-circle mr-1"></i>Tipe mobil otomatis mengikuti paket yang dipilih
    </div>
</div>
                                        
                                        <!-- Jadwal Preferensi -->
                                        <div class="input-wrapper">
                                            <label for="jadwal_preferensi" class="block text-sm text-gray-700 mb-1">Jadwal Preferensi *</label>
                                            <select id="jadwal_preferensi" name="jadwal_preferensi" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                                <option value="">Pilih Jadwal</option>
                                                <option value="pagi">Pagi</option>
                                                <option value="siang">Siang</option>
                                                <option value="sore">Sore</option>
                                            </select>
                                            <span class="valid-indicator">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <div class="error-message" id="jadwal_preferensi_error">Pilih jadwal preferensi</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kontak Darurat & Kondisi Medis -->
                                <div class="space-y-5 pt-4 border-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-ambulance text-blue-600"></i> Informasi Tambahan
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <!-- Nama Kontak Darurat -->
                                        <div class="input-wrapper">
                                            <label for="nama_kontak_darurat" class="block text-sm text-gray-700 mb-1">Nama Kontak Darurat</label>
                                            <input type="text" id="nama_kontak_darurat" name="nama_kontak_darurat"
                                                placeholder="Nama keluarga/teman"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                        </div>
                                        
                                        <!-- Nomor Kontak Darurat -->
                                        <div class="input-wrapper">
                                            <label for="kontak_darurat" class="block text-sm text-gray-700 mb-1">Nomor Kontak Darurat</label>
                                            <input type="tel" id="kontak_darurat" name="kontak_darurat" 
                                                placeholder="081234567890"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                            <div class="error-message" id="kontak_darurat_error">Format nomor HP tidak valid</div>
                                            <div class="info-message">Format: 08xxxxxxxxxx</div>
                                        </div>
                                    </div>

                                    <!-- Kondisi Medis -->
                                    <div>
                                        <label for="kondisi_medis" class="block text-sm text-gray-700 mb-1">Kondisi Medis (Opsional)</label>
                                        <textarea id="kondisi_medis" name="kondisi_medis" rows="2"
                                            placeholder="Alergi, riwayat sakit, atau kondisi khusus lainnya"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                                    </div>

                                    <!-- Catatan Admin -->
                                    <div>
                                        <label for="catatan_admin" class="block text-sm text-gray-700 mb-1">Catatan Admin</label>
                                        <textarea id="catatan_admin" name="catatan_admin" rows="2"
                                            placeholder="Catatan tambahan untuk pendaftaran ini"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                                    </div>
                                </div>

                                <!-- Total Harga -->
                                <div class="pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center bg-blue-50 p-4 rounded-lg">
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-800 mb-1">Total Biaya</h4>
                                            <p class="text-sm text-gray-600">Harga paket yang dipilih</p>
                                        </div>
                                        <div id="totalHargaContainer" class="text-right">
                                            <div class="text-3xl font-bold text-blue-600" id="totalHarga">Rp 0</div>
                                            <div class="text-sm text-gray-500" id="paketNama">Pilih paket untuk melihat harga</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-4">
                                    <button type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition duration-300 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                                        <i class="fas fa-save"></i>
                                        Simpan Data Siswa
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
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Cari nama, no. pendaftaran, email, telepon..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="baru" <?= $status_filter === 'baru' ? 'selected' : '' ?>>Baru</option>
                                    <option value="dikonfirmasi" <?= $status_filter === 'dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                    <option value="diproses" <?= $status_filter === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $status_filter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="pendaftaran.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <?php
                    $status_info = [
                        'baru' => ['color' => 'yellow', 'icon' => 'user-plus', 'label' => 'Baru'],
                        'dikonfirmasi' => ['color' => 'blue', 'icon' => 'check-circle', 'label' => 'Dikonfirmasi'],
                        'diproses' => ['color' => 'purple', 'icon' => 'cog', 'label' => 'Diproses'],
                        'selesai' => ['color' => 'green', 'icon' => 'check', 'label' => 'Selesai'],
                        'dibatalkan' => ['color' => 'red', 'icon' => 'times', 'label' => 'Dibatalkan']
                    ];

                    foreach ($status_info as $status => $info):
                        $count = 0;
                        foreach ($status_counts as $sc) {
                            if ($sc['status_pendaftaran'] === $status) {
                                $count = $sc['count'];
                                break;
                            }
                        }
                    ?>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <div class="p-2 bg-<?= $info['color'] ?>-100 rounded-lg inline-block mb-2">
                                <i class="fas fa-<?= $info['icon'] ?> text-<?= $info['color'] ?>-600"></i>
                            </div>
                            <div class="text-2xl font-bold text-gray-900"><?= $count ?></div>
                            <div class="text-sm text-gray-600"><?= $info['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Data Pendaftaran (<?= count($pendaftaran) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($pendaftaran) ?> pendaftaran
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Pendaftaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe Mobil</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($pendaftaran) > 0): ?>
                                    <?php foreach ($pendaftaran as $data): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= $data['nomor_pendaftaran'] ?></div>
                                                <div class="text-sm text-gray-500"><?= $data['telepon'] ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
                                                <div class="text-sm text-gray-500"><?= $data['email'] ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($data['nama_paket'] ?? '-') ?></div>
                                                <div class="text-sm text-gray-500">
                                                    <?php 
                                                    $durasi_jam = $data['durasi_jam'] ?? 0;
                                                    $jumlah_pertemuan = floor($durasi_jam / 50);
                                                    echo $jumlah_pertemuan > 0 ? $jumlah_pertemuan . ' pertemuan' : '-';
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php 
                                                    $tipe_mobil = $data['tipe_mobil'] ?? $data['tipe_paket'] ?? '-';
                                                    switch($tipe_mobil) {
                                                        case 'manual':
                                                            echo '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">Manual</span>';
                                                            break;
                                                        case 'matic':
                                                            echo '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Matic</span>';
                                                            break;
                                                        case 'keduanya':
                                                            echo '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">Manual & Matic</span>';
                                                            break;
                                                        default:
                                                            echo $tipe_mobil;
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d M Y', strtotime($data['dibuat_pada'])) ?>
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
                                                $status_class = $status_badges[$data['status_pendaftaran']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                                    <?= ucfirst($data['status_pendaftaran']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <!-- View Button -->
                                                    <button onclick="viewDetail(<?= $data['id'] ?>)"
                                                        class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50"
                                                        title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <!-- Edit Status Button -->
                                                    <button onclick="editStatus(<?= $data['id'] ?>, '<?= $data['status_pendaftaran'] ?>', `<?= htmlspecialchars($data['catatan_admin'] ?? '') ?>`)"
                                                        class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
                                                        title="Edit Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

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
                                            Tidak ada data pendaftaran yang ditemukan.
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

    <!-- View Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Detail Pendaftaran</h3>
                    <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="detailContent" class="mt-4">
                    <!-- Detail content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <form method="POST" id="statusForm">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="update_status" value="1">

                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Update Status</h3>
                    <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="editStatus" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="baru">Baru</option>
                            <option value="dikonfirmasi">Dikonfirmasi</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Admin</label>
                        <textarea name="catatan_admin" id="editCatatan" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeStatusModal()"
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

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // ==================== FUNGSI UTAMA ====================
        
        // Toggle form tambah siswa
        function toggleTambahForm() {
            const form = document.getElementById('tambahForm');
            const icon = document.getElementById('toggle-icon');

            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
                
                // Reset form ketika dibuka
                resetForm();
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }

        // Reset form
        function resetForm() {
            const form = document.getElementById('formPendaftaran');
            if (form) {
                form.reset();
                
                // Reset tipe mobil
                const tipeMobilSelect = document.getElementById('tipe_mobil');
                const tipeMobilNote = document.getElementById('tipeMobilNote');
                if (tipeMobilSelect) {
                    tipeMobilSelect.value = '';
                    tipeMobilSelect.classList.remove('error-input', 'success-input');
                }
                if (tipeMobilNote) {
                    tipeMobilNote.classList.add('hidden');
                }
                
                // Reset harga
                updateTotalHarga();
                
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

        // Update harga
        function updateTotalHarga() {
            const paketSelect = document.getElementById('paket_kursus_id');
            const totalHargaElement = document.getElementById('totalHarga');
            const paketNamaElement = document.getElementById('paketNama');
            
            if (paketSelect.value) {
                const selectedOption = paketSelect.options[paketSelect.selectedIndex];
                const harga = parseInt(selectedOption.getAttribute('data-harga')) || 0;
                const nama = selectedOption.text;
                
                // Format harga
                const formattedPrice = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(harga);
                
                totalHargaElement.textContent = formattedPrice;
                totalHargaElement.classList.add('price-update');
                setTimeout(() => {
                    totalHargaElement.classList.remove('price-update');
                }, 500);
                paketNamaElement.textContent = nama;
            } else {
                totalHargaElement.textContent = 'Rp 0';
                paketNamaElement.textContent = 'Pilih paket untuk melihat harga';
            }
        }

        // Fungsi untuk mengatur tipe mobil berdasarkan paket
        function onPackageSelectChange() {
            const paketSelect = document.getElementById('paket_kursus_id');
            const tipeMobilSelect = document.getElementById('tipe_mobil');
            const tipeMobilNote = document.getElementById('tipeMobilNote');
            const selectedPaketId = paketSelect.value;
            
            // Update harga
            updateTotalHarga();
            
            // Validasi paket
            validateSelect(paketSelect);
            
            if (!selectedPaketId) {
                // Reset jika tidak ada paket yang dipilih
                tipeMobilSelect.value = '';
                tipeMobilSelect.classList.remove('success-input');
                if (tipeMobilNote) {
                    tipeMobilNote.classList.add('hidden');
                }
                // Reset validasi tipe mobil
                resetValidation(tipeMobilSelect);
                return;
            }
            
            // Ambil tipe mobil dari data attribute
            const selectedOption = paketSelect.options[paketSelect.selectedIndex];
            const tipeMobil = selectedOption.getAttribute('data-tipe-mobil');
            
            if (tipeMobil) {
                // Set nilai tipe mobil
                tipeMobilSelect.value = tipeMobil;
                tipeMobilSelect.classList.add('success-input');
                
                // Tampilkan note
                if (tipeMobilNote) {
                    tipeMobilNote.classList.remove('hidden');
                }
                
                // Validasi tipe mobil
                validateSelect(tipeMobilSelect);
            }
        }

        // ==================== VALIDASI FUNGSI ====================
        
        // Format nomor telepon saat input
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            
            // Pastikan dimulai dengan 0
            if (!value.startsWith('0')) {
                value = '0' + value;
            }
            
            // Batasi panjang 10-13 digit (termasuk 0)
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            input.value = value;
            
            // Validasi format
            const phonePattern = /^0[0-9]{9,12}$/;
            return phonePattern.test(value);
        }

        // Validasi umur dari tanggal lahir
        function validateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age >= 17;
        }

        // Tampilkan/sembunyikan error message
        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorElement = document.getElementById(inputId + '_error');
            const validIndicator = input.parentElement.querySelector('.valid-indicator');
            
            if (input) {
                input.classList.remove('success-input');
                input.classList.add('error-input');
                
                // Tambahkan class error ke label
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
                
                // Hapus class error dari label
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

        // Validasi radio buttons
        function validateRadioButtons(name) {
            const radioButtons = document.querySelectorAll(`input[name="${name}"]:checked`);
            if (radioButtons.length === 0) {
                showError(name, 'Pilihan ini wajib diisi');
                return false;
            } else {
                const errorElement = document.getElementById(name + '_error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
                return true;
            }
        }

        // Validasi form sebelum submit
        function validateForm() {
            let isValid = true;
            
            // Nama Lengkap
            const namaInput = document.getElementById('nama_lengkap');
            if (!namaInput.value.trim() || namaInput.value.trim().length < 3) {
                showError('nama_lengkap', 'Nama lengkap wajib diisi (minimal 3 karakter)');
                isValid = false;
            } else {
                showSuccess('nama_lengkap');
            }
            
            // Email
            const emailInput = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailInput.value || !emailPattern.test(emailInput.value)) {
                showError('email', 'Email tidak valid');
                isValid = false;
            } else {
                showSuccess('email');
            }
            
            // Telepon
            const teleponInput = document.getElementById('telepon');
            const phonePattern = /^0[0-9]{9,12}$/;
            if (!teleponInput.value || !phonePattern.test(teleponInput.value)) {
                showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
                isValid = false;
            } else {
                showSuccess('telepon');
            }
            
            // Tanggal Lahir
            const tanggalLahirInput = document.getElementById('tanggal_lahir');
            if (!tanggalLahirInput.value) {
                showError('tanggal_lahir', 'Tanggal lahir wajib diisi');
                isValid = false;
            } else if (!validateAge(tanggalLahirInput.value)) {
                showError('tanggal_lahir', 'Minimal usia 17 tahun untuk mengikuti kursus');
                isValid = false;
            } else {
                showSuccess('tanggal_lahir');
            }
            
            // Jenis Kelamin
            if (!validateRadioButtons('jenis_kelamin')) {
                isValid = false;
            }
            
            // Alamat
            const alamatInput = document.getElementById('alamat');
            if (!alamatInput.value.trim() || alamatInput.value.trim().length < 10) {
                showError('alamat', 'Alamat wajib diisi (minimal 10 karakter)');
                isValid = false;
            } else {
                showSuccess('alamat');
            }
            
            // Paket Kursus
            const paketSelect = document.getElementById('paket_kursus_id');
            if (!validateSelect(paketSelect)) {
                isValid = false;
            }
            
            // Tipe Mobil
            const tipeMobilSelect = document.getElementById('tipe_mobil');
            if (!tipeMobilSelect.value) {
                showError('tipe_mobil', 'Tipe mobil wajib diisi. Pilih paket kursus terlebih dahulu.');
                isValid = false;
            } else {
                showSuccess('tipe_mobil');
            }
            
            // Jadwal Preferensi
            const jadwalSelect = document.getElementById('jadwal_preferensi');
            if (!validateSelect(jadwalSelect)) {
                isValid = false;
            }
            
            // Kontak Darurat (jika diisi)
            const kontakDaruratInput = document.getElementById('kontak_darurat');
            if (kontakDaruratInput.value && !/^0[0-9]{9,12}$/.test(kontakDaruratInput.value)) {
                showError('kontak_darurat', 'Format nomor HP tidak valid');
                isValid = false;
            } else {
                resetValidation(kontakDaruratInput);
            }
            
            return isValid;
        }

        // ==================== EVENT LISTENERS ====================
        
        document.addEventListener('DOMContentLoaded', function() {
            // Validasi Nama Lengkap
            const namaInput = document.getElementById('nama_lengkap');
            if (namaInput) {
                namaInput.addEventListener('blur', function() {
                    if (this.value.trim().length < 3) {
                        showError('nama_lengkap', 'Nama lengkap minimal 3 karakter');
                    } else {
                        showSuccess('nama_lengkap');
                    }
                });
            }

            // Validasi Email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(this.value)) {
                        showError('email', 'Format email tidak valid');
                    } else {
                        showSuccess('email');
                    }
                });
            }

            // Validasi Telepon
            const teleponInput = document.getElementById('telepon');
            if (teleponInput) {
                teleponInput.addEventListener('input', function() {
                    formatPhoneNumber(this);
                    const isValid = /^0[0-9]{9,12}$/.test(this.value);
                    if (!isValid && this.value) {
                        showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
                    } else if (isValid) {
                        showSuccess('telepon');
                    }
                });
                
                teleponInput.addEventListener('blur', function() {
                    if (!this.value) {
                        showError('telepon', 'Nomor telepon wajib diisi');
                    } else if (!/^0[0-9]{9,12}$/.test(this.value)) {
                        showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
                    }
                });
            }

            // Validasi Kontak Darurat
            const kontakDaruratInput = document.getElementById('kontak_darurat');
            if (kontakDaruratInput) {
                kontakDaruratInput.addEventListener('input', function() {
                    formatPhoneNumber(this);
                    const isValid = /^0[0-9]{9,12}$/.test(this.value);
                    if (!isValid && this.value) {
                        showError('kontak_darurat', 'Format nomor HP tidak valid');
                    } else if (isValid) {
                        resetValidation(this);
                    }
                });
            }

            // Validasi Tanggal Lahir
            const tanggalLahirInput = document.getElementById('tanggal_lahir');
            if (tanggalLahirInput) {
                tanggalLahirInput.addEventListener('change', function() {
                    if (!validateAge(this.value)) {
                        showError('tanggal_lahir', 'Minimal usia 17 tahun untuk mengikuti kursus');
                    } else {
                        showSuccess('tanggal_lahir');
                    }
                });
            }

            // Validasi Alamat
            const alamatInput = document.getElementById('alamat');
            if (alamatInput) {
                alamatInput.addEventListener('blur', function() {
                    if (this.value.trim().length < 10) {
                        showError('alamat', 'Alamat minimal 10 karakter');
                    } else {
                        showSuccess('alamat');
                    }
                });
            }

            // Validasi Jenis Kelamin
            const jenisKelaminRadios = document.querySelectorAll('input[name="jenis_kelamin"]');
            jenisKelaminRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    validateRadioButtons('jenis_kelamin');
                });
            });

            // Validasi Paket Kursus
            const paketSelect = document.getElementById('paket_kursus_id');
            if (paketSelect) {
                paketSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Validasi Tipe Mobil
            const tipeMobilSelect = document.getElementById('tipe_mobil');
            if (tipeMobilSelect) {
                tipeMobilSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Validasi Jadwal Preferensi
            const jadwalSelect = document.getElementById('jadwal_preferensi');
            if (jadwalSelect) {
                jadwalSelect.addEventListener('change', function() {
                    validateSelect(this);
                });
            }

            // Event listener untuk update harga saat paket berubah
            if (paketSelect) {
                paketSelect.addEventListener('change', updateTotalHarga);
            }
            
            // Initial update harga
            updateTotalHarga();
            
            // Form submission dengan pencegahan double submit
            const form = document.getElementById('formPendaftaran');
            if (form) {
                let isSubmitting = false;
                
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (isSubmitting) {
                        return;
                    }
                    
                    // Enable semua select yang disabled sementara untuk submit
                    const disabledSelects = document.querySelectorAll('select[readonly]');
                    disabledSelects.forEach(select => {
                        select.readOnly = false;
                    });
                    
                    // Validasi form
                    if (!validateForm()) {
                        // Kembalikan status readonly
                        disabledSelects.forEach(select => {
                            select.readOnly = true;
                        });
                        
                        // Scroll ke error pertama
                        const firstError = document.querySelector('.error-input, .error-message[style*="block"]');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }
                    
                    // Set flag submitting
                    isSubmitting = true;
                    
                    // Disable submit button untuk mencegah double click
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    }
                    
                    // Submit form dengan timeout kecil
                    setTimeout(() => {
                        this.submit();
                    }, 500);
                    
                    // Kembalikan status readonly
                    disabledSelects.forEach(select => {
                        select.readOnly = true;
                    });
                });
            }
        });

        // ==================== FUNGSI LAINNYA ====================
        
        // View Detail Function
        function viewDetail(id) {
            fetch(`pendaftaran_detail.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detailContent').innerHTML = html;
                    document.getElementById('detailModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat detail pendaftaran');
                });
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        // Edit Status Function
        function editStatus(id, status, catatan) {
            document.getElementById('editId').value = id;
            document.getElementById('editStatus').value = status;
            document.getElementById('editCatatan').value = catatan;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pendaftaran ini?')) {
                window.location.href = `pendaftaran.php?delete=${id}`;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const detailModal = document.getElementById('detailModal');
            const statusModal = document.getElementById('statusModal');

            if (event.target === detailModal) {
                closeDetailModal();
            }
            if (event.target === statusModal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>