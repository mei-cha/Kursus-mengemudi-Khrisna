<?php
// Sambungkan ke database
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Dapatkan nama file yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Function untuk menentukan menu aktif
function isNavActive($page_name, $current_page)
{
    return $page_name == $current_page ? 'text-blue-600 font-semibold' : 'text-gray-700 hover:text-blue-600';
}

// Function untuk mobile menu
function isMobileNavActive($page_name, $current_page)
{
    return $page_name == $current_page ? 'text-blue-600 bg-blue-50 font-semibold' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50';
}

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
                <!-- Logo - HANYA UNTUK DESKTOP & MOBILE HEADER -->
                <div class="flex items-center">
                    <img src="./assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                    <span class="text-xl font-bold text-gray-800">Krishna Kursus</span>
                </div>

                <!-- Desktop Menu - DENGAN ACTIVE STATE -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php"
                        class="font-medium transition duration-300 <?= isNavActive('index.php', $current_page) ?>">
                        Beranda
                    </a>
                    <a href="paket-kursus.php"
                        class="font-medium transition duration-300 <?= isNavActive('paket-kursus.php', $current_page) ?>">
                        Paket
                    </a>
                    <a href="instruktur.php"
                        class="font-medium transition duration-300 <?= isNavActive('instruktur.php', $current_page) ?>">
                        Instruktur
                    </a>
                    <a href="testimoni.php"
                        class="font-medium transition duration-300 <?= isNavActive('testimoni.php', $current_page) ?>">
                        Testimoni
                    </a>
                    <a href="galeri.php"
                        class="font-medium transition duration-300 <?= isNavActive('galeri.php', $current_page) ?>">
                        Galeri
                    </a>
                    <a href="tentang-kontak.php"
                        class="font-medium transition duration-300 <?= isNavActive('tentang-kontak.php', $current_page) ?>">
                        Tentang & Kontak
                    </a>

                    <!-- Menu Cek Status -->
                    <a href="cek-status.php"
                        class="flex items-center font-medium transition duration-300 <?= isNavActive('cek-status.php', $current_page) ?>">
                        <i class="fas fa-search mr-1 text-sm"></i>Cek Status
                    </a>

                    <!-- Tombol CTA yang menonjol -->
                    <a href="index.php#daftar"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 shadow-md hover:shadow-lg">
                        <i class="fas fa-edit mr-1"></i>Daftar
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button"
                        class="text-gray-700 hover:text-blue-600 transition duration-300 p-2 rounded-lg hover:bg-gray-100">
                        <i id="mobile-menu-icon" class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu - TANPA LOGO, HANYA MENU -->
            <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-100 mt-2">
                <div class="flex flex-col space-y-1 pt-3">
                    <!-- Hanya menu items, tanpa logo -->
                    <a href="index.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('index.php', $current_page) ?>">
                        <i class="fas fa-home mr-3 w-5 text-center"></i>Beranda
                    </a>
                    <a href="paket-kursus.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('paket-kursus.php', $current_page) ?>">
                        <i class="fas fa-gift mr-3 w-5 text-center"></i>Paket Kursus
                    </a>
                    <a href="instruktur.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('instruktur.php', $current_page) ?>">
                        <i class="fas fa-users mr-3 w-5 text-center"></i>Instruktur
                    </a>
                    <a href="testimoni.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('testimoni.php', $current_page) ?>">
                        <i class="fas fa-star mr-3 w-5 text-center"></i>Testimoni
                    </a>
                    <a href="galeri.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('galeri.php', $current_page) ?>">
                        <i class="fas fa-images mr-3 w-5 text-center"></i>Galeri
                    </a>
                    <a href="tentang-kontak.php"
                        class="py-3 px-4 rounded-lg transition duration-300 flex items-center <?= isMobileNavActive('tentang-kontak.php', $current_page) ?>">
                        <i class="fas fa-info-circle mr-3 w-5 text-center"></i>Tentang & Kontak
                    </a>

                    <!-- Menu Cek Status untuk Mobile -->
                    <a href="cek-status.php"
                        class="flex items-center py-3 px-4 rounded-lg transition duration-300 <?= isMobileNavActive('cek-status.php', $current_page) ?>">
                        <i class="fas fa-search mr-3 w-5 text-center"></i>Cek Status
                    </a>

                    <!-- Tombol Daftar untuk Mobile -->
                    <a href="index.php#daftar"
                        class="bg-blue-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 mt-2 shadow-md flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JavaScript untuk Mobile Menu -->
    <script>
        // Mobile Menu Toggle dengan perubahan icon
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuIcon = document.getElementById('mobile-menu-icon');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', function () {
            const isHidden = mobileMenu.classList.toggle('hidden');

            // Toggle icon
            if (isHidden) {
                // Menu tertutup, tampilkan hamburger icon
                mobileMenuIcon.classList.remove('fa-times');
                mobileMenuIcon.classList.add('fa-bars');
            } else {
                // Menu terbuka, tampilkan X icon
                mobileMenuIcon.classList.remove('fa-bars');
                mobileMenuIcon.classList.add('fa-times');
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function (event) {
            if (mobileMenu && mobileMenuButton &&
                !mobileMenu.contains(event.target) &&
                !mobileMenuButton.contains(event.target)) {

                mobileMenu.classList.add('hidden');
                // Kembalikan icon ke hamburger
                mobileMenuIcon.classList.remove('fa-times');
                mobileMenuIcon.classList.add('fa-bars');
            }
        });

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        // Tutup mobile menu setelah klik link
                        if (mobileMenu) {
                            mobileMenu.classList.add('hidden');
                            // Kembalikan icon ke hamburger
                            mobileMenuIcon.classList.remove('fa-times');
                            mobileMenuIcon.classList.add('fa-bars');
                        }
                    }
                }
            });
        });

        // Tutup menu ketika klik link di dalam menu mobile
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function () {
                if (mobileMenu) {
                    mobileMenu.classList.add('hidden');
                    // Kembalikan icon ke hamburger
                    mobileMenuIcon.classList.remove('fa-times');
                    mobileMenuIcon.classList.add('fa-bars');
                }
            });
        });
    </script>