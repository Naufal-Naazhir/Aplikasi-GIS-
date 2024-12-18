<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role = $_POST['role'];
    
    try {
        // Cek username sudah ada atau belum
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Jika mendaftar sebagai admin, perlu kode khusus
            if ($role === 'admin' && $_POST['admin_code'] !== 'DESA123') {
                $error = 'Kode admin tidak valid!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $nama_lengkap, $role]);
                
                $success = 'Pendaftaran berhasil! Silakan login.';
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DesaMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Daftar DesaMap</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama_lengkap" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Daftar Sebagai</label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="warga">Warga</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3" id="admin_code_div" style="display: none;">
                                <label class="form-label">Kode Admin</label>
                                <input type="password" class="form-control" name="admin_code">
                                <small class="text-muted">Diperlukan untuk pendaftaran sebagai admin</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('role').addEventListener('change', function() {
            const adminCodeDiv = document.getElementById('admin_code_div');
            if (this.value === 'admin') {
                adminCodeDiv.style.display = 'block';
            } else {
                adminCodeDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
