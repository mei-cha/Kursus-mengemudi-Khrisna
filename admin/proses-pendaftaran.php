<?php
session_start();
require_once 'config/database.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Ambil data dari form
$data = [
    'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'telepon' => trim($_POST['telepon'] ?? ''),
    'alamat' => trim($_POST['alamat'] ?? ''),
    'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
    'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
    'paket_kursus_id' => $_POST['paket_kursus_id'] ?? '',
    'tipe_mobil' => $_POST['tipe_mobil'] ?? '',
    'jadwal_preferensi' => $_POST['jadwal_preferensi'] ?? '',
    'pengalaman_mengemudi' => $_POST['pengalaman_mengemudi'] ?? 'pemula',
    'kondisi_medis' => trim($_POST['kondisi_medis'] ?? ''),
    'kontak_darurat' => trim($_POST['kontak_darurat'] ?? ''),
    'nama_kontak_darurat' => trim($_POST['nama_kontak_darurat'] ?? ''),
    'persetujuan' => isset($_POST['persetujuan']) ? 1 : 0,
    'sumber_pendaftaran' => $_POST['sumber_pendaftaran'] ?? 'online'
];

// Validasi data yang wajib diisi
$errors = [];

// Nama lengkap
if (empty($data['nama_lengkap'])) {
    $errors[] = 'Nama lengkap wajib diisi';
} elseif (strlen($data['nama_lengkap']) < 3) {
    $errors[] = 'Nama lengkap minimal 3 karakter';
}

// Email
if (empty($data['email'])) {
    $errors[] = 'Email wajib diisi';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}

// Telepon
if (empty($data['telepon'])) {
    $errors[] = 'Nomor telepon wajib diisi';
} elseif (!preg_match('/^0[0-9]{9,12}$/', $data['telepon'])) {
    $errors[] = 'Format nomor telepon tidak valid (contoh: 081234567890)';
}

// Tanggal lahir
if (empty($data['tanggal_lahir'])) {
    $errors[] = 'Tanggal lahir wajib diisi';
} else {
    $birthDate = new DateTime($data['tanggal_lahir']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < 17) {
        $errors[] = 'Minimal usia 17 tahun untuk mengikuti kursus';
    }
}

// Jenis kelamin
if (empty($data['jenis_kelamin']) || !in_array($data['jenis_kelamin'], ['L', 'P'])) {
    $errors[] = 'Jenis kelamin wajib dipilih';
}

// Alamat
if (empty($data['alamat'])) {
    $errors[] = 'Alamat wajib diisi';
} elseif (strlen($data['alamat']) < 10) {
    $errors[] = 'Alamat minimal 10 karakter';
}

// Paket kursus
if (empty($data['paket_kursus_id'])) {
    $errors[] = 'Paket kursus wajib dipilih';
}

// Tipe mobil
if (empty($data['tipe_mobil'])) {
    $errors[] = 'Tipe mobil wajib diisi';
}

// Jadwal preferensi
if (empty($data['jadwal_preferensi'])) {
    $errors[] = 'Jadwal preferensi wajib dipilih';
}

// Persetujuan
if (!$data['persetujuan']) {
    $errors[] = 'Anda harus menyetujui persyaratan';
}

// Jika ada error, kembalikan error
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Validasi gagal',
        'errors' => $errors
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Cek apakah email sudah terdaftar
    $stmt_check = $db->prepare("SELECT COUNT(*) FROM pendaftaran_siswa WHERE email = ?");
    $stmt_check->execute([$data['email']]);
    $email_count = $stmt_check->fetchColumn();
    
    if ($email_count > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email sudah terdaftar'
        ]);
        exit;
    }
    
    // Generate nomor pendaftaran yang unik
    $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Cek apakah nomor sudah ada
    $stmt_check_nomor = $db->prepare("SELECT COUNT(*) FROM pendaftaran_siswa WHERE nomor_pendaftaran = ?");
    $stmt_check_nomor->execute([$nomor_pendaftaran]);
    $nomor_count = $stmt_check_nomor->fetchColumn();
    
    // Jika nomor sudah ada, generate ulang
    while ($nomor_count > 0) {
        $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt_check_nomor->execute([$nomor_pendaftaran]);
        $nomor_count = $stmt_check_nomor->fetchColumn();
    }
    
    // Mulai transaction
    $db->beginTransaction();
    
    // Insert data pendaftaran
    $stmt = $db->prepare("
        INSERT INTO pendaftaran_siswa 
        (nomor_pendaftaran, nama_lengkap, email, telepon, alamat, tanggal_lahir, 
         jenis_kelamin, paket_kursus_id, tipe_mobil, jadwal_preferensi, 
         pengalaman_mengemudi, kondisi_medis, kontak_darurat, nama_kontak_darurat,
         persetujuan, sumber_pendaftaran, status_pendaftaran, dibuat_pada) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'baru', NOW())
    ");
    
    $result = $stmt->execute([
        $nomor_pendaftaran,
        $data['nama_lengkap'],
        $data['email'],
        $data['telepon'],
        $data['alamat'],
        $data['tanggal_lahir'],
        $data['jenis_kelamin'],
        $data['paket_kursus_id'],
        $data['tipe_mobil'],
        $data['jadwal_preferensi'],
        $data['pengalaman_mengemudi'],
        $data['kondisi_medis'],
        $data['kontak_darurat'],
        $data['nama_kontak_darurat'],
        $data['persetujuan'],
        $data['sumber_pendaftaran']
    ]);
    
    if ($result) {
        $last_id = $db->lastInsertId();
        
        // Jika pendaftaran offline (dari admin), langsung konfirmasi
        if ($data['sumber_pendaftaran'] === 'offline') {
            $update_stmt = $db->prepare("UPDATE pendaftaran_siswa SET status_pendaftaran = 'dikonfirmasi' WHERE id = ?");
            $update_stmt->execute([$last_id]);
        }
        
        $db->commit();
        
        // Response sukses
        echo json_encode([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil!',
            'data' => [
                'nomor_pendaftaran' => $nomor_pendaftaran,
                'sumber_pendaftaran' => $data['sumber_pendaftaran'],
                'id' => $last_id
            ]
        ]);
        
    } else {
        $db->rollBack();
        throw new Exception('Gagal menyimpan data pendaftaran');
    }
    
} catch (Exception $e) {
    // Rollback jika ada error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('Pendaftaran error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
    ]);
}
?>