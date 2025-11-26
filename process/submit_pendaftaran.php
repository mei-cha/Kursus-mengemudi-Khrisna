<?php
header('Content-Type: application/json');
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize input
    $nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
    $email = sanitizeInput($_POST['email']);
    $telepon = sanitizeInput($_POST['telepon']);
    $alamat = sanitizeInput($_POST['alamat']);
    $tanggal_lahir = sanitizeInput($_POST['tanggal_lahir']);
    $jenis_kelamin = sanitizeInput($_POST['jenis_kelamin']);
    $paket_kursus_id = sanitizeInput($_POST['paket_kursus_id']);
    $tipe_mobil = sanitizeInput($_POST['tipe_mobil']);
    $jadwal_preferensi = sanitizeInput($_POST['jadwal_preferensi']);
    $pengalaman_mengemudi = sanitizeInput($_POST['pengalaman_mengemudi']);
    $kondisi_medis = sanitizeInput($_POST['kondisi_medis']);
    $kontak_darurat = sanitizeInput($_POST['kontak_darurat']);
    $nama_kontak_darurat = sanitizeInput($_POST['nama_kontak_darurat']);
    
    // Validasi required fields
    $required_fields = [
        'nama_lengkap', 'email', 'telepon', 'alamat', 'tanggal_lahir',
        'jenis_kelamin', 'paket_kursus_id', 'tipe_mobil', 'jadwal_preferensi'
    ];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => 'Field ' . $field . ' harus diisi'
            ]);
            exit;
        }
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Format email tidak valid'
        ]);
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Generate nomor pendaftaran
        $nomor_pendaftaran = generateNomorPendaftaran();
        
        // Insert data ke database
        $query = "INSERT INTO pendaftaran_siswa SET 
            nomor_pendaftaran = :nomor_pendaftaran,
            nama_lengkap = :nama_lengkap,
            email = :email,
            telepon = :telepon,
            alamat = :alamat,
            tanggal_lahir = :tanggal_lahir,
            jenis_kelamin = :jenis_kelamin,
            paket_kursus_id = :paket_kursus_id,
            tipe_mobil = :tipe_mobil,
            jadwal_preferensi = :jadwal_preferensi,
            pengalaman_mengemudi = :pengalaman_mengemudi,
            kondisi_medis = :kondisi_medis,
            kontak_darurat = :kontak_darurat,
            nama_kontak_darurat = :nama_kontak_darurat,
            dibuat_pada = NOW()";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':nomor_pendaftaran', $nomor_pendaftaran);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telepon', $telepon);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':tanggal_lahir', $tanggal_lahir);
        $stmt->bindParam(':jenis_kelamin', $jenis_kelamin);
        $stmt->bindParam(':paket_kursus_id', $paket_kursus_id);
        $stmt->bindParam(':tipe_mobil', $tipe_mobil);
        $stmt->bindParam(':jadwal_preferensi', $jadwal_preferensi);
        $stmt->bindParam(':pengalaman_mengemudi', $pengalaman_mengemudi);
        $stmt->bindParam(':kondisi_medis', $kondisi_medis);
        $stmt->bindParam(':kontak_darurat', $kontak_darurat);
        $stmt->bindParam(':nama_kontak_darurat', $nama_kontak_darurat);
        
        if ($stmt->execute()) {
            // Kirim notifikasi
            sendNotification($nomor_pendaftaran, $nama_lengkap);
            
            echo json_encode([
                'success' => true,
                'nomor_pendaftaran' => $nomor_pendaftaran,
                'message' => 'Pendaftaran berhasil! Nomor pendaftaran Anda: ' . $nomor_pendaftaran
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan data ke database'
            ]);
        }
        
    } catch (PDOException $exception) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
}
?>