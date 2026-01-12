<?php 
// Mulai session dan include konfigurasi database
session_start();
require_once 'config/database.php';

// Buat koneksi database
$database = new Database();
$db = $database->getConnection();

// Ambil parameter kategori dari URL
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Query untuk mendapatkan data paket kursus
try {
    if (!empty($kategori)) {
        // Filter berdasarkan kategori
        $sql = "SELECT *, 
                CASE 
                    WHEN tipe_mobil = 'manual' THEN 'Manual'
                    WHEN tipe_mobil = 'matic' THEN 'Matic'
                    WHEN tipe_mobil = 'keduanya' THEN 'Keduanya'
                    ELSE tipe_mobil 
                END as tipe_mobil_text 
                FROM paket_kursus 
                WHERE tersedia = 1 
                AND kategori = :kategori 
                ORDER BY harga";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':kategori', $kategori, PDO::PARAM_STR);
        $stmt->execute();
        $paket_kursus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Tampilkan semua paket
        $sql = "SELECT *, 
                CASE 
                    WHEN tipe_mobil = 'manual' THEN 'Manual'
                    WHEN tipe_mobil = 'matic' THEN 'Matic'
                    WHEN tipe_mobil = 'keduanya' THEN 'Keduanya'
                    ELSE tipe_mobil 
                END as tipe_mobil_text 
                FROM paket_kursus 
                WHERE tersedia = 1 
                ORDER BY harga";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $paket_kursus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $paket_kursus = [];
    error_log("Database error (paket): " . $e->getMessage());
}

include 'includes/header.php'; 
?>

<!-- Header Paket Kursus -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white py-12">
    <div class="max-w-7xl mx-auto text-center px-4">
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4">
            <?php 
            if (!empty($kategori)) {
                $kategori_names = [
                    'reguler' => 'Paket Reguler',
                    'campuran' => 'Paket Campuran', 
                    'extra' => 'Paket Extra',
                    'pelancaran' => 'Paket Pelancaran'
                ];
                echo $kategori_names[$kategori] ?? 'Paket ' . ucfirst($kategori);
            } else {
                echo 'Semua Paket Kursus';
            }
            ?>
        </h1>
        <p class="text-lg text-blue-100">
            <?php if (!empty($kategori)): ?>
                Temukan paket kursus <?= $kategori ?> yang tepat untuk kebutuhan Anda
            <?php else: ?>
                Temukan paket kursus mengemudi yang tepat untuk kebutuhan Anda
            <?php endif; ?>
        </p>
        
        <?php if (!empty($kategori)): ?>
            <div class="mt-4">
                <a href="paket-kursus.php" class="inline-flex items-center text-blue-200 hover:text-white transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Semua Paket
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Filter & Konten -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="mb-8">
            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Filter Paket</h2>
                        <p class="text-sm text-gray-600">Temukan paket yang sesuai dengan kebutuhan Anda</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Filter Tipe Mobil -->
                        <div>
                            <label for="filterTipe" class="block text-sm font-medium text-gray-700 mb-1">Tipe Mobil</label>
                            <select id="filterTipe" name="filterTipe" 
                                    onchange="filterPaket()"
                                    class="w-full sm:w-40 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="semua">Semua Tipe</option>
                                <option value="manual">Manual</option>
                                <option value="matic">Matic</option>
                                <option value="keduanya">Keduanya</option>
                            </select>
                        </div>
                        
                        <!-- Filter Urutan Harga -->
                        <div>
                            <label for="filterHarga" class="block text-sm font-medium text-gray-700 mb-1">Urutkan Harga</label>
                            <select id="filterHarga" name="filterHarga" 
                                    onchange="filterPaket()"
                                    class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="default">Default</option>
                                <option value="termurah">Termurah ke Tertinggi</option>
                                <option value="termahal">Tertinggi ke Termurah</option>
                            </select>
                        </div>
                        
                        <!-- Reset Filter -->
                        <div class="flex items-end">
                            <button onclick="resetFilter()" 
                                    class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-300 font-medium">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($kategori)): ?>
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <i class="fas fa-filter text-blue-600 mr-2"></i>
                            <p class="text-sm text-blue-800">
                                Menampilkan paket dengan kategori: <span class="font-semibold"><?= ucfirst($kategori) ?></span>
                                (<?= count($paket_kursus) ?> paket)
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Info Jumlah Paket -->
            <div class="flex justify-between items-center mb-6">
                <p class="text-gray-700">
                    <span id="jumlahPaket" class="font-semibold text-blue-600"><?= count($paket_kursus) ?></span> paket tersedia
                    <?php if (!empty($kategori)): ?>
                        dalam kategori <span class="font-semibold"><?= ucfirst($kategori) ?></span>
                    <?php endif; ?>
                </p>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    <span>Klik "Pilih Paket" untuk langsung mengisi form pendaftaran</span>
                </div>
            </div>
        </div>
        
        <!-- Daftar Paket -->
        <div id="daftarPaket" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($paket_kursus)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Tidak Ada Paket Tersedia</h3>
                    <p class="text-gray-500">
                        <?php if (!empty($kategori)): ?>
                            Tidak ada paket ditemukan untuk kategori "<?= ucfirst($kategori) ?>".
                        <?php else: ?>
                            Silakan coba lagi nanti.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($kategori)): ?>
                        <a href="paket-kursus.php" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            <i class="fas fa-arrow-left mr-2"></i>Lihat Semua Paket
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($paket_kursus as $paket): ?>
                <?php 
                    // Hitung jumlah pertemuan (asumsi 50 menit per pertemuan)
                    $pertemuan = ceil($paket['durasi_jam'] / 50);
                    // Format harga
                    $harga_formatted = number_format($paket['harga'], 0, ',', '.');
                    // Warna berdasarkan tipe
                    $warna_badge = $paket['tipe_mobil'] == 'manual' ? 'bg-blue-100 text-blue-600' : 
                                  ($paket['tipe_mobil'] == 'matic' ? 'bg-green-100 text-green-600' : 'bg-purple-100 text-purple-600');
                ?>
                <div class="paket-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 border border-gray-100"
                     data-tipe="<?= $paket['tipe_mobil'] ?>"
                     data-harga="<?= $paket['harga'] ?>"
                     data-kategori="<?= $paket['kategori'] ?? 'reguler' ?>">
                    <!-- Badge -->
                    <div class="px-6 pt-6">
                        <span class="inline-block <?= $warna_badge ?> text-xs font-semibold px-3 py-1 rounded-full capitalize">
                            <i class="fas fa-<?= $paket['tipe_mobil'] == 'manual' ? 'cog' : ($paket['tipe_mobil'] == 'matic' ? 'bolt' : 'sync') ?> mr-1"></i>
                            <?= $paket['tipe_mobil'] ?>
                        </span>
                    </div>
                    
                    <!-- Konten -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($paket['nama_paket']) ?></h3>
                        
                        <div class="text-2xl font-bold text-blue-600 mb-4">Rp <?= $harga_formatted ?></div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-clock text-blue-500 w-5 mr-3"></i>
                                <span><?= $pertemuan ?> Pertemuan</span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-road text-green-500 w-5 mr-3"></i>
                                <span><?= $paket['durasi_jam'] ?> Menit Total</span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-car text-purple-500 w-5 mr-3"></i>
                                <span>Mobil <?= ucfirst($paket['tipe_mobil']) ?></span>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                            <?= htmlspecialchars($paket['deskripsi'] ?? 'Paket lengkap belajar mengemudi dengan instruktur profesional') ?>
                        </p>
                        
                        <div class="flex space-x-3">
                            <button onclick="pilihPaket(<?= $paket['id'] ?>, '<?= htmlspecialchars(addslashes($paket['nama_paket'])) ?>')" 
                                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition duration-300">
                                <i class="fas fa-shopping-cart mr-2"></i>Pilih Paket
                            </button>
                            <a href="detail-paket-kursus.php?id=<?= $paket['id'] ?>" 
                               class="flex-1 border border-blue-600 text-blue-600 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-300 text-center">
                                <i class="fas fa-info-circle mr-2"></i>Detail
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- JavaScript untuk Filter -->
<script>
// Data paket dari PHP
const semuaPaket = <?= json_encode($paket_kursus) ?>;
const kategoriAktif = "<?= $kategori ?>";

// Fungsi filter paket
function filterPaket() {
    const filterTipe = document.getElementById('filterTipe').value;
    const filterHarga = document.getElementById('filterHarga').value;
    const container = document.getElementById('daftarPaket');
    const jumlahSpan = document.getElementById('jumlahPaket');
    
    let filteredPaket = [...semuaPaket];
    
    // Filter berdasarkan tipe mobil
    if (filterTipe !== 'semua') {
        filteredPaket = filteredPaket.filter(paket => 
            paket.tipe_mobil === filterTipe
        );
    }
    
    // Urutkan berdasarkan harga
    if (filterHarga === 'termurah') {
        filteredPaket.sort((a, b) => a.harga - b.harga);
    } else if (filterHarga === 'termahal') {
        filteredPaket.sort((a, b) => b.harga - a.harga);
    }
    
    // Update jumlah paket
    jumlahSpan.textContent = filteredPaket.length;
    
    // Tampilkan pesan jika tidak ada paket
    if (filteredPaket.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Tidak Ada Paket yang Sesuai</h3>
                <p class="text-gray-500">Silakan coba filter lain.</p>
                ${kategoriAktif ? `<a href="paket-kursus.php" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Lihat Semua Paket
                </a>` : ''}
            </div>
        `;
        return;
    }
    
    // Render paket yang sudah difilter
    container.innerHTML = filteredPaket.map(paket => {
        const pertemuan = Math.ceil(paket.durasi_jam / 50);
        const hargaFormatted = new Intl.NumberFormat('id-ID').format(paket.harga);
        
        // Tentukan warna badge berdasarkan tipe mobil
        let badgeColor, badgeIcon;
        switch(paket.tipe_mobil) {
            case 'manual':
                badgeColor = 'bg-blue-100 text-blue-600';
                badgeIcon = 'cog';
                break;
            case 'matic':
                badgeColor = 'bg-green-100 text-green-600';
                badgeIcon = 'bolt';
                break;
            default:
                badgeColor = 'bg-purple-100 text-purple-600';
                badgeIcon = 'sync';
        }
        
        return `
            <div class="paket-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 border border-gray-100"
                 data-tipe="${paket.tipe_mobil}"
                 data-harga="${paket.harga}"
                 data-kategori="${paket.kategori || 'reguler'}">
                <div class="px-6 pt-6">
                    <span class="inline-block ${badgeColor} text-xs font-semibold px-3 py-1 rounded-full capitalize">
                        <i class="fas fa-${badgeIcon} mr-1"></i>
                        ${paket.tipe_mobil}
                    </span>
                </div>
                
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">${escapeHtml(paket.nama_paket)}</h3>
                    
                    <div class="text-2xl font-bold text-blue-600 mb-4">Rp ${hargaFormatted}</div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-clock text-blue-500 w-5 mr-3"></i>
                            <span>${pertemuan} Pertemuan</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-road text-green-500 w-5 mr-3"></i>
                            <span>${paket.durasi_jam} Menit Total</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-car text-purple-500 w-5 mr-3"></i>
                            <span>Mobil ${capitalizeFirst(paket.tipe_mobil)}</span>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                        ${escapeHtml(paket.deskripsi || 'Paket lengkap belajar mengemudi dengan instruktur profesional')}
                    </p>
                    
                    <div class="flex space-x-3">
                        <button onclick="pilihPaket(${paket.id}, '${escapeHtml(paket.nama_paket)}')" 
                                class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition duration-300">
                            <i class="fas fa-shopping-cart mr-2"></i>Pilih Paket
                        </button>
                        <a href="detail-paket-kursus.php?id=${paket.id}" 
                           class="flex-1 border border-blue-600 text-blue-600 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-300 text-center">
                            <i class="fas fa-info-circle mr-2"></i>Detail
                        </a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Fungsi reset filter
function resetFilter() {
    document.getElementById('filterTipe').value = 'semua';
    document.getElementById('filterHarga').value = 'default';
    filterPaket();
}

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

// Fungsi helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Inisialisasi filter saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Jika ada kategori aktif, atur filter tipe ke "semua"
    if (kategoriAktif) {
        document.getElementById('filterTipe').value = 'semua';
    }
    
    filterPaket(); // Menampilkan semua paket dengan urutan default
});
</script>

<?php include 'includes/footer.php'; ?>