<?php
require_once 'config/database.php';

class FooterSettings {
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    public function getSetting($bagian, $kunci) {
        $query = "SELECT nilai FROM pengaturan_footer WHERE bagian = :bagian AND kunci = :kunci";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bagian', $bagian);
        $stmt->bindParam(':kunci', $kunci);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nilai'] : $this->getDefault($bagian, $kunci);
    }
    
    private function getDefault($bagian, $kunci) {
        $defaults = [
            'perusahaan' => [
                'nama' => 'Krishna Kursus',
                'deskripsi' => 'Kursus mengemudi mobil profesional dengan instruktur berpengalaman dan metode terbaik. Garansi sampai bisa mengemudi dengan percaya diri.'
            ],
            'kontak' => [
                'alamat' => 'Jl. Raya Contoh No. 123, Jakarta',
                'telepon' => '+62 812-3456-7890',
                'email' => 'info@krishnadriving.com',
                'whatsapp' => '+62 812-3456-7890'
            ],
            'jam' => [
                'hari_kerja' => '08:00 - 20:00',
                'sabtu' => '08:00 - 18:00',
                'minggu' => '08:00 - 15:00'
            ],
            'media' => [
                'facebook' => '#',
                'instagram' => '#',
                'youtube' => '#',
                'tiktok' => '#'
            ]
        ];
        
        return $defaults[$bagian][$kunci] ?? '';
    }
}

$footer = new FooterSettings();
?>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-12 mt-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center mb-4">
                    <img src="./assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                    <span class="text-xl font-bold"><?= htmlspecialchars($footer->getSetting('perusahaan', 'nama')) ?></span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-4">
                    <?= htmlspecialchars($footer->getSetting('perusahaan', 'deskripsi')) ?>
                </p>
                <div class="flex space-x-3">
                    <a href="<?= htmlspecialchars($footer->getSetting('media', 'facebook')) ?>" 
                       target="_blank"
                       class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-facebook text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer->getSetting('media', 'instagram')) ?>" 
                       target="_blank"
                       class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer->getSetting('media', 'youtube')) ?>" 
                       target="_blank"
                       class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-youtube text-lg"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer->getSetting('media', 'tiktok')) ?>" 
                       target="_blank"
                       class="text-gray-400 hover:text-white transition duration-300">
                        <i class="fab fa-tiktok text-lg"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Menu Cepat</h4>
                <div class="space-y-2 text-sm">
                    <a href="index.php" class="block text-gray-400 hover:text-white transition duration-300">Beranda</a>
                    <a href="paket-kursus.php" class="block text-gray-400 hover:text-white transition duration-300">Paket Kursus</a>
                    <a href="index.php#instruktur" class="block text-gray-400 hover:text-white transition duration-300">Instruktur</a>
                    <a href="index.php#testimoni" class="block text-gray-400 hover:text-white transition duration-300">Testimoni</a>
                    <a href="cek-status.php" class="block text-gray-400 hover:text-white transition duration-300">Cek Status</a>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Kontak Kami</h4>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($footer->getSetting('kontak', 'alamat')) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-phone mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($footer->getSetting('kontak', 'telepon')) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-envelope mr-3 text-blue-400"></i>
                        <?= htmlspecialchars($footer->getSetting('kontak', 'email')) ?>
                    </p>
                    <p class="flex items-center">
                        <i class="fab fa-whatsapp mr-3 text-green-400"></i>
                        <?= htmlspecialchars($footer->getSetting('kontak', 'whatsapp')) ?>
                    </p>
                </div>
            </div>
            
            <!-- Business Hours -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Jam Operasional</h4>
                <div class="text-sm text-gray-400 space-y-2">
                    <div class="flex justify-between">
                        <span>Senin - Jumat:</span>
                        <span><?= htmlspecialchars($footer->getSetting('jam', 'hari_kerja')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sabtu:</span>
                        <span><?= htmlspecialchars($footer->getSetting('jam', 'sabtu')) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Minggu:</span>
                        <span><?= htmlspecialchars($footer->getSetting('jam', 'minggu')) ?></span>
                    </div>
                </div>
                
                <!-- WhatsApp CTA -->
                <div class="mt-4">
                    <?php 
                    $whatsapp = $footer->getSetting('kontak', 'whatsapp');
                    $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp);
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
            <p>&copy; <?= date('Y') ?> Krishna Driving Course. All rights reserved. | 
               <a href="#" class="hover:text-white transition duration-300">Privacy Policy</a> | 
               <a href="#" class="hover:text-white transition duration-300">Terms of Service</a>
            </p>
        </div>
    </div>
</footer>