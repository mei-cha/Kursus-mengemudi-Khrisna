<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Ambil data dari form
    $nama_siswa = $_POST['nama_siswa'] ?? '';
    $paket_kursus = $_POST['paket_kursus'] ?? '';
    $rating = $_POST['rating'] ?? 0;
    $testimoni_text = $_POST['testimoni_text'] ?? '';
    $usia = $_POST['usia'] ?? null;
    $lokasi = $_POST['lokasi'] ?? '';
    $tanggal_testimoni = $_POST['tanggal_testimoni'] ?? date('Y-m-d');
    
    // Validasi data
    if (empty($nama_siswa) || empty($paket_kursus) || empty($testimoni_text) || empty($lokasi) || $rating == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi!']);
        exit;
    }
    
    // Handle file upload (foto siswa)
    $foto_siswa = null;
    if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] === 0) {
        $foto_name = time() . '_' . basename($_FILES['foto_siswa']['name']);
        $target_dir = "assets/images/testimoni/";
        
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
    
    try {
        // Insert data ke database
        $stmt = $db->prepare("INSERT INTO testimoni (nama_siswa, paket_kursus, rating, testimoni_text, foto_siswa, usia, lokasi, tanggal_testimoni, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')");
        
        if ($stmt->execute([$nama_siswa, $paket_kursus, $rating, $testimoni_text, $foto_siswa, $usia, $lokasi, $tanggal_testimoni])) {
            echo json_encode(['success' => true, 'message' => 'Testimoni berhasil dikirim! Menunggu persetujuan admin.']);
        } else {
            throw new Exception('Gagal menyimpan data ke database');
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>