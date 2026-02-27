<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

try {
    // Update kendaraan yang statusnya 'dipakai' tapi sudah tidak ada jadwal aktif
    $update_query = "
        UPDATE kendaraan k
        SET status_ketersediaan = 'tersedia'
        WHERE k.status_ketersediaan = 'dipakai'
        AND k.id NOT IN (
            SELECT DISTINCT jk.kendaraan_id 
            FROM jadwal_kursus jk 
            WHERE jk.kendaraan_id IS NOT NULL 
            AND jk.status NOT IN ('selesai', 'dibatalkan')
            AND jk.tanggal_jadwal >= CURDATE()
        )
    ";
    
    $stmt = $db->prepare($update_query);
    $stmt->execute();
    
    $affected_rows = $stmt->rowCount();
    
    // Log the update
    error_log("Vehicle status updated: $affected_rows vehicles set to 'tersedia'");
    
} catch (PDOException $e) {
    error_log("Error updating vehicle status: " . $e->getMessage());
}