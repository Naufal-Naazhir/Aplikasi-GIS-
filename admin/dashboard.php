<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil statistik
try {
    // Total laporan
    $stmt = $pdo->query("SELECT COUNT(*) FROM laporan");
    $total_laporan = $stmt->fetchColumn();
    
    // Laporan per kategori
    $stmt = $pdo->query("SELECT kategori, COUNT(*) as jumlah FROM laporan GROUP BY kategori");
    $laporan_per_kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Laporan per status
    $stmt = $pdo->query("SELECT status, COUNT(*) as jumlah FROM laporan GROUP BY status");
    $laporan_per_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Laporan terbaru
    $stmt = $pdo->query("SELECT * FROM laporan ORDER BY tanggal DESC LIMIT 5");
    $laporan_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - DesaMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">DesaMap Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola-laporan.php">Kelola Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Lihat Website</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Dashboard Admin</h2>
                <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Laporan</h5>
                        <h2><?= $total_laporan ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Laporan Selesai</h5>
                        <h2><?= $laporan_per_status[array_search('selesai', array_column($laporan_per_status, 'status'))]['jumlah'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Laporan Pending</h5>
                        <h2><?= $laporan_per_status[array_search('pending', array_column($laporan_per_status, 'status'))]['jumlah'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Laporan per Kategori</h5>
                        <canvas id="kategoriChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Status Laporan</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Laporan Terbaru -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Laporan Terbaru</h5>
                        <div class="table-responsive">
                            <table class="table">
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
                                    <?php foreach($laporan_terbaru as $laporan): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($laporan['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($laporan['judul']) ?></td>
                                        <td><?= htmlspecialchars($laporan['kategori']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $laporan['status'] === 'selesai' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($laporan['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="kelola-laporan.php?id=<?= $laporan['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data untuk chart kategori
        const kategoriData = {
            labels: <?= json_encode(array_column($laporan_per_kategori, 'kategori')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($laporan_per_kategori, 'jumlah')) ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
            }]
        };

        // Data untuk chart status
        const statusData = {
            labels: <?= json_encode(array_column($laporan_per_status, 'status')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($laporan_per_status, 'jumlah')) ?>,
                backgroundColor: ['#FF6384', '#36A2EB']
            }]
        };

        // Buat chart kategori
        new Chart(document.getElementById('kategoriChart'), {
            type: 'pie',
            data: kategoriData
        });

        // Buat chart status
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: statusData
        });
    </script>
</body>
</html>
