<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM instruktur WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Data not found']);
}
?>