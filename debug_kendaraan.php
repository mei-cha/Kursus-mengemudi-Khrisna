<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "Status Kendaraan Saat Ini:\n";
$result = $db->query('SELECT id, nomor_plat, merk, model, status_ketersediaan FROM kendaraan');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . ': ' . $row['nomor_plat'] . ' - ' . $row['status_ketersediaan'] . "\n";
}

echo "\nJadwal Aktif dengan Kendaraan:\n";
$result2 = $db->query("
    SELECT j.id, j.tanggal_jadwal, j.jam_mulai, j.jam_selesai, j.status, k.nomor_plat, k.status_ketersediaan
    FROM jadwal_kursus j
    LEFT JOIN kendaraan k ON j.kendaraan_id = k.id
    WHERE j.tipe_sesi = 'praktik'
    AND j.status NOT IN ('selesai', 'dibatalkan')
    AND j.tanggal_jadwal >= CURDATE()
    ORDER BY j.tanggal_jadwal, j.jam_mulai
");
while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
    echo "Jadwal {$row['id']}: {$row['tanggal_jadwal']} {$row['jam_mulai']}-{$row['jam_selesai']} - Status: {$row['status']} - Kendaraan: {$row['nomor_plat']} ({$row['status_ketersediaan']})\n";
}

echo "\nJadwal yang sudah lewat tapi masih aktif:\n";
$result3 = $db->query("
    SELECT j.id, j.tanggal_jadwal, j.jam_selesai, j.status, k.nomor_plat
    FROM jadwal_kursus j
    LEFT JOIN kendaraan k ON j.kendaraan_id = k.id
    WHERE j.status NOT IN ('selesai', 'dibatalkan')
    AND CONCAT(j.tanggal_jadwal, ' ', j.jam_selesai) < NOW()
    ORDER BY j.tanggal_jadwal DESC
");
while ($row = $result3->fetch(PDO::FETCH_ASSOC)) {
    echo "Jadwal {$row['id']}: {$row['tanggal_jadwal']} {$row['jam_selesai']} - Status: {$row['status']} - Kendaraan: {$row['nomor_plat']}\n";
}
