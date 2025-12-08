<?php
require_once 'config/database.php';

// Ambil hanya kolom yang benar-benar ada di tabel `kontak_kami`
$db = (new Database())->getConnection();
$stmt = $db->query("
    SELECT 
        alamat,
        telepon_1,
        email_1,
        jam_operasional_weekday,
        jam_operasional_weekend,
        facebook,
        instagram,
        youtube,
        tiktok
    FROM kontak_kami 
    LIMIT 1
");

$data = $stmt->fetch(PDO::FETCH_ASSOC);
// Asumsi: data selalu ada (tidak perlu fallback)
?>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-12 mt-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center mb-4">
                    <img src="./assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                    <span class="text-xl font-bold">Krishna Kursus</span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-4">
                    Kursus mengemudi profesional dengan instruktur berpengalaman.
                </p>
                <div class="flex space-x-3">
                    <a href="<?= htmlspecialchars($data['facebook']) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-facebook text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($data['instagram']) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($data['youtube']) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-youtube text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($data['tiktok']) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-tiktok text-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Menu</h4>
                <div class="space-y-2 text-sm">
                    <a href="index.php" class="block text-gray-400 hover:text-white transition duration-300">Beranda</a>
                    <a href="paket-kursus.php" class="block text-gray-400 hover:text-white transition duration-300">Paket Kursus</a>
                    <a href="index.php#instruktur" class="block text-gray-400 hover:text-white transition duration-300">Instruktur</a>
                    <a href="index.php#testimoni" class="block text-gray-400 hover:text-white transition duration-300">Testimoni</a>
                    <a href="galeri.php" class="block text-gray-400 hover:text-white transition duration-300">Galeri</a>
                    <a href="cek-status.php" class="block text-gray-400 hover:text-white transition duration-300">Cek Status</a>
                </div>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Kontak Kami</h4>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($data['alamat']) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-phone mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($data['telepon_1']) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-envelope mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($data['email_1']) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fab fa-whatsapp mr-3 text-green-400"></i>
                        <?= htmlspecialchars($data['telepon_1']) ?>
                    </p>
                </div>
            </div>

            <!-- Business Hours -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Jam Operasional</h4>
                <div class="text-sm text-gray-400 space-y-2">
                    <p><?= htmlspecialchars($data['jam_operasional_weekday']) ?></p>
                    <p><?= htmlspecialchars($data['jam_operasional_weekend']) ?></p>
                </div>

                <!-- WhatsApp CTA -->
                <div class="mt-4">
                    <?php
                    $whatsapp_clean = preg_replace('/[^0-9]/', '', $data['telepon_1']);
                    ?>
                    <a href="https://wa.me/<?= $whatsapp_clean ?>?text=Halo%20Krishna%20Kursus,%20saya%20ingin%20bertanya%20tentang%20kursus%20mengemudi"
                        target="_blank"
                        class="inline-flex items-center bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-sm">
                        <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>&copy; <?= date('Y') ?> Krishna Driving Course. All rights reserved.</p>
        </div>
    </div>
</footer>