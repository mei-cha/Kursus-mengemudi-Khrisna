<?php
session_start();
require_once 'config/database.php';

// Buat koneksi database
$database = new Database();
$db = $database->getConnection();

// Ambil ID pendaftaran dari GET parameter atau session
$pendaftaran_id = isset($_GET['id']) ? $_GET['id'] : null;

// Jika tidak ada ID, coba cari pendaftaran terakhir dari session
if (!$pendaftaran_id && isset($_SESSION['last_registration_id'])) {
    $pendaftaran_id = $_SESSION['last_registration_id'];
}

// Query untuk mendapatkan data pendaftaran dari tabel pendaftaran_siswa
try {
    if ($pendaftaran_id) {
        $stmt = $db->prepare("
            SELECT ps.*, pk.nama_paket, pk.harga, pk.durasi_jam, 
                   pk.tipe_mobil as tipe_mobil_paket,
                   pk.deskripsi as deskripsi_paket
            FROM pendaftaran_siswa ps
            LEFT JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
            WHERE ps.id = :id
        ");
        $stmt->bindParam(':id', $pendaftaran_id);
        $stmt->execute();
        $pendaftaran = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pendaftaran) {
            throw new Exception("Data pendaftaran tidak ditemukan");
        }
    } else {
        $pendaftaran = null;
        $error = "Data pendaftaran tidak ditemukan. Silakan hubungi admin.";
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $pendaftaran = null;
}

include 'includes/header.php';
?>

<style>
    .konfirmasi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
    }
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .badge-status {
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: bold;
    }
    .badge-baru {
        background: #fbbf24;
        color: #78350f;
    }
    .badge-dikonfirmasi {
        background: #34d399;
        color: #064e3b;
    }
    .badge-diproses {
        background: #60a5fa;
        color: #1e3a8a;
    }
    .badge-selesai {
        background: #8b5cf6;
        color: #f5f3ff;
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                Konfirmasi Pendaftaran
            </h1>
            <p class="text-lg text-gray-600">Berikut adalah detail pendaftaran kursus mengemudi Anda</p>
        </div>

        <?php if (isset($error) || !$pendaftaran): ?>
            <!-- Error Message -->
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-8 rounded-2xl text-center mb-8">
                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                <h3 class="text-xl font-bold mb-2">Data Tidak Ditemukan</h3>
                <p><?= htmlspecialchars($error ?? 'Data pendaftaran tidak ditemukan') ?></p>
                <div class="mt-6 space-y-3">
                    <a href="index.php#daftar" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Pendaftaran
                    </a>
                    <a href="cek-status.php" class="inline-block border border-blue-600 text-blue-600 px-6 py-2 rounded-lg hover:bg-blue-50 transition ml-3">
                        <i class="fas fa-search mr-2"></i>Cek Status Pendaftaran
                    </a>
                </div>
            </div>
        <?php elseif ($pendaftaran): ?>
            <!-- Success Card -->
            <div class="konfirmasi-card p-8 mb-8 text-white">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-6 md:mb-0">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-car text-3xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold">Pendaftaran Berhasil!</h2>
                                <p class="text-blue-100">Krishna Driving Course</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <p><i class="fas fa-id-card mr-2"></i> <strong>Nomor Pendaftaran:</strong></p>
                            <div class="text-3xl font-bold tracking-wider"><?= htmlspecialchars($pendaftaran['nomor_pendaftaran']) ?></div>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <div class="mb-4">
                            <div class="text-sm text-blue-100 mb-1">Status Pendaftaran</div>
                            <?php
                            $status_class = [
                                'baru' => 'badge-baru',
                                'dikonfirmasi' => 'badge-dikonfirmasi',
                                'diproses' => 'badge-diproses',
                                'selesai' => 'badge-selesai',
                                'dibatalkan' => 'bg-red-100 text-red-800'
                            ];
                            $status_text = [
                                'baru' => 'BARU',
                                'dikonfirmasi' => 'DIKONFIRMASI',
                                'diproses' => 'DIPROSES',
                                'selesai' => 'SELESAI',
                                'dibatalkan' => 'DIBATALKAN'
                            ];
                            $status = $pendaftaran['status_pendaftaran'];
                            ?>
                            <div class="badge-status <?= $status_class[$status] ?>">
                                <?= $status_text[$status] ?>
                            </div>
                        </div>
                        <p class="text-sm text-blue-100">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            <?= date('d F Y, H:i', strtotime($pendaftaran['dibuat_pada'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Information Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Data Pribadi -->
                <div class="info-card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-circle text-blue-600 mr-3"></i>
                        Data Pribadi
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Nama Lengkap</span>
                            <span class="font-semibold"><?= htmlspecialchars($pendaftaran['nama_lengkap']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Email</span>
                            <span class="font-semibold"><?= htmlspecialchars($pendaftaran['email']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Telepon</span>
                            <span class="font-semibold"><?= htmlspecialchars($pendaftaran['telepon']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Tanggal Lahir</span>
                            <span class="font-semibold"><?= date('d/m/Y', strtotime($pendaftaran['tanggal_lahir'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Jenis Kelamin</span>
                            <span class="font-semibold"><?= ($pendaftaran['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Detail Kursus -->
                <div class="info-card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-book text-purple-600 mr-3"></i>
                        Detail Kursus
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Paket Kursus</span>
                            <span class="font-semibold text-right"><?= htmlspecialchars($pendaftaran['nama_paket']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Tipe Mobil</span>
                            <span class="font-semibold">
                                <?= ucfirst($pendaftaran['tipe_mobil']) ?>
                                <?php if ($pendaftaran['tipe_mobil_paket'] == 'keduanya'): ?>
                                    <span class="text-xs text-green-600 ml-2">(Paket mendukung manual & matic)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Jadwal Preferensi</span>
                            <span class="font-semibold"><?= ucfirst($pendaftaran['jadwal_preferensi']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Pengalaman</span>
                            <span class="font-semibold">
                                <?= str_replace('_', ' ', ucfirst($pendaftaran['pengalaman_mengemudi'])) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Durasi</span>
                            <span class="font-semibold"><?= $pendaftaran['durasi_jam'] ?> menit</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biaya dan Informasi Tambahan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Biaya -->
                <div class="info-card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-money-bill-wave text-green-600 mr-3"></i>
                        Informasi Biaya
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Harga Paket</span>
                            <span class="font-semibold">Rp <?= number_format($pendaftaran['harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Status Pembayaran</span>
                            <span class="font-semibold text-yellow-600">BELUM DIBAYAR</span>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-yellow-600 mr-3"></i>
                                <div>
                                    <p class="text-sm text-yellow-800 font-semibold">Pembayaran di Lokasi</p>
                                    <p class="text-xs text-yellow-700">Bayar saat datang ke kantor dengan menunjukkan nomor pendaftaran ini</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Tambahan -->
                <div class="info-card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-orange-600 mr-3"></i>
                        Informasi Tambahan
                    </h3>
                    <div class="space-y-3">
                        <?php if (!empty($pendaftaran['kondisi_medis'])): ?>
                        <div>
                            <span class="text-gray-600 block mb-1">Kondisi Medis</span>
                            <span class="font-semibold"><?= htmlspecialchars($pendaftaran['kondisi_medis']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pendaftaran['nama_kontak_darurat'])): ?>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">Kontak Darurat</span>
                            <div class="text-right">
                                <span class="font-semibold block"><?= htmlspecialchars($pendaftaran['nama_kontak_darurat']) ?></span>
                                <span class="text-sm text-gray-500"><?= htmlspecialchars($pendaftaran['kontak_darurat']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm text-blue-800 font-semibold">Lokasi Kantor</p>
                                    <p class="text-xs text-blue-700">Jl. Contoh No. 123, Jakarta. Buka Senin-Jumat 08:00-17:00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instruksi -->
            <div class="info-card p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-clipboard-list text-red-600 mr-3"></i>
                    Langkah Selanjutnya
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-print text-xl"></i>
                        </div>
                        <p class="font-semibold text-gray-800 mb-1">Simpan/Cetak</p>
                        <p class="text-sm text-gray-600">Simpan nomor pendaftaran ini</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-phone text-xl"></i>
                        </div>
                        <p class="font-semibold text-gray-800 mb-1">Hubungi Kami</p>
                        <p class="text-sm text-gray-600">Konfirmasi kedatangan via telepon</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <p class="font-semibold text-gray-800 mb-1">Datang ke Lokasi</p>
                        <p class="text-sm text-gray-600">Bawa KTP asli untuk verifikasi</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-money-bill text-xl"></i>
                        </div>
                        <p class="font-semibold text-gray-800 mb-1">Bayar di Lokasi</p>
                        <p class="text-sm text-gray-600">Lakukan pembayaran dan mulai kursus</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row gap-4 justify-center mb-12">
                <button onclick="window.print()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                    <i class="fas fa-print"></i>
                    Cetak Konfirmasi
                </button>
                <a href="index.php" 
                   class="border border-blue-600 text-blue-600 hover:bg-blue-50 px-8 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                    <i class="fas fa-home"></i>
                    Kembali ke Beranda
                </a>
                <a href="https://wa.me/6281234567890?text=Halo,%20saya%20telah%20mendaftar%20dengan%20nomor%20<?= urlencode($pendaftaran['nomor_pendaftaran']) ?>" 
                   target="_blank"
                   class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                    <i class="fab fa-whatsapp"></i>
                    Konfirmasi via WhatsApp
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
    // Simpan ID pendaftaran di sessionStorage untuk akses berikutnya
    <?php if ($pendaftaran_id): ?>
        sessionStorage.setItem('last_registration_id', '<?= $pendaftaran_id ?>');
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>