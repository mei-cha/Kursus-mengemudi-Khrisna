<?php
require_once 'config/database.php';
session_start();

// Check if package ID is provided
if (!isset($_GET['id'])) {
    header('Location: paket-kursus.php');
    exit;
}

$paket_id = $_GET['id'];

// Validate ID is numeric
if (!is_numeric($paket_id)) {
    header('Location: paket-kursus.php');
    exit;
}

$db = (new Database())->getConnection();

// Get package details
$stmt = $db->prepare("SELECT * FROM paket_kursus WHERE id = ? AND tersedia = 1");
$stmt->execute([$paket_id]);
$paket = $stmt->fetch(PDO::FETCH_ASSOC);

// If package not found or not available
if (!$paket) {
    header('Location: paket-kursus.php');
    exit;
}

// Hitung jumlah pertemuan dan format durasi
$pertemuan = ceil($paket['durasi_jam'] / 50);
$jam = floor($paket['durasi_jam'] / 60);
$menit = $paket['durasi_jam'] % 60;
$harga_formatted = number_format($paket['harga'], 0, ',', '.');

// Format durasi text
if ($jam > 0 && $menit > 0) {
    $durasi_text = "{$jam} jam {$menit} menit";
} elseif ($jam > 0) {
    $durasi_text = "{$jam} jam";
} else {
    $durasi_text = "{$menit} menit";
}

// Get similar packages
$stmt = $db->prepare("
    SELECT * FROM paket_kursus 
    WHERE tipe_mobil = ? AND id != ? AND tersedia = 1 
    ORDER BY harga ASC 
    LIMIT 3
");
$stmt->execute([$paket['tipe_mobil'], $paket_id]);
$paket_sejenis = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Detail Paket - " . htmlspecialchars($paket['nama_paket']);
?>

<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<section class="bg-gray-100 py-4">
    <div class="max-w-7xl mx-auto px-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400"></i>
                        <a href="paket-kursus.php" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                            Paket Kursus
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400"></i>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                            <?= htmlspecialchars($paket['nama_paket']) ?>
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
</section>

<!-- Detail Paket -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
<div class="lg:col-span-2">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold mb-2"><?= htmlspecialchars($paket['nama_paket']) ?></h1>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-blue-500 bg-opacity-30 rounded-full text-sm">
                            <i class="fas fa-<?= $paket['tipe_mobil'] == 'manual' ? 'cog' : ($paket['tipe_mobil'] == 'matic' ? 'bolt' : 'sync') ?> mr-1"></i>
                            <?= ucfirst($paket['tipe_mobil']) ?>
                        </span>
                        <span class="text-blue-100">
                            <i class="fas fa-check-circle mr-1"></i>Tersedia
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <!-- Highlights - Diubah menjadi 3 kolom -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 rounded-lg p-5 text-center border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?= $pertemuan ?></div>
                    <div class="text-sm font-medium text-blue-800 mb-1">Pertemuan</div>
                    <div class="text-xs text-blue-600">50 menit</div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-5 text-center border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?= $durasi_text ?></div>
                    <div class="text-sm font-medium text-blue-800 mb-1">Durasi Total</div>
                    <div class="text-xs text-blue-600"><?= $paket['durasi_jam'] ?> menit</div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-5 text-center border border-blue-100">
                    <div class="text-2xl font-bold text-red-400 mb-1">Rp <?= $harga_formatted ?></div>
                    <div class="text-sm font-medium text-blue-800 mb-1">Harga Paket</div>
                    <div class="text-xs text-blue-600">Termasuk semua materi</div>
                </div>
            </div>
            
            <!-- Deskripsi -->
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Deskripsi Paket</h3>
                <div class="prose max-w-none">
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($paket['deskripsi'])) ?></p>
                </div>
            </div>
            
            <!-- Fitur -->
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Fitur Included</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if ($paket['termasuk_teori']): ?>
                    <div class="flex items-center bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="p-2 bg-blue-100 rounded-lg mr-4">
                            <i class="fas fa-book text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Pelajaran Teori</div>
                            <div class="text-sm text-gray-600">Materi teori lengkap</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($paket['termasuk_praktik']): ?>
                    <div class="flex items-center bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="p-2 bg-green-100 rounded-lg mr-4">
                            <i class="fas fa-road text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Pelajaran Praktik</div>
                            <div class="text-sm text-gray-600">Praktik langsung mengemudi</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="p-2 bg-purple-100 rounded-lg mr-4">
                            <i class="fas fa-car text-purple-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Mobil <?= ucfirst($paket['tipe_mobil']) ?></div>
                            <div class="text-sm text-gray-600">Kendaraan sesuai pilihan</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="p-2 bg-amber-100 rounded-lg mr-4">
                            <i class="fas fa-users text-amber-600"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">Instruktur Profesional</div>
                            <div class="text-sm text-gray-600">Pengajar berpengalaman</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CTA Buttons -->
            <div class="pt-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row gap-4">
                    <button onclick="pilihPaket(<?= $paket['id'] ?>)" 
                            class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition duration-300 text-lg">
                        <i class="fas fa-shopping-cart mr-2"></i>Daftar Sekarang
                    </button>
                    <a href="paket-kursus.php" 
                       class="flex-1 border border-gray-300 text-gray-700 py-4 rounded-lg font-bold hover:bg-gray-50 transition duration-300 text-lg text-center">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Info Box -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Pendaftaran</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-4">
                                <i class="fas fa-calendar-check text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Siap Kapan Saja</div>
                                <div class="text-sm text-gray-600">Jadwal fleksibel</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-4">
                                <i class="fas fa-shield-alt text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Terjamin Aman</div>
                                <div class="text-sm text-gray-600">Asuransi & safety</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg mr-4">
                                <i class="fas fa-certificate text-purple-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">Sertifikat Resmi</div>
                                <div class="text-sm text-gray-600">Diakui secara nasional</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Paket Sejenis -->
                <?php if (count($paket_sejenis) > 0): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Paket Sejenis</h3>
                    <div class="space-y-4">
                        <?php foreach ($paket_sejenis as $similar): ?>
                        <?php 
                            $similar_pertemuan = ceil($similar['durasi_jam'] / 50);
                            $similar_harga = number_format($similar['harga'], 0, ',', '.');
                        ?>
                        <a href="detail-paket.php?id=<?= $similar['id'] ?>" 
                           class="block border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition duration-300">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-gray-800"><?= htmlspecialchars($similar['nama_paket']) ?></h4>
                                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-600 rounded">
                                    <?= ucfirst($similar['tipe_mobil']) ?>
                                </span>
                            </div>
                            <div class="text-lg font-bold text-blue-600 mb-2">Rp <?= $similar_harga ?></div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>
                                <span><?= $similar_pertemuan ?> pertemuan</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="paket-kursus.php" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                            Lihat semua paket
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-800 mb-8">Pertanyaan Umum</h2>
        
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-2">Apakah ada uang pendaftaran?</h3>
                <p class="text-gray-600">Tidak ada uang pendaftaran. Harga yang tertera sudah termasuk semua biaya kursus.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-2">Bagaimana sistem pembayaran?</h3>
                <p class="text-gray-600">Pembayaran dapat dilakukan secara tunai atau transfer setelah konfirmasi pendaftaran.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-2">Apakah bisa pilih jadwal sendiri?</h3>
                <p class="text-gray-600">Ya, Anda bisa memilih jadwal yang sesuai dengan kesibukan Anda.</p>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript untuk pilih paket -->
<script>
// Fungsi pilih paket untuk form pendaftaran
function pilihPaket(paketId, namaPaket = '') {
    // Simpan data paket di localStorage
    localStorage.setItem('selected_package_id', paketId);
    if (namaPaket) {
        localStorage.setItem('selected_package_name', namaPaket);
    }
    
    // Redirect ke halaman index.php dengan anchor #daftar
    window.location.href = `index.php#daftar`;
}

// Fungsi notifikasi
function showNotification(message, type = 'info') {
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `custom-notification fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-0');
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
    
    notification.addEventListener('click', () => {
        notification.classList.remove('translate-x-0');
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}
</script>

<!-- Style untuk Notifikasi -->
<style>
.custom-notification {
    min-width: 300px;
    max-width: 400px;
    cursor: pointer;
}

.custom-notification:hover {
    opacity: 0.9;
}
</style>

<?php include 'includes/footer.php'; ?>