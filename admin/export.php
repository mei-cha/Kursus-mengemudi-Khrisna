<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle export request
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $format = $_GET['format'] ?? 'excel';
    
    switch ($type) {
        case 'pendaftaran':
            exportRegistrations($db, $format);
            break;
        case 'pembayaran':
            exportPayments($db, $format);
            break;
        case 'testimoni':
            exportTestimonials($db, $format);
            break;
        default:
            header('Location: export.php?error=Jenis export tidak valid');
            exit;
    }
}

function exportRegistrations($db, $format) {
    $stmt = $db->query("
        SELECT 
            ps.nomor_pendaftaran,
            ps.nama_lengkap,
            ps.email,
            ps.telepon,
            ps.alamat,
            ps.tanggal_lahir,
            ps.jenis_kelamin,
            pk.nama_paket,
            pk.harga,
            ps.tipe_mobil,
            ps.jadwal_preferensi,
            ps.pengalaman_mengemudi,
            ps.status_pendaftaran,
            ps.dibuat_pada
        FROM pendaftaran_siswa ps
        JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
        ORDER BY ps.dibuat_pada DESC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = "data_pendaftaran_" . date('Y-m-d') . ".xlsx";
    exportToExcel($data, $filename, 'Data Pendaftaran');
}

function exportPayments($db, $format) {
    $stmt = $db->query("
        SELECT 
            p.nomor_kwitansi,
            ps.nomor_pendaftaran,
            ps.nama_lengkap,
            p.tanggal_pembayaran,
            p.jumlah,
            p.metode_pembayaran,
            p.tipe_pembayaran,
            p.status,
            p.diverifikasi_oleh,
            p.tanggal_verifikasi,
            pk.nama_paket,
            pk.harga as harga_paket
        FROM pembayaran p
        JOIN pendaftaran_siswa ps ON p.pendaftaran_id = ps.id
        JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
        ORDER BY p.tanggal_pembayaran DESC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = "data_pembayaran_" . date('Y-m-d') . ".xlsx";
    exportToExcel($data, $filename, 'Data Pembayaran');
}

function exportTestimonials($db, $format) {
    $stmt = $db->query("
        SELECT 
            nama_siswa,
            paket_kursus,
            rating,
            testimoni_text,
            lokasi,
            usia,
            status,
            tanggal_testimoni,
            created_at
        FROM testimoni
        ORDER BY created_at DESC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = "data_testimoni_" . date('Y-m-d') . ".xlsx";
    exportToExcel($data, $filename, 'Data Testimoni');
}

function exportToExcel($data, $filename, $sheetTitle) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new XLSXWriter();
    $writer->writeSheet($data, $sheetTitle);
    $writer->writeToFile('php://output');
    exit;
}

// Get statistics for dashboard
$total_pendaftaran = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa")->fetch()['total'];
$total_pembayaran = $db->query("SELECT COUNT(*) as total FROM pembayaran")->fetch()['total'];
$total_testimoni = $db->query("SELECT COUNT(*) as total FROM testimoni")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        .main-content {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Export Data</h1>
                        <p class="text-gray-600">Export data ke format Excel</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
                <?php endif; ?>

                <!-- Export Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Export Pendaftaran -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Data Pendaftaran</h3>
                                <p class="text-sm text-gray-600"><?= $total_pendaftaran ?> records</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">
                            Export semua data pendaftaran siswa termasuk informasi pribadi dan pilihan paket.
                        </p>
                        <a href="export.php?export=pendaftaran&format=excel" 
                           class="block w-full bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            <i class="fas fa-download mr-2"></i>Export Excel
                        </a>
                    </div>

                    <!-- Export Pembayaran -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-credit-card text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Data Pembayaran</h3>
                                <p class="text-sm text-gray-600"><?= $total_pembayaran ?> records</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">
                            Export data pembayaran termasuk status verifikasi dan informasi transaksi.
                        </p>
                        <a href="export.php?export=pembayaran&format=excel" 
                           class="block w-full bg-green-600 text-white text-center py-2 rounded-lg hover:bg-green-700 transition duration-300">
                            <i class="fas fa-download mr-2"></i>Export Excel
                        </a>
                    </div>

                    <!-- Export Testimoni -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-star text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Data Testimoni</h3>
                                <p class="text-sm text-gray-600"><?= $total_testimoni ?> records</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">
                            Export semua testimoni dari siswa termasuk rating dan status approval.
                        </p>
                        <a href="export.php?export=testimoni&format=excel" 
                           class="block w-full bg-purple-600 text-white text-center py-2 rounded-lg hover:bg-purple-700 transition duration-300">
                            <i class="fas fa-download mr-2"></i>Export Excel
                        </a>
                    </div>
                </div>

                <!-- Custom Export Form -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Export Custom</h3>
                    </div>
                    <div class="p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Data</label>
                                <select name="export" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Jenis Data</option>
                                    <option value="pendaftaran">Data Pendaftaran</option>
                                    <option value="pembayaran">Data Pembayaran</option>
                                    <option value="testimoni">Data Testimoni</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                                <select name="format" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="csv" disabled>CSV (.csv) - Coming Soon</option>
                                    <option value="pdf" disabled>PDF (.pdf) - Coming Soon</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <button type="submit" 
                                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition duration-300">
                                    <i class="fas fa-file-export mr-2"></i>Generate Export
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export History -->
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Panduan Export</h3>
                    </div>
                    <div class="p-6">
                        <div class="prose max-w-none">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Fitur Export Data</h4>
                            <ul class="list-disc list-inside space-y-2 text-gray-600">
                                <li><strong>Data Pendaftaran:</strong> Informasi lengkap siswa, paket yang dipilih, status pendaftaran</li>
                                <li><strong>Data Pembayaran:</strong> Transaksi pembayaran, status verifikasi, nominal, dan metode</li>
                                <li><strong>Data Testimoni:</strong> Ulasan dari siswa, rating, status approval</li>
                            </ul>
                            
                            <h4 class="text-lg font-semibold text-gray-800 mt-6 mb-3">Format yang Tersedia</h4>
                            <ul class="list-disc list-inside space-y-2 text-gray-600">
                                <li><strong>Excel (.xlsx):</strong> Format terbaik untuk analisis data dan reporting</li>
                                <li><strong>CSV (.csv):</strong> Coming soon - untuk integrasi dengan sistem lain</li>
                                <li><strong>PDF (.pdf):</strong> Coming soon - untuk laporan formal</li>
                            </ul>
                            
                            <h4 class="text-lg font-semibold text-gray-800 mt-6 mb-3">Tips Export</h4>
                            <ul class="list-disc list-inside space-y-2 text-gray-600">
                                <li>Export data secara berkala untuk backup</li>
                                <li>Gunakan filter di halaman masing-masing sebelum export untuk data spesifik</li>
                                <li>File Excel dapat langsung dibuka di Microsoft Excel atau Google Sheets</li>
                                <li>Data export selalu dalam format terbaru (real-time)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- sidebar -->
    <script src="../assets/js/sidebar.js"></script>
    <script>

        // Add confirmation for exports
        document.querySelectorAll('a[href*="export.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin mengexport data ini?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>