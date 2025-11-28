<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Check if evaluasi_kemajuan table exists, if not create it
try {
    $db->query("SELECT 1 FROM evaluasi_kemajuan LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $createTableQuery = "
        CREATE TABLE evaluasi_kemajuan (
            id INT PRIMARY KEY AUTO_INCREMENT,
            pendaftaran_id INT NOT NULL,
            jadwal_id INT NOT NULL,
            kategori_skill ENUM('persiapan', 'keterampilan_dasar', 'lalu_lintas', 'parkir', 'manuver') NOT NULL,
            item_skill VARCHAR(100) NOT NULL,
            nilai INT NULL,
            catatan TEXT NULL,
            dievaluasi_oleh INT NOT NULL,
            dievaluasi_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran_siswa(id),
            FOREIGN KEY (jadwal_id) REFERENCES jadwal_kursus(id)
        )
    ";
    
    try {
        $db->exec($createTableQuery);
        $table_created = true;
    } catch (PDOException $createError) {
        $table_error = "Gagal membuat tabel: " . $createError->getMessage();
    }
}

// Handle add evaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_evaluation'])) {
    // Gunakan admin_id dari session atau default ke 1 jika tidak ada
    $admin_id = $_SESSION['admin_id'] ?? 1;
    
    $data = [
        'jadwal_id' => $_POST['jadwal_id'],
        'kategori_skill' => $_POST['kategori_skill'],
        'item_skill' => $_POST['item_skill'],
        'nilai' => $_POST['nilai'],
        'catatan' => $_POST['catatan'] ?? '',
        'dievaluasi_oleh' => $admin_id
    ];
    
    try {
        // First, get pendaftaran_id from jadwal_id
        $stmt = $db->prepare("SELECT pendaftaran_id FROM jadwal_kursus WHERE id = ?");
        $stmt->execute([$data['jadwal_id']]);
        $jadwal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($jadwal) {
            $data['pendaftaran_id'] = $jadwal['pendaftaran_id'];
            
            $stmt = $db->prepare("
                INSERT INTO evaluasi_kemajuan 
                (pendaftaran_id, jadwal_id, kategori_skill, item_skill, nilai, catatan, dievaluasi_oleh) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $data['pendaftaran_id'],
                $data['jadwal_id'],
                $data['kategori_skill'],
                $data['item_skill'],
                $data['nilai'],
                $data['catatan'],
                $data['dievaluasi_oleh']
            ])) {
                $success = "Evaluasi berhasil ditambahkan!";
                // Refresh to show new data
                echo "<script>window.location.href = window.location.href;</script>";
            } else {
                $error = "Gagal menambahkan evaluasi!";
            }
        } else {
            $error = "Jadwal tidak ditemukan!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get progress data (with error handling)
try {
    $query = "SELECT ps.id as siswa_id, ps.nama_lengkap, ps.nomor_pendaftaran, 
                     COUNT(jk.id) as total_sesi,
                     COUNT(CASE WHEN jk.status = 'selesai' THEN 1 END) as sesi_selesai,
                     COALESCE(AVG(ek.nilai), 0) as rata_rata_nilai
              FROM pendaftaran_siswa ps 
              LEFT JOIN jadwal_kursus jk ON ps.id = jk.pendaftaran_id 
              LEFT JOIN evaluasi_kemajuan ek ON ps.id = ek.pendaftaran_id 
              WHERE ps.status_pendaftaran IN ('dikonfirmasi', 'diproses')
              GROUP BY ps.id, ps.nama_lengkap, ps.nomor_pendaftaran";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $progress_data = [];
    $progress_error = "Error loading progress data: " . $e->getMessage();
}

// Get students for filter
try {
    $students = $db->query("
        SELECT id, nomor_pendaftaran, nama_lengkap 
        FROM pendaftaran_siswa 
        WHERE status_pendaftaran IN ('dikonfirmasi', 'diproses')
        ORDER BY nama_lengkap
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
}

// Get completed schedules for evaluation form
try {
    $completed_schedules = $db->query("
        SELECT jk.id, jk.pendaftaran_id, ps.nama_lengkap, jk.tanggal_jadwal, jk.tipe_sesi 
        FROM jadwal_kursus jk 
        JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id 
        WHERE jk.status = 'selesai' 
        ORDER BY jk.tanggal_jadwal DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $completed_schedules = [];
    $schedule_error = "Error loading schedules: " . $e->getMessage();
}

// Get recent evaluations
try {
    $recent_evaluations = $db->query("
        SELECT ek.*, ps.nama_lengkap, ps.nomor_pendaftaran, jk.tanggal_jadwal 
        FROM evaluasi_kemajuan ek 
        JOIN pendaftaran_siswa ps ON ek.pendaftaran_id = ps.id 
        JOIN jadwal_kursus jk ON ek.jadwal_id = jk.id 
        ORDER BY ek.dievaluasi_pada DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_evaluations = [];
    $evaluation_error = "Error loading evaluations: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kemajuan Belajar - Krishna Driving</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Kemajuan Belajar Siswa</h1>
                        <p class="text-gray-600">Pantau perkembangan dan evaluasi skill siswa</p>
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
                <!-- Success Messages -->
                <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                </div>
                <?php endif; ?>

                <?php if (isset($table_created)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Tabel evaluasi_kemajuan berhasil dibuat!
                </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
                <?php endif; ?>

                <?php if (isset($table_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $table_error ?>
                </div>
                <?php endif; ?>

                <?php if (isset($evaluation_error)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?= $evaluation_error ?>
                </div>
                <?php endif; ?>

                <!-- Add Evaluation Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Tambah Evaluasi Kemajuan</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($completed_schedules)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-4"></i>
                                <p>Belum ada sesi yang selesai untuk dievaluasi.</p>
                                <p class="text-sm mt-2">Pastikan ada jadwal kursus dengan status 'selesai'.</p>
                            </div>
                        <?php else: ?>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="hidden" name="add_evaluation" value="1">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sesi yang Dievaluasi *</label>
                                <select name="jadwal_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Sesi</option>
                                    <?php foreach ($completed_schedules as $schedule): ?>
                                    <option value="<?= $schedule['id'] ?>">
                                        <?= htmlspecialchars($schedule['nama_lengkap']) ?> - 
                                        <?= date('d M Y', strtotime($schedule['tanggal_jadwal'])) ?> - 
                                        <?= ucfirst($schedule['tipe_sesi']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori Skill *</label>
                                <select name="kategori_skill" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="persiapan">Persiapan</option>
                                    <option value="keterampilan_dasar">Keterampilan Dasar</option>
                                    <option value="lalu_lintas">Lalu Lintas</option>
                                    <option value="parkir">Parkir</option>
                                    <option value="manuver">Manuver</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Item Skill *</label>
                                <input type="text" name="item_skill" required maxlength="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Contoh: Pindah gigi, Parkir paralel, dll">
                                <p class="text-xs text-gray-500 mt-1">Maksimal 100 karakter</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nilai (1-100) *</label>
                                <input type="number" name="nilai" required min="1" max="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="75">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                                <textarea name="catatan" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Catatan perkembangan siswa..."></textarea>
                            </div>
                            
                            <div class="md:col-span-2 flex justify-end">
                                <button type="submit" 
                                        class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition duration-300">
                                    <i class="fas fa-plus mr-2"></i>Tambah Evaluasi
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Overview -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Ringkasan Kemajuan Siswa</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($progress_data)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p>Belum ada data kemajuan siswa.</p>
                                <?php if (isset($progress_error)): ?>
                                <p class="text-sm text-yellow-600 mt-2"><?= $progress_error ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <?php foreach ($progress_data as $progress): 
                                $completion_rate = $progress['total_sesi'] > 0 ? ($progress['sesi_selesai'] / $progress['total_sesi']) * 100 : 0;
                                $avg_score = $progress['rata_rata_nilai'] ? round($progress['rata_rata_nilai'], 1) : 0;
                            ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-300">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($progress['nama_lengkap']) ?></h4>
                                        <p class="text-sm text-gray-500"><?= $progress['nomor_pendaftaran'] ?></p>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                        <?= $progress['sesi_selesai'] ?>/<?= $progress['total_sesi'] ?> sesi
                                    </span>
                                </div>
                                
                                <div class="space-y-2">
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">Progress Sesi</span>
                                            <span class="font-medium"><?= round($completion_rate) ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: <?= $completion_rate ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">Rata-rata Nilai</span>
                                            <span class="font-medium"><?= $avg_score ?></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($avg_score, 100) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 flex space-x-2">
                                    <button class="flex-1 bg-blue-600 text-white text-center py-2 rounded text-sm hover:bg-blue-700 transition duration-300">
                                        <i class="fas fa-chart-line mr-1"></i>Detail
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Evaluations -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Evaluasi Terbaru</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_evaluations)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-clipboard-list text-4xl mb-4"></i>
                                <p>Belum ada evaluasi terbaru.</p>
                                <p class="text-sm mt-2">Tambahkan evaluasi pertama menggunakan form di atas.</p>
                            </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_evaluations as $eval): ?>
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-300">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-bold"><?= $eval['nilai'] ?? 'N/A' ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($eval['nama_lengkap']) ?></h4>
                                        <p class="text-sm text-gray-500">
                                            <?= ucfirst(str_replace('_', ' ', $eval['kategori_skill'])) ?> - 
                                            <?= $eval['item_skill'] ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            <?= date('d M Y', strtotime($eval['tanggal_jadwal'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php if (isset($eval['nilai'])): ?>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?= $eval['nilai'] >= 80 ? 'bg-green-100 text-green-800' : 
                                           ($eval['nilai'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= $eval['nilai'] >= 80 ? 'Baik' : ($eval['nilai'] >= 60 ? 'Cukup' : 'Perlu Latihan') ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($eval['catatan'])): ?>
                                    <p class="text-xs text-gray-500 mt-1 max-w-xs truncate">
                                        <?= htmlspecialchars($eval['catatan']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
            }
        });

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const nilai = document.querySelector('input[name="nilai"]');
                if (nilai && (nilai.value < 1 || nilai.value > 100)) {
                    e.preventDefault();
                    alert('Nilai harus antara 1 dan 100');
                    nilai.focus();
                }
            });
        }
    </script>
</body>
</html>