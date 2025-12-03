<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    
    // Process each form field
    foreach ($_POST as $key => $value) {
        if (strpos($key, '_') !== false) {
            list($bagian, $kunci) = explode('_', $key, 2);
            
            // Clean input
            $bagian = trim($bagian);
            $kunci = trim($kunci);
            $nilai = trim($value);
            
            // Update or insert
            $query = "INSERT INTO pengaturan_footer (bagian, kunci, nilai) 
                     VALUES (:bagian, :kunci, :nilai)
                     ON DUPLICATE KEY UPDATE nilai = :nilai2, diperbarui_pada = CURRENT_TIMESTAMP";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bagian', $bagian);
            $stmt->bindParam(':kunci', $kunci);
            $stmt->bindParam(':nilai', $nilai);
            $stmt->bindParam(':nilai2', $nilai);
            
            if ($stmt->execute()) {
                $success = true;
            }
        }
    }
    
    if ($success) {
        $_SESSION['success_message'] = "Pengaturan footer berhasil diperbarui!";
        header('Location: edit-footer.php');
        exit;
    } else {
        $error_message = "Gagal menyimpan pengaturan.";
    }
}

// Get all footer settings
$footer_settings = [];
$query = "SELECT bagian, kunci, nilai FROM pengaturan_footer ORDER BY bagian, kunci";
$stmt = $db->query($query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $footer_settings[$row['bagian']][$row['kunci']] = $row['nilai'];
}

// Dapatkan nama file yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Footer - Admin Krishna Driving</title>
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
    <div class="flex h-screen absolute inset-0">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden relative">
            <!-- Top Header -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Edit Footer</h1>
                        <p class="text-gray-600">Kelola informasi yang ditampilkan di footer website</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900"><?= $_SESSION['admin_username'] ?></p>
                            <p class="text-xs text-gray-500"><?= date('l, d F Y') ?></p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="m-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?= $_SESSION['success_message'] ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="m-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?= $error_message ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <form method="POST" class="space-y-8">
                    <!-- Info Perusahaan -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-blue-100 rounded-lg mr-4">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Info Perusahaan</h3>
                                <p class="text-gray-600 text-sm">Informasi tentang perusahaan/kursus</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Perusahaan
                                </label>
                                <input type="text" name="perusahaan_nama" 
                                       value="<?= htmlspecialchars($footer_settings['perusahaan']['nama'] ?? 'Krishna Kursus') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Deskripsi Perusahaan
                                </label>
                                <textarea name="perusahaan_deskripsi" rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          required><?= htmlspecialchars($footer_settings['perusahaan']['deskripsi'] ?? 'Kursus mengemudi mobil profesional dengan instruktur berpengalaman dan metode terbaik. Garansi sampai bisa mengemudi dengan percaya diri.') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kontak Informasi -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-green-100 rounded-lg mr-4">
                                <i class="fas fa-address-book text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Informasi Kontak</h3>
                                <p class="text-gray-600 text-sm">Detail kontak yang ditampilkan di footer</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Alamat
                                </label>
                                <input type="text" name="kontak_alamat" 
                                       value="<?= htmlspecialchars($footer_settings['kontak']['alamat'] ?? 'Jl. Raya Contoh No. 123, Jakarta') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Telepon
                                </label>
                                <input type="text" name="kontak_telepon" 
                                       value="<?= htmlspecialchars($footer_settings['kontak']['telepon'] ?? '+62 812-3456-7890') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" name="kontak_email" 
                                       value="<?= htmlspecialchars($footer_settings['kontak']['email'] ?? 'info@krishnadriving.com') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    WhatsApp
                                </label>
                                <input type="text" name="kontak_whatsapp" 
                                       value="<?= htmlspecialchars($footer_settings['kontak']['whatsapp'] ?? '+62 812-3456-7890') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Format: +62 812-3456-7890</p>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Operasional -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-yellow-100 rounded-lg mr-4">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Jam Operasional</h3>
                                <p class="text-gray-600 text-sm">Waktu operasional kursus</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <label class="w-48 text-sm font-medium text-gray-700">Senin - Jumat</label>
                                <input type="text" name="jam_hari_kerja" 
                                       value="<?= htmlspecialchars($footer_settings['jam']['hari_kerja'] ?? '08:00 - 20:00') ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex items-center">
                                <label class="w-48 text-sm font-medium text-gray-700">Sabtu</label>
                                <input type="text" name="jam_sabtu" 
                                       value="<?= htmlspecialchars($footer_settings['jam']['sabtu'] ?? '08:00 - 18:00') ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex items-center">
                                <label class="w-48 text-sm font-medium text-gray-700">Minggu</label>
                                <input type="text" name="jam_minggu" 
                                       value="<?= htmlspecialchars($footer_settings['jam']['minggu'] ?? '08:00 - 15:00') ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Media Sosial -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-purple-100 rounded-lg mr-4">
                                <i class="fas fa-share-alt text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Media Sosial</h3>
                                <p class="text-gray-600 text-sm">Link media sosial perusahaan</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Facebook URL
                                </label>
                                <input type="url" name="media_facebook" 
                                       value="<?= htmlspecialchars($footer_settings['media']['facebook'] ?? '#') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="https://facebook.com/username">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Instagram URL
                                </label>
                                <input type="url" name="media_instagram" 
                                       value="<?= htmlspecialchars($footer_settings['media']['instagram'] ?? '#') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="https://instagram.com/username">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    YouTube URL
                                </label>
                                <input type="url" name="media_youtube" 
                                       value="<?= htmlspecialchars($footer_settings['media']['youtube'] ?? '#') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="https://youtube.com/channel">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    TikTok URL
                                </label>
                                <input type="url" name="media_tiktok" 
                                       value="<?= htmlspecialchars($footer_settings['media']['tiktok'] ?? '#') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="https://tiktok.com/@username">
                            </div>
                        </div>
                    </div>

                    <!-- Preview & Submit -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Simpan Perubahan</h3>
                                <p class="text-gray-600 text-sm">Simpan semua pengaturan footer</p>
                            </div>
                            <div class="flex space-x-4">
                                <a href="index.php" 
                                   class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300 font-medium">
                                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                                </a>
                                <button type="submit" 
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </div>
                        
                        <!-- Preview -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-700 mb-3">Pratinjau Footer:</h4>
                            <div class="text-sm text-gray-600 bg-gray-100 p-4 rounded">
                                <p>Nama Perusahaan: <span class="font-medium"><?= htmlspecialchars($footer_settings['perusahaan']['nama'] ?? 'Krishna Kursus') ?></span></p>
                                <p>WhatsApp: <span class="font-medium"><?= htmlspecialchars($footer_settings['kontak']['whatsapp'] ?? '+62 812-3456-7890') ?></span></p>
                                <p>Jam Operasional: <span class="font-medium"><?= htmlspecialchars($footer_settings['jam']['hari_kerja'] ?? '08:00 - 20:00') ?></span></p>
                                <p class="text-xs text-gray-500 mt-2">*Perubahan akan terlihat setelah disimpan</p>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successMessage = document.querySelector('.bg-green-100');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Harap isi semua field yang wajib diisi!');
            }
        });

        // Clear validation on input
        document.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('border-red-500');
            });
        });
    </script>
</body>
</html>