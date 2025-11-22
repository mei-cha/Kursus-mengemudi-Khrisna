<?php
// Konfigurasi umum
define('SITE_NAME', 'Kursus Mengemudi Mobil Krishna');
define('SITE_URL', 'http://localhost/krishna-driving');
define('ADMIN_EMAIL', 'admin@krishnadriving.com');
define('CONTACT_PHONE', '+62 812-3456-7890');

// Setting waktu
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once 'database.php';
?>