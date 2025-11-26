<?php
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateNomorPendaftaran() {
    $prefix = 'KRISHNA';
    $tahun = date('y');
    $bulan = date('m');
    $hari = date('d');
    $random = rand(100, 999);
    return $prefix . $tahun . $bulan . $hari . $random;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function sendNotification($nomor_pendaftaran, $nama_siswa) {
    // Fungsi untuk mengirim notifikasi (email/WhatsApp)
    // Bisa diimplementasikan nanti
    return true;
}

function getStatusBadge($status) {
    $badges = [
        'baru' => '<span class="badge bg-warning">Baru</span>',
        'dikonfirmasi' => '<span class="badge bg-info">Dikonfirmasi</span>',
        'diproses' => '<span class="badge bg-primary">Diproses</span>',
        'selesai' => '<span class="badge bg-success">Selesai</span>',
        'dibatalkan' => '<span class="badge bg-danger">Dibatalkan</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>