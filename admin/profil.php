<?php
require_once '../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$result = $db->query("SELECT * FROM users WHERE id = '$user_id'");
$user = $result->fetch_assoc();

// Handle edit profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_profil'])) {
        $nama = $db->escape_string($_POST['nama']);
        $email = $db->escape_string($_POST['email']);
        
        // Validasi email unik
        $check = $db->query("SELECT id FROM users WHERE email = '$email' AND id != '$user_id'");
        if ($check->num_rows > 0) {
            $error = "Email sudah digunakan!";
        } else {
            $query = "UPDATE users SET nama = '$nama', email = '$email' WHERE id = '$user_id'";
            if ($db->conn->query($query)) {
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $_SESSION['success'] = "Profil berhasil diperbarui!";
                redirect('profil.php');
            } else {
                $error = "Gagal memperbarui profil: " . $db->conn->error;
            }
        }
    }
    
    // Handle ganti password
    if (isset($_POST['ganti_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];
        
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            $error = "Semua field password harus diisi!";
        } elseif ($password_baru !== $konfirmasi_password) {
            $error = "Password baru tidak cocok!";
        } elseif (strlen($password_baru) < 6) {
            $error = "Password minimal 6 karakter!";
        } else {
            // Verify old password
            $check = $db->query("SELECT password FROM users WHERE id = '$user_id'");
            if ($check->num_rows > 0) {
                $user_data = $check->fetch_assoc();
                if (password_verify($password_lama, $user_data['password'])) {
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
                    
                    if ($db->conn->query($query)) {
                        $_SESSION['success'] = "Password berhasil diubah!";
                        redirect('profil.php');
                    } else {
                        $error = "Gagal mengubah password: " . $db->conn->error;
                    }
                } else {
                    $error = "Password lama salah!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Profil Admin - AssetCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #09637E;
            --secondary: #088395;
            --accent: #7AB2B2;
            --light: #EBF4F6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .main-content { margin-left: 280px; padding: 20px 30px; transition: all 0.3s; }
        
        /* Top Navbar Style - Konsisten dengan index.php */
        .top-navbar {
            background: white;
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title h4 { color: var(--primary); font-weight: 700; margin: 0; }
        .page-title p { margin: 0; color: #6c757d; font-size: 0.85rem; }
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .user-avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .info-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 600;
            min-width: 150px;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
            text-align: right;
        }
        
        .badge-admin {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .password-input {
            position: relative;
        }
        
        .password-input .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary);
            background: none;
            border: none;
            z-index: 10;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        @media (max-width: 1200px) {
            .main-content { padding: 15px 20px; }
        }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-top: 56px; padding: 15px; }
            .top-navbar { flex-direction: column; text-align: center; }
            .page-title { text-align: center; }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
            .user-avatar-large { width: 90px; height: 90px; font-size: 2rem; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .top-navbar h4 { font-size: 1.2rem; }
            .profile-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <h4><i class="fas fa-user-circle me-2"></i>Profil Admin</h4>
                <p>Kelola informasi akun Anda</p>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="profile-avatar me-2">
                        <?php echo generateInitials($_SESSION['nama']); ?>
                    </div>
                    <div class="d-none d-md-block">
                        <span class="fw-bold"><?php echo $_SESSION['nama']; ?></span><br>
                        <small class="text-muted">Admin</small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-card mb-4 text-center">
            <div class="user-avatar-large">
                <?php echo generateInitials($user['nama']); ?>
            </div>
            
            <h3 class="mb-2"><?php echo htmlspecialchars($user['nama']); ?></h3>
            <p class="text-muted mb-3">
                <span class="badge-admin me-2">Administrator</span>
                Bergabung sejak <?php echo date('F Y', strtotime($user['created_at'])); ?>
            </p>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfilModal">
                    <i class="fas fa-edit me-1"></i> Edit Profil
                </button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gantiPasswordModal">
                    <i class="fas fa-key me-1"></i> Ganti Password
                </button>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="profile-card">
            <h5 class="mb-4">Informasi Profil</h5>
            
            <div class="info-item">
                <span class="info-label">Nama Lengkap</span>
                <span class="info-value"><?php echo htmlspecialchars($user['nama']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Role</span>
                <span class="info-value">
                    <span class="badge-admin">Administrator</span>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">ID User</span>
                <span class="info-value font-monospace">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Tanggal Bergabung</span>
                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($user['created_at'])); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Status Akun</span>
                <span class="info-value">
                    <span class="badge bg-success">Aktif</span>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Terakhir Login</span>
                <span class="info-value"><?php echo date('d/m/Y H:i'); ?> (Sekarang)</span>
            </div>
        </div>
        
        <!-- Admin Statistics -->
        <?php
        $admin_stats = $db->query("SELECT 
            (SELECT COUNT(*) FROM pengaduan WHERE status = 'Menunggu') as menunggu,
            (SELECT COUNT(*) FROM pengaduan WHERE status = 'Diproses') as diproses,
            (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users
        ")->fetch_assoc();
        ?>
        
        <div class="profile-card mt-4">
            <h5 class="mb-4">Statistik Admin</h5>
            
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <div class="h2 text-warning"><?php echo $admin_stats['menunggu']; ?></div>
                    <div class="text-muted">Pengaduan Menunggu</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="h2 text-info"><?php echo $admin_stats['diproses']; ?></div>
                    <div class="text-muted">Pengaduan Diproses</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="h2 text-success"><?php echo $admin_stats['total_users']; ?></div>
                    <div class="text-muted">Total User</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil -->
    <div class="modal fade" id="editProfilModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="user-avatar-large d-inline-block" style="width: 80px; height: 80px; font-size: 2rem;">
                                <?php echo generateInitials($user['nama']); ?>
                            </div>
                            <p class="text-muted mt-2">Inisial akan berubah sesuai nama</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" 
                                   value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="Administrator" disabled>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_profil" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ganti Password -->
    <div class="modal fade" id="gantiPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ganti Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="password_lama" required id="oldPassword">
                                <button type="button" class="toggle-password" onclick="togglePassword('oldPassword')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="password_baru" required id="newPassword" 
                                       oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrengthBar"></div>
                            </div>
                            <small class="text-muted" id="passwordStrengthText">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="konfirmasi_password" required id="confirmPassword">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ganti_password" class="btn btn-primary">Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const bar = document.getElementById('passwordStrengthBar');
            const text = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 12.5;
            if (/[A-Z]/.test(password)) strength += 12.5;
            if (/[0-9]/.test(password)) strength += 12.5;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
            
            bar.style.width = strength + '%';
            
            if (strength < 50) {
                bar.className = 'progress-bar bg-danger';
                text.textContent = 'Password lemah';
            } else if (strength < 75) {
                bar.className = 'progress-bar bg-warning';
                text.textContent = 'Password cukup';
            } else {
                bar.className = 'progress-bar bg-success';
                text.textContent = 'Password kuat';
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordForm = document.querySelector('form[method="POST"]');
            
            if (passwordForm && passwordForm.querySelector('[name="ganti_password"]')) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('newPassword').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('Password baru dan konfirmasi password tidak cocok!');
                        return false;
                    }
                    
                    if (newPassword.length < 6) {
                        e.preventDefault();
                        alert('Password baru minimal 6 karakter!');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>