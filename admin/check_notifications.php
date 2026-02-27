<?php
session_start();
require_once '../config/database.php';

// Validasi session admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['total' => 0]);
    exit;
}

try {
    // Koneksi database
    $db = (new Database())->getConnection();
    
    // Hitung siswa yang perlu ditagih (progress ≥ 70% belum lunas)
    function countSiswaBelumLunas($db) {
        $query = "
            SELECT COUNT(DISTINCT ps.id) as total
            FROM pendaftaran_siswa ps
            JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id
            LEFT JOIN (
                SELECT pendaftaran_id,
                    SUM(CASE WHEN tipe_pembayaran = 'lunas' AND status = 'terverifikasi' THEN 1 ELSE 0 END) as lunas_count,
                    SUM(CASE WHEN status = 'terverifikasi' THEN jumlah ELSE 0 END) as total_dibayar
                FROM pembayaran
                GROUP BY pendaftaran_id
            ) pb ON pb.pendaftaran_id = ps.id
            LEFT JOIN (
                SELECT pendaftaran_id,
                    COUNT(*) as total_jadwal,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as jadwal_selesai
                FROM jadwal_kursus
                WHERE status IN ('terjadwal', 'selesai')
                GROUP BY pendaftaran_id
            ) jk ON jk.pendaftaran_id = ps.id
            WHERE ps.status_pendaftaran IN ('diproses', 'dikonfirmasi')
                AND COALESCE(jk.total_jadwal, 0) > 0
                AND COALESCE(jk.jadwal_selesai, 0) > 0
                AND COALESCE(pb.lunas_count, 0) = 0
                AND COALESCE(pb.total_dibayar, 0) < pk.harga
                AND (COALESCE(jk.jadwal_selesai, 0) * 100.0 / NULLIF(jk.total_jadwal, 0)) >= 70
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    $total = countSiswaBelumLunas($db);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total' => (int)$total
    ]);
    
} catch (Exception $e) {
    // Log error untuk debugging
    error_log('Error in count_belum_lunas.php: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'total' => 0,
        'error' => 'Internal server error'
    ]);
}
?>