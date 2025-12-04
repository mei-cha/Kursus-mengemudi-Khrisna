<?php
// cetak-pendaftaran.php
session_start();
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

if ($id == 0) {
    header('Location: index.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT s.*, p.nama_paket, p.harga, p.durasi_jam, p.tipe_mobil as tipe_mobil_paket 
                         FROM pendaftaran_siswa s 
                         LEFT JOIN paket_kursus p ON s.paket_kursus_id = p.id 
                         WHERE s.id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pendaftaran - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .print-container {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-white shadow-2xl rounded-xl p-6 md:p-8 print-container">
        
        <!-- Header -->
        <div class="text-center border-b-2 border-blue-600 pb-4 mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-blue-700">KRISHNA DRIVING SCHOOL</h1>
            <p class="text-gray-600 mt-1 text-sm md:text-base">Jl. Contoh No. 123, Jakarta - Telp: 0812-3456-789</p>
            <p class="text-gray-600 text-sm md:text-base">Email: info@krishnadriving.com</p>
        </div>
        
        <!-- Title -->
        <div class="text-center mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800">BUKTI PENDAFTARAN KURSUS MENGEMUDI</h2>
            <div class="flex justify-center items-center mt-2 space-x-4">
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold">
                    <?= htmlspecialchars($data['nomor_pendaftaran']) ?>
                </span>
                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm font-semibold">
                    Status: <?= strtoupper($data['status_pendaftaran']) ?>
                </span>
            </div>
        </div>
        
        <!-- Data Pendaftaran -->
        <div class="space-y-6">
            
            <!-- Data Pribadi -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-3">DATA PRIBADI</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Nama Lengkap:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['nama_lengkap']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Email:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['email']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Telepon:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['telepon']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Tanggal Lahir:</strong></p>
                        <p class="text-gray-800"><?= $data['tanggal_lahir'] ? date('d/m/Y', strtotime($data['tanggal_lahir'])) : '-' ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Jenis Kelamin:</strong></p>
                        <p class="text-gray-800"><?= $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-gray-600 text-sm"><strong>Alamat:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['alamat']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Data Kursus -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-3">DATA KURSUS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Paket Kursus:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($data['nama_paket'] ?? '-') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Harga:</strong></p>
                        <p class="text-gray-800 font-semibold text-lg">Rp <?= number_format($data['harga'] ?? 0, 0, ',', '.') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Tipe Mobil:</strong></p>
                        <p class="text-gray-800"><?= ucfirst($data['tipe_mobil']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Durasi:</strong></p>
                        <p class="text-gray-800"><?= $data['durasi_jam'] ?? 0 ?> Jam</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Jadwal Preferensi:</strong></p>
                        <p class="text-gray-800"><?= ucfirst($data['jadwal_preferensi']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Pengalaman:</strong></p>
                        <p class="text-gray-800"><?= ucwords(str_replace('_', ' ', $data['pengalaman_mengemudi'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Kontak Darurat -->
            <?php if (!empty($data['kontak_darurat'])): ?>
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-3">KONTAK DARURAT</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Nama Kontak Darurat:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['nama_kontak_darurat']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Telepon Kontak Darurat:</strong></p>
                        <p class="text-gray-800"><?= htmlspecialchars($data['kontak_darurat']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Kondisi Medis -->
            <?php if (!empty($data['kondisi_medis'])): ?>
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-3">KONDISI MEDIS</h3>
                <p class="text-gray-800"><?= htmlspecialchars($data['kondisi_medis']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Tanggal dan Informasi -->
            <div class="pt-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Tanggal Pendaftaran:</strong></p>
                        <p class="text-gray-800 font-semibold"><?= date('d/m/Y H:i', strtotime($data['dibuat_pada'])) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm"><strong>Ditandatangani oleh:</strong></p>
                        <div class="mt-8">
                            <p class="border-t border-gray-300 pt-2 text-center">Admin Krishna Driving</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 pt-4 border-t text-center text-gray-500 text-xs">
            <p>Bukti pendaftaran ini sah dan dapat digunakan untuk verifikasi di kantor Krishna Driving.</p>
            <p class="mt-1">Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <!-- Tombol Cetak -->
        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 inline-flex items-center">
                <i class="fas fa-print mr-2"></i>Cetak Halaman
            </button>
            <a href="konfirmasi-pendaftaran.php" 
               class="ml-4 bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-700 transition duration-300 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>
    
    <script>
        // Auto print saat halaman dimuat
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
        
        // Redirect setelah cetak (opsional)
        window.onafterprint = function() {
            setTimeout(function() {
                window.location.href = 'konfirmasi-pendaftaran.php';
            }, 3000);
        };

        // Validasi tanggal lahir (minimal 17 tahun)
document.getElementById('tanggal_lahir').addEventListener('change', function() {
    const tanggalLahir = new Date(this.value);
    const sekarang = new Date();
    const usia = sekarang.getFullYear() - tanggalLahir.getFullYear();
    
    // Cek jika usia kurang dari 17 tahun
    if (usia < 17) {
        Swal.fire({
            icon: 'warning',
            title: 'Usia Tidak Mencukupi',
            text: 'Usia minimal untuk mendaftar kursus mengemudi adalah 17 tahun',
            confirmButtonText: 'Mengerti'
        });
        this.value = '';
    }
});

// Form submission dengan AJAX
document.getElementById('formPendaftaran').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        
        // Validasi jadwal preferensi minimal 1
        const jadwalChecked = form.querySelectorAll('input[name="jadwal_preferensi[]"]:checked');
        if (jadwalChecked.length === 0) {
            throw new Error('Pilih minimal satu jadwal preferensi');
        }
        
        const response = await fetch('proses-pendaftaran.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                html: `
                    <div class="text-left">
                        <p class="mb-2">${result.message}</p>
                        <p class="font-bold mt-3">Nomor Pendaftaran: <span class="text-blue-600">${result.data.nomor_pendaftaran}</span></p>
                        <p class="text-sm text-gray-600 mt-2">Harap simpan nomor ini untuk verifikasi.</p>
                    </div>
                `,
                confirmButtonText: 'Lihat Konfirmasi',
                allowOutsideClick: false,
                showCancelButton: true,
                cancelButtonText: 'Tetap di Halaman Ini'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = result.redirect_url;
                }
            });
            
            // Reset form
            form.reset();
        } else {
            // Show error message
            let errorMessage = result.message;
            if (result.errors && result.errors.length > 0) {
                errorMessage = '<div class="text-left"><p class="font-bold mb-2">Perbaiki kesalahan berikut:</p><ul class="list-disc pl-5">' + 
                    result.errors.map(error => `<li class="mb-1">${error}</li>`).join('') + 
                    '</ul></div>';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: errorMessage,
                confirmButtonText: 'Mengerti'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: error.message || 'Gagal mengirim data. Silakan coba lagi.',
            confirmButtonText: 'OK'
        });
    } finally {
        // Reset button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
});
    </script>
</body>
</html>