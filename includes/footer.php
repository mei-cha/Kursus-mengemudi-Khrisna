<?php
require_once 'config/database.php';

// Fungsi untuk membersihkan username media sosial
function cleanSocialUsername($username) {
    if (empty($username)) return '';
    // Hapus spasi di awal dan akhir
    $username = trim($username);
    // Hapus karakter @ di awal jika ada
    $username = ltrim($username, '@');
    // Hapus spasi di tengah (jika ada)
    $username = str_replace(' ', '', $username);
    // Hapus karakter yang tidak valid
    $username = preg_replace('/[^\w\.\-]/', '', $username);
    return $username;
}

// Fungsi untuk format nomor WhatsApp
function formatWhatsAppNumber($phone) {
    if (empty($phone)) return '';
    // Hapus semua karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Pastikan dimulai dengan 62 (kode Indonesia)
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) == '08') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) == '628') {
        // Sudah benar
    } elseif (substr($phone, 0, 3) == '+62') {
        $phone = substr($phone, 1);
    }
    return $phone;
}

// Data default jika query gagal
$data = [
    'alamat' => 'Jl. Raya Contoh No. 123, Jakarta Selatan',
    'telepon_1' => '+6281234567890',
    'email_1' => 'info@krishnadriving.com',
    'jam_operasional_weekday' => 'Senin - Jumat: 08:00 - 20:00',
    'jam_operasional_weekend' => 'Sabtu - Minggu: 08:00 - 17:00',
    'facebook' => 'krishnadrivingcourse',
    'instagram' => 'krishna_driving',
    'youtube' => 'krishnadrivingcourse',
    'tiktok' => 'krishnadriving'
];

try {
    // Coba koneksi ke database
    $db = (new Database())->getConnection();
    
    // Query yang lebih sederhana dan aman
    $stmt = $db->query("SELECT * FROM kontak_kami LIMIT 1");
    
    if ($stmt) {
        $db_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($db_data) {
            // Gabungkan data dari database dengan data default
            $data = array_merge($data, $db_data);
        }
    }
} catch (Exception $e) {
    // Tetap gunakan data default jika ada error
    error_log("Footer database error: " . $e->getMessage());
}

// Persiapkan data untuk WhatsApp
$whatsapp_number = formatWhatsAppNumber($data['telepon_1']);

// Persiapkan link media sosial
$facebook_link = !empty($data['facebook']) ? 
    'https://facebook.com/' . cleanSocialUsername($data['facebook']) : '#';

$instagram_link = !empty($data['instagram']) ? 
    'https://instagram.com/' . cleanSocialUsername($data['instagram']) : '#';

$youtube_link = !empty($data['youtube']) ? 
    'https://youtube.com/@' . cleanSocialUsername($data['youtube']) : '#';

$tiktok_link = !empty($data['tiktok']) ? 
    'https://tiktok.com/@' . cleanSocialUsername($data['tiktok']) : '#';
?>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-12 mt-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center mb-4">
                    <?php if (file_exists('./assets/images/logo1.png')): ?>
                    <img src="./assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                    <?php else: ?>
                    <div class="w-10 h-10 mr-3 rounded-full bg-blue-500 flex items-center justify-center">
                        <span class="text-white font-bold">K</span>
                    </div>
                    <?php endif; ?>
                    <span class="text-xl font-bold">Krishna Kursus</span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-4">
                    Kursus mengemudi profesional dengan instruktur berpengalaman.
                </p>
                <div class="flex space-x-3">
                    <?php if (!empty($data['facebook'])): ?>
                    <a href="<?= htmlspecialchars($facebook_link) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-facebook text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['instagram'])): ?>
                    <a href="<?= htmlspecialchars($instagram_link) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['youtube'])): ?>
                    <a href="<?= htmlspecialchars($youtube_link) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-youtube text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['tiktok'])): ?>
                    <a href="<?= htmlspecialchars($tiktok_link) ?>"
                        target="_blank"
                        class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-tiktok text-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Menu</h4>
                <div class="space-y-2 text-sm">
                    <a href="index.php" class="block text-gray-400 hover:text-white transition duration-300">Beranda</a>
                    <a href="paket-kursus.php" class="block text-gray-400 hover:text-white transition duration-300">Paket Kursus</a>
                    <a href="instruktur.php" class="block text-gray-400 hover:text-white transition duration-300">Instruktur</a>
                    <a href="testimoni.php" class="block text-gray-400 hover:text-white transition duration-300">Testimoni</a>
                    <a href="galeri.php" class="block text-gray-400 hover:text-white transition duration-300">Galeri</a>
                    <a href="cek-status.php" class="block text-gray-400 hover:text-white transition duration-300">Cek Status</a>
                </div>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Kontak Kami</h4>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-start">
                        <i class="fas fa-map-marker-alt mr-3 text-blue-400 mt-1"></i>
                        <span><?= htmlspecialchars($data['alamat']) ?></span>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-phone mr-3 text-blue-400"></i>
                        <span><?= htmlspecialchars($data['telepon_1']) ?></span>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-envelope mr-3 text-blue-400"></i>
                        <span><?= htmlspecialchars($data['email_1']) ?></span>
                    </p>
                    <p class="flex items-center">
                        <i class="fab fa-whatsapp mr-3 text-green-400"></i>
                        <span><?= htmlspecialchars($data['telepon_1']) ?></span>
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
                    <?php if (!empty($whatsapp_number)): ?>
                    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Halo%20Krishna%20Kursus,%20saya%20ingin%20bertanya%20tentang%20kursus%20mengemudi"
                        target="_blank"
                        class="inline-flex items-center bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-sm">
                        <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                    </a>
                    <?php else: ?>
                    <p class="text-gray-400 text-sm">WhatsApp tidak tersedia</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>&copy; <?= date('Y') ?> Krishna Driving Course. All rights reserved.</p>
        </div>
    </div>
</footer>