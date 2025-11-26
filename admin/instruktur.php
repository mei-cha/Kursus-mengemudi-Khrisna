<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle add/edit instructor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Lihat data yang dikirim
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    
    if (isset($_POST['add_instruktur'])) {
        // Add new instructor
        $nama_lengkap = $_POST['nama_lengkap'];
        $nomor_licensi = $_POST['nomor_licensi'];
        $spesialisasi = $_POST['spesialisasi'];
        $pengalaman_tahun = $_POST['pengalaman_tahun'];
        $deskripsi = $_POST['deskripsi'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        // Set default values for new fields
        $rating = 0.00;
        $total_siswa = 0;
        
        // Handle photo upload
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $foto_name = time() . '_' . basename($_FILES['foto']['name']);
            $target_dir = "../assets/images/instruktur/";
            
            // Create directory if not exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $target_file = $target_dir . $foto_name;
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES["foto"]["tmp_name"]);
            if ($check !== false) {
                // Check file size (max 2MB)
                if ($_FILES["foto"]["size"] <= 2097152) {
                    // Allow certain file formats
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                            $foto = $foto_name;
                        } else {
                            $error = "Gagal mengupload foto!";
                        }
                    } else {
                        $error = "Format file tidak didukung! Hanya JPG, JPEG, PNG, GIF yang diizinkan.";
                    }
                } else {
                    $error = "Ukuran file terlalu besar! Maksimal 2MB.";
                }
            } else {
                $error = "File bukan gambar!";
            }
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO instruktur (nama_lengkap, nomor_licensi, spesialisasi, pengalaman_tahun, deskripsi, foto, aktif, rating, total_siswa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$nama_lengkap, $nomor_licensi, $spesialisasi, $pengalaman_tahun, $deskripsi, $foto, $aktif, $rating, $total_siswa])) {
                $success = "Instruktur berhasil ditambahkan!";
                // Reset form
                echo "<script>resetForm();</script>";
            } else {
                $error = "Gagal menambahkan instruktur! Error: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['edit_instruktur'])) {
        // Edit existing instructor
        $id = $_POST['id'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $nomor_licensi = $_POST['nomor_licensi'];
        $spesialisasi = $_POST['spesialisasi'];
        $pengalaman_tahun = $_POST['pengalaman_tahun'];
        $deskripsi = $_POST['deskripsi'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        // Handle photo upload
        $foto = $_POST['current_foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $foto_name = time() . '_' . basename($_FILES['foto']['name']);
            $target_dir = "../assets/images/instruktur/";
            
            // Create directory if not exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $target_file = $target_dir . $foto_name;
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES["foto"]["tmp_name"]);
            if ($check !== false) {
                // Check file size (max 2MB)
                if ($_FILES["foto"]["size"] <= 2097152) {
                    // Allow certain file formats
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                            // Delete old photo if exists
                            if ($foto && file_exists($target_dir . $foto)) {
                                unlink($target_dir . $foto);
                            }
                            $foto = $foto_name;
                        }
                    }
                }
            }
        }
        
        try {
            $stmt = $db->prepare("UPDATE instruktur SET nama_lengkap = ?, nomor_licensi = ?, spesialisasi = ?, pengalaman_tahun = ?, deskripsi = ?, foto = ?, aktif = ? WHERE id = ?");
            
            if ($stmt->execute([$nama_lengkap, $nomor_licensi, $spesialisasi, $pengalaman_tahun, $deskripsi, $foto, $aktif, $id])) {
                $success = "Instruktur berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate instruktur! Error: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get instructor data first to delete photo
    $stmt = $db->prepare("SELECT foto FROM instruktur WHERE id = ?");
    $stmt->execute([$id]);
    $instruktur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("DELETE FROM instruktur WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Delete photo file if exists
        if ($instruktur['foto']) {
            $file_path = "../assets/images/instruktur/" . $instruktur['foto'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $success = "Instruktur berhasil dihapus!";
    } else {
        $error = "Gagal menghapus instruktur!";
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    
    $stmt = $db->prepare("UPDATE instruktur SET aktif = NOT aktif WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Status instruktur berhasil diubah!";
    } else {
        $error = "Gagal mengubah status instruktur!";
    }
}

// Get all instructors
$stmt = $db->query("SELECT * FROM instruktur ORDER BY pengalaman_tahun DESC, nama_lengkap ASC");
$instruktur = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get instructor statistics
$total_instruktur = $db->query("SELECT COUNT(*) as total FROM instruktur")->fetch()['total'];
$instruktur_aktif = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1")->fetch()['total'];
$manual_experts = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE spesialisasi = 'manual' OR spesialisasi = 'keduanya'")->fetch()['total'];
$matic_experts = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE spesialisasi = 'matic' OR spesialisasi = 'keduanya'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Instruktur - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Instruktur</h1>
                        <p class="text-gray-600">Kelola data instruktur mengemudi</p>
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

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Instruktur</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_instruktur ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Instruktur Aktif</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $instruktur_aktif ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-cog text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ahli Manual</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $manual_experts ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-car-side text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ahli Matic</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $matic_experts ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Instructor Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900" id="formTitle">Tambah Instruktur Baru</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" enctype="multipart/form-data" id="instructorForm">
                            <input type="hidden" name="id" id="editId">
                            <input type="hidden" name="current_foto" id="currentFoto">
                            <input type="hidden" name="add_instruktur" id="addMode" value="1">
                            <input type="hidden" name="edit_instruktur" id="editMode" value="0">
                            
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Photo Upload -->
                                <div class="lg:col-span-1">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Foto Instruktur</label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                                <div id="photoPreview" class="mb-3">
                                                    <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                                                    </div>
                                                </div>
                                                <input type="file" name="foto" id="foto" accept="image/*"
                                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                <p class="text-xs text-gray-500 mt-2">Format: JPG, JPEG, PNG (max 2MB)</p>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="aktif" id="aktif" 
                                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                       checked>
                                                <label for="aktif" class="ml-2 text-sm text-gray-700">
                                                    Aktif (Tampil di website)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Personal Information -->
                                <div class="lg:col-span-2 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                                            <input type="text" name="nama_lengkap" id="nama_lengkap" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Nama lengkap instruktur">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Lisensi *</label>
                                            <input type="text" name="nomor_licensi" id="nomor_licensi" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Nomor lisensi mengemudi">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Spesialisasi *</label>
                                            <select name="spesialisasi" id="spesialisasi" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="manual">Manual</option>
                                                <option value="matic">Matic</option>
                                                <option value="keduanya">Keduanya</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Pengalaman (Tahun) *</label>
                                            <input type="number" name="pengalaman_tahun" id="pengalaman_tahun" required min="1" max="50"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="5">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi & Keahlian *</label>
                                        <textarea name="deskripsi" id="deskripsi" rows="4" required
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Deskripsi singkat tentang keahlian dan pengalaman instruktur..."></textarea>
                                    </div>
                                    
                                    <div class="flex space-x-3 pt-4">
                                        <button type="submit" id="submitButton"
                                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                                            <i class="fas fa-plus mr-2"></i>Tambah Instruktur
                                        </button>
                                        <button type="button" id="cancelButton" onclick="resetForm()" 
                                                class="bg-gray-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700 transition duration-300 hidden">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Instructors Grid -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Daftar Instruktur (<?= count($instruktur) ?>)
                            </h3>
                            <div class="text-sm text-gray-600">
                                Total: <?= count($instruktur) ?> instruktur
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <?php if (count($instruktur) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <?php foreach ($instruktur as $data): ?>
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 text-center hover:shadow-md transition duration-300">
                                    <!-- Photo -->
                                    <div class="relative mb-4">
                                        <div class="w-20 h-20 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                                            <?php if ($data['foto']): ?>
                                                <img src="../assets/images/instruktur/<?= $data['foto'] ?>" 
                                                     alt="<?= htmlspecialchars($data['nama_lengkap']) ?>" 
                                                     class="w-20 h-20 rounded-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <div class="absolute top-0 right-0">
                                            <?php if ($data['aktif']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Information -->
                                    <h4 class="font-bold text-gray-800 text-lg mb-1"><?= htmlspecialchars($data['nama_lengkap']) ?></h4>
                                    <p class="text-gray-600 text-sm mb-2"><?= $data['nomor_licensi'] ?></p>
                                    
                                    <!-- Specialization -->
                                    <div class="mb-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                                            <i class="fas fa-car mr-1"></i><?= $data['spesialisasi'] ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Experience -->
                                    <div class="text-sm text-gray-600 mb-3">
                                        <i class="fas fa-award mr-1"></i><?= $data['pengalaman_tahun'] ?>+ Tahun
                                    </div>

                                    <!-- Rating -->
                                    <div class="text-yellow-400 text-sm mb-4">
                                        <?= str_repeat('★', floor($data['rating'] ?? 5)) ?><?= str_repeat('☆', 5 - floor($data['rating'] ?? 5)) ?>
                                        <span class="text-gray-600 text-xs">(<?= number_format($data['rating'] ?? 0, 1) ?>)</span>
                                    </div>

                                    <!-- Total Siswa -->
                                    <div class="text-sm text-gray-600 mb-4">
                                        <i class="fas fa-users mr-1"></i><?= $data['total_siswa'] ?>+ Siswa
                                    </div>
                                    
                                    <!-- Description -->
                                    <p class="text-gray-700 text-sm mb-4 line-clamp-2">
                                        <?= htmlspecialchars($data['deskripsi']) ?>
                                    </p>
                                    
                                    <!-- Actions -->
                                    <div class="flex justify-center space-x-2">
                                        <!-- Edit Button -->
                                        <button onclick="editInstructor(<?= $data['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-900 text-sm"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Toggle Status -->
                                        <a href="instruktur.php?toggle=<?= $data['id'] ?>" 
                                           class="text-<?= $data['aktif'] ? 'yellow' : 'green' ?>-600 hover:text-<?= $data['aktif'] ? 'yellow' : 'green' ?>-900 text-sm"
                                           title="<?= $data['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="fas fa-<?= $data['aktif'] ? 'pause' : 'play' ?>"></i>
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <button onclick="confirmDelete(<?= $data['id'] ?>)" 
                                                class="text-red-600 hover:text-red-900 text-sm"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-chalkboard-teacher text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg text-gray-500">Belum ada instruktur.</p>
                                <p class="text-sm text-gray-400">Tambahkan instruktur pertama Anda di form atas.</p>
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
            sidebar.classList.toggle('collapsed');
        });

        // Photo Preview
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').innerHTML = `
                        <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                            <img src="${e.target.result}" alt="Preview" class="w-32 h-32 rounded-full object-cover">
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            }
        });

// Edit Instructor Function
function editInstructor(id) {
    fetch(`get_instruktur_data.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Fill form with existing data
            document.getElementById('editId').value = data.id;
            document.getElementById('nama_lengkap').value = data.nama_lengkap;
            document.getElementById('nomor_licensi').value = data.nomor_licensi;
            document.getElementById('spesialisasi').value = data.spesialisasi;
            document.getElementById('pengalaman_tahun').value = data.pengalaman_tahun;
            document.getElementById('deskripsi').value = data.deskripsi;
            document.getElementById('aktif').checked = data.aktif == 1;
            document.getElementById('currentFoto').value = data.foto;
            
            // Update photo preview
            if (data.foto) {
                document.getElementById('photoPreview').innerHTML = `
                    <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                        <img src="../assets/images/instruktur/${data.foto}" alt="Current Photo" class="w-32 h-32 rounded-full object-cover">
                    </div>
                `;
            } else {
                document.getElementById('photoPreview').innerHTML = `
                    <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                    </div>
                `;
            }
            
            // Change form to edit mode
            document.getElementById('editMode').value = '1';
            document.getElementById('formTitle').textContent = 'Edit Instruktur';
            document.getElementById('submitButton').innerHTML = '<i class="fas fa-save mr-2"></i>Update Instruktur';
            document.getElementById('cancelButton').classList.remove('hidden');
            
            // Scroll to form
            document.getElementById('instructorForm').scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal memuat data instruktur: ' + error.message);
        });
}

// Form validation dan submission
document.getElementById('instructorForm').addEventListener('submit', function(e) {
    // Validasi dasar
    const nama_lengkap = document.getElementById('nama_lengkap').value.trim();
    const nomor_licensi = document.getElementById('nomor_licensi').value.trim();
    const pengalaman = parseInt(document.getElementById('pengalaman_tahun').value);
    
    if (!nama_lengkap) {
        alert('Nama lengkap harus diisi!');
        e.preventDefault();
        return;
    }
    
    if (!nomor_licensi) {
        alert('Nomor lisensi harus diisi!');
        e.preventDefault();
        return;
    }
    
    if (pengalaman < 1 || pengalaman > 50) {
        alert('Pengalaman harus antara 1-50 tahun!');
        e.preventDefault();
        return;
    }
    
    // File size validation
    const fotoInput = document.getElementById('foto');
    if (fotoInput.files.length > 0) {
        const fileSize = fotoInput.files[0].size / 1024 / 1024; // MB
        if (fileSize > 2) {
            alert('Ukuran file foto maksimal 2MB!');
            e.preventDefault();
            return;
        }
    }
    
    // Show loading state
    const submitButton = document.getElementById('submitButton');
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
    submitButton.disabled = true;
});
    </script>
</body>
</html>