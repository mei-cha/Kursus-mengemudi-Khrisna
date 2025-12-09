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
    $kategori = $_POST['kategori'];
    $gambar = $_FILES['gambar'];
    $gambar_name = time() . '_' . basename($gambar['name']);
    $target_file = $target_dir . $gambar_name;

    // Validasi
    if ($check = getimagesize($gambar["tmp_name"]) === false) {
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
        $stmt = $db->prepare("INSERT INTO galeri (gambar, kategori, status) VALUES (?, ?, 'aktif')");
        if ($stmt->execute([$gambar_name, $kategori])) {
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

$query .= " ORDER BY created_at DESC";

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

                <!-- Upload Form Toggle Button -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
                    <div class="pt-6 px-6 pb-4">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Upload Gambar Baru</h3>
                                <p class="text-gray-600">Pilih gambar dan kategori untuk ditampilkan di galeri.</p>
                            </div>
                            <button onclick="toggleUploadForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle upload form">
                                <i id="toggle-icon" class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- Form Upload Gambar (Hidden by default) -->
                        <div id="uploadForm" class="hidden mt-6">
                            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Kolom Kiri: Upload Gambar -->
                                <div class="space-y-4">

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Gambar <span class="text-red-500">*</span>
                                        </label>
                                        <label for="gambar" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 cursor-pointer transition hover:border-blue-400 hover:bg-blue-50">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                                            <span class="text-xs text-gray-600">Klik atau seret file ke sini</span>
                                            <input
                                                id="gambar"
                                                type="file"
                                                name="gambar"
                                                required
                                                accept="image/*"
                                                class="hidden"
                                                onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'Tidak ada file dipilih'" />
                                        </label>
                                        <p id="file-name" class="text-xs text-gray-500 mt-2 text-center italic">Tidak ada file dipilih</p>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Kategori & Submit -->
                                <div class="space-y-4">

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Kategori <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="kategori"
                                            required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition text-sm">
                                            <option value="" disabled selected>Pilih kategori</option>
                                            <option value="aktivitas">Aktivitas</option>
                                            <option value="fasilitas">Fasilitas</option>
                                            <option value="sertifikat">Sertifikat</option>
                                            <option value="kendaraan">Kendaraan</option>
                                            <option value="instruktur">Instruktur</option>
                                        </select>
                                    </div>

                                    <!-- Tombol Submit -->
                                    <div class="pt-4 border-t border-gray-100">
                                        <button
                                            type="submit"
                                            class="w-full px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition flex items-center justify-center space-x-2">
                                            <i class="fas fa-upload mr-1.5"></i>
                                            <span>Upload Gambar</span>
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                                <select name="kategori"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Kategori</option>
                                    <?php
                                    $categories = [
                                        'aktivitas' => 'Aktivitas',
                                        'fasilitas' => 'Fasilitas',
                                        'sertifikat' => 'Sertifikat',
                                        'kendaraan' => 'Kendaraan',
                                        'instruktur' => 'Instruktur'
                                    ];
                                    foreach ($categories as $value => $label):
                                    ?>
                                        <option value="<?= $value ?>" <?= $kategori_filter === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="nonaktif" <?= $status_filter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-2">
                                <button type="submit"
                                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center space-x-2">
                                    <i class="fas fa-filter"></i>
                                    <span>Filter</span>
                                </button>
                                <a href="galeri.php"
                                    class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300 flex items-center justify-center space-x-2">
                                    <i class="fas fa-refresh"></i>
                                    <span>Reset</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                    <!-- Total Images -->
                    <div class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition duration-300">
                        <div class="p-3 bg-blue-100 rounded-full inline-block mb-3">
                            <i class="fas fa-images text-blue-600 text-lg"></i>
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
                        'instruktur' => ['color' => 'indigo', 'icon' => 'chalkboard-teacher', 'label' => 'Instruktur']
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
                        <div class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition duration-300">
                            <div class="p-3 bg-<?= $info['color'] ?>-100 rounded-full inline-block mb-3">
                                <i class="fas fa-<?= $info['icon'] ?> text-<?= $info['color'] ?>-600 text-lg"></i>
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
                                <i class="fas fa-info-circle mr-1"></i>
                                Total: <?= count($galeri) ?> gambar
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if (count($galeri) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <?php foreach ($galeri as $data): ?>
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
                                        <!-- Image -->
                                        <div class="relative group">
                                            <img src="../assets/images/galeri/<?= $data['gambar'] ?>"
                                                alt="Gambar <?= htmlspecialchars($data['kategori']) ?>"
                                                class="w-full h-48 object-cover"
                                                onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">

                                            <!-- Overlay Actions -->
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                <div class="flex space-x-2">
                                                    <button onclick="viewImage('<?= $data['gambar'] ?>', '<?= htmlspecialchars(ucfirst($data['kategori'])) ?>')"
                                                        class="bg-white text-blue-600 p-3 rounded-full hover:bg-blue-50 hover:scale-110 transition duration-300 transform"
                                                        title="Lihat Gambar">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="confirmDelete(<?= $data['id'] ?>)"
                                                        class="bg-white text-red-600 p-3 rounded-full hover:bg-red-50 hover:scale-110 transition duration-300 transform"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Status Badge -->
                                            <?php
                                            $status_badges = [
                                                'aktif' => 'bg-green-100 text-green-800 border border-green-200',
                                                'nonaktif' => 'bg-red-100 text-red-800 border border-red-200'
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
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="inline-block px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full capitalize">
                                                    <?= $data['kategori'] ?>
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    <?= date('d M Y', strtotime($data['created_at'])) ?>
                                                </span>
                                            </div>

                                            <!-- Quick Status Toggle -->
                                            <form method="POST" class="mt-3 pt-3 border-t border-gray-100">
                                                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-gray-600 font-medium">Status:</span>
                                                    <select name="status" onchange="this.form.submit()"
                                                        class="text-xs px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition duration-300 <?= $data['status'] === 'aktif' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>">
                                                        <option value="aktif" <?= $data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                                    </select>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg text-gray-500">Belum ada gambar di galeri.</p>
                                <p class="text-sm text-gray-400 mt-1">Upload gambar pertama Anda di form atas.</p>
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
                    <button onclick="closeImageModal()"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition duration-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-4">
                    <img id="modalImage" src="" alt="" class="w-full h-auto rounded-lg shadow">
                </div>
            </div>
        </div>
    </div>

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        function toggleUploadForm() {
            const form = document.getElementById('uploadForm');
            const icon = document.getElementById('toggle-icon');

            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }
        // View Image Modal
        function viewImage(imageName, title) {
            document.getElementById('modalImage').src = `../assets/images/galeri/${imageName}`;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus gambar ini?')) {
                window.location.href = `galeri.php?delete=${id}`;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            if (event.target === imageModal) {
                closeImageModal();
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
                    dropZone.classList.add('border-blue-400', 'bg-blue-50', 'border-solid');
                    dropZone.classList.remove('border-dashed');
                }

                function unhighlight() {
                    dropZone.classList.remove('border-blue-400', 'bg-blue-50', 'border-solid');
                    dropZone.classList.add('border-dashed');
                }

                dropZone.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    fileInput.files = files;

                    // Show file name
                    if (files.length > 0) {
                        const fileName = files[0].name;
                        const infoText = dropZone.querySelector('.text-sm.text-gray-600');
                        if (infoText) {
                            infoText.textContent = `File dipilih: ${fileName}`;
                            infoText.classList.add('text-blue-600', 'font-medium');
                        }
                    }
                }
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape to close modal
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>

</html>