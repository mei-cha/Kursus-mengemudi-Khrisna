<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle status update (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE testimoni SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        $success = "Status testimoni berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate status testimoni!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM testimoni WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Testimoni berhasil dihapus!";
    } else {
        $error = "Gagal menghapus testimoni!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM testimoni WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (nama_siswa LIKE ? OR testimoni_text LIKE ? OR paket_kursus LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$testimoni = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filter
$status_counts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM testimoni 
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Testimoni - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Testimoni</h1>
                        <p class="text-gray-600">Kelola testimoni dari siswa</p>
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

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Cari nama siswa, testimoni..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="disetujui" <?= $status_filter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                    <option value="ditolak" <?= $status_filter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="testimoni.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <?php
                    $status_info = [
                        'menunggu' => ['color' => 'yellow', 'icon' => 'clock', 'label' => 'Menunggu'],
                        'disetujui' => ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Disetujui'],
                        'ditolak' => ['color' => 'red', 'icon' => 'times-circle', 'label' => 'Ditolak']
                    ];
                    
                    foreach ($status_info as $status => $info): 
                        $count = 0;
                        foreach ($status_counts as $sc) {
                            if ($sc['status'] === $status) {
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
                                Data Testimoni (<?= count($testimoni) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($testimoni) ?> testimoni
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php if (count($testimoni) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                <?php foreach ($testimoni as $data): ?>
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 hover:shadow-md transition duration-300">
                                    <!-- Header -->
                                    <div class="flex items-center mb-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                            <?php if ($data['foto_siswa']): ?>
                                                <img src="../assets/images/testimoni/<?= $data['foto_siswa'] ?>" 
                                                     alt="<?= htmlspecialchars($data['nama_siswa']) ?>" 
                                                     class="w-12 h-12 rounded-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($data['nama_siswa']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($data['paket_kursus']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <?php
                                            $status_badges = [
                                                'menunggu' => 'bg-yellow-100 text-yellow-800',
                                                'disetujui' => 'bg-green-100 text-green-800',
                                                'ditolak' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                                <?= ucfirst($data['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <div class="text-yellow-400 mb-3">
                                        <?= str_repeat('★', $data['rating']) ?><?= str_repeat('☆', 5 - $data['rating']) ?>
                                        <span class="text-gray-600 text-sm ml-2">(<?= $data['rating'] ?>/5)</span>
                                    </div>
                                    
                                    <!-- Testimoni Text -->
                                    <p class="text-gray-600 text-sm mb-4 italic">
                                        "<?= htmlspecialchars($data['testimoni_text']) ?>"
                                    </p>
                                    
                                    <!-- Footer -->
                                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                        <div class="text-xs text-gray-500">
                                            <div><?= $data['lokasi'] ?></div>
                                            <div><?= date('d M Y', strtotime($data['tanggal_testimoni'])) ?></div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex space-x-2">
                                            <?php if ($data['status'] === 'menunggu'): ?>
                                                <!-- Approve Button -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                                                    <input type="hidden" name="status" value="disetujui">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" 
                                                            class="text-green-600 hover:text-green-900 text-sm"
                                                            title="Setujui">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                </form>
                                                
                                                <!-- Reject Button -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                                                    <input type="hidden" name="status" value="ditolak">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900 text-sm ml-2"
                                                            title="Tolak">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </form>
                                            <?php elseif ($data['status'] === 'disetujui'): ?>
                                                <!-- Already Approved -->
                                                <span class="text-green-600 text-sm">
                                                    <i class="fas fa-check-circle"></i> Disetujui
                                                </span>
                                            <?php else: ?>
                                                <!-- Rejected -->
                                                <span class="text-red-600 text-sm">
                                                    <i class="fas fa-times-circle"></i> Ditolak
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Button -->
                                            <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-900 text-sm ml-2"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg">Tidak ada testimoni yang ditemukan.</p>
                                <p class="text-sm">Coba ubah filter pencarian Anda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('collapsed');
        });

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus testimoni ini?')) {
                window.location.href = `testimoni.php?delete=${id}`;
            }
        }

        // Quick approve/reject with confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const approveButtons = document.querySelectorAll('form button[type="submit"]');
            approveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const action = this.closest('form').querySelector('input[name="status"]').value;
                    const confirmMessage = action === 'disetujui' 
                        ? 'Apakah Anda yakin ingin menyetujui testimoni ini?'
                        : 'Apakah Anda yakin ingin menolak testimoni ini?';
                    
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>