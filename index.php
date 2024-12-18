<?php
session_start();
require_once 'config/database.php';

// Redirect ke halaman login jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Ambil data laporan
try {
    $stmt = $pdo->query("SELECT * FROM laporan ORDER BY tanggal DESC");
    $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    $laporan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesaMap - Sistem Informasi Geografis Desa</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        #map {
            height: 600px;
            width: 100%;
        }
        .info-box {
            padding: 6px 8px;
            font: 14px/16px Arial, Helvetica, sans-serif;
            background: white;
            background: rgba(255,255,255,0.8);
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            border-radius: 5px;
        }
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
                        <a class="nav-link active" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Buat Laporan</a>
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Peta Desa</h5>
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inisialisasi peta
        var map = L.map('map').setView([-6.6525384, 110.7078008], 15);

        // Tambahkan layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: ' OpenStreetMap contributors'
        }).addTo(map);

        // Tambahkan marker untuk lokasi desa
        var marker = L.marker([-6.6525384, 110.7078008]).addTo(map);
        marker.bindPopup("<b>Lokasi Desa</b><br>Pusat Desa").openPopup();

        // Tambahkan marker untuk setiap laporan
        <?php foreach($laporan as $item): ?>
        L.marker([<?= $item['latitude'] ?>, <?= $item['longitude'] ?>])
            .bindPopup(`
                <strong><?= htmlspecialchars($item['judul']) ?></strong><br>
                Kategori: <?= htmlspecialchars($item['kategori']) ?><br>
                Status: <?= ucfirst($item['status']) ?><br>
                <a href="laporan.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary mt-2">Detail</a>
            `)
            .addTo(map);
        <?php endforeach; ?>

        // Tambahkan kontrol zoom
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);
    </script>
</body>
</html>
