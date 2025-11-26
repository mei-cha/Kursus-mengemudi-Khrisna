<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data galeri yang aktif dari database
try {
    // Ambil semua galeri yang aktif, diurutkan berdasarkan urutan_tampil
    $galeri_query = $db->query("SELECT * FROM galeri WHERE status = 'aktif' ORDER BY urutan_tampil ASC, created_at DESC");
    $galeri = $galeri_query->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan galeri berdasarkan kategori
    $galeri_by_kategori = [];
    foreach ($galeri as $item) {
        $galeri_by_kategori[$item['kategori']][] = $item;
    }

    // Hitung statistik
    $total_gambar = count($galeri);
    $kategori_count = count($galeri_by_kategori);

} catch (PDOException $e) {
    $galeri = [];
    $galeri_by_kategori = [];
    $total_gambar = 0;
    $kategori_count = 0;
    error_log("Database error: " . $e->getMessage());
}

// Kategori info untuk tampilan
$kategori_info = [
    'aktivitas' => [
        'label' => 'Aktivitas Belajar',
        'icon' => 'users',
        'color' => 'blue',
        'description' => 'Momen seru selama proses belajar mengemudi'
    ],
    'fasilitas' => [
        'label' => 'Fasilitas',
        'icon' => 'building',
        'color' => 'green',
        'description' => 'Fasilitas lengkap untuk kenyamanan belajar'
    ],
    'sertifikat' => [
        'label' => 'Sertifikat',
        'icon' => 'certificate',
        'color' => 'yellow',
        'description' => 'Bukti kelulusan dan sertifikasi mengemudi'
    ],
    'kendaraan' => [
        'label' => 'Kendaraan',
        'icon' => 'car',
        'color' => 'purple',
        'description' => 'Mobil-mobil terawat untuk latihan mengemudi'
    ],
    'instruktur' => [
        'label' => 'Instruktur',
        'icon' => 'chalkboard-teacher',
        'color' => 'red',
        'description' => 'Tim instruktur profesional dan berpengalaman'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

    <!-- Header Section -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Galeri Krishna Driving</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Lihat momen seru, fasilitas, dan aktivitas belajar mengemudi di Krishna Driving
            </p>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600"><?= $total_gambar ?></div>
                    <div class="text-gray-600 text-sm">Total Foto</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600"><?= $kategori_count ?></div>
                    <div class="text-gray-600 text-sm">Kategori</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">100%</div>
                    <div class="text-gray-600 text-sm">Kualitas Terjamin</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Category Navigation -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Jelajahi Kategori Galeri</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($kategori_info as $kategori => $info): 
                    $count = isset($galeri_by_kategori[$kategori]) ? count($galeri_by_kategori[$kategori]) : 0;
                ?>
                <a href="#<?= $kategori ?>" 
                   class="category-filter bg-gray-50 rounded-xl p-4 text-center hover:shadow-md transition duration-300 border-2 border-transparent hover:border-<?= $info['color'] ?>-500"
                   data-kategori="<?= $kategori ?>">
                    <div class="w-12 h-12 bg-<?= $info['color'] ?>-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-<?= $info['icon'] ?> text-<?= $info['color'] ?>-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm mb-1"><?= $info['label'] ?></h3>
                    <p class="text-<?= $info['color'] ?>-600 text-xs font-semibold"><?= $count ?> Foto</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- All Gallery Content -->
        <?php if (empty($galeri)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Galeri Masih Kosong</h3>
                <p class="text-gray-500">Silakan kembali lagi nanti untuk melihat galeri kami.</p>
            </div>
        <?php else: ?>
            <!-- Show All Categories -->
            <?php foreach ($kategori_info as $kategori => $info): 
                if (isset($galeri_by_kategori[$kategori]) && count($galeri_by_kategori[$kategori]) > 0):
            ?>
            <section id="<?= $kategori ?>" class="mb-12 scroll-mt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-<?= $info['color'] ?>-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-<?= $info['icon'] ?> text-<?= $info['color'] ?>-600"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?= $info['label'] ?></h2>
                        <p class="text-gray-600"><?= $info['description'] ?></p>
                    </div>
                    <div class="ml-auto text-sm text-gray-500">
                        <?= count($galeri_by_kategori[$kategori]) ?> foto
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($galeri_by_kategori[$kategori] as $item): ?>
                    <div class="gallery-item bg-white rounded-2xl shadow-lg hover:shadow-xl transition duration-300 overflow-hidden group"
     data-kategori="<?= $item['kategori'] ?>">
    <div class="relative overflow-hidden">
        <?php if (file_exists("assets/images/galeri/" . $item['gambar'])): ?>
            <img src="assets/images/galeri/<?= $item['gambar'] ?>" 
                 alt="<?= htmlspecialchars($item['judul']) ?>" 
                 class="w-full h-64 object-cover group-hover:scale-105 transition duration-500">
        <?php else: ?>
            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                <i class="fas fa-image text-gray-400 text-3xl"></i>
                <span class="text-gray-500 text-sm ml-2">Gambar tidak ditemukan</span>
            </div>
        <?php endif; ?>
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <div class="text-center text-white">
                <button onclick="viewImage('<?= $item['gambar'] ?>', '<?= htmlspecialchars($item['judul']) ?>', '<?= htmlspecialchars($item['deskripsi']) ?>')" 
                        class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition duration-300">
                    <i class="fas fa-expand mr-2"></i>Lihat Detail
                </button>
            </div>
        </div>
    </div>
    
    <div class="p-4">
        <h3 class="font-bold text-gray-800 text-lg mb-2"><?= htmlspecialchars($item['judul']) ?></h3>
        <?php if (!empty($item['deskripsi'])): ?>
            <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($item['deskripsi']) ?></p>
        <?php endif; ?>
    </div>
</div>
                        
                        <div class="p-4">
                            <h3 class="font-bold text-gray-800 text-lg mb-2"><?= htmlspecialchars($item['judul']) ?></h3>
                            <?php if (!empty($item['deskripsi'])): ?>
                                <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($item['deskripsi']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 text-center text-white mt-12">
            <h3 class="text-2xl font-bold mb-4">Tertarik Bergabung?</h3>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                Lihat sendiri bagaimana pengalaman belajar mengemudi yang menyenangkan di Krishna Driving. 
                Daftar sekarang dan jadilah bagian dari keluarga besar kami!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="paket-kursus.php" 
                   class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    <i class="fas fa-gift mr-2"></i>Lihat Paket Kursus
                </a>
                <a href="index.php#daftar" 
                   class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300">
                    <i class="fas fa-edit mr-2"></i>Daftar Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-4 mx-auto p-4 w-full max-w-6xl">
            <!-- Close Button -->
            <button onclick="closeImageModal()" 
                    class="absolute top-4 right-4 z-10 bg-white text-gray-800 p-2 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <!-- Navigation Buttons -->
            <button onclick="navigateImage(-1)" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 bg-white text-gray-800 p-3 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>
            <button onclick="navigateImage(1)" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 bg-white text-gray-800 p-3 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-chevron-right text-xl"></i>
            </button>
            
            <!-- Modal Content -->
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <!-- Image -->
                    <div class="lg:col-span-1">
                        <img id="modalImage" src="" alt="" class="w-full h-96 lg:h-full object-cover">
                    </div>
                    
                    <!-- Details -->
                    <div class="lg:col-span-1 p-6 flex flex-col">
                        <div class="flex-1">
                            <h3 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-4"></h3>
                            <p id="modalDescription" class="text-gray-600 leading-relaxed mb-4"></p>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-500">
                                <div>
                                    <span class="font-semibold">Kategori:</span>
                                    <span id="modalCategory" class="ml-2 capitalize"></span>
                                </div>
                                <div>
                                    <span class="font-semibold">Upload:</span>
                                    <span id="modalDate" class="ml-2"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex space-x-3">
                                <a href="index.php#daftar" 
                                   class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 text-center">
                                    <i class="fas fa-edit mr-2"></i>Daftar Sekarang
                                </a>
                                <button onclick="closeImageModal()" 
                                        class="px-6 py-3 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Global variables for image navigation
        let currentGalleryItems = [];
        let currentImageIndex = 0;

        // View Image Modal
function viewImage(imageName, title, description, category = '', date = '') {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');
    const modalCategory = document.getElementById('modalCategory');
    const modalDate = document.getElementById('modalDate');
    
    // Check if image exists
    const imagePath = `assets/images/galeri/${imageName}`;
    
    // Set modal content
    modalImage.src = imagePath;
    modalTitle.textContent = title;
    modalDescription.textContent = description || 'Tidak ada deskripsi';
    modalCategory.textContent = category || 'general';
    modalDate.textContent = date || new Date().toLocaleDateString('id-ID');
    
    // Prepare for navigation
    currentGalleryItems = Array.from(document.querySelectorAll('.gallery-item'));
    currentImageIndex = currentGalleryItems.findIndex(item => {
        const img = item.querySelector('img');
        return img && img.src.includes(imageName);
    });
    
    // Show modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Handle image error
    modalImage.onerror = function() {
        modalImage.alt = "Gambar tidak dapat dimuat";
        modalImage.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect width='400' height='300' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='16' fill='%239ca3af'%3EGambar tidak ditemukan%3C/text%3E%3C/svg%3E";
    };
}

        // Image Navigation
        function navigateImage(direction) {
            if (currentGalleryItems.length === 0) return;
            
            currentImageIndex += direction;
            
            // Loop around
            if (currentImageIndex < 0) {
                currentImageIndex = currentGalleryItems.length - 1;
            } else if (currentImageIndex >= currentGalleryItems.length) {
                currentImageIndex = 0;
            }
            
            const currentItem = currentGalleryItems[currentImageIndex];
            const img = currentItem.querySelector('img');
            const title = currentItem.querySelector('h3').textContent;
            const description = currentItem.querySelector('p')?.textContent || '';
            
            viewImage(
                img.src.split('/').pop(),
                title,
                description
            );
        }

        // Category Filter
        function filterByCategory(kategori) {
            const allItems = document.querySelectorAll('.gallery-item');
            const categoryButtons = document.querySelectorAll('.category-filter');
            
            // Update active button
            categoryButtons.forEach(btn => {
                if (btn.dataset.kategori === kategori) {
                    btn.classList.add('border-blue-500', 'bg-blue-50');
                } else {
                    btn.classList.remove('border-blue-500', 'bg-blue-50');
                }
            });
            
            // Show/hide items
            allItems.forEach(item => {
                if (kategori === 'all' || item.dataset.kategori === kategori) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Scroll to category section
            if (kategori !== 'all') {
                const section = document.getElementById(kategori);
                if (section) {
                    section.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('imageModal');
            if (!modal.classList.contains('hidden')) {
                if (e.key === 'Escape') {
                    closeImageModal();
                } else if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                }
            }
        });

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Category filter buttons
            const categoryButtons = document.querySelectorAll('.category-filter');
            categoryButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    filterByCategory(this.dataset.kategori);
                });
            });
            
            // Close modal when clicking outside image
            document.getElementById('imageModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeImageModal();
                }
            });
            
            // Mobile menu
            document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
                const mobileMenu = document.getElementById('mobile-menu');
                if (mobileMenu) {
                    mobileMenu.classList.toggle('hidden');
                }
            });
        });
    </script>
</body>
</html>