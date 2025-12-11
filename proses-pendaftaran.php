<?php
session_start();
require_once 'config/database.php';

// Buat koneksi database
$database = new Database();
$db = $database->getConnection();

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $errors = [];
        
        // Required fields
        $required_fields = [
            'nama_lengkap', 'email', 'telepon', 'tanggal_lahir', 
            'jenis_kelamin', 'alamat', 'paket_kursus_id', 
            'tipe_mobil', 'jadwal_preferensi'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Field $field wajib diisi";
            }
        }
        
        // Validasi email
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid";
        }
        
        // Validasi telepon
        if (!empty($_POST['telepon']) && !preg_match('/^0[0-9]{9,12}$/', $_POST['telepon'])) {
            $errors[] = "Format nomor telepon tidak valid";
        }
        
        // Validasi tanggal lahir (minimal 17 tahun)
        if (!empty($_POST['tanggal_lahir'])) {
            $birthDate = new DateTime($_POST['tanggal_lahir']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            
            if ($age < 17) {
                $errors[] = "Minimal usia 17 tahun untuk mengikuti kursus";
            }
        }
        
        // Validasi jadwal_preferensi
        $jadwal_valid = ['pagi', 'siang', 'sore'];
        if (!empty($_POST['jadwal_preferensi']) && !in_array($_POST['jadwal_preferensi'], $jadwal_valid)) {
            $errors[] = "Jadwal preferensi tidak valid";
        }
        
        // Validasi tipe_mobil
        $tipe_mobil_valid = ['manual', 'matic', 'keduanya'];
        if (!empty($_POST['tipe_mobil']) && !in_array($_POST['tipe_mobil'], $tipe_mobil_valid)) {
            $errors[] = "Tipe mobil tidak valid";
        }
        
        // Jika ada errors, return error
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $errors
            ]);
            exit;
        }
        
        // Generate nomor pendaftaran
        $nomor_pendaftaran = 'KD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

        
        // Prepare data untuk database - GUNAKAN TABEL pendaftaran_siswa
        $data = [
            'nomor_pendaftaran' => $nomor_pendaftaran,
            'nama_lengkap' => $_POST['nama_lengkap'],
            'email' => $_POST['email'],
            'telepon' => $_POST['telepon'],
            'alamat' => $_POST['alamat'],
            'tanggal_lahir' => $_POST['tanggal_lahir'],
            'jenis_kelamin' => $_POST['jenis_kelamin'],
            'paket_kursus_id' => $_POST['paket_kursus_id'],
            'tipe_mobil' => $_POST['tipe_mobil'],
            'jadwal_preferensi' => $_POST['jadwal_preferensi'],
            'pengalaman_mengemudi' => $_POST['pengalaman_mengemudi'] ?? 'pemula',
            'kondisi_medis' => $_POST['kondisi_medis'] ?? null,
            'kontak_darurat' => $_POST['kontak_darurat'] ?? null,
            'nama_kontak_darurat' => $_POST['nama_kontak_darurat'] ?? null,
            'status_pendaftaran' => 'baru',
            'catatan_admin' => null,
            'dibuat_pada' => date('Y-m-d H:i:s')
        ];
        
        // Prepare SQL query - GUNAKAN TABEL pendaftaran_siswa
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO pendaftaran_siswa ($columns) VALUES ($placeholders)";
        $stmt = $db->prepare($sql);
        
        // Bind parameters
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        // Execute query
        if ($stmt->execute()) {
            // Get the last inserted ID
            $last_id = $db->lastInsertId();
            
            // Simpan di session untuk konfirmasi langsung
            $_SESSION['last_registration_id'] = $last_id;
            $_SESSION['last_registration_nomor'] = $nomor_pendaftaran;
            $_SESSION['last_registration_phone'] = $_POST['telepon'];
            
            // Return success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Pendaftaran berhasil! Nomor pendaftaran Anda telah dibuat.',
                'data' => [
                    'nomor_pendaftaran' => $nomor_pendaftaran,
                    'id' => $last_id
                ]
            ]);
        } else {
            // Get error info
            $errorInfo = $stmt->errorInfo();
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan data ke database',
                'error_details' => $errorInfo[2]
            ]);
        }
        
    } catch (PDOException $e) {
        // Database error
        echo json_encode([
            'status' => 'error',
            'message' => 'Terjadi kesalahan database',
            'error_details' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        // General error
        echo json_encode([
            'status' => 'error',
            'message' => 'Terjadi kesalahan',
            'error_details' => $e->getMessage()
        ]);
    }
} else {
    // Method not allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak diizinkan'
    ]);
}
?>