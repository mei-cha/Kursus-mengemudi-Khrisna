<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Test connection
try {
    $db->query("SELECT 1");
    echo "Database connection: OK<br>";
} catch (PDOException $e) {
    echo "Database connection FAILED: " . $e->getMessage() . "<br>";
}

// Test table structure
try {
    $stmt = $db->query("DESCRIBE instruktur");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table structure: OK<br>";
    echo "Columns: <br>";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
} catch (PDOException $e) {
    echo "Table check FAILED: " . $e->getMessage() . "<br>";
}

// Test insert
try {
    $test_data = [
        'nama_lengkap' => 'Test Instruktur',
        'nomor_licensi' => 'TEST-001',
        'spesialisasi' => 'manual',
        'pengalaman_tahun' => 5,
        'deskripsi' => 'Ini test instruktur',
        'aktif' => 1
    ];
    
    $stmt = $db->prepare("INSERT INTO instruktur (nama_lengkap, nomor_licensi, spesialisasi, pengalaman_tahun, deskripsi, aktif) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([
        $test_data['nama_lengkap'],
        $test_data['nomor_licensi'],
        $test_data['spesialisasi'],
        $test_data['pengalaman_tahun'],
        $test_data['deskripsi'],
        $test_data['aktif']
    ])) {
        echo "Test INSERT: SUCCESS - ID: " . $db->lastInsertId() . "<br>";
        
        // Hapus test data
        $db->query("DELETE FROM instruktur WHERE nomor_licensi = 'TEST-001'");
        echo "Test data cleaned up<br>";
    } else {
        echo "Test INSERT: FAILED - " . implode(", ", $stmt->errorInfo()) . "<br>";
    }
} catch (PDOException $e) {
    echo "Test INSERT FAILED: " . $e->getMessage() . "<br>";
}

echo "<br><a href='instruktur.php'>Kembali ke Kelola Instruktur</a>";
?>