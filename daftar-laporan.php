<?php
session_start();
require_once 'config/database.php';

try {
    $sql = "SELECT * FROM laporan ORDER BY tanggal DESC";
    $stmt = $pdo->query($sql);
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
    <title>Daftar Laporan - DesaMap</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Buat Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="daftar-laporan.php">Daftar Laporan</a>
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
                        <h5 class="card-title">Peta Sebaran Laporan</h5>
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Daftar Laporan</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($laporan as $item): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($item['judul']) ?></td>
                                        <td><?= htmlspecialchars($item['kategori']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $item['status'] === 'selesai' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($item['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="showMarker(<?= $item['latitude'] ?>, <?= $item['longitude'] ?>, '<?= htmlspecialchars($item['judul']) ?>')">
                                                Lihat di Peta
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
        var map = L.map('map').setView([-6.6525384, 110.7078008], 15);
        var markers = {};

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        <?php foreach($laporan as $item): ?>
        markers[<?= $item['id'] ?>] = L.marker([<?= $item['latitude'] ?>, <?= $item['longitude'] ?>])
            .bindPopup("<b><?= htmlspecialchars($item['judul']) ?></b><br><?= htmlspecialchars($item['kategori']) ?>")
            .addTo(map);
        <?php endforeach; ?>

        function showMarker(lat, lng, title) {
            map.setView([lat, lng], 17);
            L.marker([lat, lng]).bindPopup("<b>" + title + "</b>").openPopup();
        }
    </script>
</body>
</html>
