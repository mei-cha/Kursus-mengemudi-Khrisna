<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_siswa = $_POST['nama_siswa'];
    $paket_kursus = $_POST['paket_kursus'];
    $rating = $_POST['rating'];
    $testimoni_text = $_POST['testimoni_text'];
    $lokasi = $_POST['lokasi'];
    $usia = $_POST['usia'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("INSERT INTO testimoni (nama_siswa, paket_kursus, rating, testimoni_text, lokasi, usia, status, tanggal_testimoni) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    if ($stmt->execute([$nama_siswa, $paket_kursus, $rating, $testimoni_text, $lokasi, $usia, $status])) {
        header('Location: testimoni.php?success=Testimoni berhasil ditambahkan');
        exit;
    } else {
        $error = "Gagal menambahkan testimoni!";
    }
}
?>

<!-- Tambahkan button di halaman testimoni.php -->
<div class="px-6 py-4 border-b border-gray-200">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">
            Data Testimoni (<?= count($testimoni) ?>)
        </h3>
        <div class="flex items-center space-x-2">
            <a href="add_testimoni.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                <i class="fas fa-plus mr-2"></i>Tambah Testimoni
            </a>
        </div>
    </div>
</div>