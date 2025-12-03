<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied');
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("
    SELECT ps.*, pk.nama_paket, pk.harga, pk.durasi_jam, pk.tipe_mobil 
    FROM pendaftaran_siswa ps 
    LEFT JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE ps.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<div class="text-red-600 p-4">Data tidak ditemukan!</div>';
    exit;
}

$status_badges = [
    'baru' => 'bg-yellow-100 text-yellow-800',
    'dikonfirmasi' => 'bg-blue-100 text-blue-800',
    'diproses' => 'bg-purple-100 text-purple-800',
    'selesai' => 'bg-green-100 text-green-800',
    'dibatalkan' => 'bg-red-100 text-red-800'
];
$status_class = $status_badges[$data['status_pendaftaran']] ?? 'bg-gray-100 text-gray-800';
?>

<div class="max-w-6xl mx-auto p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-white text-xl font-bold">
                        <?= strtoupper(substr($data['nama_lengkap'], 0, 1)) ?>
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></h1>
                    <div class="flex items-center space-x-4 mt-1">
                        <span class="text-sm text-gray-600"><?= $data['nomor_pendaftaran'] ?></span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $status_class ?>">
                            <?= ucfirst($data['status_pendaftaran']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.history.back()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </button>
                <a href="pendaftaran_edit.php?id=<?= $id ?>" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri - Data Pribadi -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Data Pribadi -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user-circle text-blue-500 mr-3"></i>
                        Data Pribadi
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap</label>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['nama_lengkap']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                                <p class="text-gray-900"><?= $data['email'] ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Lahir</label>
                                <p class="text-gray-900"><?= date('d M Y', strtotime($data['tanggal_lahir'])) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Alamat</label>
                                <p class="text-gray-900"><?= htmlspecialchars($data['alamat']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Telepon</label>
                                <p class="text-gray-900"><?= $data['telepon'] ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Kelamin</label>
                                <p class="text-gray-900">
                                    <?= $data['jenis_kelamin'] === 'L' ? 
                                        '<span class="inline-flex items-center"><i class="fas fa-mars text-blue-500 mr-2"></i>Laki-laki</span>' : 
                                        '<span class="inline-flex items-center"><i class="fas fa-venus text-pink-500 mr-2"></i>Perempuan</span>' ?>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Daftar</label>
                                <p class="text-gray-900"><?= date('d M Y H:i', strtotime($data['dibuat_pada'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Kursus -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-book-open text-green-500 mr-3"></i>
                        Data Kursus
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Paket Kursus</label>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['nama_paket']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Harga</label>
                                <p class="text-xl font-bold text-green-600">Rp <?= number_format($data['harga'], 0, ',', '.') ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Durasi</label>
                                <p class="text-gray-900"><?= $data['durasi_jam'] ?> Jam</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Mobil</label>
                                <p class="text-gray-900">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-car mr-2"></i>
                                        <?= ucfirst($data['tipe_mobil']) ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Jadwal Preferensi</label>
                                <p class="text-gray-900"><?= ucfirst($data['jadwal_preferensi']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Pengalaman Mengemudi</label>
                                <p class="text-gray-900">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                        <?= $data['pengalaman_mengemudi'] === 'pemula' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                        <i class="fas fa-<?= $data['pengalaman_mengemudi'] === 'pemula' ? 'baby' : 'user' ?> mr-2"></i>
                                        <?= ucfirst(str_replace('_', ' ', $data['pengalaman_mengemudi'])) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan - Informasi Tambahan -->
        <div class="space-y-6">
            <!-- Kontak Darurat -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-phone-alt text-red-500 mr-3"></i>
                        Kontak Darurat
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Kontak Darurat</label>
                            <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['nama_kontak_darurat']) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Telepon Darurat</label>
                            <p class="text-gray-900">
                                <a href="tel:<?= $data['kontak_darurat'] ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-phone mr-2"></i><?= $data['kontak_darurat'] ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Medis -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-heartbeat text-purple-500 mr-3"></i>
                        Informasi Medis
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Kondisi Medis</label>
                            <p class="text-gray-900">
                                <?= $data['kondisi_medis'] ? 
                                    '<span class="bg-red-50 text-red-700 px-3 py-2 rounded-lg block">' . htmlspecialchars($data['kondisi_medis']) . '</span>' : 
                                    '<span class="text-green-600"><i class="fas fa-check-circle mr-2"></i>Tidak ada</span>' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan Admin -->
            <?php if ($data['catatan_admin']): ?>
            <div class="bg-white rounded-lg shadow-sm border border-yellow-200">
                <div class="px-6 py-4 border-b border-yellow-200 bg-yellow-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-sticky-note text-yellow-500 mr-3"></i>
                        Catatan Admin
                    </h3>
                </div>
                <div class="p-6 bg-yellow-50">
                    <p class="text-gray-800 leading-relaxed"><?= htmlspecialchars($data['catatan_admin']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-bolt text-orange-500 mr-3"></i>
                        Quick Actions
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <a href="https://wa.me/<?= $data['telepon'] ?>?text=Halo%20<?= urlencode($data['nama_lengkap']) ?>%2C%20kami%20dari%20Krishna%20Driving%20Course"
                           target="_blank"
                           class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                            <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                        </a>
                        <a href="mailto:<?= $data['email'] ?>"
                           class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </a>
                        <a href="jadwal.php?siswa=<?= $id ?>"
                           class="w-full flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200">
                            <i class="fas fa-calendar mr-2"></i>Jadwal
                        </a>
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