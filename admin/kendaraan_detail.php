<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied');
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM kendaraan WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<div class="text-red-600 p-4">Data kendaraan tidak ditemukan!</div>';
    exit;
}

// Helper functions
function formatDate($dateString, $format = 'd F Y') {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($dateString));
}

function getDaysRemaining($dateString) {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return null;
    }
    
    $today = new DateTime();
    $target = new DateTime($dateString);
    $diff = $today->diff($target);
    
    if ($today > $target) {
        return -$diff->days; // Already expired
    }
    
    return $diff->days;
}

// Calculate days remaining
$pajak_days = getDaysRemaining($data['tanggal_pajak']);
$stnk_days = getDaysRemaining($data['tanggal_stnk']);

// Status colors
$status_colors = [
    'tersedia' => 'bg-green-100 text-green-800 border-green-200',
    'dipakai' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'servis' => 'bg-red-100 text-red-800 border-red-200'
];

$condition_colors = [
    'baik' => 'bg-green-100 text-green-800 border-green-200',
    'perbaikan' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'rusak' => 'bg-red-100 text-red-800 border-red-200'
];

$transmission_colors = [
    'manual' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'matic' => 'bg-purple-100 text-purple-800 border-purple-200'
];

$status_class = $status_colors[$data['status_ketersediaan']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
$condition_class = $condition_colors[$data['kondisi']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
$transmission_class = $transmission_colors[$data['tipe_transmisi']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Detail Kendaraan</h1>
                    <div class="flex items-center space-x-4 mt-1">
                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($data['nomor_plat']) ?></span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full border <?= $status_class ?>">
                            <?= ucfirst($data['status_ketersediaan']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informasi Kendaraan -->
        <div class="space-y-6">
            <!-- Informasi Dasar -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                        Informasi Dasar
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Merk</label>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['merk']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Model</label>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($data['model']) ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tahun</label>
                                <p class="text-gray-900"><?= htmlspecialchars($data['tahun']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Warna</label>
                                <p class="text-gray-900"><?= htmlspecialchars($data['warna']) ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tipe Transmisi</label>
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full border <?= $transmission_class ?>">
                                    <?= ucfirst($data['tipe_transmisi']) ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Kondisi</label>
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full border <?= $condition_class ?>">
                                    <?= ucfirst($data['kondisi']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Teknis -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-cogs text-green-500 mr-3"></i>
                        Informasi Teknis
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Kapasitas Bahan Bakar</label>
                                <p class="text-gray-900 font-medium"><?= $data['kapasitas_bahan_bakar'] ?> Liter</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Kilometer Terakhir</label>
                                <p class="text-gray-900 font-medium"><?= number_format($data['kilometer_terakhir'], 0, ',', '.') ?> km</p>
                            </div>
                        </div>
                        
                        <!-- Kilometer Progress Bar -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Tingkat Penggunaan</label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <?php
                                        $km_percentage = min(($data['kilometer_terakhir'] / 100000) * 100, 100);
                                        $km_color = $km_percentage > 80 ? 'bg-red-500' : ($km_percentage > 60 ? 'bg-yellow-500' : 'bg-green-500');
                                        ?>
                                        <div class="h-full <?= $km_color ?>" style="width: <?= $km_percentage ?>%"></div>
                                    </div>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?= round($km_percentage) ?>%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= $km_percentage < 30 ? 'Baru' : ($km_percentage < 60 ? 'Sedang' : ($km_percentage < 80 ? 'Tinggi' : 'Sangat Tinggi')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen & Status -->
        <div class="space-y-6">
            <!-- Status & Dokumen -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-file-alt text-purple-500 mr-3"></i>
                        Status & Dokumen
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Status Ketersediaan</label>
                            <span class="px-3 py-2 inline-flex text-sm font-semibold rounded-full border <?= $status_class ?>">
                                <?= ucfirst($data['status_ketersediaan']) ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Pajak Berlaku</label>
                                <div>
                                    <p class="text-gray-900 font-medium"><?= formatDate($data['tanggal_pajak']) ?></p>
                                    <?php if ($pajak_days !== null): ?>
                                        <?php if ($pajak_days < 0): ?>
                                            <span class="text-xs text-red-600 font-medium">(Kadaluarsa <?= abs($pajak_days) ?> hari yang lalu)</span>
                                        <?php elseif ($pajak_days < 30): ?>
                                            <span class="text-xs text-yellow-600 font-medium">(<?= $pajak_days ?> hari lagi)</span>
                                        <?php else: ?>
                                            <span class="text-xs text-green-600 font-medium">(Masih <?= $pajak_days ?> hari)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">STNK Berlaku</label>
                                <div>
                                    <p class="text-gray-900 font-medium"><?= formatDate($data['tanggal_stnk']) ?></p>
                                    <?php if ($stnk_days !== null): ?>
                                        <?php if ($stnk_days < 0): ?>
                                            <span class="text-xs text-red-600 font-medium">(Kadaluarsa <?= abs($stnk_days) ?> hari yang lalu)</span>
                                        <?php elseif ($stnk_days < 30): ?>
                                            <span class="text-xs text-yellow-600 font-medium">(<?= $stnk_days ?> hari lagi)</span>
                                        <?php else: ?>
                                            <span class="text-xs text-green-600 font-medium">(Masih <?= $stnk_days ?> hari)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan & Metadata -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-orange-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-clipboard text-orange-500 mr-3"></i>
                        Catatan & Metadata
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php if (!empty($data['catatan'])): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Catatan</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <p class="text-gray-700 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($data['catatan']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div>
                                <label class="font-medium text-gray-700">Dibuat Pada:</label>
                                <p><?= !empty($data['dibuat_pada']) ? date('d M Y H:i', strtotime($data['dibuat_pada'])) : '-' ?></p>
                            </div>
                            <div>
                                <label class="font-medium text-gray-700">Diupdate Pada:</label>
                                <p><?= !empty($data['diupdate_pada']) ? date('d M Y H:i', strtotime($data['diupdate_pada'])) : '-' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>