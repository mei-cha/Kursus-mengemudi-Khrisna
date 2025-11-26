<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['error' => 'Access denied']));
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['error' => 'ID is required']));
}

try {
    $stmt = $db->prepare("SELECT * FROM paket_kursus WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Data not found']);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>