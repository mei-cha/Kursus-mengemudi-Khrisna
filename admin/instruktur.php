<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine mode: 'add' or 'edit'
    $mode = 'add';
    if (isset($_POST['edit_instruktur']) && $_POST['edit_instruktur'] == '1') {
        $mode = 'edit';
    }

    if ($mode === 'add') {
        $nama_lengkap = $_POST['nama_lengkap'];
        $nomor_licensi = $_POST['nomor_licensi'];
        $spesialisasi = $_POST['spesialisasi'];
        $pengalaman_tahun = $_POST['pengalaman_tahun'];
        $deskripsi = $_POST['deskripsi'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $rating = 0.00;
        $total_siswa = 0;
        $foto = null;

        // Cek duplikasi nomor lisensi
        $check_stmt = $db->prepare("SELECT id FROM instruktur WHERE nomor_licensi = ?");
        $check_stmt->execute([$nomor_licensi]);
        if ($check_stmt->rowCount() > 0) {
            $error = "Nomor lisensi '$nomor_licensi' sudah digunakan!";
        } else {
            // Handle upload foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $foto_name = time() . '_' . basename($_FILES['foto']['name']);
                $target_dir = "../assets/images/instruktur/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $target_file = $target_dir . $foto_name;
                $check = getimagesize($_FILES["foto"]["tmp_name"]);
                if ($check !== false && $_FILES["foto"]["size"] <= 2097152) {
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                            $foto = $foto_name;
                        } else {
                            $error = "Gagal mengupload foto!";
                        }
                    } else {
                        $error = "Format file tidak didukung! Hanya JPG, JPEG, PNG, GIF yang diizinkan.";
                    }
                } else {
                    $error = "File bukan gambar atau ukuran melebihi 2MB!";
                }
            }

            if (!isset($error)) {
                try {
                    $stmt = $db->prepare("INSERT INTO instruktur (nama_lengkap, nomor_licensi, spesialisasi, pengalaman_tahun, deskripsi, foto, aktif, rating, total_siswa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$nama_lengkap, $nomor_licensi, $spesialisasi, $pengalaman_tahun, $deskripsi, $foto, $aktif, $rating, $total_siswa])) {
                        $success = "Instruktur berhasil ditambahkan!";
                        echo "<script>resetForm();</script>";
                    } else {
                        $error = "Gagal menambahkan instruktur.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif ($mode === 'edit') {
        $id = $_POST['id'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $nomor_licensi = $_POST['nomor_licensi'];
        $spesialisasi = $_POST['spesialisasi'];
        $pengalaman_tahun = $_POST['pengalaman_tahun'];
        $deskripsi = $_POST['deskripsi'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $foto = $_POST['current_foto'];

        // Cek duplikasi kecuali milik sendiri
        $check_stmt = $db->prepare("SELECT id FROM instruktur WHERE nomor_licensi = ? AND id != ?");
        $check_stmt->execute([$nomor_licensi, $id]);
        if ($check_stmt->rowCount() > 0) {
            $error = "Nomor lisensi '$nomor_licensi' sudah digunakan oleh instruktur lain!";
        } else {
            // Handle upload foto baru
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $foto_name = time() . '_' . basename($_FILES['foto']['name']);
                $target_dir = "../assets/images/instruktur/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $target_file = $target_dir . $foto_name;
                $check = getimagesize($_FILES["foto"]["tmp_name"]);
                if ($check !== false && $_FILES["foto"]["size"] <= 2097152) {
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                            if ($foto && file_exists($target_dir . $foto)) {
                                unlink($target_dir . $foto);
                            }
                            $foto = $foto_name;
                        }
                    }
                }
            }

            try {
                $stmt = $db->prepare("UPDATE instruktur SET nama_lengkap = ?, nomor_licensi = ?, spesialisasi = ?, pengalaman_tahun = ?, deskripsi = ?, foto = ?, aktif = ? WHERE id = ?");
                if ($stmt->execute([$nama_lengkap, $nomor_licensi, $spesialisasi, $pengalaman_tahun, $deskripsi, $foto, $aktif, $id])) {
                    $success = "Instruktur berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate instruktur.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("SELECT foto FROM instruktur WHERE id = ?");
    $stmt->execute([$id]);
    $instruktur = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("DELETE FROM instruktur WHERE id = ?");
    if ($stmt->execute([$id])) {
        if ($instruktur['foto'] && file_exists("../assets/images/instruktur/" . $instruktur['foto'])) {
            unlink("../assets/images/instruktur/" . $instruktur['foto']);
        }
        $success = "Instruktur berhasil dihapus!";
    } else {
        $error = "Gagal menghapus instruktur!";
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $db->prepare("UPDATE instruktur SET aktif = NOT aktif WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Status instruktur berhasil diubah!";
    } else {
        $error = "Gagal mengubah status instruktur!";
    }
}

// Fetch all instructors
$stmt = $db->query("SELECT * FROM instruktur ORDER BY pengalaman_tahun DESC, nama_lengkap ASC");
$instruktur = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$total_instruktur = $db->query("SELECT COUNT(*) as total FROM instruktur")->fetch()['total'];
$instruktur_aktif = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE aktif = 1")->fetch()['total'];
$manual_experts = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE spesialisasi = 'manual' OR spesialisasi = 'keduanya'")->fetch()['total'];
$matic_experts = $db->query("SELECT COUNT(*) as total FROM instruktur WHERE spesialisasi = 'matic' OR spesialisasi = 'keduanya'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Instruktur - Krishna Driving</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 40px;
            max-height: 48px;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Kelola Instruktur</h1>
                        <p class="text-gray-600">Kelola data instruktur mengemudi</p>
                    </div>
                    <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bars text-gray-600"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Tambah Instruktur - Form Hidden by Default -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="p-5">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg  font-semibold text-gray-900">Kelola Instruktur</h3>
                                <p class="text-sm text-gray-500">Tambah atau edit data instruktur</p>
                            </div>
                            <button onclick="toggleInstructorForm()"
                                class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full shadow-md hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                aria-label="Toggle form">
                                <i id="toggleInstructorIcon" class="fas fa-plus text-sm"></i>
                            </button>
                        </div>

                        <!-- Form (hidden by default) -->
                        <div id="instructorFormContainer" class="mt-6 hidden">
                            <form method="POST" enctype="multipart/form-data" id="instructorForm">
                                <input type="hidden" name="id" id="editId">
                                <input type="hidden" name="current_foto" id="currentFoto">
                                <input type="hidden" name="add_instruktur" value="1">

                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Foto & Status -->
                                    <div class="lg:col-span-1">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Foto Instruktur</label>
                                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                                    <div id="photoPreview" class="mb-3">
                                                        <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
                                                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                                                        </div>
                                                    </div>
                                                    <input type="file" name="foto" id="foto" accept="image/*"
                                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                    <p class="text-xs text-gray-500 mt-2">JPG, PNG • Maks. 2MB</p>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="aktif" id="aktif" class="w-4 h-4 text-blue-600 rounded" checked>
                                                    <label for="aktif" class="ml-2 text-sm text-gray-700">Aktif</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Instruktur -->
                                    <div class="lg:col-span-2 space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                                                <input type="text" name="nama_lengkap" id="nama_lengkap" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Lisensi *</label>
                                                <input type="text" name="nomor_licensi" id="nomor_licensi" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Spesialisasi *</label>
                                                <select name="spesialisasi" id="spesialisasi" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                    <option value="manual">Manual</option>
                                                    <option value="matic">Matic</option>
                                                    <option value="keduanya">Keduanya</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Pengalaman (Tahun) *</label>
                                                <input type="number" name="pengalaman_tahun" id="pengalaman_tahun" required min="1" max="50"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi & Keahlian *</label>
                                            <textarea name="deskripsi" id="deskripsi" rows="4" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                        </div>
                                        <div class="flex space-x-3 pt-2">
                                            <button type="submit"
                                                class="flex-1 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition">
                                                <i class="fas fa-plus mr-1.5"></i> Tambah Instruktur
                                            </button>
                                            <button type="button" onclick="toggleInstructorForm()"
                                                class="flex-1 px-5 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                                                <i class="fas fa-times mr-1.5"></i> Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Instruktur</p>
                                <p class="text-2xl font-bold"><?= $total_instruktur ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Instruktur Aktif</p>
                                <p class="text-2xl font-bold"><?= $instruktur_aktif ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-cog text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ahli Manual</p>
                                <p class="text-2xl font-bold"><?= $manual_experts ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-car-side text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ahli Matic</p>
                                <p class="text-2xl font-bold"><?= $matic_experts ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructors List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium">Daftar Instruktur (<?= count($instruktur) ?>)</h3>
                    </div>
                    <div class="p-6">
                        <?php if (count($instruktur) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <?php foreach ($instruktur as $data): ?>
                                    <div class="bg-gray-50 rounded-lg border p-6 text-center hover:shadow-md transition">
                                        <div class="relative mb-4">
                                            <div class="w-20 h-20 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                                                <?php if ($data['foto']): ?>
                                                    <img src="../assets/images/instruktur/<?= htmlspecialchars($data['foto']) ?>" class="w-20 h-20 rounded-full object-cover">
                                                <?php else: ?>
                                                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="absolute top-0 right-0">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $data['aktif'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <?= $data['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </div>
                                        </div>
                                        <h4 class="font-bold text-gray-800 text-lg mb-1"><?= htmlspecialchars($data['nama_lengkap']) ?></h4>
                                        <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($data['nomor_licensi']) ?></p>
                                        <span class="inline-block px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 capitalize"><?= htmlspecialchars($data['spesialisasi']) ?></span>
                                        <div class="text-sm text-gray-600 mt-2"><?= $data['pengalaman_tahun'] ?>+ Tahun</div>
                                        <p class="text-gray-700 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($data['deskripsi']) ?></p>
                                        <div class="flex justify-center space-x-2 mt-3">
                                            <button type="button" onclick="editInstructor(<?= $data['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="instruktur.php?toggle=<?= $data['id'] ?>" class="text-<?= $data['aktif'] ? 'yellow' : 'green' ?>-600 hover:text-<?= $data['aktif'] ? 'yellow' : 'green' ?>-900" title="<?= $data['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="fas fa-<?= $data['aktif'] ? 'pause' : 'play' ?>"></i>
                                            </a>
                                            <button type="button" onclick="confirmDelete(<?= $data['id'] ?>)" class="text-red-600 hover:text-red-900" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-chalkboard-teacher text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">Belum ada instruktur.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleInstructorForm() {
            const form = document.getElementById('instructorFormContainer');
            const icon = document.getElementById('toggleInstructorIcon');

            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        }


        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').innerHTML = `
                        <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                            <img src="${e.target.result}" class="w-32 h-32 rounded-full object-cover">
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        function editInstructor(id) {
            fetch(`get_instruktur_data.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editId').value = data.id;
                    document.getElementById('nama_lengkap').value = data.nama_lengkap;
                    document.getElementById('nomor_licensi').value = data.nomor_licensi;
                    document.getElementById('spesialisasi').value = data.spesialisasi;
                    document.getElementById('pengalaman_tahun').value = data.pengalaman_tahun;
                    document.getElementById('deskripsi').value = data.deskripsi;
                    document.getElementById('aktif').checked = data.aktif == 1;
                    document.getElementById('currentFoto').value = data.foto;

                    if (data.foto) {
                        document.getElementById('photoPreview').innerHTML = `
                            <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                                <img src="../assets/images/instruktur/${encodeURIComponent(data.foto)}" class="w-32 h-32 rounded-full object-cover">
                            </div>
                        `;
                    } else {
                        document.getElementById('photoPreview').innerHTML = `
                            <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                            </div>
                        `;
                    }

                    document.getElementById('editMode').value = '1';
                    document.getElementById('addMode').value = '0';
                    document.getElementById('formTitle').textContent = 'Edit Instruktur';
                    document.getElementById('submitButton').innerHTML = '<i class="fas fa-save mr-2"></i>Update Instruktur';
                    document.getElementById('cancelButton').classList.remove('hidden');
                    document.getElementById('instructorForm').scrollIntoView({
                        behavior: 'smooth'
                    });
                })
                .catch(err => {
                    alert('Gagal memuat data instruktur.');
                });
        }

        function confirmDelete(id) {
            if (confirm('Yakin ingin menghapus instruktur ini?')) {
                window.location.href = `instruktur.php?delete=${id}`;
            }
        }

        function resetForm() {
            document.getElementById('instructorForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('currentFoto').value = '';
            document.getElementById('editMode').value = '0';
            document.getElementById('addMode').value = '1';
            document.getElementById('formTitle').textContent = 'Tambah Instruktur Baru';
            document.getElementById('submitButton').innerHTML = '<i class="fas fa-plus mr-2"></i>Tambah Instruktur';
            document.getElementById('cancelButton').classList.add('hidden');
            document.getElementById('photoPreview').innerHTML = `
                <div class="w-32 h-32 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                </div>
            `;
        }

        document.getElementById('instructorForm').addEventListener('submit', function(e) {
            const nama = document.getElementById('nama_lengkap').value.trim();
            const lisensi = document.getElementById('nomor_licensi').value.trim();
            const pengalaman = parseInt(document.getElementById('pengalaman_tahun').value);
            if (!nama || !lisensi) {
                alert('Nama dan nomor lisensi wajib diisi!');
                e.preventDefault();
                return;
            }
            if (pengalaman < 1 || pengalaman > 50) {
                alert('Pengalaman harus antara 1–50 tahun!');
                e.preventDefault();
                return;
            }
            const foto = document.getElementById('foto').files[0];
            if (foto && foto.size > 2 * 1024 * 1024) {
                alert('Ukuran foto maksimal 2MB!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>

</html>