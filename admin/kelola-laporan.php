<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Update status laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['laporan_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE laporan SET status = ?, komentar_admin = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['komentar'], $_POST['laporan_id']]);
        header('Location: kelola-laporan.php?success=1');
        exit;
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil daftar laporan
try {
    if (isset($_GET['id'])) {
        // Detail satu laporan
        $stmt = $pdo->prepare("SELECT l.*, u.nama_lengkap FROM laporan l LEFT JOIN users u ON l.user_id = u.id WHERE l.id = ?");
        $stmt->execute([$_GET['id']]);
        $laporan = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Daftar semua laporan
        $stmt = $pdo->query("SELECT l.*, u.nama_lengkap FROM laporan l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.tanggal DESC");
        $laporan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - DesaMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="kelola-laporan.php">Kelola Laporan</a>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Status laporan berhasil diperbarui!</div>
        <?php endif; ?>

        <?php if (isset($laporan)): ?>
            <!-- Detail Laporan -->
            <div class="row">
                <div class="col-md-8">
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
                                    <img src="../uploads/<?= htmlspecialchars($laporan['foto']) ?>" class="img-fluid" style="max-width: 300px;">
                                </div>
                            <?php endif; ?>
                            <div id="map" style="height: 300px;" class="mb-3"></div>
                            
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="laporan_id" value="<?= $laporan['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Update Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="pending" <?= $laporan['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="proses" <?= $laporan['status'] === 'proses' ? 'selected' : '' ?>>Sedang Diproses</option>
                                        <option value="selesai" <?= $laporan['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Komentar Admin</label>
                                    <textarea class="form-control" name="komentar" rows="3"><?= htmlspecialchars($laporan['komentar_admin'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                                <a href="kelola-laporan.php" class="btn btn-secondary">Kembali</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Daftar Laporan -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daftar Laporan</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($laporan_list as $item): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($item['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($item['judul']) ?></td>
                                    <td><?= htmlspecialchars($item['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($item['kategori']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $item['status'] === 'selesai' ? 'success' : ($item['status'] === 'proses' ? 'info' : 'warning') ?>">
                                            <?= ucfirst($item['status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($laporan)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([<?= $laporan['latitude'] ?>, <?= $laporan['longitude'] ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?= $laporan['latitude'] ?>, <?= $laporan['longitude'] ?>])
            .bindPopup("<?= htmlspecialchars($laporan['judul']) ?>")
            .addTo(map);
    </script>
    <?php endif; ?>
</body>
</html>
