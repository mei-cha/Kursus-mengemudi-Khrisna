<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "Jadwal dengan kendaraan AG 4536 BC (ID: 2):\n";
$result = $db->query("
    SELECT j.id, j.tanggal_jadwal, j.jam_mulai, j.jam_selesai, j.status
    FROM jadwal_kursus j
    WHERE j.kendaraan_id = 2
    AND j.tipe_sesi = 'praktik'
    ORDER BY j.tanggal_jadwal, j.jam_mulai
");
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "Jadwal {$row['id']}: {$row['tanggal_jadwal']} {$row['jam_mulai']}-{$row['jam_selesai']} - Status: {$row['status']}\n";
}

echo "\nCek ketersediaan tanggal 5 Februari 2026:\n";
$check_date = '2026-02-05';
$result2 = $db->prepare("
    SELECT COUNT(*) as count
    FROM jadwal_kursus
    WHERE kendaraan_id = 2
    AND tanggal_jadwal = ?
    AND tipe_sesi = 'praktik'
    AND status NOT IN ('dibatalkan', 'selesai')
");
$result2->execute([$check_date]);
$conflict = $result2->fetch(PDO::FETCH_ASSOC);
echo "Jumlah jadwal aktif di tanggal $check_date: {$conflict['count']}\n";
?>