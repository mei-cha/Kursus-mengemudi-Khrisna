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
    echo '<div class="text-red-600">Data tidak ditemukan!</div>';
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

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Data Pribadi -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Data Pribadi</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Nama Lengkap</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Email</label>
                <p class="text-gray-900"><?= $data['email'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Telepon</label>
                <p class="text-gray-900"><?= $data['telepon'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Lahir</label>
                <p class="text-gray-900"><?= date('d M Y', strtotime($data['tanggal_lahir'])) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Jenis Kelamin</label>
                <p class="text-gray-900"><?= $data['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Alamat</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['alamat']) ?></p>
            </div>
        </div>
    </div>

    <!-- Data Kursus -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Data Kursus</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">No. Pendaftaran</label>
                <p class="text-gray-900 font-mono"><?= $data['nomor_pendaftaran'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Paket Kursus</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_paket']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Harga</label>
                <p class="text-gray-900">Rp <?= number_format($data['harga'], 0, ',', '.') ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tipe Mobil</label>
                <p class="text-gray-900"><?= ucfirst($data['tipe_mobil']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Jadwal Preferensi</label>
                <p class="text-gray-900"><?= ucfirst($data['jadwal_preferensi']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Pengalaman Mengemudi</label>
                <p class="text-gray-900"><?= ucfirst(str_replace('_', ' ', $data['pengalaman_mengemudi'])) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                    <?= ucfirst($data['status_pendaftaran']) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Data Tambahan -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Kontak Darurat</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Nama Kontak Darurat</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_kontak_darurat']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Telepon Darurat</label>
                <p class="text-gray-900"><?= $data['kontak_darurat'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Informasi Tambahan</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Kondisi Medis</label>
                <p class="text-gray-900"><?= $data['kondisi_medis'] ? htmlspecialchars($data['kondisi_medis']) : '-' ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Daftar</label>
                <p class="text-gray-900"><?= date('d M Y H:i', strtotime($data['dibuat_pada'])) ?></p>
            </div>
            <?php if ($data['catatan_admin']): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Catatan Admin</label>
                <p class="text-gray-900 bg-yellow-50 p-2 rounded"><?= htmlspecialchars($data['catatan_admin']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>