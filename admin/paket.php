<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle add/edit package
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_paket'])) {
        // Add new package
        $nama_paket = $_POST['nama_paket'];
        $deskripsi = $_POST['deskripsi'];
        $durasi_menit = $_POST['durasi_menit'];
        $harga = $_POST['harga'];
        $tipe_mobil = $_POST['tipe_mobil'];
        $termasuk_teori = isset($_POST['termasuk_teori']) ? 1 : 0;
        $termasuk_praktik = isset($_POST['termasuk_praktik']) ? 1 : 0;
        $maksimal_siswa = $_POST['maksimal_siswa'];
        $tersedia = isset($_POST['tersedia']) ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO paket_kursus (nama_paket, deskripsi, durasi_jam, harga, tipe_mobil, termasuk_teori, termasuk_praktik, maksimal_siswa, tersedia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([$nama_paket, $deskripsi, $durasi_menit, $harga, $tipe_mobil, $termasuk_teori, $termasuk_praktik, $maksimal_siswa, $tersedia])) {
            $_SESSION['success'] = "Paket kursus berhasil ditambahkan!";
            header('Location: paket.php');
            exit;
        } else {
            $error = "Gagal menambahkan paket kursus! Error: " . implode(", ", $stmt->errorInfo());
        }
    } elseif (isset($_POST['edit_paket'])) {
        // Edit existing package
        $id = $_POST['id'];
        $nama_paket = $_POST['nama_paket'];
        $deskripsi = $_POST['deskripsi'];
        $durasi_menit = $_POST['durasi_menit'];
        $harga = $_POST['harga'];
        $tipe_mobil = $_POST['tipe_mobil'];
        $termasuk_teori = isset($_POST['termasuk_teori']) ? 1 : 0;
        $termasuk_praktik = isset($_POST['termasuk_praktik']) ? 1 : 0;
        $maksimal_siswa = $_POST['maksimal_siswa'];
        $tersedia = isset($_POST['tersedia']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE paket_kursus SET nama_paket = ?, deskripsi = ?, durasi_jam = ?, harga = ?, tipe_mobil = ?, termasuk_teori = ?, termasuk_praktik = ?, maksimal_siswa = ?, tersedia = ? WHERE id = ?");

        if ($stmt->execute([$nama_paket, $deskripsi, $durasi_menit, $harga, $tipe_mobil, $termasuk_teori, $termasuk_praktik, $maksimal_siswa, $tersedia, $id])) {
            $_SESSION['success'] = "Paket kursus berhasil diupdate!";
            header('Location: paket.php');
            exit;
        } else {
            $error = "Gagal mengupdate paket kursus! Error: " . implode(", ", $stmt->errorInfo());
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Check if package is being used in any registration
    $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM pendaftaran WHERE paket_kursus_id = ?");
    $check_stmt->execute([$id]);
    $usage = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($usage['count'] > 0) {
        $_SESSION['error'] = "Tidak bisa menghapus paket ini karena sudah digunakan dalam pendaftaran!";
    } else {
        $stmt = $db->prepare("DELETE FROM paket_kursus WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Paket kursus berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus paket kursus!";
        }
    }
    header('Location: paket.php');
    exit;
}

// Handle toggle availability
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];

    $stmt = $db->prepare("UPDATE paket_kursus SET tersedia = NOT tersedia WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "Status ketersediaan berhasil diubah!";
    } else {
        $_SESSION['error'] = "Gagal mengubah status ketersediaan!";
    }
    header('Location: paket.php');
    exit;
}

// Get success/error messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get all packages
$stmt = $db->query("SELECT * FROM paket_kursus ORDER BY harga ASC");
$paket_kursus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get package statistics
$total_paket = $db->query("SELECT COUNT(*) as total FROM paket_kursus")->fetch()['total'];
$paket_aktif = $db->query("SELECT COUNT(*) as total FROM paket_kursus WHERE tersedia = 1")->fetch()['total'];
$paket_manual = $db->query("SELECT COUNT(*) as total FROM paket_kursus WHERE tipe_mobil = 'manual'")->fetch()['total'];
$paket_matic = $db->query("SELECT COUNT(*) as total FROM paket_kursus WHERE tipe_mobil = 'matic'")->fetch()['total'];
$paket_keduanya = $db->query("SELECT COUNT(*) as total FROM paket_kursus WHERE tipe_mobil = 'keduanya'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Paket Kursus - Krishna Driving</title>
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
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Paket Kursus</h1>
                        <p class="text-gray-600">Atur harga dan fitur paket kursus mengemudi</p>
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
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= $success ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= $error ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Package Form - Hidden by default -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <!-- Toggle Button -->
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Tambah Paket Kursus Baru</h3>
                                <p class="text-gray-600">Isi detail paket kursus dengan lengkap</p>
                            </div>
                            <button id="toggleFormBtn"
                                onclick="togglePackageForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle form">
                                <i id="toggleFormIcon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Form (hidden by default) -->
                        <div id="packageFormContainer" class="hidden mt-6">
                            <div class="p-6 bg-white rounded-b-lg border border-t-0 border-gray-200">
                                <form method="POST" id="packageForm">
                                    <input type="hidden" name="id" id="editId">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Paket *</label>
                                                <input type="text" name="nama_paket" id="nama_paket" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Contoh: Paket Pemula Manual">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi *</label>
                                                <textarea name="deskripsi" id="deskripsi" rows="3" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Deskripsi lengkap paket kursus"></textarea>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Total (Menit) *</label>
                                                    <div class="flex space-x-2">
                                                        <input type="number" name="durasi_menit" id="durasi_menit" required min="1" step="1"
                                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="500" value="500">
                                                        <button type="button" onclick="convertToHours()" class="px-3 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition duration-300">
                                                            <i class="fas fa-calculator"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mt-1 text-sm text-gray-500" id="durationDisplay">
                                                        500 menit = 8 jam 20 menit
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        <button type="button" onclick="setDuration(50)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">50m (1x)</button>
                                                        <button type="button" onclick="setDuration(100)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">100m (2x)</button>
                                                        <button type="button" onclick="setDuration(250)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">250m (5x)</button>
                                                        <button type="button" onclick="setDuration(500)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">500m (10x)</button>
                                                        <button type="button" onclick="setDuration(750)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">750m (15x)</button>
                                                        <button type="button" onclick="setDuration(1000)" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">1000m (20x)</button>
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500" id="sessionInfo">
                                                        500 menit = 10 pertemuan @50 menit
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Maksimal Siswa *</label>
                                                    <input type="number" name="maksimal_siswa" id="maksimal_siswa" required min="1"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="1" value="1">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp) *</label>
                                                <input type="number" name="harga" id="harga" required min="0"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="1500000">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Mobil *</label>
                                                <select name="tipe_mobil" id="tipe_mobil" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    <option value="">Pilih Tipe Mobil</option>
                                                    <option value="manual">Manual</option>
                                                    <option value="matic">Matic</option>
                                                    <option value="keduanya">Keduanya</option>
                                                </select>
                                            </div>

                                            <div class="space-y-3">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Fitur Included</label>

                                                <div class="flex items-center">
                                                    <input type="checkbox" name="termasuk_teori" id="termasuk_teori"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                        checked>
                                                    <label for="termasuk_teori" class="ml-2 text-sm text-gray-700">
                                                        Termasuk Pelajaran Teori
                                                    </label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" name="termasuk_praktik" id="termasuk_praktik"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                        checked>
                                                    <label for="termasuk_praktik" class="ml-2 text-sm text-gray-700">
                                                        Termasuk Pelajaran Praktik
                                                    </label>
                                                </div>

                                                <div class="flex items-center">
                                                    <input type="checkbox" name="tersedia" id="tersedia"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                        checked>
                                                    <label for="tersedia" class="ml-2 text-sm text-gray-700">
                                                        Tersedia untuk Pendaftaran
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="flex space-x-3 pt-4">
                                                <button type="submit" name="add_paket" id="submitButton"
                                                    class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                                                    <i class="fas fa-plus mr-2"></i>Tambah Paket
                                                </button>
                                                <button type="button" id="cancelButton" onclick="resetForm()"
                                                    class="bg-gray-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700 transition duration-300 hidden">
                                                    Batal
                                                </button>
                                                <button type="submit" name="edit_paket" id="editButton"
                                                    class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition duration-300 hidden">
                                                    <i class="fas fa-save mr-2"></i>Update Paket
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-box text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Paket</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_paket ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Paket Aktif</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $paket_aktif ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-cog text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Manual</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $paket_manual ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-car-side text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Matic</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $paket_matic ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Packages List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Daftar Paket Kursus (<?= count($paket_kursus) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($paket_kursus) ?> paket
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <?php if (count($paket_kursus) > 0): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Paket</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe Mobil</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fitur</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($paket_kursus as $paket): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($paket['nama_paket']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($paket['deskripsi']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php
                                                $durasi_menit = $paket['durasi_jam'];
                                                $jam = floor($durasi_menit / 60);
                                                $menit = $durasi_menit % 60;
                                                $pertemuan = floor($durasi_menit / 50);

                                                if ($jam > 0 && $menit > 0) {
                                                    echo "{$jam}j {$menit}m";
                                                } elseif ($jam > 0) {
                                                    echo "{$jam} jam";
                                                } else {
                                                    echo "{$menit} menit";
                                                }
                                                echo "<br><span class='text-xs text-gray-500'>{$pertemuan} pertemuan @50m</span>";
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="font-semibold">Rp <?= number_format($paket['harga'], 0, ',', '.') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                <?= $paket['tipe_mobil'] == 'manual' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                                <?= $paket['tipe_mobil'] == 'matic' ? 'bg-purple-100 text-purple-800' : '' ?>
                                                <?= $paket['tipe_mobil'] == 'keduanya' ? 'bg-blue-100 text-blue-800' : '' ?>">
                                                    <i class="fas fa-<?= $paket['tipe_mobil'] == 'manual' ? 'cog' : ($paket['tipe_mobil'] == 'matic' ? 'car-side' : 'cars') ?> mr-1"></i>
                                                    <?= $paket['tipe_mobil'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if ($paket['termasuk_teori']): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-book mr-1"></i>Teori
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($paket['termasuk_praktik']): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-road mr-1"></i>Praktik
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <i class="fas fa-users mr-1"></i><?= $paket['maksimal_siswa'] ?> Siswa
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($paket['tersedia']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Tersedia
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Tidak Tersedia
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <!-- Edit Button -->
                                                    <button onclick="editPackage(<?= $paket['id'] ?>)"
                                                        class="text-blue-600 hover:text-blue-900"
                                                        title="Edit Paket">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <!-- Toggle Availability -->
                                                    <a href="paket.php?toggle=<?= $paket['id'] ?>"
                                                        class="text-<?= $paket['tersedia'] ? 'yellow' : 'green' ?>-600 hover:text-<?= $paket['tersedia'] ? 'yellow' : 'green' ?>-900"
                                                        title="<?= $paket['tersedia'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                        <i class="fas fa-<?= $paket['tersedia'] ? 'pause' : 'play' ?>"></i>
                                                    </a>

                                                    <!-- Delete Button -->
                                                    <button onclick="confirmDelete(<?= $paket['id'] ?>)"
                                                        class="text-red-600 hover:text-red-900"
                                                        title="Hapus Paket">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg">Belum ada paket kursus.</p>
                                <p class="text-sm">Tambahkan paket pertama Anda di form atas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pricing Tips -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-lightbulb text-blue-600 text-xl mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-lg font-medium text-blue-800">Tips Durasi & Harga</h4>
                            <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                                <li><strong>Paket Trial</strong>: 50-100 menit (1-2 pertemuan) - Rp 200-400 ribu</li>
                                <li><strong>Paket Pemula</strong>: 250-500 menit (5-10 pertemuan) - Rp 1-2 juta</li>
                                <li><strong>Paket Lengkap</strong>: 750-1000 menit (15-20 pertemuan) - Rp 2-3 juta</li>
                                <li><strong>Paket Executive</strong>: 1250+ menit (25+ pertemuan) - Rp 3 juta+</li>
                                <li>1 pertemuan = 50 menit praktik mengemudi</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Toggle form visibility
        function togglePackageForm() {
            const container = document.getElementById('packageFormContainer');
            const icon = document.getElementById('toggleFormIcon');

            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
            } else {
                container.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }
        // Duration conversion functions
        function convertToHours() {
            const minutes = parseInt(document.getElementById('durasi_menit').value) || 0;
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            const sessions = Math.floor(minutes / 50);

            let displayText = `${minutes} menit = `;
            if (hours > 0) {
                displayText += `${hours} jam `;
            }
            if (remainingMinutes > 0) {
                displayText += `${remainingMinutes} menit`;
            }
            if (hours === 0 && remainingMinutes === 0) {
                displayText = '0 menit';
            }

            document.getElementById('durationDisplay').textContent = displayText;
            document.getElementById('sessionInfo').textContent = `${minutes} menit = ${sessions} pertemuan @50 menit`;
        }

        function setDuration(minutes) {
            document.getElementById('durasi_menit').value = minutes;
            convertToHours();
        }

        // Initialize duration display
        document.addEventListener('DOMContentLoaded', function() {
            convertToHours();
            document.getElementById('durasi_menit').addEventListener('input', convertToHours);
        });

        // Edit Package Function
        function editPackage(id) {
            fetch(`get_paket_data.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Fill form with existing data
                    document.getElementById('editId').value = data.id;
                    document.getElementById('nama_paket').value = data.nama_paket;
                    document.getElementById('deskripsi').value = data.deskripsi;
                    document.getElementById('durasi_menit').value = data.durasi_jam;
                    document.getElementById('harga').value = data.harga;
                    document.getElementById('tipe_mobil').value = data.tipe_mobil;
                    document.getElementById('maksimal_siswa').value = data.maksimal_siswa;
                    document.getElementById('termasuk_teori').checked = data.termasuk_teori == 1;
                    document.getElementById('termasuk_praktik').checked = data.termasuk_praktik == 1;
                    document.getElementById('tersedia').checked = data.tersedia == 1;

                    // Update duration display
                    convertToHours();

                    // Change form to edit mode
                    document.getElementById('formTitle').textContent = 'Edit Paket Kursus';
                    document.getElementById('submitButton').classList.add('hidden');
                    document.getElementById('editButton').classList.remove('hidden');
                    document.getElementById('cancelButton').classList.remove('hidden');

                    // Scroll to form
                    document.getElementById('packageForm').scrollIntoView({
                        behavior: 'smooth'
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat data paket. Pastikan file get_paket_data.php ada dan berfungsi.');
                });
        }

        // Reset Form Function
        function resetForm() {
            document.getElementById('packageForm').reset();
            document.getElementById('formTitle').textContent = 'Tambah Paket Kursus Baru';
            document.getElementById('submitButton').classList.remove('hidden');
            document.getElementById('editButton').classList.add('hidden');
            document.getElementById('cancelButton').classList.add('hidden');
            document.getElementById('editId').value = '';

            // Reset checkboxes to default
            document.getElementById('termasuk_teori').checked = true;
            document.getElementById('termasuk_praktik').checked = true;
            document.getElementById('tersedia').checked = true;

            // Reset duration to default
            document.getElementById('durasi_menit').value = 500;
            convertToHours();
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus paket ini?')) {
                window.location.href = `paket.php?delete=${id}`;
            }
        }

        // Auto-calculate price suggestions based on minutes
        document.getElementById('durasi_menit').addEventListener('input', function() {
            const minutes = parseInt(this.value) || 0;
            const sessions = minutes / 50;
            const hargaInput = document.getElementById('harga');

            if (minutes > 0) {
                // Basic pricing calculation (40k per session as base)
                const basePrice = Math.round(sessions * 40000);
                if (!hargaInput.value || parseInt(hargaInput.value) < basePrice) {
                    hargaInput.placeholder = `Rekomendasi: Rp ${basePrice.toLocaleString('id-ID')}`;
                }
            }
        });

        // Form validation
        document.getElementById('packageForm').addEventListener('submit', function(e) {
            const harga = parseInt(document.getElementById('harga').value);
            const durasi = parseInt(document.getElementById('durasi_menit').value);
            const tipeMobil = document.getElementById('tipe_mobil').value;

            if (!harga || harga < 50000) {
                alert('Harga terlalu rendah! Minimum Rp 50.000');
                e.preventDefault();
                return;
            }

            if (!durasi || durasi < 1) {
                alert('Durasi harus minimal 1 menit!');
                e.preventDefault();
                return;
            }

            if (!tipeMobil) {
                alert('Pilih tipe mobil!');
                e.preventDefault();
                return;
            }

            const maksimalSiswa = parseInt(document.getElementById('maksimal_siswa').value);
            if (!maksimalSiswa || maksimalSiswa < 1) {
                alert('Maksimal siswa harus minimal 1!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>

</html>