<?php
require_once 'config/database.php';
include 'includes/header.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data testimoni yang sudah disetujui dari database
try {
    // Ambil semua testimoni yang disetujui
    $testimoni_query = $db->query("SELECT * FROM testimoni WHERE status = 'disetujui' ORDER BY rating DESC, created_at DESC");
    $testimoni = $testimoni_query->fetchAll(PDO::FETCH_ASSOC);

    // Hitung statistik
    $total_testimoni = count($testimoni);
    $average_rating = $db->query("SELECT AVG(rating) as avg_rating FROM testimoni WHERE status = 'disetujui'")->fetch()['avg_rating'];
    $average_rating = number_format($average_rating, 1);
    
    // Ambil testimoni dengan rating tertinggi
    $top_testimoni = $db->query("SELECT * FROM testimoni WHERE status = 'disetujui' ORDER BY rating DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    // Hitung distribusi rating
    $rating_distribution = [];
    for ($i = 1; $i <= 5; $i++) {
        $count = $db->query("SELECT COUNT(*) as count FROM testimoni WHERE status = 'disetujui' AND rating = $i")->fetch()['count'];
        $rating_distribution[$i] = $count;
    }

} catch (PDOException $e) {
    $testimoni = [];
    $total_testimoni = 0;
    $average_rating = 0;
    $top_testimoni = [];
    $rating_distribution = array_fill(1, 5, 0);
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni Siswa - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

    <!-- Header Section -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Testimoni Siswa</h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                Lihat pengalaman langsung dari siswa-siswa yang telah belajar mengemudi bersama kami
            </p>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600"><?= $total_testimoni ?></div>
                    <div class="text-gray-600 text-sm">Total Testimoni</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600"><?= $average_rating ?></div>
                    <div class="text-gray-600 text-sm">Rating Rata-rata</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">100%</div>
                    <div class="text-gray-600 text-sm">Kepuasan Siswa</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Rating Distribution -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Distribusi Rating</h2>
            <div class="max-w-2xl mx-auto space-y-3">
                <?php for ($i = 5; $i >= 1; $i--): 
                    $count = $rating_distribution[$i] ?? 0;
                    $percentage = $total_testimoni > 0 ? ($count / $total_testimoni) * 100 : 0;
                ?>
                <div class="flex items-center">
                    <div class="w-16 text-right mr-4">
                        <span class="text-yellow-400">
                            <?= str_repeat('★', $i) ?><?= str_repeat('☆', 5 - $i) ?>
                        </span>
                    </div>
                    <div class="flex-1 bg-gray-200 rounded-full h-4">
                        <div class="bg-yellow-400 h-4 rounded-full" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <div class="w-16 text-right ml-4 text-sm text-gray-600">
                        <?= $count ?> (<?= number_format($percentage, 1) ?>%)
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Top Testimonials -->
        <?php if (!empty($top_testimoni)): ?>
        <div class="mb-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Testimoni Terbaik</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($top_testimoni as $testi): ?>
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl p-6 text-white transform hover:scale-105 transition duration-300">
                    <div class="flex items-center mb-4">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <?php if (!empty($testi['foto_siswa'])): ?>
                                <img src="assets/images/testimoni/<?= $testi['foto_siswa'] ?>" 
                                     alt="<?= htmlspecialchars($testi['nama_siswa']) ?>" 
                                     class="w-16 h-16 rounded-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg"><?= htmlspecialchars($testi['nama_siswa']) ?></h4>
                            <p class="text-yellow-100 text-sm"><?= htmlspecialchars($testi['paket_kursus']) ?></p>
                        </div>
                    </div>
                    <div class="text-yellow-200 mb-4">
                        <?= str_repeat('★', $testi['rating']) ?><?= str_repeat('☆', 5 - $testi['rating']) ?>
                    </div>
                    <p class="text-yellow-100 italic mb-4">
                        "<?= htmlspecialchars($testi['testimoni_text']) ?>"
                    </p>
                    <div class="text-yellow-200 text-sm">
                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($testi['lokasi']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Semua Testimoni</h2>
                    <p class="text-gray-600">Filter berdasarkan rating dan paket kursus</p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-wrap gap-4">
                    <select id="filter-rating" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Rating</option>
                        <option value="5">5 Bintang</option>
                        <option value="4">4 Bintang</option>
                        <option value="3">3 Bintang</option>
                    </select>
                    <select id="filter-paket" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Paket</option>
                        <option value="Manual">Manual</option>
                        <option value="Matic">Matic</option>
                        <option value="Kombinasi">Kombinasi</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- All Testimonials Grid -->
        <?php if (empty($testimoni)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Belum Ada Testimoni</h3>
                <p class="text-gray-500">Jadilah yang pertama memberikan testimoni!</p>
            </div>
        <?php else: ?>
            <div id="testimonialsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($testimoni as $testi): ?>
                <div class="testimonial-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border border-gray-100"
                     data-rating="<?= $testi['rating'] ?>"
                     data-paket="<?= htmlspecialchars($testi['paket_kursus']) ?>">
                    
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                <?php if (!empty($testi['foto_siswa'])): ?>
                                    <img src="assets/images/testimoni/<?= $testi['foto_siswa'] ?>" 
                                         alt="<?= htmlspecialchars($testi['nama_siswa']) ?>" 
                                         class="w-14 h-14 rounded-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-blue-600 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($testi['nama_siswa']) ?></h3>
                                <p class="text-gray-600 text-sm"><?= htmlspecialchars($testi['paket_kursus']) ?></p>
                                <?php if ($testi['usia']): ?>
                                    <p class="text-gray-500 text-xs"><?= $testi['usia'] ?> tahun</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Rating -->
                        <div class="text-yellow-400 text-center mb-4">
                            <?= str_repeat('★', $testi['rating']) ?><?= str_repeat('☆', 5 - $testi['rating']) ?>
                            <span class="text-gray-600 text-sm ml-2">(<?= $testi['rating'] ?>/5)</span>
                        </div>

                        <!-- Testimoni Text -->
                        <p class="text-gray-600 text-center italic mb-6 leading-relaxed">
                            "<?= htmlspecialchars($testi['testimoni_text']) ?>"
                        </p>

                        <!-- Footer -->
                        <div class="flex justify-between items-center text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i>
                                <span><?= htmlspecialchars($testi['lokasi']) ?></span>
                            </div>
                            <div>
                                <?= date('d M Y', strtotime($testi['tanggal_testimoni'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<!-- Add Testimoni Section -->
<div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 text-white mt-12">
    <div class="text-center mb-6">
        <h3 class="text-2xl font-bold mb-2">Bagikan Pengalaman Anda</h3>
        <p class="text-blue-100">Ceritakan pengalaman belajar mengemudi Anda di Krishna Driving</p>
    </div>
    
    <form id="testimoniForm" action="process/submit_testimoni.php" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-blue-100 mb-2">Nama Lengkap *</label>
                <input type="text" name="nama_siswa" required
                       class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white placeholder-blue-200 focus:ring-2 focus:ring-white focus:border-white"
                       placeholder="Nama Anda">
            </div>
            <div>
                <label class="block text-sm font-medium text-blue-100 mb-2">Paket Kursus *</label>
                <select name="paket_kursus" required
                        class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white focus:ring-2 focus:ring-white focus:border-white">
                    <option value="">Pilih Paket</option>
                    <option value="Kursus Manual">Kursus Manual</option>
                    <option value="Kursus Matic">Kursus Matic</option>
                    <option value="Kursus Kombinasi">Kursus Kombinasi</option>
                    <option value="Kursus Intensive">Kursus Intensive</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-blue-100 mb-2">Usia</label>
                <input type="number" name="usia" min="17" max="70"
                       class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white placeholder-blue-200 focus:ring-2 focus:ring-white focus:border-white"
                       placeholder="Usia Anda">
            </div>
            <div>
                <label class="block text-sm font-medium text-blue-100 mb-2">Lokasi *</label>
                <input type="text" name="lokasi" required
                       class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white placeholder-blue-200 focus:ring-2 focus:ring-white focus:border-white"
                       placeholder="Kota tempat tinggal">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-blue-100 mb-2">Foto Siswa</label>
            <input type="file" name="foto_siswa" accept="image/*"
                   class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white file:bg-blue-600 file:border-0 file:text-white file:px-4 file:py-2 file:rounded-lg file:mr-4">
            <p class="text-xs text-blue-200 mt-1">Format: JPG, JPEG, PNG (max 2MB)</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-blue-100 mb-2">Rating *</label>
            <div class="flex justify-center space-x-2" id="ratingStars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="text-2xl text-gray-300 hover:text-yellow-300 transition duration-200" 
                        data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>)">
                    ★
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="selectedRating" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-blue-100 mb-2">Testimoni *</label>
            <textarea name="testimoni_text" rows="4" required
                      class="w-full px-3 py-2 bg-blue-500 border border-blue-400 rounded-lg text-white placeholder-blue-200 focus:ring-2 focus:ring-white focus:border-white"
                      placeholder="Ceritakan pengalaman belajar mengemudi Anda..."></textarea>
        </div>

        <div class="text-center">
            <button type="submit" 
                    class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Testimoni
            </button>
        </div>
    </form>
</div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Testimoni Terkirim!</h3>
            <p class="text-gray-600 mb-4">Terima kasih telah berbagi pengalaman Anda. Testimoni Anda akan ditinjau terlebih dahulu sebelum ditampilkan.</p>
            <button onclick="closeSuccessModal()" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                Tutup
            </button>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Rating System
let currentRating = 0;

function setRating(rating) {
    currentRating = rating;
    document.getElementById('selectedRating').value = rating;
    
    const stars = document.querySelectorAll('#ratingStars button');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
        }
    });
}

// Filter functionality
function filterTestimonials() {
    const selectedRating = document.getElementById('filter-rating').value;
    const selectedPaket = document.getElementById('filter-paket').value;
    
    const testimonials = document.querySelectorAll('.testimonial-card');
    let visibleCount = 0;
    
    testimonials.forEach(testimonial => {
        const rating = testimonial.dataset.rating;
        const paket = testimonial.dataset.paket.toLowerCase();
        
        // Check filters
        const ratingMatch = selectedRating === 'all' || rating === selectedRating;
        const paketMatch = selectedPaket === 'all' || paket.includes(selectedPaket.toLowerCase());
        
        if (ratingMatch && paketMatch) {
            testimonial.style.display = 'block';
            visibleCount++;
        } else {
            testimonial.style.display = 'none';
        }
    });
}

// Form submission dengan AJAX
document.getElementById('testimoniForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentRating) {
        alert('Silakan berikan rating!');
        return;
    }
    
    // Validasi form
    const formData = new FormData(this);
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
    submitButton.disabled = true;
    
    // Kirim data via AJAX
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Tampilkan modal sukses
            document.getElementById('successModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Reset form
            this.reset();
            setRating(0);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim testimoni.');
    })
    .finally(() => {
        // Reset button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Filter events
    const filterRating = document.getElementById('filter-rating');
    const filterPaket = document.getElementById('filter-paket');
    
    if (filterRating) filterRating.addEventListener('change', filterTestimonials);
    if (filterPaket) filterPaket.addEventListener('change', filterTestimonials);
    
    // Close modal when clicking outside
    const successModal = document.getElementById('successModal');
    if (successModal) {
        successModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessModal();
            }
        });
    }
});
    </script>
</body>
</html>