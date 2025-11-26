<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data instruktur dari database
try {
    // Ambil semua instruktur aktif
    $instruktur_query = $db->query("SELECT * FROM instruktur WHERE aktif = 1 ORDER BY pengalaman_tahun DESC, rating DESC");
    $instruktur = $instruktur_query->fetchAll(PDO::FETCH_ASSOC);

    // Hitung statistik
    $total_instruktur = count($instruktur);
    $instruktur_manual = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1 AND spesialisasi = 'manual'")->fetch()['total'];
    $instruktur_matic = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1 AND spesialisasi = 'matic'")->fetch()['total'];
    $instruktur_keduanya = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1 AND spesialisasi = 'keduanya'")->fetch()['total'];
    
    // Ambil instruktur dengan rating tertinggi
    $top_instruktur = $db->query("SELECT * FROM instruktur WHERE aktif = 1 ORDER BY rating DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $instruktur = [];
    $total_instruktur = 0;
    $instruktur_manual = 0;
    $instruktur_matic = 0;
    $instruktur_keduanya = 0;
    $top_instruktur = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruktur Profesional - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-car text-2xl text-blue-600 mr-2"></i>
                        <span class="text-xl font-bold text-gray-800">Krishna Driving</span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Beranda</a>
                    <a href="paket-kursus.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Paket Kursus</a>
                    <a href="instruktur.php" class="text-blue-600 font-medium transition duration-300">Instruktur</a>
                    <a href="index.php#testimoni" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Testimoni</a>
                    <a href="index.php#tentang-kontak" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Tentang & Kontak</a>
                    
                    <!-- Menu Cek Status -->
                    <a href="cek-status.php" class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        <i class="fas fa-search mr-1 text-sm"></i>Cek Status
                    </a>
                    
                    <!-- Tombol CTA -->
                    <a href="index.php#daftar" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 shadow-md hover:shadow-lg">
                        <i class="fas fa-edit mr-1"></i>Daftar
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-blue-600 transition duration-300 p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Tim Instruktur Profesional</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Belajar dari instruktur berpengalaman dan bersertifikat yang siap membimbing Anda sampai bisa mengemudi
            </p>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600"><?= $total_instruktur ?></div>
                    <div class="text-gray-600 text-sm">Total Instruktur</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600"><?= $instruktur_manual ?></div>
                    <div class="text-gray-600 text-sm">Spesialis Manual</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600"><?= $instruktur_matic ?></div>
                    <div class="text-gray-600 text-sm">Spesialis Matic</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600"><?= $instruktur_keduanya ?></div>
                    <div class="text-gray-600 text-sm">Spesialis Keduanya</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Semua Instruktur</h2>
                    <p class="text-gray-600">Pilih berdasarkan spesialisasi dan pengalaman</p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-wrap gap-4">
                    <select id="filter-spesialisasi" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Spesialisasi</option>
                        <option value="manual">Manual</option>
                        <option value="matic">Matic</option>
                        <option value="keduanya">Keduanya</option>
                    </select>
                    <select id="filter-pengalaman" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Pengalaman</option>
                        <option value="5">5+ Tahun</option>
                        <option value="10">10+ Tahun</option>
                        <option value="15">15+ Tahun</option>
                    </select>
                    <select id="filter-rating" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Rating</option>
                        <option value="4.5">4.5+ Bintang</option>
                        <option value="4.7">4.7+ Bintang</option>
                        <option value="4.9">4.9+ Bintang</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- All Instructors Grid -->
        <?php if (empty($instruktur)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Data Instruktur Sedang Tidak Tersedia</h3>
                <p class="text-gray-500">Silakan hubungi kami untuk informasi lebih lanjut.</p>
            </div>
        <?php else: ?>
            <div id="instructorsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($instruktur as $inst): ?>
                <div class="instructor-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border border-gray-100"
                     data-spesialisasi="<?= $inst['spesialisasi'] ?>"
                     data-pengalaman="<?= $inst['pengalaman_tahun'] ?>"
                     data-rating="<?= $inst['rating'] ?>">
                    
                    <!-- Header dengan Foto -->
                    <div class="relative">
                        <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-600 rounded-t-2xl flex items-center justify-center">
                            <?php if (!empty($inst['foto'])): ?>
                                <img src="assets/images/instruktur/<?= $inst['foto'] ?>" 
                                     alt="<?= htmlspecialchars($inst['nama_lengkap']) ?>" 
                                     class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                            <?php else: ?>
                                <div class="w-32 h-32 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-4xl text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Badge Spesialisasi -->
                        <div class="absolute top-4 right-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                <?= $inst['spesialisasi'] == 'manual' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $inst['spesialisasi'] == 'matic' ? 'bg-purple-100 text-purple-800' : '' ?>
                                <?= $inst['spesialisasi'] == 'keduanya' ? 'bg-green-100 text-green-800' : '' ?>">
                                <i class="fas fa-<?= $inst['spesialisasi'] == 'manual' ? 'cog' : ($inst['spesialisasi'] == 'matic' ? 'car-side' : 'cars') ?> mr-1"></i>
                                <?= ucfirst($inst['spesialisasi']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Nama dan Rating -->
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($inst['nama_lengkap']) ?></h3>
                            <div class="text-right">
                                <div class="text-yellow-400 text-sm">
                                    <?= str_repeat('★', floor($inst['rating'])) ?><?= str_repeat('☆', 5 - floor($inst['rating'])) ?>
                                </div>
                                <div class="text-sm text-gray-500">(<?= number_format($inst['rating'], 1) ?>)</div>
                            </div>
                        </div>

                        <!-- Nomor Lisensi -->
                        <div class="flex items-center text-gray-600 text-sm mb-3">
                            <i class="fas fa-id-card mr-2 text-blue-500"></i>
                            <span>Lisensi: <?= htmlspecialchars($inst['nomor_licensi']) ?></span>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center bg-blue-50 rounded-lg p-3">
                                <div class="text-lg font-bold text-blue-600"><?= $inst['pengalaman_tahun'] ?>+</div>
                                <div class="text-xs text-gray-600">Tahun</div>
                            </div>
                            <div class="text-center bg-green-50 rounded-lg p-3">
                                <div class="text-lg font-bold text-green-600"><?= $inst['total_siswa'] ?>+</div>
                                <div class="text-xs text-gray-600">Siswa</div>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <p class="text-gray-600 text-sm mb-4 leading-relaxed">
                            <?= htmlspecialchars($inst['deskripsi'] ?? 'Instruktur profesional berpengalaman dengan metode pengajaran yang mudah dipahami.') ?>
                        </p>

                        <!-- Keahlian -->
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-700 text-sm mb-2">Keahlian:</h4>
                            <div class="flex flex-wrap gap-1">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <i class="fas fa-road mr-1"></i>Praktik Mengemudi
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    <i class="fas fa-book mr-1"></i>Teori Berkendara
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">
                                    <i class="fas fa-shield-alt mr-1"></i>Safety Driving
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button onclick="showInstructorDetail(<?= htmlspecialchars(json_encode($inst)) ?>)" 
                                    class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 text-sm">
                                <i class="fas fa-eye mr-1"></i>Lihat Detail
                            </button>
                            <a href="https://wa.me/6281234567890?text=Halo,%20saya%20tertarik%20dengan%20instruktur%20<?= urlencode($inst['nama_lengkap']) ?>%20di%20Krishna%20Driving"
                               target="_blank"
                               class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-300">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 text-center text-white mt-12">
            <h3 class="text-2xl font-bold mb-4">Siap Belajar Mengemudi?</h3>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                Pilih instruktur favorit Anda dan mulai perjalanan belajar mengemudi dengan profesional.
                Garansi sampai bisa!
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

    <!-- Instructor Detail Modal -->
    <div id="instructorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-2xl font-bold text-gray-800" id="modalName">Nama Instruktur</h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-1">
                        <div id="modalPhoto" class="w-32 h-32 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <!-- Foto akan dimuat via JavaScript -->
                        </div>
                        <div class="text-center">
                            <div id="modalRating" class="text-yellow-400 text-sm mb-2">
                                <!-- Rating stars -->
                            </div>
                            <div id="modalLicense" class="text-gray-600 text-sm mb-2">
                                <!-- Nomor lisensi -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <div id="modalExperience" class="text-lg font-bold text-blue-600">0+</div>
                                <div class="text-sm text-gray-600">Tahun Pengalaman</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <div id="modalStudents" class="text-lg font-bold text-green-600">0+</div>
                                <div class="text-sm text-gray-600">Total Siswa</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-700 mb-2">Spesialisasi:</h4>
                            <span id="modalSpecialization" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold">
                                <!-- Spesialisasi -->
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Tentang Instruktur:</h4>
                    <p id="modalDescription" class="text-gray-600 leading-relaxed">
                        <!-- Deskripsi -->
                    </p>
                </div>
                
                <div class="mt-6 flex space-x-3">
                    <a href="#" id="modalWhatsApp" 
                       target="_blank"
                       class="flex-1 bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-center">
                        <i class="fab fa-whatsapp mr-2"></i>Konsultasi via WhatsApp
                    </a>
                    <a href="index.php#daftar" 
                       class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 text-center">
                        <i class="fas fa-edit mr-2"></i>Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Filter functionality
        function filterInstructors() {
            const selectedSpesialisasi = document.getElementById('filter-spesialisasi').value;
            const selectedPengalaman = document.getElementById('filter-pengalaman').value;
            const selectedRating = document.getElementById('filter-rating').value;
            
            const instructors = document.querySelectorAll('.instructor-card');
            let visibleCount = 0;
            
            instructors.forEach(instructor => {
                const spesialisasi = instructor.dataset.spesialisasi;
                const pengalaman = parseInt(instructor.dataset.pengalaman);
                const rating = parseFloat(instructor.dataset.rating);
                
                // Check filters
                const spesialisasiMatch = selectedSpesialisasi === 'all' || spesialisasi === selectedSpesialisasi;
                const pengalamanMatch = selectedPengalaman === 'all' || pengalaman >= parseInt(selectedPengalaman);
                const ratingMatch = selectedRating === 'all' || rating >= parseFloat(selectedRating);
                
                if (spesialisasiMatch && pengalamanMatch && ratingMatch) {
                    instructor.style.display = 'block';
                    visibleCount++;
                } else {
                    instructor.style.display = 'none';
                }
            });
        }
        
        // Modal functionality
        function showInstructorDetail(instructor) {
            const modal = document.getElementById('instructorModal');
            const photoContainer = document.getElementById('modalPhoto');
            const ratingContainer = document.getElementById('modalRating');
            
            // Set modal content
            document.getElementById('modalName').textContent = instructor.nama_lengkap;
            document.getElementById('modalLicense').textContent = `Lisensi: ${instructor.nomor_licensi}`;
            document.getElementById('modalExperience').textContent = `${instructor.pengalaman_tahun}+`;
            document.getElementById('modalStudents').textContent = `${instructor.total_siswa}+`;
            document.getElementById('modalDescription').textContent = instructor.deskripsi || 'Instruktur profesional berpengalaman dengan metode pengajaran yang mudah dipahami.';
            
            // Set photo
            if (instructor.foto) {
                photoContainer.innerHTML = `<img src="assets/images/instruktur/${instructor.foto}" alt="${instructor.nama_lengkap}" class="w-32 h-32 rounded-full object-cover border-4 border-white">`;
            } else {
                photoContainer.innerHTML = '<i class="fas fa-user text-4xl text-white"></i>';
            }
            
            // Set rating stars
            const stars = '★'.repeat(Math.floor(instructor.rating)) + '☆'.repeat(5 - Math.floor(instructor.rating));
            ratingContainer.innerHTML = `${stars} <span class="text-gray-600 ml-2">(${instructor.rating})</span>`;
            
            // Set specialization badge
            const specializationBadge = document.getElementById('modalSpecialization');
            specializationBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${
                instructor.spesialisasi === 'manual' ? 'bg-yellow-100 text-yellow-800' :
                instructor.spesialisasi === 'matic' ? 'bg-purple-100 text-purple-800' :
                'bg-green-100 text-green-800'
            }`;
            specializationBadge.innerHTML = `<i class="fas fa-${
                instructor.spesialisasi === 'manual' ? 'cog' : 
                instructor.spesialisasi === 'matic' ? 'car-side' : 'cars'
            } mr-1"></i>${instructor.spesialisasi.charAt(0).toUpperCase() + instructor.spesialisasi.slice(1)}`;
            
            // Set WhatsApp link
            const whatsappLink = document.getElementById('modalWhatsApp');
            whatsappLink.href = `https://wa.me/6281234567890?text=Halo,%20saya%20tertarik%20dengan%20instruktur%20${encodeURIComponent(instructor.nama_lengkap)}%20di%20Krishna%20Driving`;
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('instructorModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Filter events
            document.getElementById('filter-spesialisasi').addEventListener('change', filterInstructors);
            document.getElementById('filter-pengalaman').addEventListener('change', filterInstructors);
            document.getElementById('filter-rating').addEventListener('change', filterInstructors);
            
            // Close modal when clicking outside
            document.getElementById('instructorModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
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