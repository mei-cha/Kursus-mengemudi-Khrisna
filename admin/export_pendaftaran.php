<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Get filter parameters dari URL
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sumber_filter = $_GET['sumber'] ?? '';
$tab_active = $_GET['tab'] ?? 'all';
$export_type = $_GET['export_type'] ?? 'all'; // all, online, offline

// Build query untuk data pendaftaran
$query = "SELECT ps.*, pk.nama_paket, pk.harga, pk.durasi_jam, pk.tipe_mobil as tipe_paket
          FROM pendaftaran_siswa ps 
          LEFT JOIN paket_kursus pk ON ps.paket_kursus_id = pk.id 
          WHERE 1=1";

$params = [];

// Filter berdasarkan tab/export_type
if ($export_type === 'online') {
    $query .= " AND ps.sumber_pendaftaran = 'online'";
    $report_title = "Laporan Pendaftaran Online";
} elseif ($export_type === 'offline') {
    $query .= " AND ps.sumber_pendaftaran = 'offline'";
    $report_title = "Laporan Pendaftaran Offline";
} else {
    $report_title = "Laporan Data Pendaftar";
}

// Filter status jika ada
if ($status_filter) {
    $query .= " AND ps.status_pendaftaran = ?";
    $params[] = $status_filter;
}

// Filter pencarian jika ada
if ($search) {
    $query .= " AND (ps.nama_lengkap LIKE ? OR ps.nomor_pendaftaran LIKE ? OR ps.email LIKE ? OR ps.telepon LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Filter sumber tambahan (jika ada)
if ($sumber_filter && $export_type === 'all') {
    $query .= " AND ps.sumber_pendaftaran = ?";
    $params[] = $sumber_filter;
}

// Urutkan berdasarkan ID terbaru
$query .= " ORDER BY ps.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah data
$total_data = count($pendaftaran);

// Tentukan periode berdasarkan tanggal data
$periode = "Semua Periode";
if ($total_data > 0) {
    $first_date = $pendaftaran[$total_data - 1]['dibuat_pada'];
    $last_date = $pendaftaran[0]['dibuat_pada'];
    
    $month_first = date('F Y', strtotime($first_date));
    $month_last = date('F Y', strtotime($last_date));
    
    if ($month_first === $month_last) {
        $periode = $month_first;
    } else {
        $periode = $month_first . " - " . $month_last;
    }
}

// Format tanggal cetak
$tanggal_cetak = date('d F Y');
?>
<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Export Pendaftaran - Kursus Mengemudi Krishna</title> 
    <style> 
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Arial', 'Helvetica', sans-serif; 
        } 
                
        body { 
            background-color: #ffffff; 
            color: #000000; 
            padding: 30px 20px; 
            line-height: 1.4; 
            font-size: 12px; 
        } 
                
        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
            background-color: white; 
            padding: 0; 
            border: 1px solid #cccccc; 
        } 
                
        /* Kop Surat */ 
        .letterhead { 
            padding: 25px 30px 20px; 
            border-bottom: 2px solid #000000; 
            position: relative; 
        } 
                
        .letterhead-top { 
            display: flex; 
            align-items: flex-start; 
            margin-bottom: 15px; 
        } 
                
        .logo-container { 
            width: 80px; 
            height: 80px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 20px; 
            overflow: hidden; 
            background-color: transparent; 
            border: none; 
        } 
                
        .logo { 
            max-width: 100%; 
            max-height: 100%; 
            width: auto; 
            height: auto; 
            object-fit: contain; 
            background-color: transparent; 
        } 
                
        .company-info { 
            flex: 1; 
        } 
                
        .company-name { 
            font-size: 20px; 
            font-weight: bold; 
            color: #000000; 
            margin-bottom: 5px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        } 
                
        .company-tagline { 
            font-size: 12px; 
            color: #333333; 
            margin-bottom: 8px; 
            font-style: italic; 
        } 
                
        .company-details { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 10px; 
        } 
                
        .contact-info { 
            font-size: 11px; 
            color: #333333; 
        } 
                
        .contact-line { 
            margin-bottom: 3px; 
        } 
                
        .report-title-section { 
            text-align: center; 
            margin: 20px 30px 15px; 
        } 
                
        .report-title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 5px; 
            text-transform: uppercase; 
            text-decoration: underline; 
            letter-spacing: 1px; 
        } 
                
        .report-subtitle { 
            font-size: 12px; 
            color: #333333; 
            margin-bottom: 15px; 
        } 
                
        .report-info { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 20px; 
            padding: 0 30px; 
            font-size: 11px; 
        } 
                
        .date-info, .page-info { 
            color: #333333; 
        } 
                
        /* Tabel Data */ 
        .data-section { 
            padding: 0 30px; 
        } 
                
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0 0 20px; 
            font-size: 11px; 
            table-layout: fixed; 
        } 
                
        th { 
            background-color: #f5f5f5; 
            color: #000000; 
            text-align: left; 
            padding: 10px 8px; 
            font-weight: bold; 
            border: 1px solid #000000; 
            border-bottom: 2px solid #000000; 
        } 
                
        td { 
            padding: 8px; 
            border: 1px solid #dddddd; 
            vertical-align: top; 
            word-wrap: break-word; 
        } 
                
        tr:nth-child(even) { 
            background-color: #fafafa; 
        } 
                
        .no-col { 
            width: 40px; 
            text-align: center; 
        } 
                
        .nomor-col { 
            width: 120px; 
        } 
                
        .nama-col { 
            width: 140px; 
        } 
                
        .email-col { 
            width: 150px; 
        } 
                
        .telepon-col { 
            width: 100px; 
        } 
                
        .alamat-col { 
            width: 180px; 
        } 
                
        .paket-col { 
            width: 120px; 
        } 
                
        .sumber-col { 
            width: 80px; 
        } 
                
        .status-col { 
            width: 90px; 
        } 
                
        /* Bagian Tanda Tangan */ 
        .signature-section { 
            padding: 25px 30px 30px; 
            margin-top: 20px; 
            border-top: 1px solid #000000; 
        } 
                
        .signature-container { 
            display: flex; 
            justify-content: flex-end; 
            margin-top: 40px; 
        } 
                
        .signature-box { 
            width: 250px; 
            text-align: center; 
        } 
                
        .signature-placeholder { 
            height: 60px; 
            margin-bottom: 5px; 
            border-bottom: 1px solid #000000; 
            position: relative; 
        } 
                
        .signature-text { 
            font-size: 10px; 
            color: #333333; 
            margin-top: 5px; 
        } 
                
        .signature-name { 
            font-weight: bold; 
            margin-top: 10px; 
            text-transform: uppercase; 
            font-size: 11px; 
        } 
                
        .signature-position { 
            font-size: 10px; 
            color: #666666; 
        } 
                
        .footer { 
            padding: 15px 30px; 
            background-color: #f8f8f8; 
            text-align: center; 
            font-size: 10px; 
            color: #666666; 
            border-top: 1px solid #dddddd; 
        } 
                
        .footer-text { 
            margin-bottom: 5px; 
        } 
                
        @media print { 
            body { 
                background-color: white; 
                padding: 15px 10px; 
                font-size: 10px; 
            } 
                    
            .container { 
                border: none; 
            } 
                    
            .company-name { 
                font-size: 18px; 
            } 
                    
            .report-title { 
                font-size: 14px; 
            } 
                    
            table { 
                font-size: 9px; 
            } 
                    
            th, td { 
                padding: 6px 5px; 
            } 
                    
            .signature-placeholder { 
                height: 50px; 
            } 
                    
            .logo-container { 
                background-color: transparent !important; 
            } 
            
            .logo { 
                background-color: transparent !important; 
            } 
            
            .no-print { 
                display: none !important; 
            }
        } 
                
        @media (max-width: 768px) { 
            body { 
                padding: 15px; 
            } 
                    
            .letterhead { 
                padding: 20px; 
            } 
                    
            .data-section { 
                padding: 0 20px; 
            } 
                    
            .report-info { 
                padding: 0 20px; 
                flex-direction: column; 
            } 
                    
            .report-info div { 
                margin-bottom: 10px; 
            } 
                    
            table { 
                font-size: 10px; 
            } 
                    
            th, td { 
                padding: 6px 4px; 
            } 
                    
            .company-name { 
                font-size: 18px; 
            } 
                    
            .report-title { 
                font-size: 14px; 
            } 
                    
            .signature-container { 
                justify-content: center; 
            } 
        } 
        
        /* Print Button */
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            z-index: 1000;
            border: none;
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }
    </style> 
</head> 
<body> 
    <button class="print-button no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak Laporan
    </button>
    
    <div class="container"> 
        <div class="letterhead"> 
            <div class="letterhead-top"> 
                <div class="logo-container"> 
                    <img src="../assets/images/logo1.png" class="logo" alt="Logo Kursus Mengemudi Krishna" onerror="this.style.display='none'"> 
                </div> 
                <div class="company-info"> 
                    <div class="company-name">Kursus Mengemudi Krishna</div> 
                    <div class="company-tagline">Pelayanan Terbaik untuk Perjalanan Anda</div> 
                    <div class="company-details"> 
                        <div class="contact-info"> 
                            <div class="contact-line"><strong>Alamat:</strong> Jl. Ki Mangun Sarkoro No.107, Dusun Krajan, Beji, Kec. Boyolangu</div> 
                            <div class="contact-line"><strong>Kota:</strong> Tulungagung, Jawa Timur</div> 
                        </div> 
                        <div class="contact-info"> 
                            <div class="contact-line"><strong>Telepon:</strong> +62 857-3911-5520</div> 
                            <div class="contact-line"><strong>WhatsApp:</strong> +62 857-3911-5520</div> 
                            <div class="contact-line"><strong>Email:</strong>fajarbersinarcahaya@gmail.com</div> 
                        </div> 
                    </div> 
                </div> 
            </div> 
        </div> 
                
        <div class="report-title-section"> 
            <div class="report-title"><?= $report_title ?></div> 
            <div class="report-subtitle">Periode: <?= $periode ?></div> 
        </div> 
                
        <div class="report-info"> 
            <div class="date-info">
                <strong>Tanggal Cetak:</strong> <?= $tanggal_cetak ?><br>
                <strong>Total Data:</strong> <?= $total_data ?> pendaftaran
                <?php if($export_type !== 'all'): ?>
                    <br><strong>Jenis:</strong> <?= ucfirst($export_type) ?>
                <?php endif; ?>
            </div> 
            <div class="page-info">
                <strong>Halaman:</strong> 1 dari 1<br>
                <strong>Filter Status:</strong> <?= $status_filter ? ucfirst($status_filter) : 'Semua' ?>
                <?php if($search): ?>
                    <br><strong>Pencarian:</strong> "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
            </div> 
        </div> 
                
        <div class="data-section"> 
            <table> 
                <thead> 
                    <tr> 
                        <th class="no-col">No</th> 
                        <th class="nomor-col">No. Pendaftaran</th> 
                        <th class="nama-col">Nama Lengkap</th> 
                        <th class="email-col">Email</th> 
                        <th class="telepon-col">Telepon</th> 
                        <th class="alamat-col">Alamat</th> 
                        <th class="paket-col">Paket Kursus</th> 
                        <th class="sumber-col">Sumber</th> 
                        <th class="status-col">Status</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php if ($total_data > 0): ?>
                        <?php foreach ($pendaftaran as $index => $data): ?>
                            <tr> 
                                <td class="no-col"><?= $index + 1 ?></td> 
                                <td class="nomor-col"><?= $data['nomor_pendaftaran'] ?></td> 
                                <td class="nama-col"><?= htmlspecialchars($data['nama_lengkap']) ?></td> 
                                <td class="email-col"><?= $data['email'] ?></td> 
                                <td class="telepon-col"><?= $data['telepon'] ?></td> 
                                <td class="alamat-col"><?= htmlspecialchars($data['alamat']) ?></td> 
                                <td class="paket-col"><?= htmlspecialchars($data['nama_paket'] ?? '-') ?></td> 
                                <td class="sumber-col"><?= ucfirst($data['sumber_pendaftaran']) ?></td> 
                                <td class="status-col"><?= ucfirst($data['status_pendaftaran']) ?></td> 
                            </tr> 
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: #666;">
                                Tidak ada data pendaftaran untuk diexport
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody> 
            </table> 
        </div> 
                
        <div class="signature-section"> 
            <div class="signature-container"> 
                <div class="signature-box"> 
                    <div class="signature-placeholder"></div> 
                    <div class="signature-text">Tanda tangan dan stempel perusahaan</div> 
                    <div class="signature-name">Dian Dwi Satria</div> 
                    <div class="signature-position">Kursus Krishna</div> 
                </div> 
            </div> 
        </div> 
                
        <div class="footer"> 
            <div class="footer-text">Sistem Informasi Kursus Mengemudi Krishna</div> 
            <div class="footer-text">Dokumen ini dicetak secara otomatis dari sistem dan sah sebagai laporan resmi</div> 
        </div> 
    </div> 
    
    <!-- Load Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script> 
        // Auto print ketika halaman load (opsional)
        document.addEventListener('DOMContentLoaded', function() {
            // Uncomment line berikut untuk auto print
            // window.print();
            
            // Atau beri pilihan
            setTimeout(function() {
                const shouldPrint = confirm('Apakah Anda ingin mencetak laporan ini?');
                if (shouldPrint) {
                    window.print();
                }
            }, 1000);
        });
        
        // Tambahkan tombol untuk download PDF (jika ingin menambahkan fitur PDF)
        function downloadPDF() {
            alert('Fitur download PDF akan tersedia segera!');
        }
    </script> 
</body> 
</html>