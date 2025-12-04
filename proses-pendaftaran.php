<?php
// proses-pendaftaran.php
session_start();
require_once 'config/database.php';

// Buat koneksi database
$database = new Database();
$db = $database->getConnection();

// Set header untuk JSON response
header('Content-Type: application/json');

// Cek apakah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

try {
    // Ambil data dari form
    $data = [
        'nama_lengkap' => $_POST['nama_lengkap'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telepon' => $_POST['telepon'] ?? '',
        'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
        'alamat' => $_POST['alamat'] ?? '',
        'tanggal_kursus' => $_POST['tanggal_kursus'] ?? '',
        'paket_kursus_id' => $_POST['paket_kursus_id'] ?? 0,
        'tipe_mobil' => $_POST['tipe_mobil'] ?? 'manual',
        'pengalaman' => $_POST['pengalaman'] ?? 'pemula',
        'kondisi_medis' => $_POST['kondisi_medis'] ?? '',
        'kontak_darurat' => $_POST['kontak_darurat'] ?? '',
        'nama_kontak_darurat' => $_POST['nama_kontak_darurat'] ?? '',
        'persetujuan' => isset($_POST['persetujuan']) ? 1 : 0
    ];

    // Handle jadwal preferensi (checkbox array)
    $jadwal_preferensi = $_POST['jadwal_preferensi'] ?? [];
    // Ambil hanya nilai pertama jika multiple (sesuai enum di database)
    $data['jadwal_preferensi'] = !empty($jadwal_preferensi) ? strtolower($jadwal_preferensi[0]) : 'pagi';

    // Validasi data wajib
    $errors = [];
    
    // Validasi field wajib
    $required_fields = [
        'nama_lengkap' => 'Nama lengkap',
        'email' => 'Email',
        'telepon' => 'Telepon',
        'alamat' => 'Alamat',
        'tanggal_lahir' => 'Tanggal lahir',
        'jenis_kelamin' => 'Jenis kelamin',
        'paket_kursus_id' => 'Paket kursus'
    ];
    
    foreach ($required_fields as $field => $label) {
        if (empty(trim($data[$field]))) {
            $errors[] = "$label wajib diisi";
        }
    }
    
    // Validasi tanggal lahir
    if (!empty($data['tanggal_lahir'])) {
        $tanggal_lahir = DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
        if (!$tanggal_lahir || $tanggal_lahir->format('Y-m-d') !== $data['tanggal_lahir']) {
            $errors[] = 'Format tanggal lahir tidak valid';
        }
        
        // Cek usia minimal 17 tahun
        $usia = $tanggal_lahir->diff(new DateTime())->y;
        if ($usia < 17) {
            $errors[] = 'Usia minimal 17 tahun untuk mendaftar kursus mengemudi';
        }
    }
    
    // Validasi email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    // Validasi telepon (minimal 10 digit)
    if (!empty($data['telepon']) && !preg_match('/^[0-9]{10,13}$/', $data['telepon'])) {
        $errors[] = 'Nomor telepon harus 10-13 digit angka';
    }
    
    // Validasi paket kursus
    if (!empty($data['paket_kursus_id'])) {
        $stmt = $db->prepare("SELECT id FROM paket_kursus WHERE id = ? AND tersedia = 1");
        $stmt->execute([$data['paket_kursus_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Paket kursus tidak tersedia';
        }
    }
    
    // Validasi jadwal preferensi
    if (empty($jadwal_preferensi)) {
        $errors[] = 'Pilih minimal satu jadwal preferensi';
    }
    
    // Validasi persetujuan
    if (!$data['persetujuan']) {
        $errors[] = 'Harap menyetujui persyaratan';
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
    
    // Generate nomor pendaftaran (sesuai dengan proses yang ada)
    $tahun = date('Y');
    $bulan = date('m');
    $stmt = $db->query("SELECT COUNT(*) as total FROM pendaftaran_siswa WHERE YEAR(dibuat_pada) = $tahun");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $urutan = $result['total'] + 1;
    $nomor_pendaftaran = "KDS-" . $tahun . $bulan . str_pad($urutan, 4, '0', STR_PAD_LEFT);
    
    // Format data untuk database
    if (!empty($data['tanggal_lahir'])) {
        $tanggal_lahir_db = $data['tanggal_lahir'];
    } else {
        $tanggal_lahir_db = null;
    }
    
    // Convert tipe_mobil ke lowercase (sesuai enum di database)
    $tipe_mobil_db = strtolower($data['tipe_mobil']);
    
    // Convert pengalaman ke format database
    $pengalaman_db = strtolower($data['pengalaman']);
    if ($pengalaman_db === 'menengah') {
        $pengalaman_db = 'pernah_kursus';
    } elseif ($pengalaman_db === 'lanjutan') {
        $pengalaman_db = 'pernah_ujian';
    }
    
    // Insert data ke database
    $sql = "INSERT INTO pendaftaran_siswa (
        nomor_pendaftaran, nama_lengkap, email, telepon, alamat, tanggal_lahir,
        jenis_kelamin, paket_kursus_id, tipe_mobil, jadwal_preferensi,
        pengalaman_mengemudi, kondisi_medis, kontak_darurat, nama_kontak_darurat,
        status_pendaftaran
    ) VALUES (
        :nomor_pendaftaran, :nama_lengkap, :email, :telepon, :alamat, :tanggal_lahir,
        :jenis_kelamin, :paket_kursus_id, :tipe_mobil, :jadwal_preferensi,
        :pengalaman_mengemudi, :kondisi_medis, :kontak_darurat, :nama_kontak_darurat,
        'baru'
    )";
    
    $stmt = $db->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':nomor_pendaftaran', $nomor_pendaftaran);
    $stmt->bindParam(':nama_lengkap', $data['nama_lengkap']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':telepon', $data['telepon']);
    $stmt->bindParam(':alamat', $data['alamat']);
    $stmt->bindParam(':tanggal_lahir', $tanggal_lahir_db);
    $stmt->bindParam(':jenis_kelamin', $data['jenis_kelamin']);
    $stmt->bindParam(':paket_kursus_id', $data['paket_kursus_id'], PDO::PARAM_INT);
    $stmt->bindParam(':tipe_mobil', $tipe_mobil_db);
    $stmt->bindParam(':jadwal_preferensi', $data['jadwal_preferensi']);
    $stmt->bindParam(':pengalaman_mengemudi', $pengalaman_db);
    $stmt->bindParam(':kondisi_medis', $data['kondisi_medis']);
    $stmt->bindParam(':kontak_darurat', $data['kontak_darurat']);
    $stmt->bindParam(':nama_kontak_darurat', $data['nama_kontak_darurat']);
    
    // Eksekusi query
    if ($stmt->execute()) {
        $lastId = $db->lastInsertId();
        
        // Ambil data paket untuk konfirmasi
        $stmt_paket = $db->prepare("SELECT nama_paket, harga FROM paket_kursus WHERE id = ?");
        $stmt_paket->execute([$data['paket_kursus_id']]);
        $paket = $stmt_paket->fetch(PDO::FETCH_ASSOC);
        
        // Simpan data di session
        $_SESSION['pendaftaran_success'] = true;
        $_SESSION['pendaftaran_data'] = [
            'id' => $lastId,
            'nomor_pendaftaran' => $nomor_pendaftaran,
            'nama_lengkap' => $data['nama_lengkap'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
            'paket' => $paket['nama_paket'] ?? '',
            'harga' => $paket['harga'] ?? 0,
            'tanggal_daftar' => date('Y-m-d H:i:s')
        ];
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil disimpan!',
            'data' => [
                'nomor_pendaftaran' => $nomor_pendaftaran,
                'id' => $lastId
            ],
            'redirect_url' => 'konfirmasi-pendaftaran.php'
        ]);
    } else {
        throw new Exception('Gagal menyimpan data ke database');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan database: ' . $e->getMessage()
    ]);
    error_log("Database error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    error_log("Pendaftaran error: " . $e->getMessage());
}
?>