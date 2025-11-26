<?php include 'includes/header.php'; ?>

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
            <a href="#daftar" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-gray-100 transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="fas fa-edit mr-2"></i>Daftar Sekarang
            </a>
            <a href="#paket-kursus" class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition duration-300">
                <i class="fas fa-gift mr-2"></i>Lihat Paket
            </a>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-16 max-w-4xl mx-auto">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-yellow-300">
                    <?php 
                    try {
                        $siswa_query = $db->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'selesai'");
                        $total_siswa = $siswa_query->fetch(PDO::FETCH_ASSOC)['total'];
                        echo number_format($total_siswa + 5000);
                    } catch (Exception $e) {
                        echo "5,000+";
                    }
                    ?>
                </div>
                <div class="text-blue-100">Siswa Lulus</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-yellow-300">98%</div>
                <div class="text-blue-100">Tingkat Kelulusan</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-yellow-300">15+</div>
                <div class="text-blue-100">Tahun Pengalaman</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-yellow-300">
                    <?php 
                    try {
                        $instruktur_query = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1");
                        $total_instruktur = $instruktur_query->fetch(PDO::FETCH_ASSOC)['total'];
                        echo $total_instruktur ? $total_instruktur . '+' : '50+';
                    } catch (Exception $e) {
                        echo "50+";
                    }
                    ?>
                </div>
                <div class="text-blue-100">Instruktur</div>
            </div>
        </div>
    </div>
</section>

<!-- Informasi Kursus Section - DISEDERHANAKAN -->
<section id="paket-kursus" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Pilihan Paket Kursus</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">Pilih kategori paket yang sesuai dengan kebutuhan belajar mengemudi Anda</p>
        </div>
        
        <!-- Kategori Paket -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Paket Reguler -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-car text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Paket Reguler</h3>
                <p class="text-blue-100 mb-4 text-sm">Kursus standar dengan jadwal reguler</p>
                <div class="text-2xl font-bold mb-4">Mulai Rp 550rb</div>
                <a href="paket-kursus.php#reguler" 
                   class="block w-full bg-white text-blue-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Lihat Detail
                </a>
            </div>

            <!-- Paket Campuran -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Paket Campuran</h3>
                <p class="text-green-100 mb-4 text-sm">Belajar manual & matic dalam satu paket</p>
                <div class="text-2xl font-bold mb-4">Mulai Rp 650rb</div>
                <a href="paket-kursus.php#campuran" 
                   class="block w-full bg-white text-green-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Lihat Detail
                </a>
            </div>

            <!-- Paket Extra -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-moon text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Paket Extra</h3>
                <p class="text-purple-100 mb-4 text-sm">Kursus malam & hari libur</p>
                <div class="text-2xl font-bold mb-4">Mulai Rp 650rb</div>
                <a href="paket-kursus.php#extra" 
                   class="block w-full bg-white text-purple-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Lihat Detail
                </a>
            </div>

            <!-- Paket Pelancaran -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-2">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Paket Pelancaran</h3>
                <p class="text-orange-100 mb-4 text-sm">Kursus singkat untuk yang sudah punya dasar</p>
                <div class="text-2xl font-bold mb-4">Mulai Rp 350rb</div>
                <a href="paket-kursus.php#pelancaran" 
                   class="block w-full bg-white text-orange-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Lihat Detail
                </a>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center">
            <a href="paket-kursus.php" 
               class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300 shadow-lg hover:shadow-xl">
                <i class="fas fa-list mr-2"></i>Lihat Semua Paket
            </a>
        </div>
    </div>
</section>

<!-- JavaScript untuk Menampilkan Detail Paket -->
<script>
// Data paket dari PHP di-convert ke JavaScript
const allPackages = <?= json_encode($paket_kursus) ?>;

function showPackageDetail(category) {
    const detailSection = document.getElementById('packageDetail');
    const packageList = document.getElementById('packageList');
    const detailTitle = document.getElementById('detailTitle');
    
    // Filter paket berdasarkan kategori
    let filteredPackages = [];
    let title = '';
    
    switch(category) {
        case 'reguler':
            filteredPackages = allPackages.filter(pkg => 
                pkg.nama_paket.toLowerCase().includes('reguler')
            );
            title = 'Paket Reguler';
            break;
        case 'campuran':
            filteredPackages = allPackages.filter(pkg => 
                pkg.nama_paket.toLowerCase().includes('campuran') || 
                pkg.tipe_mobil === 'keduanya'
            );
            title = 'Paket Campuran';
            break;
        case 'extra':
            filteredPackages = allPackages.filter(pkg => 
                pkg.nama_paket.toLowerCase().includes('extra')
            );
            title = 'Paket Extra';
            break;
        case 'pelancaran':
            filteredPackages = allPackages.filter(pkg => 
                pkg.nama_paket.toLowerCase().includes('pelancaran')
            );
            title = 'Paket Pelancaran';
            break;
    }
    
    // Update title
    detailTitle.textContent = title;
    
    // Clear previous content
    packageList.innerHTML = '';
    
    // Add packages to the list
    if (filteredPackages.length > 0) {
        filteredPackages.forEach(pkg => {
            const pertemuan = Math.floor(pkg.durasi_jam / 50);
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-100';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                    <h4 class="text-lg font-bold text-gray-800">${pkg.nama_paket}</h4>
                    <span class="bg-blue-100 text-blue-600 text-xs font-semibold px-3 py-1 rounded-full capitalize">
                        ${pkg.tipe_mobil}
                    </span>
                </div>
                
                <div class="text-2xl font-bold text-blue-600 mb-4">
                    Rp ${formatNumber(pkg.harga)}
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock text-blue-500 w-5 mr-3"></i>
                        <span>${pertemuan} Pertemuan</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-road text-green-500 w-5 mr-3"></i>
                        <span>${pkg.durasi_jam} Menit Total</span>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-4 leading-relaxed">${pkg.deskripsi || 'Paket lengkap belajar mengemudi'}</p>
                
                <button onclick="pilihPaket(${pkg.id})" 
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i>Pilih Paket Ini
                </button>
            `;
            packageList.appendChild(card);
        });
    } else {
        packageList.innerHTML = `
            <div class="col-span-3 text-center py-8">
                <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Belum ada paket dalam kategori ini.</p>
            </div>
        `;
    }
    
    // Show the detail section
    detailSection.classList.remove('hidden');
    
    // Scroll to detail section
    detailSection.scrollIntoView({ behavior: 'smooth' });
}

function hidePackageDetail() {
    document.getElementById('packageDetail').classList.add('hidden');
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Fungsi pilih paket untuk form pendaftaran
function pilihPaket(paketId) {
    const selectElement = document.getElementById('paket_kursus_id');
    if (selectElement) {
        selectElement.value = paketId;
        document.getElementById('daftar').scrollIntoView({
            behavior: 'smooth'
        });
        
        // Tutup detail section
        hidePackageDetail();
    }
}

// Close detail when clicking outside
document.addEventListener('click', function(e) {
    const detailSection = document.getElementById('packageDetail');
    if (!detailSection.contains(e.target) && !e.target.closest('button[onclick*="showPackageDetail"]')) {
        hidePackageDetail();
    }
});
</script>

<!-- Profil Instruktur Section -->
<section id="instruktur" class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Instruktur Profesional</h2>
            <p class="text-xl text-gray-600">Belajar dari instruktur berpengalaman dan bersertifikat</p>
        </div>
        
        <?php if (empty($instruktur)): ?>
            <div class="text-center py-12">
                <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Data Instruktur Sedang Tidak Tersedia</h3>
                <p class="text-gray-500">Silakan hubungi kami untuk informasi lebih lanjut.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($instruktur as $inst): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition duration-300">
                    <div class="flex items-start space-x-4">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-md">
                            <?php if (!empty($inst['foto'])): ?>
                                <img src="assets/images/instruktur/<?= $inst['foto'] ?>" alt="<?= htmlspecialchars($inst['nama_lengkap']) ?>" class="w-20 h-20 rounded-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-2xl text-white"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($inst['nama_lengkap']) ?></h3>
                            <div class="flex items-center mb-2">
                                <div class="text-yellow-400 mr-2">
                                    <?= str_repeat('★', floor($inst['rating'])) ?><?= str_repeat('☆', 5 - floor($inst['rating'])) ?>
                                </div>
                                <span class="text-gray-600 text-sm">(<?= number_format($inst['rating'], 1) ?>)</span>
                            </div>
                            <p class="text-blue-600 font-semibold mb-2"><?= $inst['pengalaman_tahun'] ?>+ Tahun Pengalaman</p>
                            <p class="text-gray-600 text-sm mb-3">Spesialis: <?= ucfirst($inst['spesialisasi']) ?></p>
                            <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($inst['deskripsi'] ?? 'Instruktur profesional berpengalaman') ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Testimoni Section -->
<section id="testimoni" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Apa Kata Siswa Kami</h2>
            <p class="text-xl text-gray-600">Testimoni dari siswa yang sudah berhasil mengemudi dengan percaya diri</p>
        </div>
        
        <?php if (empty($testimoni)): ?>
            <div class="text-center py-12">
                <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Belum Ada Testimoni</h3>
                <p class="text-gray-500">Jadilah yang pertama memberikan testimoni.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($testimoni as $testi): ?>
                <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-lg transition duration-300">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4 shadow-md">
                            <?php if (!empty($testi['foto_siswa'])): ?>
                                <img src="assets/images/testimoni/<?= $testi['foto_siswa'] ?>" alt="<?= htmlspecialchars($testi['nama_siswa']) ?>" class="w-12 h-12 rounded-full object-cover">
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
                        <?= str_repeat('★', $testi['rating']) ?><?= str_repeat('☆', 5 - $testi['rating']) ?>
                    </div>
                    
                    <p class="text-gray-600 italic text-lg leading-relaxed">"<?= htmlspecialchars($testi['testimoni_text']) ?>"</p>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex justify-between text-sm text-gray-500">
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
        <?php endif; ?>
    </div>
</section>

<!-- Galeri Section -->
<section id="galeri" class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Galeri Aktivitas</h2>
            <p class="text-xl text-gray-600">Lihat momen-momen belajar mengemudi di Krishna Driving</p>
        </div>
        
        <?php if (empty($galeri)): ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">Galeri Sedang Tidak Tersedia</h3>
                <p class="text-gray-500">Silakan kunjungi lagi nanti.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($galeri as $item): ?>
                <div class="group relative overflow-hidden rounded-lg shadow-md hover:shadow-xl transition duration-300">
                    <img src="assets/images/galeri/<?= $item['gambar'] ?>" 
                         alt="<?= htmlspecialchars($item['judul']) ?>" 
                         class="w-full h-48 object-cover group-hover:scale-110 transition duration-300">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition duration-300 flex items-center justify-center">
                        <div class="text-white text-center opacity-0 group-hover:opacity-100 transition duration-300 p-4">
                            <h4 class="font-bold text-sm"><?= htmlspecialchars($item['judul']) ?></h4>
                            <p class="text-xs mt-1"><?= htmlspecialchars($item['deskripsi']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Form Pendaftaran Section -->
<section id="daftar" class="py-16 bg-gradient-to-br from-blue-50 to-gray-100">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Daftar Kursus Sekarang</h2>
            <p class="text-xl text-gray-600">Isi form berikut untuk memulai perjalanan mengemudi Anda</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <form id="formPendaftaran" action="proses-pendaftaran.php" method="POST" class="space-y-6">
                <!-- Data Pribadi -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nama_lengkap" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-300">
                    </div>
                    <div>
                        <label for="telepon" class="block text-sm font-semibold text-gray-700 mb-2">Nomor Telepon/HP *</label>
                        <input type="tel" id="telepon" name="telepon" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-300">
                    </div>
                    <div>
                        <label for="paket_kursus_id" class="block text-sm font-semibold text-gray-700 mb-2">Pilih Paket *</label>
                        <select id="paket_kursus_id" name="paket_kursus_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-300">
                            <option value="">Pilih Paket Kursus</option>
                            <?php foreach ($paket_kursus as $paket): ?>
                            <option value="<?= $paket['id'] ?>"><?= htmlspecialchars($paket['nama_paket']) ?> - Rp <?= number_format($paket['harga'], 0, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="persetujuan" name="persetujuan" required
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="persetujuan" class="ml-2 text-sm text-gray-700">
                        Saya menyetujui syarat dan ketentuan yang berlaku *
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Pendaftaran
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>