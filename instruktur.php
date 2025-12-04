<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data instruktur dari database
try {
    // Ambil semua instruktur aktif
    $instruktur_query = $db->query("SELECT * FROM instruktur WHERE aktif = 1 ORDER BY pengalaman_tahun DESC");
    $instruktur = $instruktur_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $instruktur = [];
    error_log("Database error: " . $e->getMessage());
}
?>

<!-- Header Section -->
<section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Tim Instruktur Profesional</h1>
        <p class="text-xl text-blue-100 max-w-3xl mx-auto">
            Belajar dari instruktur berpengalaman dan bersertifikat yang siap membimbing Anda sampai bisa mengemudi
        </p>
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
                 data-pengalaman="<?= $inst['pengalaman_tahun'] ?>">
                
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
                    
                    <!-- Badge Pengalaman -->
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            <i class="fas fa-award mr-1"></i>
                            <?= $inst['pengalaman_tahun'] ?>+ Tahun
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Nama dan Spesialisasi -->
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($inst['nama_lengkap']) ?></h3>
                        
                        <div class="flex items-center text-gray-600 mb-3">
                            <i class="fas fa-car mr-2 text-blue-500"></i>
                            <span class="font-medium">Spesialis: <?= ucfirst($inst['spesialisasi']) ?></span>
                        </div>

                        <!-- Nomor Lisensi -->
                        <div class="flex items-center text-gray-600 text-sm mb-4">
                            <i class="fas fa-id-card mr-2 text-blue-500"></i>
                            <span>Lisensi: <?= htmlspecialchars($inst['nomor_licensi']) ?></span>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center bg-blue-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-blue-600"><?= $inst['pengalaman_tahun'] ?>+</div>
                            <div class="text-xs text-gray-600">Tahun Pengalaman</div>
                        </div>
                        <div class="text-center bg-green-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-green-600">Bersertifikat</div>
                            <div class="text-xs text-gray-600">Profesional</div>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-4">
                        <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                            <?= htmlspecialchars($inst['deskripsi'] ?? 'Instruktur profesional berpengalaman dengan metode pengajaran yang mudah dipahami.') ?>
                        </p>
                    </div>

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
                            <?php if ($inst['spesialisasi'] == 'manual'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                <i class="fas fa-cog mr-1"></i>Kopling & Gigi
                            </span>
                            <?php elseif ($inst['spesialisasi'] == 'matic'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">
                                <i class="fas fa-bolt mr-1"></i>Transmisi Otomatis
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-800">
                                <i class="fas fa-sync-alt mr-1"></i>Manual & Matic
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button onclick="showInstructorDetail(<?= htmlspecialchars(json_encode($inst)) ?>)" 
                                class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 text-sm">
                            <i class="fas fa-eye mr-1"></i>Lihat Profil
                        </button>
                        <a href="https://wa.me/6281234567890?text=Halo,%20saya%20tertarik%20belajar%20dengan%20instruktur%20<?= urlencode($inst['nama_lengkap']) ?>%20di%20Krishna%20Driving.%20Saya%20ingin%20konsultasi%20terlebih%20dahulu."
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
        <div class="max-w-2xl mx-auto">
            <h3 class="text-2xl font-bold mb-4">Siap Belajar Mengemudi?</h3>
            <p class="text-blue-100 mb-6">
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
                            <div class="text-lg font-bold text-green-600">Profesional</div>
                            <div class="text-sm text-gray-600">Sertifikasi</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Spesialisasi:</h4>
                        <span id="modalSpecialization" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold">
                            <!-- Spesialisasi -->
                        </span>
                    </div>

                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Keahlian:</h4>
                        <div id="modalSkills" class="flex flex-wrap gap-2">
                            <!-- Keahlian akan dimuat via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
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

<script>
    // Filter functionality
    function filterInstructors() {
        const selectedSpesialisasi = document.getElementById('filter-spesialisasi').value;
        const selectedPengalaman = document.getElementById('filter-pengalaman').value;
        
        const instructors = document.querySelectorAll('.instructor-card');
        
        instructors.forEach(instructor => {
            const spesialisasi = instructor.dataset.spesialisasi;
            const pengalaman = parseInt(instructor.dataset.pengalaman);
            
            // Check filters
            const spesialisasiMatch = selectedSpesialisasi === 'all' || spesialisasi === selectedSpesialisasi;
            const pengalamanMatch = selectedPengalaman === 'all' || pengalaman >= parseInt(selectedPengalaman);
            
            if (spesialisasiMatch && pengalamanMatch) {
                instructor.style.display = 'block';
            } else {
                instructor.style.display = 'none';
            }
        });
    }
    
    // Modal functionality
    function showInstructorDetail(instructor) {
        const modal = document.getElementById('instructorModal');
        const photoContainer = document.getElementById('modalPhoto');
        
        // Set modal content
        document.getElementById('modalName').textContent = instructor.nama_lengkap;
        document.getElementById('modalLicense').textContent = `Lisensi: ${instructor.nomor_licensi}`;
        document.getElementById('modalExperience').textContent = `${instructor.pengalaman_tahun}+`;
        document.getElementById('modalDescription').textContent = instructor.deskripsi || 'Instruktur profesional berpengalaman dengan metode pengajaran yang mudah dipahami.';
        
        // Set photo
        if (instructor.foto) {
            photoContainer.innerHTML = `<img src="assets/images/instruktur/${instructor.foto}" alt="${instructor.nama_lengkap}" class="w-32 h-32 rounded-full object-cover border-4 border-white">`;
        } else {
            photoContainer.innerHTML = '<i class="fas fa-user text-4xl text-white"></i>';
        }
        
        // Set specialization badge
        const specializationBadge = document.getElementById('modalSpecialization');
        specializationBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${
            instructor.spesialisasi === 'manual' ? 'bg-yellow-100 text-yellow-800' :
            instructor.spesialisasi === 'matic' ? 'bg-purple-100 text-purple-800' :
            'bg-green-100 text-green-800'
        }`;
        specializationBadge.innerHTML = `<i class="fas fa-${
            instructor.spesialisasi === 'manual' ? 'cog' : 
            instructor.spesialisasi === 'matic' ? 'bolt' : 'sync-alt'
        } mr-1"></i>${instructor.spesialisasi.charAt(0).toUpperCase() + instructor.spesialisasi.slice(1)}`;
        
        // Set skills
        const skillsContainer = document.getElementById('modalSkills');
        const skills = [
            { name: 'Praktik Mengemudi', icon: 'road', color: 'blue' },
            { name: 'Teori Berkendara', icon: 'book', color: 'green' },
            { name: 'Safety Driving', icon: 'shield-alt', color: 'red' }
        ];
        
        if (instructor.spesialisasi === 'manual') {
            skills.push({ name: 'Kopling & Gigi', icon: 'cog', color: 'yellow' });
        } else if (instructor.spesialisasi === 'matic') {
            skills.push({ name: 'Transmisi Otomatis', icon: 'bolt', color: 'purple' });
        } else {
            skills.push({ name: 'Manual & Matic', icon: 'sync-alt', color: 'indigo' });
        }
        
        skillsContainer.innerHTML = skills.map(skill => 
            `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-${skill.color}-100 text-${skill.color}-800">
                <i class="fas fa-${skill.icon} mr-1"></i>${skill.name}
            </span>`
        ).join('');
        
        // Set WhatsApp link
        const whatsappLink = document.getElementById('modalWhatsApp');
        whatsappLink.href = `https://wa.me/6281234567890?text=Halo,%20saya%20tertarik%20belajar%20dengan%20instruktur%20${encodeURIComponent(instructor.nama_lengkap)}%20di%20Krishna%20Driving.%20Saya%20ingin%20konsultasi%20terlebih%20dahulu.`;
        
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
        
        // Close modal when clicking outside
        document.getElementById('instructorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });
</script>

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>