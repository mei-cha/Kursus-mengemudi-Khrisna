<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kursus Mengemudi Krishna - Laporan Pendaftaran Offline</title>
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

        .date-info,
        .page-info {
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
            text-align: center;
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
            width: 50px;
            text-align: center;
        }

        .no-pendaftaran-col {
            width: 120px;
        }

        .nama-col {
            width: 100px;
        }

        .email-col {
            width: 150px;
        }

        .telepon-col {
            width: 110px;
        }

        .alamat-col {
            width: 120px;
        }

        .paket-col {
            width: 110px;
        }

        .sumber-col {
            width: 70px;
            text-align: center;
        }

        .status-col {
            width: 100px;
            text-align: center;
        }

        .status-dikonfirmasi {
            color: green;
            font-weight: bold;
        }

        .status-baru {
            color: blue;
            font-weight: bold;
        }

        .status-situs {
            color: orange;
            font-weight: bold;
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

            th,
            td {
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

            th,
            td {
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
    </style>
</head>

<body>
    <div class="container">
        <div class="letterhead">
            <div class="letterhead-top">
                <div class="logo-container">
                    <img src="logo1.png" class="logo" alt="Logo Kursus Mengemudi Krishna" onerror="this.style.display='none'">
                </div>
                <div class="company-info">
                    <div class="company-name">Kursus Mengemudi Krishna</div>
                    <div class="company-tagline">Pelayanan Terbaik untuk Perjalanan Anda</div>
                    <div class="company-details">
                        <div class="contact-info">
                            <div class="contact-line"><strong>Alamat:</strong> Jl. Ki Mangun Sarkoro No. 22, Ds. Beji, Tulungagung</div>
                            <div class="contact-line"><strong>Kota:</strong> Tulungagung, Jawa Timur</div>
                        </div>
                        <div class="contact-info">
                            <div class="contact-line"><strong>Telepon:</strong> (022) 70536462</div>
                            <div class="contact-line"><strong>WhatsApp:</strong> 08522034659</div>
                            <div class="contact-line"><strong>Email:</strong> info@suryarental.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-title-section">
            <div class="report-title">Laporan Pendaftaran Offline</div>
            <div class="report-subtitle">Periode: December 2025</div>
        </div>

        <div class="report-info">
            <div class="date-info"><strong>Tanggal Cetak:</strong> 29 January 2026</div>
            <div class="page-info">
                <strong>Total Data:</strong> 8 pendaftaran | 
                <strong>Jenis:</strong> Offline |
                <strong>Halaman:</strong> 1 dari 1
            </div>
        </div>

        <div class="data-section">
            <table>
                <thead>
                    <tr>
                        <th class="no-col">No</th>
                        <th class="no-pendaftaran-col">No. Pendaftaran</th>
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
                    <!-- DATA ASLI DARI FILE IMAGE.PNG -->
                    <tr>
                        <td class="no-col">1</td>
                        <td class="no-pendaftaran-col">KD20251212220</td>
                        <td class="nama-col">prsky</td>
                        <td class="email-col">prsky@gmail.com</td>
                        <td class="telepon-col">0847273876</td>
                        <td class="alamat-col">zsendanggg</td>
                        <td class="paket-col">Paket Pelancaran</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-dikonfirmasi">Dikonfirmasi</td>
                    </tr>
                    <tr>
                        <td class="no-col">2</td>
                        <td class="no-pendaftaran-col">KD20251212861</td>
                        <td class="nama-col">naza</td>
                        <td class="email-col">naz12@gmail.com</td>
                        <td class="telepon-col">086723134567</td>
                        <td class="alamat-col">tanggunggunung</td>
                        <td class="paket-col">Paket Extra</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-dikonfirmasi">Dikonfirmasi</td>
                    </tr>
                    <tr>
                        <td class="no-col">3</td>
                        <td class="no-pendaftaran-col">KD20251211185</td>
                        <td class="nama-col">riri</td>
                        <td class="email-col">riri@gmail.com</td>
                        <td class="telepon-col">085732992601</td>
                        <td class="alamat-col">Jawa Barat</td>
                        <td class="paket-col">Paket Extra</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-baru">Baru</td>
                    </tr>
                    <tr>
                        <td class="no-col">4</td>
                        <td class="no-pendaftaran-col">KD20251211040</td>
                        <td class="nama-col">Tama</td>
                        <td class="email-col">tamaa@gmail.com</td>
                        <td class="telepon-col">085732992601</td>
                        <td class="alamat-col">Desa Serut</td>
                        <td class="paket-col">Paket Reguler</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-baru">Baru</td>
                    </tr>
                    <tr>
                        <td class="no-col">5</td>
                        <td class="no-pendaftaran-col">KD20251211525</td>
                        <td class="nama-col">kumala</td>
                        <td class="email-col">kumala@gmail.com</td>
                        <td class="telepon-col">081234567890</td>
                        <td class="alamat-col">Permai Jepun</td>
                        <td class="paket-col">Paket Pelancaran</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-dikonfirmasi">Dikonfirmasi</td>
                    </tr>
                    <tr>
                        <td class="no-col">6</td>
                        <td class="no-pendaftaran-col">KD20251211664</td>
                        <td class="nama-col">naevada</td>
                        <td class="email-col">naevada@gmail.com</td>
                        <td class="telepon-col">085732992601</td>
                        <td class="alamat-col">Sumatera Utara</td>
                        <td class="paket-col">Paket Extra</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-dikonfirmasi">Dikonfirmasi</td>
                    </tr>
                    <tr>
                        <td class="no-col">7</td>
                        <td class="no-pendaftaran-col">KD20251211786</td>
                        <td class="nama-col">aieebv</td>
                        <td class="email-col">hhbosdfh@gmail.com</td>
                        <td class="telepon-col">975321570885</td>
                        <td class="alamat-col">sdiebslvodkd</td>
                        <td class="paket-col">Paket Extra</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-situs">Situs</td>
                    </tr>
                    <tr>
                        <td class="no-col">8</td>
                        <td class="no-pendaftaran-col">KRISHNA-20251211-327</td>
                        <td class="nama-col">zahra</td>
                        <td class="email-col">zahra@gmail.com</td>
                        <td class="telepon-col">085790867886</td>
                        <td class="alamat-col">Jakarta Selatan</td>
                        <td class="paket-col">Paket Campuran</td>
                        <td class="sumber-col">Offline</td>
                        <td class="status-col status-situs">Situs</td>
                    </tr>
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
            <div class="footer-text">Kursus Mengemudi Krishna - Laporan Pendaftaran Offline</div>
            <div class="footer-text">Dokumen ini dicetak secara otomatis dari sistem dan sah sebagai laporan resmi</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Laporan Pendaftaran Offline Kursus Mengemudi Krishna");
        });
    </script>
</body>

</html>