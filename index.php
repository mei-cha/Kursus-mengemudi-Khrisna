<?php
// Mulai session dan include konfigurasi database
session_start();
require_once 'config/database.php';

// Buat koneksi database
$database = new Database();
$db = $database->getConnection();

// Query untuk mendapatkan data paket kursus - DIMODIFIKASI
try {
    $paket_query = $db->query("SELECT *, 
        CASE 
            WHEN tipe_mobil = 'manual' THEN 'Manual'
            WHEN tipe_mobil = 'matic' THEN 'Matic'
            WHEN tipe_mobil = 'keduanya' THEN 'Keduanya'
            ELSE tipe_mobil 
        END as tipe_mobil_text 
        FROM paket_kursus 
        WHERE aktif = 1 
        ORDER BY harga");
    $paket_kursus = $paket_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paket_kursus = [];
    error_log("Database error (paket): " . $e->getMessage());
}

// Query untuk mendapatkan data instruktur
try {
    $instruktur_query = $db->query("SELECT * FROM instruktur WHERE aktif = 1 ORDER BY rating DESC LIMIT 4");
    $instruktur = $instruktur_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $instruktur = [];
    error_log("Database error (instruktur): " . $e->getMessage());
}

// Query untuk mendapatkan testimoni
try {
    $testimoni_query = $db->query("SELECT * FROM testimoni WHERE status = 'disetujui' ORDER BY tanggal_testimoni DESC LIMIT 4");
    $testimoni = $testimoni_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimoni = [];
    error_log("Database error (testimoni): " . $e->getMessage());
}

// Query untuk mendapatkan galeri
try {
    $galeri_query = $db->query("SELECT * FROM galeri WHERE aktif = 1 ORDER BY tanggal_upload DESC LIMIT 8");
    $galeri = $galeri_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $galeri = [];
    error_log("Database error (galeri): " . $e->getMessage());
}

include 'includes/header.php';

// Hitung tanggal minimum (17 tahun yang lalu dari hari ini)
$today = new DateTime();
$minDate = clone $today;
$minDate->modify('-17 years');
$minDateStr = $minDate->format('Y-m-d');

// Hitung tanggal maksimum (80 tahun yang lalu dari hari ini - untuk validasi saja)
$maxDate = clone $today;
$maxDate->modify('-80 years');
$maxDateStr = $maxDate->format('Y-m-d');
?>
<style>
    .scrollbar-hide {
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    /* Styling untuk select yang disabled */
    select:disabled {
        cursor: not-allowed;
        background-color: #f9fafb;
    }

    /* Styling untuk badge tipe mobil */
    .badge-tipe-mobil {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        display: inline-block;
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
    }

    /* Animasi untuk transisi */
    .transition-all {
        transition: all 0.3s ease;
    }

    /* Styling untuk error messages */
    .error-message {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: none;
    }

    .error-input {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }

    .error-label {
        color: #ef4444 !important;
    }

    .success-input {
        border-color: #10b981 !important;
        background-color: #f0fdf4 !important;
    }

    /* Styling untuk valid indicator */
    .valid-indicator {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #10b981;
        display: none;
    }

    .input-wrapper {
        position: relative;
    }

    /* Styling untuk info messages */
    .info-message {
        color: #3b82f6;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    /* Styling untuk total harga */
    #totalHargaContainer {
        transition: all 0.3s ease;
    }

    /* Animasi untuk perubahan harga */
    @keyframes priceUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .price-update {
        animation: priceUpdate 0.5s ease;
    }
    /* Tambahkan di style section */
select[readonly] {
    background-color: #f9fafb !important;
    cursor: not-allowed !important;
}

select[readonly]:focus {
    border-color: #d1d5db !important;
    box-shadow: none !important;
}
</style>

<!-- Hero Section -->
<section id="beranda" class="bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
            Kursus Mengemudi Mobil <span class="text-yellow-300">Profesional</span>
        </h1>
        <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto leading-relaxed">
            Belajar mengemudi dengan instruktur berpengalaman, metode terbaik, dan garansi sampai bisa
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="#daftar"
                class="bg-white text-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-gray-100 transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="fas fa-edit mr-2"></i>Daftar Sekarang
            </a>
            <a href="#paket-kursus"
                class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition duration-300">
                <i class="fas fa-gift mr-2"></i>Lihat Paket
            </a>
        </div>
    </div>
</section>

<!-- Informasi Kursus Section -->
<section id="paket-kursus" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Pilihan Paket Kursus</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">Pilih kategori paket yang sesuai dengan kebutuhan belajar
                mengemudi Anda</p>
        </div>

        <!-- Semua Kartu Paket Menggunakan Warna Biru -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php
            // Kelompokkan paket berdasarkan kategori
            $kategori_paket = [
                'reguler' => [
                    'icon' => 'fas fa-car',
                    'nama' => 'Paket Reguler',
                    'deskripsi' => 'Kursus standar dengan jadwal reguler',
                    'warna' => 'from-blue-600 via-blue-700 to-blue-800'
                ],
                'campuran' => [
                    'icon' => 'fas fa-cogs',
                    'nama' => 'Paket Campuran',
                    'deskripsi' => 'Paket manual dan matic',
                    'warna' => 'from-blue-600 via-blue-700 to-blue-800'
                ],
                'extra' => [
                    'icon' => 'fas fa-moon',
                    'nama' => 'Paket Extra',
                    'deskripsi' => 'Kursus dengan waktu flexibel',
                    'warna' => 'from-blue-600 via-blue-700 to-blue-800'
                ],
                'pelancaran' => [
                    'icon' => 'fas fa-bolt',
                    'nama' => 'Paket Pelancaran',
                    'deskripsi' => 'Kursus singkat untuk pelancaran',
                    'warna' => 'from-blue-600 via-blue-700 to-blue-800'
                ]
            ];
            
            foreach ($kategori_paket as $kategori => $data):
                // Filter paket berdasarkan kategori dari nama_paket
                $paket_kategori = array_filter($paket_kursus, function($pkg) use ($kategori) {
                    return stripos($pkg['nama_paket'], $kategori) !== false;
                });
                
                // Ambil harga terendah dari paket dalam kategori ini
                $harga_terendah = $paket_kategori ? min(array_column($paket_kategori, 'harga')) : 0;
            ?>
            <div
                class="bg-gradient-to-br <?= $data['warna'] ?> text-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                <div
                    class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="<?= $data['icon'] ?> text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2"><?= $data['nama'] ?></h3>
                <p class="text-blue-100 mb-4 text-sm"><?= $data['deskripsi'] ?></p>
                
                <!-- Tampilkan variasi tipe mobil yang tersedia -->
                <?php if ($paket_kategori): ?>
                    <div class="mb-3">
                        <?php 
                        $tipe_mobil_available = array_unique(array_column($paket_kategori, 'tipe_mobil_text'));
                        foreach ($tipe_mobil_available as $tipe): 
                        ?>
                            <span class="inline-block bg-white bg-opacity-20 text-xs px-2 py-1 rounded mr-1 mb-1">
                                <?= $tipe ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-2xl font-bold mb-4">Mulai Rp <?= number_format($harga_terendah, 0, ',', '.') ?></div>
                <a href="paket-kursus.php"
    class="block w-full bg-white text-blue-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
    Lihat Detail
</a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA Tetap Biru -->
        <div class="text-center mt-8">
            <a href="paket-kursus.php"
                class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300 shadow-lg hover:shadow-xl">
                <i class="fas fa-list mr-2"></i>Lihat Semua Paket
            </a>
        </div>
    </div>
</section>

<!-- Instruktur Profesional Section -->
<section id="instruktur" class="py-16 px-4 md:px-8 lg:px-16 bg-white">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center gap-12">
        <div class="md:w-1/2">
            <p class="text-blue-600 font-medium uppercase tracking-widest mb-2">
                Tim Instruktur
            </p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                Belajar dengan<br />Instruktur Terbaik
            </h2>

            <div class="space-y-8">
                <!-- Instruktur 1 -->
                <div class="flex items-start gap-5">
                    <div class="w-12 h-12 flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-tie text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">
                            Instruktur Bersertifikat
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            Semua instruktur kami memiliki sertifikasi resmi dan pengalaman mengajar minimal 5 tahun.
                            Mereka terlatih dengan metode mengajar yang efektif.
                        </p>
                    </div>
                </div>

                <!-- Instruktur 2 -->
                <div class="flex items-start gap-5">
                    <div class="w-12 h-12 flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-award text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">
                            Pengalaman Luas
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            Instruktur kami memiliki pengalaman mengajar berbagai tipe siswa, dari pemula total
                            hingga yang membutuhkan pelatihan khusus.
                        </p>
                    </div>
                </div>

                <!-- Instruktur 3 -->
                <div class="flex items-start gap-5">
                    <div class="w-12 h-12 flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-comments text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">
                            Sabar dan Komunikatif
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            Kami memastikan instruktur kami memiliki kesabaran tinggi dan kemampuan komunikasi
                            yang baik untuk membuat Anda nyaman belajar.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:w-1/2">
            <img
                src="assets/images/logoinstr.jpg"
                alt="Tim Instruktur Profesional"
                class="w-full h-auto"
            />
        </div>
    </div>
</section>

<!-- Testimoni Section (Carousel Horizontal) -->
<section id="testimoni" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Apa Kata Siswa Kami</h2>
            <p class="text-xl text-gray-600">Testimoni dari siswa yang sudah berhasil mengemudi dengan percaya diri</p>
        </div>

        <?php if (empty($testimoni)): ?>
            <div class="text-center py-12">
                <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Belum Ada Testimoni</h3>
                <p class="text-gray-500">Jadilah yang pertama memberikan testimoni.</p>
            </div>
        <?php else: ?>
            <!-- Carousel Wrapper -->
            <div class="relative">
                <!-- Tombol Navigasi Kiri -->
                <button id="prevTestimoni"
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white shadow-lg rounded-full w-10 h-10 flex items-center justify-center hover:bg-gray-100 transition hidden md:block">
                    <i class="fas fa-chevron-left text-gray-700"></i>
                </button>

                <!-- Container Scroll -->
                <div id="testimoniContainer"
                    class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide gap-6 pb-4 px-2 md:px-0">
                    <?php foreach ($testimoni as $testi): ?>
                        <div
                            class="flex-shrink-0 w-full max-w-xs snap-start bg-gray-50 rounded-2xl p-6 shadow hover:shadow-lg transition duration-300">
                            <div class="flex items-start mb-4">
                                <div
                                    class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4 shadow-md">
                                    <?php if (!empty($testi['foto_siswa'])): ?>
                                        <img src="assets/images/testimoni/<?= $testi['foto_siswa'] ?>"
                                            alt="<?= htmlspecialchars($testi['nama_siswa']) ?>"
                                            class="w-12 h-12 rounded-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-white"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($testi['nama_siswa']) ?></h4>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($testi['paket_kursus']) ?></p>
                                </div>
                            </div>

                            <div class="text-yellow-400 mb-4">
                                <?= str_repeat('★', $testi['rating']) ?>
                                <?= str_repeat('☆', 5 - $testi['rating']) ?>
                            </div>

                            <p class="text-gray-600 italic text-sm md:text-base leading-relaxed">
                                "<?= htmlspecialchars($testi['testimoni_text']) ?>"
                            </p>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span class="flex items-center">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= $testi['lokasi'] ?? 'Jakarta' ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?= date('d M Y', strtotime($testi['tanggal_testimoni'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tombol Navigasi Kanan -->
                <button id="nextTestimoni"
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white shadow-lg rounded-full w-10 h-10 flex items-center justify-center hover:bg-gray-100 transition hidden md:block">
                    <i class="fas fa-chevron-right text-gray-700"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Form Pendaftaran Section -->
<section id="daftar" class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Daftar Kursus Sekarang</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Isi form berikut untuk memulai perjalanan mengemudi Anda bersama Krishna Driving
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
            <form id="formPendaftaran" action="proses-pendaftaran.php" method="POST" class="space-y-7" novalidate>
                <!-- Data Pribadi -->
                <div class="space-y-5">
                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-user text-blue-600"></i> Data Pribadi
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Lengkap -->
                        <div class="input-wrapper">
                            <label for="nama_lengkap" class="block text-sm text-gray-700 mb-1">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" required
                                placeholder="Nama lengkap Anda"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                            <span class="valid-indicator">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="error-message" id="nama_lengkap_error">Nama lengkap wajib diisi (minimal 3 karakter)</div>
                        </div>
                        
                        <!-- Email -->
                        <div class="input-wrapper">
                            <label for="email" class="block text-sm text-gray-700 mb-1">Email *</label>
                            <input type="email" id="email" name="email" required placeholder="email@contoh.com"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                            <span class="valid-indicator">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="error-message" id="email_error">Email tidak valid</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Telepon -->
                        <div class="input-wrapper">
                            <label for="telepon" class="block text-sm text-gray-700 mb-1">Telepon *</label>
                            <input type="tel" id="telepon" name="telepon" required 
                                placeholder="081234567890"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                            <span class="valid-indicator">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="error-message" id="telepon_error">Format nomor HP tidak valid (contoh: 081234567890)</div>
                            <div class="info-message">Format: 08xxxxxxxxxx (10-13 digit)</div>
                        </div>
                        
                        <!-- Tanggal Lahir -->
                        <div class="input-wrapper">
                            <label for="tanggal_lahir" class="block text-sm text-gray-700 mb-1">Tanggal Lahir *</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                                max="<?= $minDateStr ?>"
                                min="<?= $maxDateStr ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                            <span class="valid-indicator">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="error-message" id="tanggal_lahir_error">Minimal usia 17 tahun untuk mengikuti kursus</div>
                            <div class="info-message">Minimal usia: 17 tahun</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Jenis Kelamin -->
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Jenis Kelamin *</label>
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center">
                                    <input type="radio" id="jenis_kelamin_l" name="jenis_kelamin" value="L" required
                                        class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Laki-laki</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" id="jenis_kelamin_p" name="jenis_kelamin" value="P"
                                        class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Perempuan</span>
                                </label>
                            </div>
                            <div class="error-message" id="jenis_kelamin_error">Pilih jenis kelamin</div>
                        </div>
                        
                        <!-- Pengalaman Mengemudi -->
                        <div class="input-wrapper">
                            <label for="pengalaman_mengemudi" class="block text-sm text-gray-700 mb-1">Pengalaman Mengemudi</label>
                            <select id="pengalaman_mengemudi" name="pengalaman_mengemudi"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                                <option value="pemula">Pemula</option>
                                <option value="pernah_kursus">Pernah kursus</option>
                                <option value="pernah_ujian">Pernah ujian</option>
                            </select>
                        </div>
                        
                        <!-- Alamat -->
                        <div class="input-wrapper">
                            <label for="alamat" class="block text-sm text-gray-700 mb-1">Alamat *</label>
                            <textarea id="alamat" name="alamat" rows="1" required placeholder="Alamat lengkap"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                            <span class="valid-indicator">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="error-message" id="alamat_error">Alamat wajib diisi (minimal 10 karakter)</div>
                        </div>
                    </div>
                </div>

                <!-- Preferensi Kursus -->
<div class="space-y-5 pt-4 border-t border-gray-200">
    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
        <i class="fas fa-car text-blue-600"></i> Preferensi Kursus
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Paket Kursus -->
        <div class="input-wrapper">
            <label for="paket_kursus_id" class="block text-sm text-gray-700 mb-1">Paket Kursus *</label>
            <select id="paket_kursus_id" name="paket_kursus_id" required onchange="onPackageSelectChange()"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                <option value="">Pilih Paket</option>
                <?php foreach ($paket_kursus as $paket): ?>
                    <option value="<?= $paket['id'] ?>" 
                            data-harga="<?= $paket['harga'] ?>"
                            data-tipe-mobil="<?= $paket['tipe_mobil'] ?>">
                        <?= htmlspecialchars($paket['nama_paket']) ?> 
                        (<?= $paket['tipe_mobil_text'] ?? ucfirst($paket['tipe_mobil']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="valid-indicator">
                <i class="fas fa-check"></i>
            </span>
            <div class="error-message" id="paket_kursus_id_error">Pilih paket kursus</div>
            <p class="text-xs text-gray-500 mt-1">Pilih paket, tipe mobil akan otomatis terisi</p>
        </div>
        
        <!-- Tipe Mobil -->
<div class="input-wrapper">
    <label for="tipe_mobil" class="block text-sm text-gray-700 mb-1">Tipe Mobil *</label>
    <select id="tipe_mobil" name="tipe_mobil" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition bg-gray-100" 
        style="pointer-events: none; cursor: not-allowed;"> <!-- READONLY STYLE -->
        <option value="">Pilih Paket Dulu</option>
        <option value="manual">Manual</option>
        <option value="matic">Matic</option>
        <option value="keduanya">Keduanya</option>
    </select>
    <span class="valid-indicator">
        <i class="fas fa-check"></i>
    </span>
    <div class="error-message" id="tipe_mobil_error">Tipe mobil wajib diisi</div>
    <div id="tipeMobilNote" class="text-xs text-blue-600 mt-1 hidden">
        <i class="fas fa-info-circle mr-1"></i>Tipe mobil otomatis mengikuti paket yang dipilih
    </div>
</div>
        
        <!-- Jadwal Preferensi -->
        <div class="input-wrapper">
            <label for="jadwal_preferensi" class="block text-sm text-gray-700 mb-1">Jadwal Preferensi *</label>
            <select id="jadwal_preferensi" name="jadwal_preferensi" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                <option value="">Pilih Jadwal</option>
                <option value="pagi">Pagi</option>
                <option value="siang">Siang</option>
                <option value="sore">Sore</option>
            </select>
            <span class="valid-indicator">
                <i class="fas fa-check"></i>
            </span>
            <div class="error-message" id="jadwal_preferensi_error">Pilih jadwal preferensi</div>
        </div>
    </div>
</div>

                <!-- Kontak Darurat & Kondisi Medis -->
                <div class="space-y-5 pt-4 border-t border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-ambulance text-blue-600"></i> Informasi Tambahan
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Kontak Darurat -->
                        <div class="input-wrapper">
                            <label for="nama_kontak_darurat" class="block text-sm text-gray-700 mb-1">Nama Kontak
                                Darurat</label>
                            <input type="text" id="nama_kontak_darurat" name="nama_kontak_darurat"
                                placeholder="Nama keluarga/teman"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                        </div>
                        
                        <!-- Nomor Kontak Darurat -->
                        <div class="input-wrapper">
                            <label for="kontak_darurat" class="block text-sm text-gray-700 mb-1">Nomor Kontak
                                Darurat</label>
                            <input type="tel" id="kontak_darurat" name="kontak_darurat" 
                                placeholder="081234567890"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition">
                            <div class="error-message" id="kontak_darurat_error">Format nomor HP tidak valid</div>
                            <div class="info-message">Format: 08xxxxxxxxxx</div>
                        </div>
                    </div>

                    <!-- Kondisi Medis -->
                    <div>
                        <label for="kondisi_medis" class="block text-sm text-gray-700 mb-1">Kondisi Medis
                            (Opsional)</label>
                        <textarea id="kondisi_medis" name="kondisi_medis" rows="2"
                            placeholder="Alergi, riwayat sakit, atau kondisi khusus lainnya"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none"></textarea>
                    </div>
                </div>

                <!-- Total Harga -->
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center bg-blue-50 p-4 rounded-lg">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 mb-1">Total Biaya</h4>
                            <p class="text-sm text-gray-600">Harga paket yang Anda pilih</p>
                        </div>
                        <div id="totalHargaContainer" class="text-right">
                            <div class="text-3xl font-bold text-blue-600" id="totalHarga">Rp 0</div>
                            <div class="text-sm text-gray-500" id="paketNama">Pilih paket untuk melihat harga</div>
                        </div>
                    </div>
                </div>

                <!-- Persetujuan -->
                <div class="pt-2">
                    <div class="flex items-start">
                        <input type="checkbox" id="persetujuan" name="persetujuan" required
                            class="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <label for="persetujuan" class="ml-2 text-sm text-gray-700">
                            Saya menyetujui membawa KTP asli saat datang ke lokasi untuk verifikasi pendaftaran. *
                        </label>
                    </div>
                    <div class="error-message" id="persetujuan_error">Anda harus menyetujui persyaratan ini</div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition duration-300 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        Kirim Pendaftaran
                    </button>
                </div>
                
                <!-- Form Status -->
                <div id="formStatus" class="hidden p-4 rounded-lg text-center"></div>
            </form>
        </div>
    </div>
</section>

<script>
    // Data paket dari PHP di-convert ke JavaScript
    const allPackages = <?= json_encode($paket_kursus) ?>;
    let selectedPackagePrice = 0;
    let selectedPackageName = '';

    // Fungsi untuk mengupdate total harga
    function updateTotalHarga() {
        const paketSelect = document.getElementById('paket_kursus_id');
        const totalHargaElement = document.getElementById('totalHarga');
        const paketNamaElement = document.getElementById('paketNama');
        
        if (paketSelect.value) {
            const selectedOption = paketSelect.options[paketSelect.selectedIndex];
            selectedPackagePrice = parseInt(selectedOption.getAttribute('data-harga')) || 0;
            selectedPackageName = selectedOption.text;
            
            // Format harga
            const formattedPrice = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(selectedPackagePrice);
            
            totalHargaElement.textContent = formattedPrice;
            totalHargaElement.classList.add('price-update');
            setTimeout(() => {
                totalHargaElement.classList.remove('price-update');
            }, 500);
            
            paketNamaElement.textContent = selectedPackageName;
        } else {
            selectedPackagePrice = 0;
            selectedPackageName = '';
            totalHargaElement.textContent = 'Rp 0';
            paketNamaElement.textContent = 'Pilih paket untuk melihat harga';
        }
    }

    // Format nomor telepon saat input
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        // Pastikan dimulai dengan 0
        if (!value.startsWith('0')) {
            value = '0' + value;
        }
        
        // Batasi panjang 10-13 digit (termasuk 0)
        if (value.length > 13) {
            value = value.substring(0, 13);
        }
        
        input.value = value;
        
        // Validasi format
        const phonePattern = /^0[0-9]{9,12}$/;
        return phonePattern.test(value);
    }

    // Validasi umur dari tanggal lahir
    function validateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age >= 17;
    }

    // Format tanggal untuk ditampilkan
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    // Tampilkan/sembunyikan error message
    function showError(inputId, message) {
        const input = document.getElementById(inputId);
        const errorElement = document.getElementById(inputId + '_error');
        const validIndicator = input.parentElement.querySelector('.valid-indicator');
        
        if (input) {
            input.classList.remove('success-input');
            input.classList.add('error-input');
            
            // Tambahkan class error ke label
            const label = input.parentElement.querySelector('label');
            if (label) {
                label.classList.add('error-label');
            }
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        
        if (validIndicator) {
            validIndicator.style.display = 'none';
        }
    }

    function showSuccess(inputId) {
        const input = document.getElementById(inputId);
        const errorElement = document.getElementById(inputId + '_error');
        const validIndicator = input.parentElement.querySelector('.valid-indicator');
        
        if (input) {
            input.classList.remove('error-input');
            input.classList.add('success-input');
            
            // Hapus class error dari label
            const label = input.parentElement.querySelector('label');
            if (label) {
                label.classList.remove('error-label');
            }
        }
        
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        
        if (validIndicator) {
            validIndicator.style.display = 'block';
        }
    }

    function resetValidation(input) {
        if (!input) return;
        
        input.classList.remove('error-input', 'success-input');
        
        const errorElement = document.getElementById(input.id + '_error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        
        const label = input.parentElement.querySelector('label');
        if (label) {
            label.classList.remove('error-label');
        }
        
        const validIndicator = input.parentElement.querySelector('.valid-indicator');
        if (validIndicator) {
            validIndicator.style.display = 'none';
        }
    }

    // Validasi select element
    function validateSelect(selectElement) {
        if (!selectElement.value) {
            showError(selectElement.id, 'Pilihan ini wajib diisi');
            return false;
        } else {
            showSuccess(selectElement.id);
            return true;
        }
    }

    // Validasi radio buttons
    function validateRadioButtons(name) {
        const radioButtons = document.querySelectorAll(`input[name="${name}"]:checked`);
        if (radioButtons.length === 0) {
            showError(name, 'Pilihan ini wajib diisi');
            return false;
        } else {
            const errorElement = document.getElementById(name + '_error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            return true;
        }
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Fungsi untuk menangani select yang disabled agar nilainya dikirim
    function enableDisabledSelectForSubmit(selectElement) {
        if (selectElement.disabled) {
            // Simpan status disabled
            const wasDisabled = selectElement.disabled;
            // Enable sementara untuk submit
            selectElement.disabled = false;
            // Set timeout untuk mengembalikan status disabled
            setTimeout(() => {
                selectElement.disabled = wasDisabled;
            }, 100);
        }
    }

    // Fungsi pilih paket untuk form pendaftaran
    function pilihPaket(paketId, tipeMobil, namaPaket) {
    const selectElement = document.getElementById('paket_kursus_id');
    const tipeMobilSelect = document.getElementById('tipe_mobil');
    const tipeMobilNote = document.getElementById('tipeMobilNote');
    
    if (selectElement && tipeMobilSelect) {
        // Set nilai paket kursus
        selectElement.value = paketId;
        
        // Set nilai tipe mobil sesuai dengan paket yang dipilih
        tipeMobilSelect.value = tipeMobil.toLowerCase();
        
        // Tampilkan note
        if (tipeMobilNote) {
            tipeMobilNote.classList.remove('hidden');
        }
        
        // Update total harga
        updateTotalHarga();
        
        // Validasi visual
        validateSelect(selectElement);
        validateSelect(tipeMobilSelect);
            
            // Scroll ke form pendaftaran
            document.getElementById('daftar').scrollIntoView({
                behavior: 'smooth'
            });

            // Tampilkan notifikasi
            Swal.fire({
                icon: 'success',
                title: 'Paket Dipilih!',
                html: `Paket <strong>${namaPaket}</strong> telah dipilih.<br>Total biaya: <strong>Rp ${formatNumber(selectedPackagePrice)}</strong>`,
                confirmButtonText: 'Lanjutkan',
                timer: 3000,
                timerProgressBar: true
            });

            // Tutup detail section
            hidePackageDetail();
        }
    }

    // Fungsi untuk menyesuaikan tipe mobil ketika paket dipilih dari dropdown
    function onPackageSelectChange() {
    const paketSelect = document.getElementById('paket_kursus_id');
    const tipeMobilSelect = document.getElementById('tipe_mobil');
    const tipeMobilNote = document.getElementById('tipeMobilNote');
    const selectedPaketId = paketSelect.value;
    
    // Update total harga
    updateTotalHarga();
    
    // Validasi paket
    validateSelect(paketSelect);
    
    if (!selectedPaketId) {
        // Reset jika tidak ada paket yang dipilih
        tipeMobilSelect.style.backgroundColor = '#f9fafb';
        tipeMobilSelect.value = '';
        if (tipeMobilNote) {
            tipeMobilNote.classList.add('hidden');
        }
        // Reset validasi tipe mobil
        resetValidation(tipeMobilSelect);
        return;
    }
    
    // Cari paket yang dipilih dari allPackages
    const selectedPaket = allPackages.find(pkg => pkg.id == selectedPaketId);
    
    if (selectedPaket) {
        // Set nilai tipe mobil sesuai dengan paket yang dipilih
        tipeMobilSelect.value = selectedPaket.tipe_mobil;
        
        // Tampilkan note
        if (tipeMobilNote) {
            tipeMobilNote.classList.remove('hidden');
        }
        
        // Validasi tipe mobil
        validateSelect(tipeMobilSelect);
    }
}
    // Fungsi format rupiah
    function formatRupiah(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Validasi form sebelum submit
    function validateForm() {
        let isValid = true;
        
        // Nama Lengkap
        const namaInput = document.getElementById('nama_lengkap');
        if (!namaInput.value.trim() || namaInput.value.trim().length < 3) {
            showError('nama_lengkap', 'Nama lengkap wajib diisi (minimal 3 karakter)');
            isValid = false;
        }
        
        // Email
        const emailInput = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value || !emailPattern.test(emailInput.value)) {
            showError('email', 'Email tidak valid');
            isValid = false;
        }
        
        // Telepon
        const teleponInput = document.getElementById('telepon');
        const phonePattern = /^0[0-9]{9,12}$/;
        if (!teleponInput.value || !phonePattern.test(teleponInput.value)) {
            showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
            isValid = false;
        }
        
        // Tanggal Lahir
        const tanggalLahirInput = document.getElementById('tanggal_lahir');
        if (!tanggalLahirInput.value) {
            showError('tanggal_lahir', 'Tanggal lahir wajib diisi');
            isValid = false;
        } else if (!validateAge(tanggalLahirInput.value)) {
            showError('tanggal_lahir', 'Minimal usia 17 tahun untuk mengikuti kursus');
            isValid = false;
        }
        
        // Jenis Kelamin
        if (!validateRadioButtons('jenis_kelamin')) {
            isValid = false;
        }
        
        // Alamat
        const alamatInput = document.getElementById('alamat');
        if (!alamatInput.value.trim() || alamatInput.value.trim().length < 10) {
            showError('alamat', 'Alamat wajib diisi (minimal 10 karakter)');
            isValid = false;
        }
        
        // Paket Kursus
        const paketSelect = document.getElementById('paket_kursus_id');
        if (!validateSelect(paketSelect)) {
            isValid = false;
        }
        
        // Tipe Mobil
const tipeMobilSelect = document.getElementById('tipe_mobil');
if (!tipeMobilSelect.value) {
    showError('tipe_mobil', 'Tipe mobil wajib diisi. Pilih paket kursus terlebih dahulu.');
    isValid = false;
} else {
    showSuccess('tipe_mobil');
}
        
        // Jadwal Preferensi
        const jadwalSelect = document.getElementById('jadwal_preferensi');
        if (!validateSelect(jadwalSelect)) {
            isValid = false;
        }
        
        // Kontak Darurat (jika diisi)
        const kontakDaruratInput = document.getElementById('kontak_darurat');
        if (kontakDaruratInput.value && !/^0[0-9]{9,12}$/.test(kontakDaruratInput.value)) {
            showError('kontak_darurat', 'Format nomor HP tidak valid');
            isValid = false;
        }
        
        // Persetujuan
        const persetujuanCheckbox = document.getElementById('persetujuan');
        if (!persetujuanCheckbox.checked) {
            showError('persetujuan', 'Anda harus menyetujui persyaratan ini');
            isValid = false;
        }
        
        return isValid;
    }

    // Event Listeners untuk validasi real-time
    document.addEventListener('DOMContentLoaded', function() {
        // Validasi Nama Lengkap
        const namaInput = document.getElementById('nama_lengkap');
        if (namaInput) {
            namaInput.addEventListener('blur', function() {
                if (this.value.trim().length < 3) {
                    showError('nama_lengkap', 'Nama lengkap minimal 3 karakter');
                } else {
                    showSuccess('nama_lengkap');
                }
            });
        }

        // Validasi Email
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(this.value)) {
                    showError('email', 'Format email tidak valid');
                } else {
                    showSuccess('email');
                }
            });
        }

        // Validasi Telepon
        const teleponInput = document.getElementById('telepon');
        if (teleponInput) {
            teleponInput.addEventListener('input', function() {
                formatPhoneNumber(this);
                const isValid = /^0[0-9]{9,12}$/.test(this.value);
                if (!isValid && this.value) {
                    showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
                } else if (isValid) {
                    showSuccess('telepon');
                }
            });
            
            teleponInput.addEventListener('blur', function() {
                if (!this.value) {
                    showError('telepon', 'Nomor telepon wajib diisi');
                } else if (!/^0[0-9]{9,12}$/.test(this.value)) {
                    showError('telepon', 'Format nomor HP tidak valid (contoh: 081234567890)');
                }
            });
        }

        // Validasi Kontak Darurat
        const kontakDaruratInput = document.getElementById('kontak_darurat');
        if (kontakDaruratInput) {
            kontakDaruratInput.addEventListener('input', function() {
                formatPhoneNumber(this);
                const isValid = /^0[0-9]{9,12}$/.test(this.value);
                if (!isValid && this.value) {
                    showError('kontak_darurat', 'Format nomor HP tidak valid');
                } else if (isValid) {
                    resetValidation(this);
                }
            });
        }

        // Validasi Tanggal Lahir
        const tanggalLahirInput = document.getElementById('tanggal_lahir');
        if (tanggalLahirInput) {
            tanggalLahirInput.addEventListener('change', function() {
                if (!validateAge(this.value)) {
                    showError('tanggal_lahir', 'Minimal usia 17 tahun untuk mengikuti kursus');
                } else {
                    showSuccess('tanggal_lahir');
                }
            });
        }

        // Validasi Alamat
        const alamatInput = document.getElementById('alamat');
        if (alamatInput) {
            alamatInput.addEventListener('blur', function() {
                if (this.value.trim().length < 10) {
                    showError('alamat', 'Alamat minimal 10 karakter');
                } else {
                    showSuccess('alamat');
                }
            });
        }

        // Validasi Jenis Kelamin
        const jenisKelaminRadios = document.querySelectorAll('input[name="jenis_kelamin"]');
        jenisKelaminRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                validateRadioButtons('jenis_kelamin');
            });
        });

        // Validasi Paket Kursus
        const paketSelect = document.getElementById('paket_kursus_id');
        if (paketSelect) {
            paketSelect.addEventListener('change', function() {
                validateSelect(this);
            });
        }

        // Validasi Tipe Mobil
        const tipeMobilSelect = document.getElementById('tipe_mobil');
        if (tipeMobilSelect) {
            tipeMobilSelect.addEventListener('change', function() {
                validateSelect(this);
            });
        }

        // Validasi Jadwal Preferensi
        const jadwalSelect = document.getElementById('jadwal_preferensi');
        if (jadwalSelect) {
            jadwalSelect.addEventListener('change', function() {
                validateSelect(this);
            });
        }

        // Validasi Persetujuan
        const persetujuanCheckbox = document.getElementById('persetujuan');
        if (persetujuanCheckbox) {
            persetujuanCheckbox.addEventListener('change', function() {
                const errorElement = document.getElementById('persetujuan_error');
                if (!this.checked) {
                    if (errorElement) errorElement.style.display = 'block';
                } else {
                    if (errorElement) errorElement.style.display = 'none';
                }
            });
        }

        // Event listener untuk update harga saat paket berubah
        if (paketSelect) {
            paketSelect.addEventListener('change', updateTotalHarga);
        }
        
        // Auto select package from localStorage when page loads
        const selectedPackageId = localStorage.getItem('selected_package_id');
        const selectedPackageName = localStorage.getItem('selected_package_name');

        if (selectedPackageId && document.getElementById('paket_kursus_id')) {
            const selectElement = document.getElementById('paket_kursus_id');
            const tipeMobilElement = document.getElementById('tipe_mobil');
            const tipeMobilNote = document.getElementById('tipeMobilNote');

            // Set nilai paket kursus
            selectElement.value = selectedPackageId;
            
            // Trigger change event untuk mengatur tipe mobil dan harga
            if (selectElement.value) {
                onPackageSelectChange();
            }

            // Scroll ke form pendaftaran jika ada anchor #daftar di URL
            if (window.location.hash === '#daftar') {
                setTimeout(() => {
                    document.getElementById('daftar').scrollIntoView({
                        behavior: 'smooth'
                    });

                    // Tampilkan notifikasi
                    if (selectedPackageName) {
                        const formStatus = document.getElementById('formStatus');
                        formStatus.className = 'bg-blue-50 border border-blue-200 text-blue-700 p-4 rounded-lg';
                        formStatus.innerHTML = `
                            <div class="text-center">
                                <i class="fas fa-info-circle text-2xl mb-2 text-blue-500"></i>
                                <p>Paket <strong>${selectedPackageName}</strong> telah dipilih. Silakan lengkapi form pendaftaran.</p>
                                <p class="font-bold mt-2">Total Biaya: ${formatRupiah(selectedPackagePrice)}</p>
                            </div>
                        `;
                        formStatus.classList.remove('hidden');
                        formStatus.scrollIntoView({ behavior: 'smooth' });
                        
                        // Sembunyikan setelah 5 detik
                        setTimeout(() => {
                            formStatus.classList.add('hidden');
                        }, 5000);
                    }
                }, 500);
            }

            // Hapus data dari localStorage setelah digunakan
            localStorage.removeItem('selected_package_id');
            localStorage.removeItem('selected_package_name');
        }
        
        // Initial update harga
        updateTotalHarga();
        
        // Testimoni Carousel
        const container = document.getElementById('testimoniContainer');
        const prevBtn = document.getElementById('prevTestimoni');
        const nextBtn = document.getElementById('nextTestimoni');

        if (container) {
            // Lebar satu kartu (termasuk gap)
            const cardWidth = container.querySelector('.flex-shrink-0').offsetWidth + 24;

            // Fungsi scroll
            function scrollTestimoni(direction) {
                container.scrollBy({
                    left: direction * cardWidth,
                    behavior: 'smooth'
                });
            }

            // Event listeners
            if (prevBtn) prevBtn.addEventListener('click', () => scrollTestimoni(-1));
            if (nextBtn) nextBtn.addEventListener('click', () => scrollTestimoni(1));

            // Opsional: Sembunyikan tombol jika tidak dibutuhkan di mobile
            function updateNavButtons() {
                if (window.innerWidth < 768) {
                    if (prevBtn) prevBtn.classList.add('hidden');
                    if (nextBtn) nextBtn.classList.add('hidden');
                } else {
                    if (prevBtn) prevBtn.classList.remove('hidden');
                    if (nextBtn) nextBtn.classList.remove('hidden');
                }
            }

            // Jalankan saat load dan resize
            updateNavButtons();
            window.addEventListener('resize', updateNavButtons);
        }
        
        // Fungsi untuk menangani anchor #daftar saat halaman dimuat
        if (window.location.hash === '#daftar') {
            setTimeout(() => {
                const daftarSection = document.getElementById('daftar');
                if (daftarSection) {
                    daftarSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 100);
        }
    });

    // Form submission dengan AJAX
    document.getElementById('formPendaftaran').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Enable semua select yang disabled sementara untuk submit
        const disabledSelects = document.querySelectorAll('select[disabled]');
        disabledSelects.forEach(select => {
            select.disabled = false;
        });

        // Validasi form
        if (!validateForm()) {
            // Kembalikan status disabled
            disabledSelects.forEach(select => {
                select.disabled = true;
            });
            
            // Scroll ke error pertama
            const firstError = document.querySelector('.error-input, .error-message[style*="block"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        const formStatus = document.getElementById('formStatus');

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);

            // Debug: log data yang akan dikirim
            console.log('Data yang dikirim:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            // Kembalikan status disabled setelah submit
            disabledSelects.forEach(select => {
                select.disabled = true;
            });

            if (result.status === 'success') {
                // Show success message in form
                formStatus.className = 'bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg';
                formStatus.innerHTML = `
                    <div class="text-center p-4">
    <i class="fas fa-check-circle text-4xl mb-3 text-green-500"></i>

    <h4 class="font-bold text-xl mb-2">Pendaftaran Berhasil!</h4>

    <p class="mb-2">${result.message}</p>

    <p class="font-bold mt-3">
        Nomor Pendaftaran:
        <span class="text-blue-600">${result.data.nomor_pendaftaran}</span>
    </p>

    <p class="text-sm text-gray-600 mt-2">
        Harap simpan nomor ini untuk verifikasi.
    </p>

    <p class="text-sm text-gray-700 mt-3">
        Untuk melihat status pendaftaran, silakan buka halaman 
        <a href="cek-status.php" class="text-blue-600 underline">Cek Status</a>
        dan masukkan nomor pendaftaran Anda.
    </p>
</div>

                `;
                formStatus.classList.remove('hidden');
                
                // Scroll to success message
                formStatus.scrollIntoView({ behavior: 'smooth' });

                // Reset form
                form.reset();
                
                // Reset tipe mobil
                const tipeMobilSelect = document.getElementById('tipe_mobil');
                const tipeMobilNote = document.getElementById('tipeMobilNote');
                if (tipeMobilSelect) {
                    tipeMobilSelect.disabled = false;
                    tipeMobilSelect.value = 'manual';
                    resetValidation(tipeMobilSelect);
                }
                if (tipeMobilNote) {
                    tipeMobilNote.classList.add('hidden');
                }
                
                // Reset total harga
                updateTotalHarga();
                
                // Reset semua validasi visual
                document.querySelectorAll('.error-input, .success-input').forEach(el => {
                    el.classList.remove('error-input', 'success-input');
                });
                document.querySelectorAll('.valid-indicator').forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.error-label').forEach(el => {
                    el.classList.remove('error-label');
                });
                
            } else {
                // Show error message in form
                formStatus.className = 'bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg';
                let errorMessage = result.message;
                
                if (result.errors && result.errors.length > 0) {
                    errorMessage = '<div class="text-left"><p class="font-bold mb-2">Perbaiki kesalahan berikut:</p><ul class="list-disc pl-5">' +
                        result.errors.map(error => `<li class="mb-1">${error}</li>`).join('') +
                        '</ul></div>';
                }
                
                formStatus.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-circle text-3xl mb-3 text-red-500"></i>
                        <h4 class="font-bold text-lg mb-2">Pendaftaran Gagal</h4>
                        <div>${errorMessage}</div>
                    </div>
                `;
                formStatus.classList.remove('hidden');
                formStatus.scrollIntoView({ behavior: 'smooth' });  
            }
        } catch (error) {
            console.error('Error:', error);
            formStatus.className = 'bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg';
            formStatus.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-circle text-3xl mb-3 text-red-500"></i>
                    <h4 class="font-bold text-lg mb-2">Terjadi Kesalahan</h4>
                    <p>${error.message || 'Gagal mengirim data. Silakan coba lagi.'}</p>
                </div>
            `;
            formStatus.classList.remove('hidden');
            formStatus.scrollIntoView({ behavior: 'smooth' });
        } finally {
            // Reset button state
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });
</script>   

<?php include 'includes/footer.php'; ?>