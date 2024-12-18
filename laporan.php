<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$error = '';
$success = '';

// Buat direktori uploads jika belum ada
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori = $_POST['kategori'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    
    try {
        $foto_name = null;
        
        // Handle file upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed));
            }
            
            if ($_FILES['foto']['size'] > 5000000) { // 5MB limit
                throw new Exception('Ukuran file terlalu besar. Maksimal 5MB.');
            }
            
            // Generate unique filename
            $foto_name = uniqid() . '_' . time() . '.' . $ext;
            $target_path = 'uploads/' . $foto_name;
            
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target_path)) {
                throw new Exception('Gagal mengupload file.');
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO laporan (user_id, judul, deskripsi, kategori, latitude, longitude, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $judul, $deskripsi, $kategori, $latitude, $longitude, $foto_name]);
        
        $success = 'Laporan berhasil dikirim!';
        
    } catch(Exception $e) {
        $error = $e->getMessage();
        // Hapus file jika upload gagal
        if (isset($foto_name) && file_exists('uploads/' . $foto_name)) {
            unlink('uploads/' . $foto_name);
        }
    }
}

// Ambil laporan jika ada ID
$laporan = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT l.*, u.nama_lengkap FROM laporan l LEFT JOIN users u ON l.user_id = u.id WHERE l.id = ?");
        $stmt->execute([$_GET['id']]);
        $laporan = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_GET['id']) ? 'Detail Laporan' : 'Buat Laporan' ?> - DesaMap</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #map { height: 400px; width: 100%; margin-bottom: 20px; }
        .preview-image { max-width: 300px; margin-top: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">DesaMap</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="laporan.php">Buat Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="daftar-laporan.php">Daftar Laporan</a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">Dashboard Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Hai, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($laporan)): ?>
            <!-- Detail Laporan -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($laporan['judul']) ?></h5>
                    <p class="text-muted">
                        Dilaporkan oleh: <?= htmlspecialchars($laporan['nama_lengkap']) ?><br>
                        Tanggal: <?= date('d/m/Y H:i', strtotime($laporan['tanggal'])) ?>
                    </p>
                    <div class="mb-3">
                        <strong>Kategori:</strong> <?= htmlspecialchars($laporan['kategori']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Deskripsi:</strong><br>
                        <?= nl2br(htmlspecialchars($laporan['deskripsi'])) ?>
                    </div>
                    <?php if ($laporan['foto']): ?>
                        <div class="mb-3">
                            <strong>Foto:</strong><br>
                            <img src="uploads/<?= htmlspecialchars($laporan['foto']) ?>" class="img-fluid" style="max-width: 300px;">
                        </div>
                    <?php endif; ?>
                    <div id="map"></div>
                </div>
            </div>
        <?php else: ?>
            <!-- Form Laporan Baru -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Buat Laporan Baru</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Judul Laporan</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori" required>
                                <option value="infrastruktur">Infrastruktur</option>
                                <option value="lingkungan">Lingkungan</option>
                                <option value="sosial">Sosial</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Lokasi pada Peta</label>
                            <div id="map"></div>
                            <input type="hidden" name="latitude" id="latitude" required>
                            <input type="hidden" name="longitude" id="longitude" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB.</small>
                            <div id="imagePreview"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Kirim Laporan</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        var map = L.map('map').setView([-6.6525384, 110.7078008], 15);
        var marker = null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        <?php if (isset($laporan)): ?>
            // Tampilkan marker untuk detail laporan
            L.marker([<?= $laporan['latitude'] ?>, <?= $laporan['longitude'] ?>])
                .bindPopup("<?= htmlspecialchars($laporan['judul']) ?>")
                .addTo(map);
        <?php else: ?>
            // Untuk form laporan baru
            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);
                
                document.getElementById('latitude').value = e.latlng.lat;
                document.getElementById('longitude').value = e.latlng.lng;
            });
        <?php endif; ?>

        // Preview gambar sebelum upload
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-image');
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
