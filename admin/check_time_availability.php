<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$db = (new Database())->getConnection();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$instruktur_id = $data['instruktur_id'] ?? null;
$pendaftaran_id = $data['pendaftaran_id'] ?? null;
$tanggal_jadwal = $data['tanggal_jadwal'] ?? null;
$jam_mulai = $data['jam_mulai'] ?? null;
$jam_selesai = $data['jam_selesai'] ?? null;
$kendaraan_id = $data['kendaraan_id'] ?? null;
$tipe_sesi = $data['tipe_sesi'] ?? null;

$response = [
    'error' => null,
    'selectedTimeAvailable' => true,
    'conflictInfo' => null,
    'availableSlots' => [],
    'occupiedSlots' => [],
    'existingSchedules' => []
];

try {
    // Check instructor availability
    if ($instruktur_id && $tanggal_jadwal && $jam_mulai && $jam_selesai) {
        $check_instruktur = $db->prepare("
            SELECT jk.*, ps.nama_lengkap 
            FROM jadwal_kursus jk
            JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id
            WHERE jk.instruktur_id = ? 
            AND jk.tanggal_jadwal = ? 
            AND jk.status NOT IN ('dibatalkan', 'selesai')
            AND (
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai >= ? AND jk.jam_selesai <= ?)
            )
        ");
        
        $check_instruktur->execute([
            $instruktur_id,
            $tanggal_jadwal,
            $jam_selesai, $jam_mulai,
            $jam_mulai, $jam_selesai,
            $jam_mulai, $jam_selesai
        ]);
        
        $instruktur_conflicts = $check_instruktur->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($instruktur_conflicts) > 0) {
            $response['selectedTimeAvailable'] = false;
            $response['conflictInfo'] = [
                'type' => 'instruktur',
                'with' => $instruktur_conflicts[0]['nama_lengkap'],
                'schedule' => $instruktur_conflicts[0]
            ];
        }
    }
    
    // Check student availability (only if not already in conflict)
    if ($response['selectedTimeAvailable'] && $pendaftaran_id) {
        $check_student = $db->prepare("
            SELECT jk.*, ps.nama_lengkap 
            FROM jadwal_kursus jk
            JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id
            WHERE jk.pendaftaran_id = ? 
            AND jk.tanggal_jadwal = ? 
            AND jk.status NOT IN ('dibatalkan', 'selesai')
            AND (
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai >= ? AND jk.jam_selesai <= ?)
            )
        ");
        
        $check_student->execute([
            $pendaftaran_id,
            $tanggal_jadwal,
            $jam_selesai, $jam_mulai,
            $jam_mulai, $jam_selesai,
            $jam_mulai, $jam_selesai
        ]);
        
        $student_conflicts = $check_student->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($student_conflicts) > 0) {
            $response['selectedTimeAvailable'] = false;
            $response['conflictInfo'] = [
                'type' => 'siswa',
                'with' => $student_conflicts[0]['nama_lengkap'],
                'schedule' => $student_conflicts[0]
            ];
        }
    }
    
    // Check vehicle availability (only for praktik sessions)
    if ($response['selectedTimeAvailable'] && $tipe_sesi === 'praktik' && $kendaraan_id) {
        $check_vehicle = $db->prepare("
            SELECT jk.*, ps.nama_lengkap 
            FROM jadwal_kursus jk
            JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id
            WHERE jk.kendaraan_id = ? 
            AND jk.tanggal_jadwal = ? 
            AND jk.tipe_sesi = 'praktik'
            AND jk.status NOT IN ('dibatalkan', 'selesai')
            AND (
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR
                (jk.jam_mulai >= ? AND jk.jam_selesai <= ?)
            )
        ");
        
        $check_vehicle->execute([
            $kendaraan_id,
            $tanggal_jadwal,
            $jam_selesai, $jam_mulai,
            $jam_mulai, $jam_selesai,
            $jam_mulai, $jam_selesai
        ]);
        
        $vehicle_conflicts = $check_vehicle->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($vehicle_conflicts) > 0) {
            $response['selectedTimeAvailable'] = false;
            $response['conflictInfo'] = [
                'type' => 'kendaraan',
                'with' => $vehicle_conflicts[0]['nama_lengkap'],
                'schedule' => $vehicle_conflicts[0]
            ];
        }
    }
    
    // Get existing schedules for the instructor on that day
    if ($instruktur_id && $tanggal_jadwal) {
        $get_schedules = $db->prepare("
            SELECT jk.*, ps.nama_lengkap as nama_siswa
            FROM jadwal_kursus jk
            JOIN pendaftaran_siswa ps ON jk.pendaftaran_id = ps.id
            WHERE jk.instruktur_id = ? 
            AND jk.tanggal_jadwal = ?
            ORDER BY jk.jam_mulai
        ");
        
        $get_schedules->execute([$instruktur_id, $tanggal_jadwal]);
        $response['existingSchedules'] = $get_schedules->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $response['error'] = "Database error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);