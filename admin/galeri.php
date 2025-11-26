<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

$target_dir = "../assets/images/galeri/";

// Auto create folder jika tidak ada
if (!file_exists($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        die("Error: Gagal membuat folder galeri. Silakan buat manual folder: " . $target_dir);
    }
}

// Pastikan folder writable
if (!is_writable($target_dir)) {
    die("Error: Folder tidak dapat ditulisi. Periksa permissions folder: " . $target_dir);
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gambar'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];
    $urutan_tampil = $_POST['urutan_tampil'] ?? 0;
    
    $gambar = $_FILES['gambar'];
    $gambar_name = time() . '_' . basename($gambar['name']);
    $target_dir = "../assets/images/galeri/";
    $target_file = $target_dir . $gambar_name;
    
    // Auto create folder jika tidak ada
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            $error = "Error: Gagal membuat folder galeri. Silakan buat manual folder: " . $target_dir;
        }
    }
    
    // Check folder permissions
    if (!is_writable($target_dir)) {
        $error = "Error: Folder tidak dapat ditulisi. Periksa permissions folder: " . $target_dir;
    }
    // Check if image file is a actual image
    elseif ($check = getimagesize($gambar["tmp_name"]) === false) {
        $error = "File bukan gambar!";
    } 
    // Check file size (max 5MB)
    elseif ($gambar["size"] > 5000000) {
        $error = "Ukuran file terlalu besar! Maksimal 5MB.";
    }
    // Allow certain file formats
    elseif (!in_array(strtolower(pathinfo($gambar_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
        $error = "Hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
    }
    // Upload file
    elseif (move_uploaded_file($gambar["tmp_name"], $target_file)) {
        $stmt = $db->prepare("INSERT INTO galeri (judul, deskripsi, gambar, kategori, urutan_tampil, status) VALUES (?, ?, ?, ?, ?, 'aktif')");
        if ($stmt->execute([$judul, $deskripsi, $gambar_name, $kategori, $urutan_tampil])) {
            $success = "Gambar berhasil diupload!";
        } else {
            $error = "Gagal menyimpan data ke database!";
            // Delete uploaded file if database failed
            if (file_exists($target_file)) {
                unlink($target_file);
            }
        }
    } else {
        $error = "Terjadi kesalahan saat upload gambar! Periksa permissions folder.";
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE galeri SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        $success = "Status galeri berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate status galeri!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image filename first
    $stmt = $db->prepare("SELECT gambar FROM galeri WHERE id = ?");
    $stmt->execute([$id]);
    $gambar = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($gambar) {
        $stmt = $db->prepare("DELETE FROM galeri WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Delete image file
            $file_path = "../assets/images/galeri/" . $gambar['gambar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $success = "Gambar berhasil dihapus!";
        } else {
            $error = "Gagal menghapus gambar dari database!";
        }
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_gambar'])) {
    $id = $_POST['id'];
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];
    $urutan_tampil = $_POST['urutan_tampil'] ?? 0;
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE galeri SET judul = ?, deskripsi = ?, kategori = ?, urutan_tampil = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$judul, $deskripsi, $kategori, $urutan_tampil, $status, $id])) {
        $success = "Data galeri berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate data galeri!";
    }
}

// Get filter parameters
$kategori_filter = $_GET['kategori'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT * FROM galeri WHERE 1=1";
$params = [];

if ($kategori_filter) {
    $query .= " AND kategori = ?";
    $params[] = $kategori_filter;
}

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY urutan_tampil ASC, created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$galeri = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category counts
$kategori_counts = $db->query("
    SELECT kategori, COUNT(*) as count 
    FROM galeri 
    GROUP BY kategori
")->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$status_counts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM galeri 
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Galeri</h1>
                        <p class="text-gray-600">Upload dan kelola foto galeri</p>
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

                <!-- Upload Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Upload Gambar Baru</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Judul *</label>
                                    <input type="text" name="judul" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Judul gambar">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                                    <textarea name="deskripsi" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Deskripsi gambar"></textarea>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori *</label>
                                        <select name="kategori" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Pilih Kategori</option>
                                            <option value="aktivitas">Aktivitas</option>
                                            <option value="fasilitas">Fasilitas</option>
                                            <option value="sertifikat">Sertifikat</option>
                                            <option value="kendaraan">Kendaraan</option>
                                            <option value="instruktur">Instruktur</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Urutan Tampil</label>
                                        <input type="number" name="urutan_tampil" value="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar *</label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-600 mb-2">Upload gambar (max 5MB)</p>
                                        <input type="file" name="gambar" required accept="image/*"
                                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        <p class="text-xs text-gray-500 mt-2">Format: JPG, JPEG, PNG, GIF</p>
                                    </div>
                                </div>
                                
                                <button type="submit" 
                                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-upload mr-2"></i>Upload Gambar
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                                <select name="kategori" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Kategori</option>
                                    <option value="aktivitas" <?= $kategori_filter === 'aktivitas' ? 'selected' : '' ?>>Aktivitas</option>
                                    <option value="fasilitas" <?= $kategori_filter === 'fasilitas' ? 'selected' : '' ?>>Fasilitas</option>
                                    <option value="sertifikat" <?= $kategori_filter === 'sertifikat' ? 'selected' : '' ?>>Sertifikat</option>
                                    <option value="kendaraan" <?= $kategori_filter === 'kendaraan' ? 'selected' : '' ?>>Kendaraan</option>
                                    <option value="instruktur" <?= $kategori_filter === 'instruktur' ? 'selected' : '' ?>>Instruktur</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="nonaktif" <?= $status_filter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                                <a href="galeri.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300">
                                    <i class="fas fa-refresh mr-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <!-- Total Images -->
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="p-2 bg-blue-100 rounded-lg inline-block mb-2">
                            <i class="fas fa-images text-blue-600"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900"><?= count($galeri) ?></div>
                        <div class="text-sm text-gray-600">Total Gambar</div>
                    </div>
                    
                    <!-- Category Stats -->
                    <?php
                    $kategori_info = [
                        'aktivitas' => ['color' => 'green', 'icon' => 'users', 'label' => 'Aktivitas'],
                        'fasilitas' => ['color' => 'purple', 'icon' => 'building', 'label' => 'Fasilitas'],
                        'sertifikat' => ['color' => 'yellow', 'icon' => 'certificate', 'label' => 'Sertifikat'],
                        'kendaraan' => ['color' => 'red', 'icon' => 'car', 'label' => 'Kendaraan'],
                        'instruktur' => ['color' => 'blue', 'icon' => 'chalkboard-teacher', 'label' => 'Instruktur']
                    ];
                    
                    foreach ($kategori_info as $kategori => $info): 
                        $count = 0;
                        foreach ($kategori_counts as $kc) {
                            if ($kc['kategori'] === $kategori) {
                                $count = $kc['count'];
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

                <!-- Gallery Grid -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Galeri Gambar (<?= count($galeri) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($galeri) ?> gambar
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <?php if (count($galeri) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <?php foreach ($galeri as $data): ?>
                                <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition duration-300">
                                    <!-- Image -->
                                    <div class="relative group">
                                        <img src="../assets/images/galeri/<?= $data['gambar'] ?>" 
                                             alt="<?= htmlspecialchars($data['judul']) ?>" 
                                             class="w-full h-48 object-cover">
                                        
                                        <!-- Overlay Actions -->
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <div class="flex space-x-2">
                                                <button onclick="viewImage('<?= $data['gambar'] ?>', '<?= htmlspecialchars($data['judul']) ?>')" 
                                                        class="bg-white text-blue-600 p-2 rounded-full hover:bg-blue-50 transition duration-300"
                                                        title="Lihat Gambar">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editGambar(<?= $data['id'] ?>)" 
                                                        class="bg-white text-green-600 p-2 rounded-full hover:bg-green-50 transition duration-300"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                        class="bg-white text-red-600 p-2 rounded-full hover:bg-red-50 transition duration-300"
                                                        title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <?php
                                        $status_badges = [
                                            'aktif' => 'bg-green-100 text-green-800',
                                            'nonaktif' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <div class="absolute top-2 right-2">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                                                <?= ucfirst($data['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="p-4">
                                        <h4 class="font-bold text-gray-800 text-sm mb-1"><?= htmlspecialchars($data['judul']) ?></h4>
                                        <p class="text-gray-600 text-xs mb-2"><?= htmlspecialchars($data['deskripsi']) ?></p>
                                        
                                        <div class="flex justify-between items-center text-xs text-gray-500">
                                            <span class="capitalize"><?= $data['kategori'] ?></span>
                                            <span>Urutan: <?= $data['urutan_tampil'] ?></span>
                                        </div>
                                        
                                        <!-- Quick Status Toggle -->
                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                            <form method="POST" class="flex justify-between items-center">
                                                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <span class="text-xs text-gray-600">Status:</span>
                                                <select name="status" onchange="this.form.submit()" 
                                                        class="text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                                    <option value="aktif" <?= $data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg text-gray-500">Belum ada gambar di galeri.</p>
                                <p class="text-sm text-gray-400">Upload gambar pertama Anda di form atas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 w-full max-w-4xl">
            <div class="bg-white rounded-lg shadow-xl">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 id="modalTitle" class="text-xl font-bold text-gray-900"></h3>
                    <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                <div class="p-4">
                    <img id="modalImage" src="" alt="" class="w-full h-auto rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Image Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="edit_gambar" value="1">
                
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Edit Gambar</h3>
                    <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Judul</label>
                        <input type="text" name="judul" id="editJudul" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea name="deskripsi" id="editDeskripsi" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                            <select name="kategori" id="editKategori" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="aktivitas">Aktivitas</option>
                                <option value="fasilitas">Fasilitas</option>
                                <option value="sertifikat">Sertifikat</option>
                                <option value="kendaraan">Kendaraan</option>
                                <option value="instruktur">Instruktur</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="editStatus"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Urutan Tampil</label>
                        <input type="number" name="urutan_tampil" id="editUrutan" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-300">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                        Update
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

        // View Image Modal
        function viewImage(imageName, title) {
            document.getElementById('modalImage').src = `../assets/images/galeri/${imageName}`;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Edit Image Modal
        function editGambar(id) {
            // In real implementation, you would fetch the data via AJAX
            // For now, we'll use a simple approach
            fetch(`get_gambar_data.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editJudul').value = data.judul;
                    document.getElementById('editDeskripsi').value = data.deskripsi;
                    document.getElementById('editKategori').value = data.kategori;
                    document.getElementById('editStatus').value = data.status;
                    document.getElementById('editUrutan').value = data.urutan_tampil;
                    document.getElementById('editModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat data gambar');
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus gambar ini?')) {
                window.location.href = `galeri.php?delete=${id}`;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === imageModal) {
                closeImageModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }

        // Drag and drop file upload enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            const dropZone = fileInput.closest('.border-dashed');
            
            if (dropZone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, unhighlight, false);
                });
                
                function highlight() {
                    dropZone.classList.add('border-blue-400', 'bg-blue-50');
                }
                
                function unhighlight() {
                    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                }
                
                dropZone.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    fileInput.files = files;
                }
            }
        });
    </script>
</body>
</html>