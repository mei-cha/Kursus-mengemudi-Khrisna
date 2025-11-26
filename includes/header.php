<?php
// Sambungkan ke database
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data dari database
try {
    // Ambil paket kursus
    $paket_query = $db->query("SELECT * FROM paket_kursus WHERE tersedia = 1 ORDER BY harga ASC");
    $paket_kursus = $paket_query->fetchAll(PDO::FETCH_ASSOC);

    // Ambil testimoni
    $testimoni_query = $db->query("SELECT * FROM testimoni WHERE status = 'disetujui' ORDER BY rating DESC, created_at DESC LIMIT 6");
    $testimoni = $testimoni_query->fetchAll(PDO::FETCH_ASSOC);

    // Ambil galeri
    $galeri_query = $db->query("SELECT * FROM galeri WHERE status = 'aktif' ORDER BY urutan_tampil ASC, created_at DESC LIMIT 8");
    $galeri = $galeri_query->fetchAll(PDO::FETCH_ASSOC);

    // Ambil instruktur
    $instruktur_query = $db->query("SELECT * FROM instruktur WHERE aktif = 1 ORDER BY pengalaman_tahun DESC, rating DESC LIMIT 4");
    $instruktur = $instruktur_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fallback data jika query gagal
    $paket_kursus = [];
    $testimoni = [];
    $galeri = [];
    $instruktur = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kursus Mengemudi Mobil Krishna - Professional Driving Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar yang Disederhanakan -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <!-- Logo -->
                <div class="flex items-center">
                    <i class="fas fa-car text-2xl text-blue-600 mr-2"></i>
                    <span class="text-xl font-bold text-gray-800">Krishna Driving</span>
                </div>

                <!-- Desktop Menu - DISEDERHANAKAN -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Beranda</a>
                    <a href="paket-kursus.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Paket</a>
                    <a href="instruktur.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Instruktur</a>
                    <a href="testimoni.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Testimoni</a>
                    <a href="#galeri" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Galeri</a>
                    <a href="tentang-kontak.php" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Tentang & Kontak</a>
                    
                    <!-- Menu Cek Status -->
                    <a href="cek-status.php" class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        <i class="fas fa-search mr-1 text-sm"></i>Cek Status
                    </a>
                    
                    <!-- Tombol CTA yang menonjol -->
                    <a href="#daftar" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 shadow-md hover:shadow-lg">
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

            <!-- Mobile Menu - DISEDERHANAKAN -->
            <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-100 mt-2">
                <div class="flex flex-col space-y-1 pt-3">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-home mr-3 w-5 text-center"></i>Beranda
                    </a>
                    <a href="paket-kursus.php" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-gift mr-3 w-5 text-center"></i>Paket Kursus
                    </a>
                    <a href="#instruktur" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-users mr-3 w-5 text-center"></i>Instruktur
                    </a>
                    <a href="testimoni.php" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-star mr-3 w-5 text-center"></i>Testimoni
                    </a>
                    <a href="#galeri" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-images mr-3 w-5 text-center"></i>Galeri
                    </a>
                    <a href="tentang-kontak.php" class="text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300 flex items-center">
                        <i class="fas fa-info-circle mr-3 w-5 text-center"></i>Tentang & Kontak
                    </a>
                    
                    <!-- Menu Cek Status untuk Mobile -->
                    <a href="cek-status.php" class="flex items-center text-gray-700 hover:text-blue-600 font-medium py-3 px-4 rounded-lg hover:bg-blue-50 transition duration-300">
                        <i class="fas fa-search mr-3 w-5 text-center"></i>Cek Status
                    </a>
                    
                    <!-- Tombol Daftar untuk Mobile -->
                    <a href="#daftar" class="bg-blue-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 mt-2 shadow-md flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </nav>