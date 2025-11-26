<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID required');
}

$db = (new Database())->getConnection();
$id = $_GET['id'];

$stmt = $db->prepare("SELECT * FROM instruktur WHERE id = ?");
$stmt->execute([$id]);
$instruktur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instruktur) {
    header('HTTP/1.1 404 Not Found');
    exit('Instruktur not found');
}

header('Content-Type: application/json');
echo json_encode($instruktur);
?>