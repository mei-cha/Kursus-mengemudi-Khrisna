<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "Mengupdate status jadwal yang sudah lewat...\n";

try {
    $db->beginTransaction();

    // Update jadwal yang sudah lewat menjadi 'selesai'
    $update_stmt = $db->prepare("
        UPDATE jadwal_kursus
        SET status = 'selesai'
        WHERE status NOT IN ('selesai', 'dibatalkan')
        AND CONCAT(tanggal_jadwal, ' ', jam_selesai) < NOW()
    ");

    $result = $update_stmt->execute();
    $affected_rows = $update_stmt->rowCount();

    echo "Jadwal yang diupdate: $affected_rows\n";

    // Sekarang update status kendaraan yang tidak lagi digunakan
    $vehicle_update_stmt = $db->prepare("
        UPDATE kendaraan
        SET status_ketersediaan = 'tersedia'
        WHERE id IN (
            SELECT DISTINCT k.id
            FROM kendaraan k
            LEFT JOIN jadwal_kursus j ON k.id = j.kendaraan_id
                AND j.tipe_sesi = 'praktik'
                AND j.status NOT IN ('selesai', 'dibatalkan')
                AND j.tanggal_jadwal >= CURDATE()
            WHERE k.status_ketersediaan = 'dipakai'
            AND j.id IS NULL
        )
    ");

    $vehicle_result = $vehicle_update_stmt->execute();
    $vehicle_affected = $vehicle_update_stmt->rowCount();

    echo "Kendaraan yang dikembalikan ke tersedia: $vehicle_affected\n";

    $db->commit();
    echo "Update selesai!\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
