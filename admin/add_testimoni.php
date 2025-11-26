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
    $lokasi = $_POST['lokasi'] ?? '';
    $usia = $_POST['usia'] ?? null;
    $status = $_POST['status'] ?? 'disetujui';
    
    // Handle photo upload
    $foto_siswa = null;
    if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] === 0) {
        $foto_name = time() . '_' . basename($_FILES['foto_siswa']['name']);
        $target_dir = "../assets/images/testimoni/";
        
        // Create directory if not exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $target_file = $target_dir . $foto_name;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["foto_siswa"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (max 2MB)
            if ($_FILES["foto_siswa"]["size"] <= 2097152) {
                // Allow certain file formats
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES["foto_siswa"]["tmp_name"], $target_file)) {
                        $foto_siswa = $foto_name;
                    }
                }
            }
        }
    }
    
    $stmt = $db->prepare("INSERT INTO testimoni (nama_siswa, paket_kursus, rating, testimoni_text, foto_siswa, lokasi, usia, status, tanggal_testimoni) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
    
    if ($stmt->execute([$nama_lengkap, $paket_kursus, $rating, $testimoni_text, $foto_siswa, $lokasi, $usia, $status])) {
        $_SESSION['success'] = "Testimoni berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan testimoni!";
    }
    
    header('Location: testimoni.php');
    exit;
}
?>