<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data galeri yang aktif dari database
try {
    $galeri_query = $db->query("SELECT * FROM galeri WHERE status = 'aktif' ORDER BY urutan_tampil ASC, created_at DESC");
    $galeri = $galeri_query->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan galeri berdasarkan kategori
    $galeri_by_kategori = [];
    foreach ($galeri as $item) {
        $galeri_by_kategori[$item['kategori']][] = $item;
    }

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
       <!-- Simplified Category Filter -->
<div class="flex flex-wrap justify-center gap-2 mb-8">
    <button 
        onclick="filterByCategory('all')" 
        class="category-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 transition"
        data-kategori="all">
        Semua
    </button>
    <?php foreach ($kategori_info as $kategori => $info): ?>
        <button 
            onclick="filterByCategory('<?= $kategori ?>')" 
            class="category-btn px-4 py-2 text-sm font-medium rounded-full bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 transition"
            data-kategori="<?= $kategori ?>">
            <?= $info['label'] ?>
        </button>
    <?php endforeach; ?>
</div>

        <!-- All Gallery Content -->
        <?php if (empty($galeri)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Galeri Masih Kosong</h3>
                <p class="text-gray-500">Silakan kembali lagi nanti untuk melihat galeri kami.</p>
            </div>
        <?php else: ?>
            <?php foreach ($kategori_info as $kategori => $info):
                if (isset($galeri_by_kategori[$kategori]) && count($galeri_by_kategori[$kategori]) > 0):
                    ?>
                    <section id="<?= $kategori ?>" class="mb-12 scroll-mt-8">
                        <div class="flex items-center mb-6">
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
                                        <div
                                            class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <div class="text-center text-white">
                                                <button
                                                    onclick="viewImage('<?= $item['gambar'] ?>', '<?= htmlspecialchars($item['judul']) ?>', '<?= htmlspecialchars($item['deskripsi']) ?>')"
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
            <button onclick="closeImageModal()"
                class="absolute top-4 right-4 z-10 bg-white text-gray-800 p-2 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-times text-xl"></i>
            </button>

            <button onclick="navigateImage(-1)"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 bg-white text-gray-800 p-3 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>
            <button onclick="navigateImage(1)"
                class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 bg-white text-gray-800 p-3 rounded-full hover:bg-gray-200 transition duration-300">
                <i class="fas fa-chevron-right text-xl"></i>
            </button>

            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="lg:col-span-1">
                        <img id="modalImage" src="" alt="" class="w-full h-96 lg:h-full object-cover">
                    </div>
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
        // Close modal
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Navigate
        let currentGalleryItems = [];
        let currentImageIndex = 0;

        function navigateImage(direction) {
            if (currentGalleryItems.length === 0) return;
            currentImageIndex += direction;
            if (currentImageIndex < 0) currentImageIndex = currentGalleryItems.length - 1;
            if (currentImageIndex >= currentGalleryItems.length) currentImageIndex = 0;

            const item = currentGalleryItems[currentImageIndex];
            const img = item.querySelector('img');
            const title = item.querySelector('h3').textContent;
            const desc = item.querySelector('p')?.textContent || '';
            const kategori = item.dataset.kategori || '';
            viewImage(img.src.split('/').pop(), title, desc, kategori, new Date().toLocaleDateString('id-ID'));
        }

        // View image
        function viewImage(imageName, title, description, category = '', date = '') {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalDescription = document.getElementById('modalDescription');
            const modalCategory = document.getElementById('modalCategory');
            const modalDate = document.getElementById('modalDate');

            modalImage.src = `assets/images/galeri/${imageName}`;
            modalTitle.textContent = title;
            modalDescription.textContent = description || 'Tidak ada deskripsi';
            modalCategory.textContent = category ? getCategoryLabel(category) : 'Umum';
            modalDate.textContent = date;

            currentGalleryItems = Array.from(document.querySelectorAll('.gallery-item'));
            currentImageIndex = currentGalleryItems.findIndex(item => {
                const img = item.querySelector('img');
                return img && img.src.includes(imageName);
            });

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            modalImage.onerror = function () {
                modalImage.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect width='400' height='300' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='16' fill='%239ca3af'%3EGambar tidak ditemukan%3C/text%3E%3C/svg%3E";
            };
        }

        function getCategoryLabel(category) {
            const labels = {
                'aktivitas': 'Aktivitas Belajar',
                'fasilitas': 'Fasilitas',
                'sertifikat': 'Sertifikat',
                'kendaraan': 'Kendaraan',
                'instruktur': 'Instruktur'
            };
            return labels[category] || category;
        }

        function filterByCategory(kategori) {
    // Hapus active dari SEMUA tombol
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    // Tambahkan active hanya ke yang dipilih
    document.querySelector(`.category-btn[data-kategori="${kategori}"]`)?.classList.add('active');

    // Filter galeri
    document.querySelectorAll('.gallery-item').forEach(item => {
        item.style.display = (kategori === 'all' || item.dataset.kategori === kategori) ? 'block' : 'none';
    });

    if (kategori !== 'all') {
        const section = document.getElementById(kategori);
        if (section) section.scrollIntoView({ behavior: 'smooth' });
    }
}

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('.category-btn[data-kategori="all"]').classList.add('active');

            document.getElementById('imageModal').addEventListener('click', e => {
                if (e.target === e.currentTarget) closeImageModal();
            });

            document.addEventListener('keydown', e => {
                if (!document.getElementById('imageModal').classList.contains('hidden')) {
                    if (e.key === 'Escape') closeImageModal();
                    else if (e.key === 'ArrowLeft') navigateImage(-1);
                    else if (e.key === 'ArrowRight') navigateImage(1);
                }
            });
        });
    </script>
</body>

</html>