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
        'mobil_digunakan' => $_POST['mobil_digunakan'] ?? '',
        'status' => 'terjadwal'
    ];
    
    try {
        $stmt = $db->prepare("
            INSERT INTO jadwal_kursus 
            (pendaftaran_id, instruktur_id, tanggal_jadwal, jam_mulai, jam_selesai, tipe_sesi, lokasi, mobil_digunakan, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute(array_values($data))) {
            $success = "Jadwal berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan jadwal!";
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

// PERBAIKAN: Query yang benar untuk join dengan tabel instruktur
$query = "SELECT jk.*, ps.nama_lengkap, ps.nomor_pendaftaran, 
                 i.nama_lengkap as nama_instruktur 
          FROM jadwal_kursus jk 
          JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id 
          JOIN instruktur i ON jk.instruktur_id = i.id 
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

$query .= " ORDER BY jk.tanggal_jadwal ASC, jk.jam_mulai ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_jadwal = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus")->fetch()['total'];
$jadwal_terjadwal = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE status = 'terjadwal'")->fetch()['total'];
$jadwal_selesai = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE status = 'selesai'")->fetch()['total'];
$jadwal_hari_ini = $db->query("SELECT COUNT(*) as total FROM jadwal_kursus WHERE tanggal_jadwal = CURDATE()")->fetch()['total'];

// Get data for forms
$active_registrations = $db->query("
    SELECT ps.id, ps.nomor_pendaftaran, ps.nama_lengkap, pk.nama_paket 
    FROM pendaftaran_siswa ps 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE ps.status_pendaftaran IN ('dikonfirmasi', 'diproses')
    ORDER BY ps.nama_lengkap
")->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Query untuk instruktur - hilangkan WHERE status karena kolom status tidak ada
$instrukturs = $db->query("SELECT id, nama_lengkap, spesialisasi FROM instruktur")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Jadwal Kursus</h1>
                        <p class="text-gray-600">Atur jadwal teori dan praktik mengemudi</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Di bagian header, ganti button toggle dengan ini: -->
<button id="sidebar-toggle" class="p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-all duration-300 hover:scale-105">
    <i class="fas fa-bars text-blue-600"></i>
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

                <!-- Add Schedule Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Tambah Jadwal Baru</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <input type="hidden" name="add_schedule" value="1">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Siswa *</label>
                                <select name="pendaftaran_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Siswa</option>
                                    <?php foreach ($active_registrations as $reg): ?>
                                    <option value="<?= $reg['id'] ?>">
                                        <?= $reg['nomor_pendaftaran'] ?> - <?= htmlspecialchars($reg['nama_lengkap']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Instruktur *</label>
                                <select name="instruktur_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Instruktur</option>
                                    <?php foreach ($instrukturs as $instruktur): ?>
                                    <option value="<?= $instruktur['id'] ?>">
                                        <?= htmlspecialchars($instruktur['nama_lengkap']) ?> - <?= $instruktur['spesialisasi'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal *</label>
                                <input type="date" name="tanggal_jadwal" required min="<?= date('Y-m-d') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai *</label>
                                <input type="time" name="jam_mulai" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="08:00">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai *</label>
                                <input type="time" name="jam_selesai" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="10:00">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Sesi *</label>
                                <select name="tipe_sesi" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="teori">Teori</option>
                                    <option value="praktik">Praktik</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                                <input type="text" name="lokasi"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Lokasi kursus">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mobil Digunakan</label>
                                <input type="text" name="mobil_digunakan"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Mobil yang akan digunakan">
                            </div>
                            
                            <div class="md:col-span-3 flex justify-end">
                                <button type="submit" 
                                        class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition duration-300">
                                    <i class="fas fa-plus mr-2"></i>Tambah Jadwal
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
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
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

<script>
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

    // Update Schedule Function (existing)
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

    // Auto-hide success message after 5 seconds
    setTimeout(() => {
        const successMessage = document.querySelector('.bg-green-100');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);
</script>
</body>
</html>