<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied');
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

// Query yang diperbaiki - hanya mengambil kolom yang ada di database
$stmt = $db->prepare("
    SELECT 
        jk.*,
        ps.nama_lengkap as nama_siswa, 
        ps.nomor_pendaftaran, 
        ps.telepon, 
        ps.email,
        i.nama_lengkap as nama_instruktur, 
        i.spesialisasi,
        pk.nama_paket, 
        pk.durasi_jam
    FROM jadwal_kursus jk 
    JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id 
    JOIN instruktur i ON jk.instruktur_id = i.id 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE jk.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<div class="text-red-600 p-4">Data jadwal tidak ditemukan!</div>';
    exit;
}

// Fungsi untuk format tanggal dengan penanganan error
function formatDate($dateString, $format = 'd F Y') {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($dateString));
}

// Fungsi untuk format waktu
function formatTime($timeString) {
    if (empty($timeString)) {
        return '-';
    }
    return date('H:i', strtotime($timeString));
}

$status_badges = [
    'terjadwal' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    'selesai' => 'bg-green-100 text-green-800 border border-green-200',
    'dibatalkan' => 'bg-red-100 text-red-800 border border-red-200',
    'diubah' => 'bg-purple-100 text-purple-800 border border-purple-200'
];
$status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800 border border-gray-200';

$tipe_badges = [
    'teori' => 'bg-blue-100 text-blue-800 border border-blue-200',
    'praktik' => 'bg-green-100 text-green-800 border border-green-200'
];
$tipe_class = $tipe_badges[$data['tipe_sesi']] ?? 'bg-gray-100 text-gray-800 border border-gray-200';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Detail Jadwal Kursus</h1>
                    <div class="flex items-center space-x-4 mt-1">
                        <span class="text-sm text-gray-600"><?= htmlspecialchars($data['nomor_pendaftaran']) ?></span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $status_class ?>">
                            <?= ucfirst($data['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informasi Jadwal -->
        <div class="space-y-6">
            <!-- Informasi Utama -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                        Informasi Jadwal
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal</label>
                                <p class="text-gray-900 font-medium"><?= formatDate($data['tanggal_jadwal']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Waktu</label>
                                <p class="text-gray-900 font-medium">
                                    <?= formatTime($data['jam_mulai']) ?> - <?= formatTime($data['jam_selesai']) ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Sesi</label>
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full <?= $tipe_class ?> capitalize">
                                    <?= htmlspecialchars($data['tipe_sesi']) ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Durasi</label>
                                <p class="text-gray-900">
                                    <?php
                                    if (!empty($data['jam_mulai']) && !empty($data['jam_selesai'])) {
                                        $start = new DateTime($data['jam_mulai']);
                                        $end = new DateTime($data['jam_selesai']);
                                        $duration = $start->diff($end);
                                        echo $duration->h . ' jam ' . $duration->i . ' menit';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($data['lokasi'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Lokasi</label>
                            <p class="text-gray-900"><?= htmlspecialchars($data['lokasi']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($data['mobil_digunakan'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Mobil Digunakan</label>
                            <p class="text-gray-900"><?= htmlspecialchars($data['mobil_digunakan']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Informasi Siswa -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user-graduate text-green-500 mr-3"></i>
                        Informasi Siswa
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap</label>
                            <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['nama_siswa']) ?></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">No. Pendaftaran</label>
                                <p class="text-gray-900"><?= htmlspecialchars($data['nomor_pendaftaran']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Telepon</label>
                                <p class="text-gray-900"><?= htmlspecialchars($data['telepon']) ?></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                            <p class="text-gray-900"><?= htmlspecialchars($data['email']) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Paket Kursus</label>
                            <p class="text-gray-900">
                                <?= htmlspecialchars($data['nama_paket']) ?> 
                                (<?= htmlspecialchars($data['durasi_jam']) ?> jam)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Instruktur & Status -->
        <div class="space-y-6">
            <!-- Informasi Instruktur -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-chalkboard-teacher text-purple-500 mr-3"></i>
                        Informasi Instruktur
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Instruktur</label>
                            <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['nama_instruktur']) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Spesialisasi</label>
                            <p class="text-gray-900"><?= htmlspecialchars($data['spesialisasi']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Kehadiran -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-orange-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-clipboard-check text-orange-500 mr-3"></i>
                        Status & Kehadiran
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Status Jadwal</label>
                            <span class="px-3 py-2 inline-flex text-sm font-semibold rounded-full <?= $status_class ?>">
                                <?= ucfirst($data['status']) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($data['kehadiran_siswa'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Kehadiran Siswa</label>
                            <?php
                            $kehadiran_badges = [
                                'hadir' => 'bg-green-100 text-green-800 border border-green-200',
                                'tidak_hadir' => 'bg-red-100 text-red-800 border border-red-200',
                                'izin' => 'bg-yellow-100 text-yellow-800 border border-yellow-200'
                            ];
                            $kehadiran_class = $kehadiran_badges[$data['kehadiran_siswa']] ?? 'bg-gray-100 text-gray-800 border border-gray-200';
                            ?>
                            <span class="px-3 py-2 inline-flex text-sm font-semibold rounded-full <?= $kehadiran_class ?> capitalize">
                                <?= str_replace('_', ' ', $data['kehadiran_siswa']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($data['catatan_instruktur'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Catatan Instruktur</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($data['catatan_instruktur'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 gap-4 text-sm text-gray-600">
                            <div>
                                <label class="font-medium">Dibuat Pada:</label>
                                <p>
                                    <?= !empty($data['dibuat_pada']) ? 
                                        date('d M Y H:i', strtotime($data['dibuat_pada'])) : 
                                        '-' 
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-to-br {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }
</style>