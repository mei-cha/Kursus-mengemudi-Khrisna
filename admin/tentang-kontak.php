<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_tentang'])) {
        $judul_tentang = $_POST['judul_tentang'] ?? '';
        $deskripsi_sejarah = $_POST['deskripsi_sejarah'] ?? '';
        $visi = $_POST['visi'] ?? '';

        $misi_array = [];
        if (!empty($_POST['misi']) && is_array($_POST['misi'])) {
            foreach ($_POST['misi'] as $misi_item) {
                $misi_item = trim($misi_item);
                if (!empty($misi_item)) {
                    $misi_array[] = $misi_item;
                }
            }
        }
        $misi_json = json_encode($misi_array, JSON_UNESCAPED_UNICODE);

        // Hanya menyimpan tahun_berdiri
        $tahun_berdiri = (int) ($_POST['tahun_berdiri'] ?? 2010);

        try {
            $stmt = $db->prepare("UPDATE tentang_kami SET 
                judul_tentang = ?,
                deskripsi_sejarah = ?,
                visi = ?,
                misi = ?,
                tahun_berdiri = ?
                WHERE id = 1");

            if (
                $stmt->execute([
                    $judul_tentang,
                    $deskripsi_sejarah,
                    $visi,
                    $misi_json,
                    $tahun_berdiri
                ])
            ) {
                $success = "Data Tentang Kami berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui data Tentang Kami.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_kontak'])) {
        $alamat = $_POST['alamat'] ?? '';
        $telepon_1 = $_POST['telepon_1'] ?? '';
        $email_1 = $_POST['email_1'] ?? '';
        $jam_operasional_weekday = $_POST['jam_operasional_weekday'] ?? '';
        $jam_operasional_weekend = $_POST['jam_operasional_weekend'] ?? '';
        $embed_map = $_POST['embed_map'] ?? '';
        $link_map = $_POST['link_map'] ?? '';
        $facebook = $_POST['facebook'] ?? '';
        $instagram = $_POST['instagram'] ?? '';
        $youtube = $_POST['youtube'] ?? '';
        $tiktok = $_POST['tiktok'] ?? '';

        try {
            $stmt = $db->prepare("UPDATE kontak_kami SET 
                alamat = ?,
                telepon_1 = ?,
                email_1 = ?,
                jam_operasional_weekday = ?,
                jam_operasional_weekend = ?,
                embed_map = ?,
                link_map = ?,
                facebook = ?,
                instagram = ?,
                youtube = ?,
                tiktok = ?
                WHERE id = 1");

            if (
                $stmt->execute([
                    $alamat,
                    $telepon_1,
                    $email_1,
                    $jam_operasional_weekday,
                    $jam_operasional_weekend,
                    $embed_map,
                    $link_map,
                    $facebook,
                    $instagram,
                    $youtube,
                    $tiktok
                ])
            ) {
                $success = "Data Kontak Kami berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui data Kontak Kami.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch data with safe fallback
try {
    $stmt_tentang = $db->query("SELECT * FROM tentang_kami WHERE id = 1");
    $tentang_row = $stmt_tentang->fetch(PDO::FETCH_ASSOC);

    $stmt_kontak = $db->query("SELECT * FROM kontak_kami WHERE id = 1");
    $kontak_row = $stmt_kontak->fetch(PDO::FETCH_ASSOC);

    // Decode misi
    $misi = [];
    if ($tentang_row && isset($tentang_row['misi'])) {
        $decoded = json_decode($tentang_row['misi'], true);
        if (is_array($decoded)) {
            $misi = $decoded;
        }
    }

    // Fallback untuk $tentang (tanpa statistik)
    $tentang = [
        'judul_tentang' => $tentang_row['judul_tentang'] ?? 'Tentang Krishna Driving',
        'deskripsi_sejarah' => $tentang_row['deskripsi_sejarah'] ?? '',
        'visi' => $tentang_row['visi'] ?? '',
        'misi' => $tentang_row['misi'] ?? '[]',
        'tahun_berdiri' => (int) ($tentang_row['tahun_berdiri'] ?? 2010),
        // Statistik dihapus
    ];

    // Fallback untuk $kontak
    $kontak = [
        'alamat' => $kontak_row['alamat'] ?? '',
        'telepon_1' => $kontak_row['telepon_1'] ?? '',
        'email_1' => $kontak_row['email_1'] ?? '',
        'jam_operasional_weekday' => $kontak_row['jam_operasional_weekday'] ?? '',
        'jam_operasional_weekend' => $kontak_row['jam_operasional_weekend'] ?? '',
        'embed_map' => $kontak_row['embed_map'] ?? '',
        'link_map' => $kontak_row['link_map'] ?? '',
        'facebook' => $kontak_row['facebook'] ?? '',
        'instagram' => $kontak_row['instagram'] ?? '',
        'youtube' => $kontak_row['youtube'] ?? '',
        'tiktok' => $kontak_row['tiktok'] ?? '',
    ];

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Default fallback (tanpa statistik)
    $tentang = [
        'judul_tentang' => 'Tentang Krishna Driving',
        'deskripsi_sejarah' => '',
        'visi' => '',
        'misi' => '[]',
        'tahun_berdiri' => 2010,
    ];
    $kontak = array_fill_keys([
        'alamat',
        'telepon_1',
        'email_1',
        'jam_operasional_weekday',
        'jam_operasional_weekend',
        'embed_map',
        'link_map',
        'facebook',
        'instagram',
        'youtube',
        'tiktok'
    ], '');
    $misi = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Halaman Publik - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-button.active {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Halaman Publik</h1>
                    <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bars text-gray-600"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <!-- Pesan tetap di sini -->
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- ⚠️ SATU FORM BESAR MULAI DI SINI -->
                <form method="POST">
                    <!-- Kedua flag update aktif sekaligus -->
                    <input type="hidden" name="update_tentang" value="1">
                    <input type="hidden" name="update_kontak" value="1">

                    <!-- Tab Navigation (Tab Statistik dihapus) -->
                    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200">
                        <button type="button"
                            class="tab-button px-4 py-2 rounded-t-lg font-medium text-gray-600 hover:bg-gray-100 active"
                            data-tab="profil">Sejarah & Profil</button>
                        <button type="button"
                            class="tab-button px-4 py-2 rounded-t-lg font-medium text-gray-600 hover:bg-gray-100"
                            data-tab="visi-misi">Visi & Misi</button>
                        <!-- Tab Statistik dihapus -->
                        <button type="button"
                            class="tab-button px-4 py-2 rounded-t-lg font-medium text-gray-600 hover:bg-gray-100"
                            data-tab="kontak">Kontak</button>
                        <button type="button"
                            class="tab-button px-4 py-2 rounded-t-lg font-medium text-gray-600 hover:bg-gray-100"
                            data-tab="media">Media Sosial & Peta</button>
                    </div>

                    <!-- 1. Profil & Sejarah -->
                    <div id="profil" class="tab-content active bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Sejarah & Profil Perusahaan</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Halaman</label>
                                <input type="text" name="judul_tentang"
                                    value="<?= htmlspecialchars($tentang['judul_tentang']) ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi & Sejarah</label>
                                <textarea name="deskripsi_sejarah" rows="5" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($tentang['deskripsi_sejarah']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Berdiri</label>
                                <input type="number" name="tahun_berdiri" value="<?= (int) $tentang['tahun_berdiri'] ?>"
                                    min="1900" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Visi & Misi -->
                    <div id="visi-misi" class="tab-content bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Visi & Misi</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Visi</label>
                                <textarea name="visi" rows="3" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($tentang['visi']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Misi</label>
                                <div id="misi-container" class="space-y-2">
                                    <?php if (!empty($misi)): ?>
                                        <?php foreach ($misi as $item): ?>
                                            <div class="flex gap-2">
                                                <input type="text" name="misi[]" value="<?= htmlspecialchars($item) ?>"
                                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <button type="button"
                                                    class="remove-misi px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="flex gap-2">
                                            <input type="text" name="misi[]"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <button type="button"
                                                class="remove-misi px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-misi"
                                    class="mt-2 px-3 py-1.5 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                                    <i class="fas fa-plus mr-1"></i> Tambah Misi
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Kontak -->
                    <div id="kontak" class="tab-content bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Kontak Utama</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat (boleh HTML)</label>
                                <textarea name="alamat" rows="3" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($kontak['alamat']) ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Telepon 1</label>
                                    <input type="text" name="telepon_1"
                                        value="<?= htmlspecialchars($kontak['telepon_1']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email 1</label>
                                    <input type="email" name="email_1"
                                        value="<?= htmlspecialchars($kontak['email_1']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jam Operasional
                                        (Weekday)</label>
                                    <input type="text" name="jam_operasional_weekday"
                                        value="<?= htmlspecialchars($kontak['jam_operasional_weekday']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jam Operasional
                                        (Weekend)</label>
                                    <input type="text" name="jam_operasional_weekend"
                                        value="<?= htmlspecialchars($kontak['jam_operasional_weekend']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Media Sosial & Peta -->
                    <div id="media" class="tab-content bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Media Sosial & Google Maps</h2>
                        <div class="space-y-4">
                            <div class="border-b pb-4 mb-4">
                                <h3 class="font-medium text-gray-800 mb-2">Media Sosial</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Facebook</label>
                                        <input type="text" name="facebook"
                                            value="<?= htmlspecialchars($kontak['facebook']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">Instagram</label>
                                        <input type="text" name="instagram"
                                            value="<?= htmlspecialchars($kontak['instagram']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">YouTube</label>
                                        <input type="text" name="youtube"
                                            value="<?= htmlspecialchars($kontak['youtube']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-700 mb-1">TikTok</label>
                                        <input type="text" name="tiktok"
                                            value="<?= htmlspecialchars($kontak['tiktok']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Embed Google Maps (iframe
                                    src)</label>
                                <textarea name="embed_map" rows="2" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($kontak['embed_map']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Link Google Maps</label>
                                <input type="url" name="link_map" value="<?= htmlspecialchars($kontak['link_map']) ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- SATU TOMBOL SIMPAN UNTUK SEMUA -->
                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Simpan Semua Perubahan
                        </button>
                    </div>
                </form> <!-- TUTUP FORM -->
            </main>
        </div>
    </div>

    <!-- JS TETAP SAMA -->
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Misi dynamic
        document.getElementById('add-misi')?.addEventListener('click', function () {
            const container = document.getElementById('misi-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
                <input type="text" name="misi[]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="button" class="remove-misi px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('.remove-misi')) {
                const btn = e.target.closest('.remove-misi');
                const container = document.getElementById('misi-container');
                if (container.children.length > 1) {
                    btn.parentElement.remove();
                } else {
                    btn.parentElement.querySelector('input').value = '';
                }
            }
        });
    </script>
    
</body>

</html>