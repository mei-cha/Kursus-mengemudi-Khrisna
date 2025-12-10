<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied');
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("
    SELECT p.*, ps.nama_lengkap, ps.nomor_pendaftaran, ps.telepon, ps.email, 
           pk.nama_paket, pk.harga as harga_paket 
    FROM pembayaran p 
    JOIN pendaftaran_siswa ps ON p.pendaftaran_id = ps.id 
    JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<div class="text-red-600">Data pembayaran tidak ditemukan!</div>';
    exit;
}

$status_badges = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'terverifikasi' => 'bg-green-100 text-green-800',
    'ditolak' => 'bg-red-100 text-red-800'
];
$status_class = $status_badges[$data['status']] ?? 'bg-gray-100 text-gray-800';

// Handle status display
$status_display = ucfirst($data['status']);
if ($data['status'] === 'terverifikasi') {
    $status_display = 'Terverifikasi';
} elseif ($data['status'] === 'menunggu') {
    $status_display = 'Menunggu Verifikasi';
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Informasi Pembayaran -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Informasi Pembayaran</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">No. Kwitansi</label>
                <p class="text-gray-900 font-mono"><?= $data['nomor_kwitansi'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Pembayaran</label>
                <p class="text-gray-900"><?= date('d M Y', strtotime($data['tanggal_pembayaran'])) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Jumlah</label>
                <p class="text-gray-900 font-semibold">Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Metode Pembayaran</label>
                <p class="text-gray-900 capitalize"><?= $data['metode_pembayaran'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tipe Pembayaran</label>
                <p class="text-gray-900 capitalize">
                    <?= $data['tipe_pembayaran'] === 'dp' ? 'DP (Uang Muka)' : 
                       ($data['tipe_pembayaran'] === 'pelunasan' ? 'Pelunasan' : 'Lunas') ?>
                </p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                    <?= $status_display ?>
                </span>
            </div>
            <?php if ($data['diverifikasi_oleh']): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Diverifikasi Oleh</label>
                <p class="text-gray-900"><?= $data['diverifikasi_oleh'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Verifikasi</label>
                <p class="text-gray-900"><?= date('d M Y H:i', strtotime($data['tanggal_verifikasi'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informasi Siswa -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Informasi Siswa</h4>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Nama Lengkap</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_lengkap']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">No. Pendaftaran</label>
                <p class="text-gray-900"><?= $data['nomor_pendaftaran'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Telepon</label>
                <p class="text-gray-900"><?= $data['telepon'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Email</label>
                <p class="text-gray-900"><?= $data['email'] ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Paket Kursus</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_paket']) ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Harga Paket</label>
                <p class="text-gray-900">Rp <?= number_format($data['harga_paket'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Bukti Pembayaran & Catatan -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (!empty($data['bukti_bayar'])): ?>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Bukti Pembayaran</h4>
        <div class="border border-gray-200 rounded-lg p-4">
            <img src="../assets/images/bukti_bayar/<?= $data['bukti_bayar'] ?>" 
                 alt="Bukti Pembayaran" 
                 class="w-full max-w-xs mx-auto rounded shadow-sm cursor-pointer hover:opacity-90 transition-opacity"
                 onclick="window.open('../assets/images/bukti_bayar/<?= $data['bukti_bayar'] ?>', '_blank')">
            <p class="text-sm text-gray-500 text-center mt-2">Klik gambar untuk melihat ukuran penuh</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($data['catatan'])): ?>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Catatan</h4>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($data['catatan'])) ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Informasi Bank (jika transfer) -->
<?php if ($data['metode_pembayaran'] === 'transfer' && (!empty($data['nama_bank']) || !empty($data['nomor_rekening']))): ?>
<div class="mt-6">
    <h4 class="text-lg font-medium text-gray-900 mb-4">Informasi Transfer</h4>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (!empty($data['nama_bank'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Nama Bank</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nama_bank']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($data['nomor_rekening'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Nomor Rekening</label>
                <p class="text-gray-900"><?= htmlspecialchars($data['nomor_rekening']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>