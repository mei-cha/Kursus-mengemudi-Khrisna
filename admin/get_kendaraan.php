<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM kendaraan WHERE id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vehicle) {
    // Format dates for form input
    if ($vehicle['tanggal_pajak'] && $vehicle['tanggal_pajak'] != '0000-00-00') {
        $vehicle['tanggal_pajak'] = $vehicle['tanggal_pajak'];
    } else {
        $vehicle['tanggal_pajak'] = '';
    }
    
    if ($vehicle['tanggal_stnk'] && $vehicle['tanggal_stnk'] != '0000-00-00') {
        $vehicle['tanggal_stnk'] = $vehicle['tanggal_stnk'];
    } else {
        $vehicle['tanggal_stnk'] = '';
    }
    
    echo json_encode(['success' => true, 'vehicle' => $vehicle]);
} else {
    echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
}
?>