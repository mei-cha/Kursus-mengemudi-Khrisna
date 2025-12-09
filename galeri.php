<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data galeri yang aktif dari database
try {
    $galeri_query = $db->query("SELECT * FROM galeri WHERE status = 'aktif' ORDER BY urutan_tampil ASC, created_at DESC");
    $galeri = $galeri_query->fetchAll(PDO::FETCH_ASSOC);
    
    $total_gambar = count($galeri);
} catch (PDOException $e) {
    $galeri = [];
    $total_gambar = 0;
    error_log("Database error: " . $e->getMessage());
}

// Kategori info untuk tampilan
$kategori_info = [
    'all' => ['label' => 'Semua', 'description' => 'Semua gambar galeri'],
    'aktivitas' => ['label' => 'Aktivitas Belajar', 'description' => 'Momen seru selama proses belajar mengemudi'],
    'fasilitas' => ['label' => 'Fasilitas', 'description' => 'Fasilitas lengkap untuk kenyamanan belajar'],
    'sertifikat' => ['label' => 'Sertifikat', 'description' => 'Bukti kelulusan dan sertifikasi mengemudi'],
    'kendaraan' => ['label' => 'Kendaraan', 'description' => 'Mobil-mobil terawat untuk latihan mengemudi'],
    'instruktur' => ['label' => 'Instruktur', 'description' => 'Tim instruktur profesional dan berpengalaman']
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
    <style>
        .category-btn.active {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .gallery-item {
            transition: all 0.3s ease;
            opacity: 1;
            transform: scale(1);
        }

        .gallery-item.hidden {
            opacity: 0;
            transform: scale(0.8);
            display: none !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .gallery-item:not(.hidden) {
            animation: fadeIn 0.3s ease-out;
        }

        .category-btn {
            transition: all 0.2s ease;
        }

        .category-btn.active {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .category-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Category Filter -->
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <?php foreach ($kategori_info as $kategori => $info): ?>
                <button
                    onclick="filterByCategory('<?= $kategori ?>')"
                    class="category-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 transition"
                    data-kategori="<?= $kategori ?>">
                    <?= $info['label'] ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Gallery Content -->
        <?php if (empty($galeri)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Galeri Masih Kosong</h3>
                <p class="text-gray-500">Silakan kembali lagi nanti untuk melihat galeri kami.</p>
            </div>
        <?php else: ?>
            <section class="mb-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3" id="galleryContainer">
                    <?php foreach ($galeri as $item): ?>
                        <div class="gallery-item group cursor-pointer overflow-hidden rounded-lg shadow-sm hover:shadow-md transition"
                            data-kategori="<?= htmlspecialchars($item['kategori']) ?>"
                            onclick="viewImage('<?= htmlspecialchars($item['gambar']) ?>')">
                            <div class="aspect-square bg-gray-100">
                                <?php if (file_exists("assets/images/galeri/" . $item['gambar'])): ?>
                                    <img src="assets/images/galeri/<?= htmlspecialchars($item['gambar']) ?>"
                                        alt="<?= htmlspecialchars($item['kategori']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
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

            <!-- Main Image -->
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <img id="modalImage" src="" alt="" class="w-full h-auto object-contain max-h-[90vh] mx-auto">
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        function filterByCategory(kategori) {
            // Update active button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.kategori === kategori) {
                    btn.classList.add('active');
                }
            });

            // Show/hide gallery items
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            galleryItems.forEach(item => {
                if (kategori === 'all') {
                    item.classList.remove('hidden');
                } else {
                    if (item.dataset.kategori === kategori) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                }
            });

            // Smooth scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function viewImage(imageName) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            modalImage.src = `assets/images/galeri/${imageName}`;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Error handling
            modalImage.onerror = function() {
                modalImage.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect width='400' height='300' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='16' fill='%239ca3af'%3EGambar tidak ditemukan%3C/text%3E%3C/svg%3E";
            };
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Set 'Semua' as active by default
            document.querySelector('.category-btn[data-kategori="all"]').classList.add('active');

            // Close modal when clicking outside
            document.getElementById('imageModal').addEventListener('click', e => {
                if (e.target === e.currentTarget) closeImageModal();
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', e => {
                if (!document.getElementById('imageModal').classList.contains('hidden')) {
                    if (e.key === 'Escape') closeImageModal();
                }
            });
        });
    </script>
</body>
</html>