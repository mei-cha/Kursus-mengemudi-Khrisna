<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();
$result = null;
$error = '';

// Handle status check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_pendaftaran = $_POST['nomor_pendaftaran'] ?? '';
    $telepon = $_POST['telepon'] ?? '';
    
    if (!empty($nomor_pendaftaran) && !empty($telepon)) {
        $stmt = $db->prepare("
            SELECT ps.*, pk.nama_paket, pk.harga,
                   (SELECT COUNT(*) FROM pembayaran p WHERE p.pendaftaran_id = ps.id AND p.status = 'terverifikasi') as jumlah_pembayaran,
                   (SELECT SUM(jumlah) FROM pembayaran p WHERE p.pendaftaran_id = ps.id AND p.status = 'terverifikasi') as total_dibayar
            FROM pendaftaran_siswa ps 
            JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
            WHERE ps.nomor_pendaftaran = ? AND ps.telepon = ?
        ");
        $stmt->execute([$nomor_pendaftaran, $telepon]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $error = "Data tidak ditemukan. Pastikan nomor pendaftaran dan telepon benar.";
        }
    } else {
        $error = "Harap isi nomor pendaftaran dan telepon.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Cek Status Pendaftaran</h1>
            <p class="text-xl mb-8">Lihat status pendaftaran dan progress pembayaran Anda</p>
        </div>
    </section>

    <!-- Status Check Form -->
    <section class="py-12">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Cek Status Anda</h2>
                
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="nomor_pendaftaran" class="block text-sm font-medium text-gray-700 mb-2">
                            Nomor Pendaftaran *
                        </label>
                        <input type="text" id="nomor_pendaftaran" name="nomor_pendaftaran" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Contoh: KRISHNA240101001"
                               value="<?= htmlspecialchars($_POST['nomor_pendaftaran'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label for="telepon" class="block text-sm font-medium text-gray-700 mb-2">
                            Nomor Telepon/HP *
                        </label>
                        <input type="tel" id="telepon" name="telepon" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Contoh: 081234567890"
                               value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-search mr-2"></i>Cek Status
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Lupa nomor pendaftaran? 
                        <a href="index.php#kontak" class="text-blue-600 hover:text-blue-800 font-medium">
                            Hubungi kami
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <?php if ($result): ?>
    <section class="pb-16">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Status Pendaftaran Anda</h2>
                
                <!-- Status Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 mb-2">
                            <?= $result['nomor_pendaftaran'] ?>
                        </div>
                        <div class="text-sm text-gray-600">Nomor Pendaftaran</div>
                    </div>
                    
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 mb-2">
                            <?= htmlspecialchars($result['nama_lengkap']) ?>
                        </div>
                        <div class="text-sm text-gray-600">Nama Siswa</div>
                    </div>
                    
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 mb-2 capitalize">
                            <?= $result['status_pendaftaran'] ?>
                        </div>
                        <div class="text-sm text-gray-600">Status</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Progress Pendaftaran</h3>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <?php
                        $progress = 0;
                        $status = $result['status_pendaftaran'];
                        
                        if ($status === 'baru') $progress = 25;
                        elseif ($status === 'dikonfirmasi') $progress = 50;
                        elseif ($status === 'diproses') $progress = 75;
                        elseif ($status === 'selesai') $progress = 100;
                        
                        $color_class = 'bg-blue-600';
                        if ($progress >= 75) $color_class = 'bg-green-600';
                        elseif ($progress >= 50) $color_class = 'bg-yellow-600';
                        ?>
                        <div class="h-3 rounded-full <?= $color_class ?> transition-all duration-500" 
                             style="width: <?= $progress ?>%"></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 mt-2">
                        <span>Baru</span>
                        <span>Dikonfirmasi</span>
                        <span>Diproses</span>
                        <span>Selesai</span>
                    </div>
                </div>

                <!-- Detailed Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Data Pribadi -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Pribadi</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Nama Lengkap</span>
                                <span class="font-medium"><?= htmlspecialchars($result['nama_lengkap']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Email</span>
                                <span class="font-medium"><?= $result['email'] ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Telepon</span>
                                <span class="font-medium"><?= $result['telepon'] ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Tanggal Daftar</span>
                                <span class="font-medium"><?= date('d M Y', strtotime($result['dibuat_pada'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kursus -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Kursus</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Paket Kursus</span>
                                <span class="font-medium"><?= htmlspecialchars($result['nama_paket']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Harga Paket</span>
                                <span class="font-medium">Rp <?= number_format($result['harga'], 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Tipe Mobil</span>
                                <span class="font-medium capitalize"><?= $result['tipe_mobil'] ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Total Dibayar</span>
                                <span class="font-medium text-green-600">
                                    Rp <?= number_format($result['total_dibayar'] ?? 0, 0, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($result['jumlah_pembayaran'] > 0): ?>
                <div class="mt-8 p-6 bg-green-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">
                        <i class="fas fa-check-circle mr-2"></i>Informasi Pembayaran
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-green-700">
                                Jumlah Pembayaran: <strong><?= $result['jumlah_pembayaran'] ?>x</strong>
                            </p>
                            <p class="text-green-700">
                                Total Terbayar: <strong>Rp <?= number_format($result['total_dibayar'] ?? 0, 0, ',', '.') ?></strong>
                            </p>
                        </div>
                        <div>
                            <p class="text-green-700">
                                Sisa Pembayaran: 
                                <strong>Rp <?= number_format($result['harga'] - ($result['total_dibayar'] ?? 0), 0, ',', '.') ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-8 p-6 bg-yellow-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Informasi Pembayaran
                    </h3>
                    <p class="text-yellow-700">
                        Belum ada pembayaran yang terverifikasi. Silakan lakukan pembayaran sesuai instruksi yang diberikan.
                    </p>
                </div>
                <?php endif; ?>

                <!-- Next Steps -->
                <div class="mt-8 p-6 bg-blue-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">Langkah Selanjutnya</h3>
                    <?php if ($result['status_pendaftaran'] === 'baru'): ?>
                        <p class="text-blue-700 mb-2">✅ Pendaftaran Anda telah diterima</p>
                        <p class="text-blue-700 mb-2">⏳ Menunggu konfirmasi dari admin</p>
                        <p class="text-blue-700">💳 Silakan lakukan pembayaran sesuai instruksi</p>
                    <?php elseif ($result['status_pendaftaran'] === 'dikonfirmasi'): ?>
                        <p class="text-blue-700 mb-2">✅ Pendaftaran telah dikonfirmasi</p>
                        <p class="text-blue-700 mb-2">✅ Pembayaran telah diverifikasi</p>
                        <p class="text-blue-700">⏳ Menunggu jadwal kursus dari admin</p>
                    <?php elseif ($result['status_pendaftaran'] === 'diproses'): ?>
                        <p class="text-blue-700 mb-2">✅ Pendaftaran telah dikonfirmasi</p>
                        <p class="text-blue-700 mb-2">✅ Pembayaran telah diverifikasi</p>
                        <p class="text-blue-700 mb-2">✅ Sedang dalam proses kursus</p>
                        <p class="text-blue-700">📞 Hubungi kami untuk info jadwal</p>
                    <?php elseif ($result['status_pendaftaran'] === 'selesai'): ?>
                        <p class="text-blue-700 mb-2">✅ Pendaftaran telah dikonfirmasi</p>
                        <p class="text-blue-700 mb-2">✅ Pembayaran telah diverifikasi</p>
                        <p class="text-blue-700 mb-2">✅ Proses kursus telah selesai</p>
                        <p class="text-blue-700">🎉 Selamat! Anda telah menyelesaikan kursus</p>
                    <?php endif; ?>
                </div>

                <!-- Contact Information -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Butuh bantuan? 
                        <a href="index.php#kontak" class="text-blue-600 hover:text-blue-800 font-medium">
                            Hubungi customer service kami
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2024 Krishna Driving Course. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Auto-format phone number
        document.getElementById('telepon').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 12); // Limit to 12 digits
            }
            e.target.value = value;
        });

        // Auto-uppercase for registration number
        document.getElementById('nomor_pendaftaran').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>