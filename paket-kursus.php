<?php
require_once 'config/database.php';
include 'includes/header.php'; 
$database = new Database();
$db = $database->getConnection();

// Ambil data dari database
try {
    // Ambil paket kursus
    $paket_query = $db->query("SELECT * FROM paket_kursus WHERE tersedia = 1 ORDER BY harga ASC");
    $paket_kursus = $paket_query->fetchAll(PDO::FETCH_ASSOC);

    // Ambil testimoni untuk sidebar
    $testimoni_query = $db->query("SELECT * FROM testimoni WHERE status = 'disetujui' ORDER BY rating DESC, created_at DESC LIMIT 3");
    $testimoni = $testimoni_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $paket_kursus = [];
    $testimoni = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Kursus Mengemudi - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->


    <!-- Header Section -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Paket Kursus Mengemudi</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Pilih paket yang paling sesuai dengan kebutuhan belajar mengemudi Anda
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filter -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Filter Paket</h3>
                    
                    <!-- Filter Tipe Mobil -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-700 mb-3">Tipe Mobil</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-mobil" value="manual" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Manual</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-mobil" value="matic" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Matic</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-mobil" value="keduanya" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Keduanya</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Kategori -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-700 mb-3">Kategori</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-kategori" value="reguler" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Reguler</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-kategori" value="campuran" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Campuran</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-kategori" value="extra" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Extra</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="filter-kategori" value="pelancaran" checked 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Pelancaran</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Harga -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-700 mb-3">Rentang Harga</h4>
                        <select id="filter-harga" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Semua Harga</option>
                            <option value="0-500000">Rp 0 - 500rb</option>
                            <option value="500000-1000000">Rp 500rb - 1jt</option>
                            <option value="1000000-2000000">Rp 1jt - 2jt</option>
                            <option value="2000000-999999999">> Rp 2jt</option>
                        </select>
                    </div>

                    <button onclick="resetFilters()" class="w-full bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-300 transition duration-300">
                        Reset Filter
                    </button>
                </div>

                <!-- Testimoni Sidebar -->
                <?php if (!empty($testimoni)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Testimoni Terbaru</h3>
                    <div class="space-y-4">
                        <?php foreach ($testimoni as $testi): ?>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <p class="text-gray-600 text-sm italic">"<?= htmlspecialchars(substr($testi['testimoni_text'], 0, 100)) ?>..."</p>
                            <div class="flex items-center mt-2">
                                <div class="text-yellow-400 text-sm">
                                    <?= str_repeat('★', $testi['rating']) ?>
                                </div>
                                <span class="ml-2 text-sm font-semibold text-gray-700"><?= htmlspecialchars($testi['nama_siswa']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Package List -->
            <div class="lg:w-3/4">
                <!-- Filter Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Semua Paket Kursus</h2>
                            <p class="text-gray-600" id="packageCount">
                                Menampilkan <?= count($paket_kursus) ?> paket tersedia
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <select id="sort-packages" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="harga-asc">Harga: Rendah ke Tinggi</option>
                                <option value="harga-desc">Harga: Tinggi ke Rendah</option>
                                <option value="durasi-asc">Durasi: Pendek ke Panjang</option>
                                <option value="durasi-desc">Durasi: Panjang ke Pendek</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Packages Grid -->
                <?php if (empty($paket_kursus)): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                        <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-600 mb-2">Paket Kursus Sedang Tidak Tersedia</h3>
                        <p class="text-gray-500">Silakan hubungi kami untuk informasi lebih lanjut.</p>
                    </div>
                <?php else: ?>
                    <div id="packagesGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($paket_kursus as $paket): ?>
                        <?php
                        $pertemuan = floor($paket['durasi_jam'] / 50);
                        $kategori = 'reguler';
                        if (strpos(strtolower($paket['nama_paket']), 'campuran') !== false || $paket['tipe_mobil'] === 'keduanya') {
                            $kategori = 'campuran';
                        } elseif (strpos(strtolower($paket['nama_paket']), 'extra') !== false) {
                            $kategori = 'extra';
                        } elseif (strpos(strtolower($paket['nama_paket']), 'pelancaran') !== false) {
                            $kategori = 'pelancaran';
                        }
                        ?>
                        <div class="package-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border border-gray-100 transform hover:-translate-y-1"
                             data-tipe="<?= $paket['tipe_mobil'] ?>" 
                             data-kategori="<?= $kategori ?>"
                             data-harga="<?= $paket['harga'] ?>"
                             data-durasi="<?= $paket['durasi_jam'] ?>">
                            <div class="p-6">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($paket['nama_paket']) ?></h3>
                                        <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold rounded-full 
                                            <?= $kategori === 'reguler' ? 'bg-blue-100 text-blue-800' : '' ?>
                                            <?= $kategori === 'campuran' ? 'bg-green-100 text-green-800' : '' ?>
                                            <?= $kategori === 'extra' ? 'bg-purple-100 text-purple-800' : '' ?>
                                            <?= $kategori === 'pelancaran' ? 'bg-orange-100 text-orange-800' : '' ?>">
                                            <?= ucfirst($kategori) ?>
                                        </span>
                                    </div>
                                    <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full capitalize">
                                        <?= $paket['tipe_mobil'] ?>
                                    </span>
                                </div>
                                
                                <!-- Harga -->
                                <div class="text-2xl font-bold text-blue-600 mb-4">
                                    Rp <?= number_format($paket['harga'], 0, ',', '.') ?>
                                </div>
                                
                                <!-- Info -->
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-clock text-blue-500 w-5 mr-3"></i>
                                        <span><?= $pertemuan ?> Pertemuan (<?= $paket['durasi_jam'] ?> menit)</span>
                                    </div>
                                    <?php if ($paket['termasuk_teori']): ?>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-book text-green-500 w-5 mr-3"></i>
                                        <span>Termasuk Kelas Teori</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($paket['termasuk_praktik']): ?>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-road text-orange-500 w-5 mr-3"></i>
                                        <span>Termasuk Praktik Langsung</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-users text-purple-500 w-5 mr-3"></i>
                                        <span>Maksimal <?= $paket['maksimal_siswa'] ?> Siswa</span>
                                    </div>
                                </div>
                                
                                <!-- Deskripsi -->
                                <p class="text-gray-600 text-sm mb-6 leading-relaxed"><?= htmlspecialchars($paket['deskripsi'] ?? 'Paket lengkap belajar mengemudi') ?></p>
                                
                                <!-- Tombol -->
                                <div class="flex space-x-3">
                                    <a href="index.php#daftar" 
                                       class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition duration-300 text-center">
                                        <i class="fas fa-shopping-cart mr-2"></i>Pilih Paket
                                    </a>
                                    <button onclick="sharePackage('<?= htmlspecialchars($paket['nama_paket']) ?>', <?= $paket['harga'] ?>)" 
                                            class="px-4 py-3 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition duration-300">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Call to Action -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 text-center text-white mt-8">
                    <h3 class="text-2xl font-bold mb-4">Masih Bingung Memilih Paket?</h3>
                    <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                        Konsultasikan kebutuhan belajar mengemudi Anda dengan tim kami. 
                        Kami akan membantu memilih paket yang paling sesuai.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="https://wa.me/6281234567890?text=Halo,%20saya%20ingin%20konsultasi%20tentang%20paket%20kursus%20mengemudi" 
                           target="_blank"
                           class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300">
                            <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                        </a>
                        <a href="tel:+6281234567890" 
                           class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                            <i class="fas fa-phone mr-2"></i>Telepon Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Filter functionality
        function filterPackages() {
            const selectedMobil = Array.from(document.querySelectorAll('.filter-mobil:checked')).map(cb => cb.value);
            const selectedKategori = Array.from(document.querySelectorAll('.filter-kategori:checked')).map(cb => cb.value);
            const selectedHarga = document.getElementById('filter-harga').value;
            const sortBy = document.getElementById('sort-packages').value;
            
            const packages = document.querySelectorAll('.package-card');
            let visibleCount = 0;
            
            packages.forEach(package => {
                const tipe = package.dataset.tipe;
                const kategori = package.dataset.kategori;
                const harga = parseInt(package.dataset.harga);
                const durasi = parseInt(package.dataset.durasi);
                
                // Check filters
                const mobilMatch = selectedMobil.includes(tipe);
                const kategoriMatch = selectedKategori.includes(kategori);
                const hargaMatch = checkHargaFilter(harga, selectedHarga);
                
                if (mobilMatch && kategoriMatch && hargaMatch) {
                    package.style.display = 'block';
                    visibleCount++;
                } else {
                    package.style.display = 'none';
                }
            });
            
            // Update package count
            document.getElementById('packageCount').textContent = `Menampilkan ${visibleCount} paket tersedia`;
            
            // Sort packages
            sortPackages(sortBy);
        }
        
        function checkHargaFilter(harga, filter) {
            if (filter === 'all') return true;
            
            const [min, max] = filter.split('-').map(Number);
            return harga >= min && harga <= max;
        }
        
        function sortPackings(sortBy) {
            const container = document.getElementById('packagesGrid');
            const packages = Array.from(container.querySelectorAll('.package-card'));
            
            packages.sort((a, b) => {
                const aHarga = parseInt(a.dataset.harga);
                const bHarga = parseInt(b.dataset.harga);
                const aDurasi = parseInt(a.dataset.durasi);
                const bDurasi = parseInt(b.dataset.durasi);
                
                switch(sortBy) {
                    case 'harga-asc':
                        return aHarga - bHarga;
                    case 'harga-desc':
                        return bHarga - aHarga;
                    case 'durasi-asc':
                        return aDurasi - bDurasi;
                    case 'durasi-desc':
                        return bDurasi - aDurasi;
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted packages
            packages.forEach(pkg => container.appendChild(pkg));
        }
        
        function resetFilters() {
            document.querySelectorAll('.filter-mobil, .filter-kategori').forEach(cb => cb.checked = true);
            document.getElementById('filter-harga').value = 'all';
            document.getElementById('sort-packages').value = 'harga-asc';
            filterPackages();
        }
        
        function sharePackage(namaPaket, harga) {
            const text = `Saya tertarik dengan paket ${namaPaket} seharga Rp ${harga.toLocaleString('id-ID')} di Krishna Driving Course.`;
            const url = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: namaPaket,
                    text: text,
                    url: url
                });
            } else {
                // Fallback untuk browser yang tidak support Web Share API
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
                window.open(whatsappUrl, '_blank');
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.filter-mobil, .filter-kategori').forEach(cb => {
                cb.addEventListener('change', filterPackages);
            });
            document.getElementById('filter-harga').addEventListener('change', filterPackages);
            document.getElementById('sort-packages').addEventListener('change', filterPackages);
            
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