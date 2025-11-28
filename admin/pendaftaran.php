<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle tambah siswa manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_siswa'])) {
    // Generate nomor pendaftaran
    $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
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
        'status_pendaftaran' => 'dikonfirmasi', // Langsung dikonfirmasi karena input manual
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
        } else {
            $error = "Gagal menambahkan siswa!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
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
    } else {
        $error = "Gagal mengupdate status pendaftaran!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM pendaftaran_siswa WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Pendaftaran berhasil dihapus!";
    } else {
        $error = "Gagal menghapus pendaftaran!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT ps.*, pk.nama_paket, pk.harga 
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

$query .= " ORDER BY ps.dibuat_pada DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filter
$status_counts = $db->query("
    SELECT status_pendaftaran, COUNT(*) as count 
    FROM pendaftaran_siswa 
    GROUP BY status_pendaftaran
")->fetchAll(PDO::FETCH_ASSOC);

// Get paket kursus untuk form tambah siswa - DIUBAH: hilangkan WHERE status
$paket_kursus = $db->query("SELECT id, nama_paket, harga FROM paket_kursus")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftaran - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Pendaftaran</h1>
                        <p class="text-gray-600">Kelola data pendaftaran siswa</p>
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
                                    class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition duration-300">
                                <i class="fas fa-user-plus mr-2"></i>Tambah Siswa
                            </button>
                        </div>

                        <!-- Form Tambah Siswa (Hidden by default) -->
                        <div id="tambahForm" class="mt-6 hidden">
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="hidden" name="tambah_siswa" value="1">
                                
                                <div class="space-y-4">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Data Pribadi</h4>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                                        <input type="text" name="nama_lengkap" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Nama lengkap siswa">
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                            <input type="email" name="email" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="email@contoh.com">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Telepon *</label>
                                            <input type="tel" name="telepon" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="08123456789">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir *</label>
                                            <input type="date" name="tanggal_lahir" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin *</label>
                                            <select name="jenis_kelamin" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="L">Laki-laki</option>
                                                <option value="P">Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat *</label>
                                        <textarea name="alamat" required rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Alamat lengkap"></textarea>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Data Kursus</h4>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Paket Kursus *</label>
                                        <select name="paket_kursus_id" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Pilih Paket</option>
                                            <?php foreach ($paket_kursus as $paket): ?>
                                            <option value="<?= $paket['id'] ?>">
                                                <?= htmlspecialchars($paket['nama_paket']) ?> - Rp <?= number_format($paket['harga'], 0, ',', '.') ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Mobil *</label>
                                            <select name="tipe_mobil" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="manual">Manual</option>
                                                <option value="matic">Matic</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Jadwal Preferensi *</label>
                                            <select name="jadwal_preferensi" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="pagi">Pagi</option>
                                                <option value="siang">Siang</option>
                                                <option value="sore">Sore</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pengalaman Mengemudi</label>
                                        <select name="pengalaman_mengemudi"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="pemula">Pemula</option>
                                            <option value="pernah_kursus">Pernah Kursus</option>
                                            <option value="pernah_ujian">Pernah Ujian</option>
                                        </select>
                                    </div>
                                    
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Data Tambahan</h4>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi Medis</label>
                                        <textarea name="kondisi_medis" rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Kondisi medis khusus (jika ada)"></textarea>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kontak Darurat</label>
                                            <input type="text" name="nama_kontak_darurat"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Nama kontak darurat">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Telepon Darurat</label>
                                            <input type="tel" name="kontak_darurat"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="08123456789">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Admin</label>
                                        <textarea name="catatan_admin" rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Catatan tambahan"></textarea>
                                    </div>
                                    
                                    <div class="flex space-x-3 pt-4">
                                        <button type="button" onclick="toggleTambahForm()" 
                                                class="flex-1 bg-gray-600 text-white py-3 rounded-lg font-bold hover:bg-gray-700 transition duration-300">
                                            Batal
                                        </button>
                                        <button type="submit" 
                                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                                            <i class="fas fa-save mr-2"></i>Simpan Data
                                        </button>
                                    </div>
                                </div>
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
                                            <div class="text-sm text-gray-500">Rp <?= number_format($data['harga'] ?? 0, 0, ',', '.') ?></div>
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
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
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

    <script>
        // Toggle form tambah siswa
        function toggleTambahForm() {
            const form = document.getElementById('tambahForm');
            form.classList.toggle('hidden');
        }

        // Sidebar Toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        });

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