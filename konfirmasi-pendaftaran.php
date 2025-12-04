<?php
// konfirmasi-pendaftaran.php
session_start();

// Redirect jika tidak ada data pendaftaran
if (!isset($_SESSION['pendaftaran_success'])) {
    header('Location: index.php');
    exit;
}

$data = $_SESSION['pendaftaran_data'] ?? null;
if (!$data) {
    header('Location: index.php');
    exit;
}

// Ambil data lengkap dari database untuk kontak darurat
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT kontak_darurat, nama_kontak_darurat FROM pendaftaran_siswa WHERE id = ?");
$stmt->execute([$data['id']]);
$detail = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Konfirmasi Pendaftaran Section -->
<section class="min-h-screen bg-gradient-to-br from-blue-50 to-gray-100 py-16">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 text-center">
            <!-- Icon Success -->
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8">
                <i class="fas fa-check-circle text-5xl text-green-600"></i>
            </div>
            
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">
                Pendaftaran Berhasil!
            </h1>
            
            <div class="bg-blue-50 rounded-xl p-6 mb-8 text-left">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Detail Pendaftaran</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Nomor Pendaftaran:</strong></p>
                        <p class="text-gray-800 font-bold text-lg"><?= htmlspecialchars($data['nomor_pendaftaran']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Tanggal Pendaftaran:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= date('d/m/Y H:i', strtotime($data['tanggal_daftar'])) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Nama Lengkap:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($data['nama_lengkap']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Email:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($data['email']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Telepon:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($data['telepon']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Paket Kursus:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($data['paket']) ?></p>
                    </div>
                    <?php if (!empty($detail['kontak_darurat'])): ?>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Kontak Darurat:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($detail['kontak_darurat']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Nama Kontak Darurat:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($detail['nama_kontak_darurat']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="md:col-span-2">
                        <p class="text-gray-600 text-sm"><strong>Total Biaya:</strong></p>
                        <p class="text-2xl font-bold text-blue-600">Rp <?= number_format($data['harga'], 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Penting -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 text-left">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-400 text-xl mt-1"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-yellow-800">Informasi Penting:</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Harap membawa <strong>KTP asli</strong> saat datang ke kantor untuk konfirmasi</li>
                                <li>Nomor pendaftaran di atas akan digunakan untuk semua komunikasi</li>
                                <li>Tim kami akan menghubungi Anda dalam 1x24 jam untuk konfirmasi jadwal</li>
                                <li>Simpan nomor pendaftaran ini untuk keperluan verifikasi</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="index.php" 
                       class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 inline-flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                    <a href="cetak-pendaftaran.php?id=<?= $data['id'] ?>" 
                       target="_blank"
                       class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300 inline-flex items-center justify-center">
                        <i class="fas fa-print mr-2"></i>Cetak Bukti Pendaftaran
                    </a>
                </div>
                
                <p class="text-gray-500 text-sm mt-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Jika Anda memiliki pertanyaan, hubungi kami di: 
                    <a href="tel:08123456789" class="text-blue-600 hover:underline font-semibold">0812-3456-789</a> 
                    atau email: <a href="mailto:info@krishnadriving.com" class="text-blue-600 hover:underline font-semibold">info@krishnadriving.com</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php 
// Hapus session data setelah ditampilkan
unset($_SESSION['pendaftaran_success']);
unset($_SESSION['pendaftaran_data']);

include 'includes/footer.php'; 
?>