<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

$db = (new Database())->getConnection();

try {
    // Get tentang data
    $stmt_tentang = $db->prepare("SELECT * FROM tentang_kami WHERE id = 1");
    $stmt_tentang->execute();
    $tentang = $stmt_tentang->fetch(PDO::FETCH_ASSOC);
    
    // Get kontak data
    $stmt_kontak = $db->prepare("SELECT * FROM kontak_kami WHERE id = 1");
    $stmt_kontak->execute();
    $kontak = $stmt_kontak->fetch(PDO::FETCH_ASSOC);
    
    if (!$tentang) {
        $tentang = [
            'id' => 1,
            'judul_tentang' => 'Tentang Krishna Driving',
            'deskripsi_sejarah' => '',
            'visi' => '',
            'misi' => '[]'
        ];
    }
    
    if (!$kontak) {
    $kontak = [
        'alamat' => '',
        'telepon_1' => '',
        'email_1' => '',
        'jam_operasional_weekday' => '',
        'jam_operasional_weekend' => '',
        'embed_map' => '',
        'link_map' => '',
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
        'tiktok' => ''
    ];
}
    
    // Decode misi
    if ($tentang['misi']) {
        $tentang['misi'] = json_decode($tentang['misi'], true);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'tentang' => $tentang,
        'kontak' => $kontak
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>