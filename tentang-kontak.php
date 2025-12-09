<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data dari database
try {
    // Ambil data tentang kami
    $stmt_tentang = $db->query("SELECT * FROM tentang_kami WHERE id = 1");
    $tentang = $stmt_tentang->fetch(PDO::FETCH_ASSOC);

    // Ambil data kontak kami
    $stmt_kontak = $db->query("SELECT * FROM kontak_kami WHERE id = 1");
    $kontak = $stmt_kontak->fetch(PDO::FETCH_ASSOC);

    // Decode misi dari JSON
    if ($tentang && isset($tentang['misi'])) {
        $misi = json_decode($tentang['misi'], true);
        if (!is_array($misi)) {
            $misi = [];
        }
    } else {
        $misi = [];
        $tentang = [
            'judul_tentang' => 'Tentang Krishna Driving',
            'deskripsi_sejarah' => 'Krishna Driving Course telah berdiri sejak tahun 2010 dan telah meluluskan lebih dari 5,000 siswa yang berhasil mendapatkan SIM dan mengemudi dengan percaya diri di jalan raya. Dengan pengalaman lebih dari 15 tahun di bidang pendidikan mengemudi, kami berkomitmen untuk memberikan pelatihan yang aman, profesional, dan menyenangkan dengan instruktur yang berpengalaman dan kendaraan yang terawat.',
            'visi' => 'Menjadi lembaga kursus mengemudi terdepan yang menghasilkan pengemudi yang bertanggung jawab, terampil, dan berkarakter.',
            'tahun_berdiri' => 2010
        ];
    }

    if (!$kontak) {
        $kontak = [
            'alamat' => 'Jl. Raya Contoh No. 123<br>Jakarta Selatan, 12560<br>Indonesia',
            'telepon_1' => '+6281234567890',
            'telepon_2' => '+6282109876543',
            'email_1' => 'info@krishnadriving.com',
            'email_2' => 'admin@krishnadriving.com',
            'jam_operasional_weekday' => 'Senin–Sabtu: 08:00–20:00',
            'jam_operasional_weekend' => 'Minggu: 08:00–15:00',
            'whatsapp' => '+6281234567890',
            'embed_map' => 'https://www.google.com/maps/embed?pb=    !1m18!1m12!1m3!1d3951.2345678901234!2d111.9012031!3d-8.0866823!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e78e3c4fe9397f1%3A0xfc0e685cc4d51cc9!2sKursus%20Mengemudi%20KRISHNA!5e0!3m2!1sen!2sid!4v1712345678901!5m2!1sen!2sid',
            'link_map' => 'https://maps.app.goo.gl/AQwKp9buZ7LhVFrk8?g_st=aw    '
        ];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tentang = [];
    $kontak = [];
    $misi = [];
}
?>

<!-- Tentang & Kontak Section (GABUNGAN) -->
<section id="tentang-kontak" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Tentang Kami -->
        <div class="mb-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    <?= htmlspecialchars($tentang['judul_tentang']) ?>
                </h2>
                <p class="text-xl text-gray-600">Mengenal lebih dekat dengan lembaga kursus mengemudi terpercaya</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <!-- Kartu Visi & Misi -->
                <div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-8 text-white shadow-lg h-full">
                        <h3 class="text-2xl font-bold mb-6">Visi & Misi Kami</h3>

                        <div class="mb-6">
                            <h4 class="text-xl font-bold mb-3 text-yellow-300">Visi</h4>
                            <p class="text-blue-100 leading-relaxed">
                                <?= htmlspecialchars($tentang['visi']) ?>
                            </p>
                        </div>

                        <?php if (!empty($misi)): ?>
                            <div>
                                <h4 class="text-xl font-bold mb-3 text-yellow-300">Misi</h4>
                                <ul class="text-blue-100 space-y-2 list-disc list-inside">
                                    <?php foreach ($misi as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kartu Sejarah -->
                <div>
                    <div class=" p-8 rounded-2xl shadow-lg h-full">
                        <h3 class="text-2xl font-bold mb-4">Sejarah Kami</h3>
                        <p class=" leading-relaxed">
                            <?= nl2br(htmlspecialchars($tentang['deskripsi_sejarah'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kontak Kami -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Hubungi Kami</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Kami siap membantu Anda memulai perjalanan mengemudi dengan aman dan percaya diri.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Informasi Kontak & Media Sosial  -->
            <div class="space-y-6">
                <div class="backdrop-blur-sm bg-white/80 rounded-2xl p-6 shadow-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Header -->
                        <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-5">Informasi Kontak</h3>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-5">Media Sosial</h3>
                    </div>

                    <!-- Baris 1: Alamat ↔ Facebook -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 w-10 h-10 flex items-center justify-center bg-blue-100 rounded-lg text-blue-600 flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-base"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800 text-sm md:text-base">Alamat</h4>
                                <p class="text-gray-600 text-sm"><?= $kontak['alamat'] ?></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 flex items-center justify-center bg-blue-600 rounded-lg text-white">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-800 text-sm md:text-base">Facebook</h5>
                                <?php if (!empty($kontak['facebook'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="https://facebook.com/    <?= htmlspecialchars(ltrim($kontak['facebook'], '@')) ?>"
                                            target="_blank" class="hover:underline">
                                            Kursus Mengemudi Krishna
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Baris 2: Telepon ↔ Instagram -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 w-9 h-9 flex items-center justify-center bg-blue-100 rounded-lg text-blue-600">
                                <i class="fas fa-phone text-base"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm md:text-base">Telepon & WhatsApp</h4>
                                <?php if (!empty($kontak['telepon_1'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="tel:<?= htmlspecialchars($kontak['telepon_1']) ?>" class="hover:underline">
                                            <?= htmlspecialchars($kontak['telepon_1']) ?>
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 flex items-center justify-center bg-pink-600 rounded-lg text-white">
                                <i class="fab fa-instagram text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-800 text-sm md:text-base">Instagram</h5>
                                <?php if (!empty($kontak['instagram'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="https://instagram.com/    <?= htmlspecialchars(ltrim($kontak['instagram'], '@')) ?>"
                                            target="_blank" class="hover:underline">
                                            @<?= htmlspecialchars(ltrim($kontak['instagram'], '@')) ?>
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Baris 3: Email ↔ YouTube -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 w-9 h-9 flex items-center justify-center bg-blue-100 rounded-lg text-blue-600">
                                <i class="fas fa-envelope text-base"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm md:text-base">Email</h4>
                                <?php if (!empty($kontak['email_1'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="mailto:<?= htmlspecialchars($kontak['email_1']) ?>"
                                            class="hover:underline">
                                            <?= htmlspecialchars($kontak['email_1']) ?>
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 flex items-center justify-center bg-red-600 rounded-lg text-white">
                                <i class="fab fa-youtube text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-800 text-sm md:text-base">YouTube</h5>
                                <?php if (!empty($kontak['youtube'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="https://youtube.com/    <?= htmlspecialchars($kontak['youtube']) ?>"
                                            target="_blank" class="hover:underline">
                                            Krishna Driving Course
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Baris 4: Jam Operasional ↔ TikTok -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 w-9 h-9 flex items-center justify-center bg-blue-100 rounded-lg text-blue-600">
                                <i class="fas fa-clock text-base"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm md:text-base">Jam Operasional</h4>
                                <p class="text-gray-600 text-sm">
                                    <?= htmlspecialchars($kontak['jam_operasional_weekday']) ?><br>
                                    <?= htmlspecialchars($kontak['jam_operasional_weekend']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 flex items-center justify-center bg-gray-900 rounded-lg text-white">
                                <i class="fab fa-tiktok text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-800 text-sm md:text-base">TikTok</h5>
                                <?php if (!empty($kontak['tiktok'])): ?>
                                    <p class="text-gray-600 text-sm">
                                        <a href="https://tiktok.com/@    <?= htmlspecialchars(ltrim($kontak['tiktok'], '@')) ?>"
                                            target="_blank" class="hover:underline">
                                            @<?= htmlspecialchars(ltrim($kontak['tiktok'], '@')) ?>
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm italic">Belum tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Google Maps -->
            <div class="space-y-4">
                <div
                    class="backdrop-blur-sm bg-white/80 rounded-2xl p-6 shadow-lg border border-gray-200 h-full flex flex-col">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Lokasi Kami</h3>
                    <div class="flex-1 rounded-xl overflow-hidden h-80 shadow-sm">
                        <?php if (!empty($kontak['embed_map'])): ?>
                            <iframe src="<?= htmlspecialchars($kontak['embed_map']) ?>" width="100%" height="100%"
                                style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                <p class="text-gray-500">Peta tidak tersedia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>