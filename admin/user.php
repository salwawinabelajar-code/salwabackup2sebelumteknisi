<?php
require_once '../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$error = '';
$success = '';

// Handle tambah user baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $nama = $db->escape_string($_POST['nama']);
    $email = $db->escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $db->escape_string($_POST['role']);
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Nama, email, dan password wajib diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek email sudah terdaftar atau belum
        $check = $db->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (nama, email, password, role, created_at) 
                      VALUES ('$nama', '$email', '$hashed_password', '$role', NOW())";
            
            if ($db->conn->query($query)) {
                $_SESSION['success'] = "User baru berhasil ditambahkan!";
                redirect('user.php');
            } else {
                $error = "Gagal menambahkan user: " . $db->conn->error;
            }
        }
    }
}

// Handle edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = $db->escape_string($_POST['user_id']);
    $nama = $db->escape_string($_POST['nama']);
    $email = $db->escape_string($_POST['email']);
    $role = $db->escape_string($_POST['role']);
    $password_baru = $_POST['password_baru'] ?? '';
    
    // Validasi
    if (empty($nama) || empty($email)) {
        $error = "Nama dan email wajib diisi!";
    } else {
        // Cek email sudah terdaftar atau belum (kecuali email sendiri)
        $check = $db->query("SELECT id FROM users WHERE email = '$email' AND id != '$user_id'");
        if ($check->num_rows > 0) {
            $error = "Email sudah digunakan oleh user lain!";
        } else {
            // Update query
            if (!empty($password_baru)) {
                if (strlen($password_baru) < 6) {
                    $error = "Password minimal 6 karakter!";
                } else {
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET nama = '$nama', email = '$email', role = '$role', password = '$hashed_password' WHERE id = '$user_id'";
                }
            } else {
                $query = "UPDATE users SET nama = '$nama', email = '$email', role = '$role' WHERE id = '$user_id'";
            }
            
            if (!$error && $db->conn->query($query)) {
                $_SESSION['success'] = "User berhasil diperbarui!";
                redirect('user.php');
            } elseif (!$error) {
                $error = "Gagal memperbarui user: " . $db->conn->error;
            }
        }
    }
}

// Handle hapus user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_user'])) {
    $user_id = $db->escape_string($_POST['user_id']);

    // Cegah admin menghapus akun sendiri
    if ($user_id == $_SESSION['user_id']) {
        $error = "Anda tidak dapat menghapus akun sendiri!";
    } else {
        // Mulai transaksi
        $db->conn->begin_transaction();

        try {
            // Hapus semua pengaduan milik user terlebih dahulu
            $deletePengaduan = "DELETE FROM pengaduan WHERE user_id = '$user_id'";
            $db->conn->query($deletePengaduan);

            // Hapus user
            $deleteUser = "DELETE FROM users WHERE id = '$user_id'";
            if ($db->conn->query($deleteUser)) {
                $db->conn->commit();
                $_SESSION['success'] = "User beserta seluruh pengaduannya berhasil dihapus!";
            } else {
                throw new Exception($db->conn->error);
            }
        } catch (Exception $e) {
            $db->conn->rollback();
            $error = "Gagal menghapus user: " . $e->getMessage();
        }

        redirect('user.php');
    }
}

// Get search filter
$search = isset($_GET['search']) ? $db->escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $db->escape_string($_GET['role']) : '';

// Build query
$query = "SELECT *, 
          (SELECT COUNT(*) FROM pengaduan WHERE user_id = users.id) as total_pengaduan
          FROM users WHERE 1=1";

if ($search) {
    $query .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role_filter) {
    $query .= " AND role = '$role_filter'";
}

$query .= " ORDER BY created_at DESC";
$result = $db->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manajemen User - AssetCare Admin</title>
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
        
        .user-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .user-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
        
        .badge-role {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-pegawai { background: #6c757d; color: white; }
        .badge-teknisi { background: #17a2b8; color: white; }
        .badge-admin { background: var(--primary); color: white; }
        
        .btn-hapus {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-hapus:hover {
            background: #dc3545;
            color: white;
        }

        .btn-edit {
            color: #ffc107;
            border-color: #ffc107;
        }
        .btn-edit:hover {
            background: #ffc107;
            color: white;
        }

        .btn-tugas {
            color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-tugas:hover {
            background: #17a2b8;
            color: white;
        }

        .btn-tambah-user {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-tambah-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(9, 99, 126, 0.3);
            color: white;
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
            .user-stats {
                flex-direction: column;
                gap: 10px;
            }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .top-navbar h4 { font-size: 1.2rem; }
            .btn-sm { padding: 4px 8px; font-size: 0.7rem; }
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
                <h4><i class="fas fa-users me-2"></i>Manajemen User</h4>
                <p>Kelola akun pengguna sistem</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-tambah-user" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                    <i class="fas fa-user-plus me-1"></i> Tambah User
                </button>
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

        <!-- Filter Section -->
        <div class="user-card">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter User</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search" placeholder="Cari nama atau email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="">Semua Role</option>
                        <option value="pegawai" <?php echo $role_filter == 'pegawai' ? 'selected' : ''; ?>>Pegawai</option>
                        <option value="teknisi" <?php echo $role_filter == 'teknisi' ? 'selected' : ''; ?>>Teknisi</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
                
                <div class="col-12">
                    <a href="user.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-1"></i> Reset Filter
                    </a>
                    <?php if ($search || $role_filter): ?>
                        <span class="ms-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo count($users); ?> user ditemukan
                        </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Users Grid -->
        <div class="row">
            <?php if (empty($users)): ?>
                <div class="col-12">
                    <div class="user-card text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada user ditemukan</h5>
                        <p class="mb-0">Coba gunakan filter yang berbeda</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php echo generateInitials($user['nama']); ?>
                            </div>
                            
                            <div class="text-center mb-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['nama']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo $user['email']; ?>
                                </p>
                                
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="badge-role badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $user['total_pengaduan']; ?></div>
                                    <div class="stat-label">Pengaduan</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </div>
                                    <div class="stat-label">Bergabung</div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="d-grid gap-2">
                                    <?php if ($user['role'] == 'teknisi'): ?>
                                        <a href="riwayat_tugas_teknisi.php?user_id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-tugas">
                                            <i class="fas fa-clipboard-list me-1"></i> Lihat Tugas
                                        </a>
                                    <?php else: ?>
                                        <a href="pengaduan.php?user_id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-clipboard-list me-1"></i> Lihat Pengaduan
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <!-- Tombol Edit -->
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-edit flex-grow-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>

                                        <!-- Tombol Hapus (kecuali admin yang sedang login) -->
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus flex-grow-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#hapusModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash-alt me-1"></i> Hapus
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal Edit User -->
                                    <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit me-2"></i>Edit User
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                                    <input type="text" class="form-control" name="nama" 
                                                                           value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                                    <input type="email" class="form-control" name="email" 
                                                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Password Baru</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                                    <input type="password" class="form-control" name="password_baru" 
                                                                           placeholder="Kosongkan jika tidak ingin mengubah password" id="password_baru_<?php echo $user['id']; ?>">
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordEdit('password_baru_<?php echo $user['id']; ?>')">
                                                                        <i class="far fa-eye"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="text-muted">Minimal 6 karakter jika diisi</small>
                                                            </div>
                                                            
                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                                                <select class="form-select" name="role" required>
                                                                    <option value="pegawai" <?php echo $user['role'] == 'pegawai' ? 'selected' : ''; ?>>Pegawai</option>
                                                                    <option value="teknisi" <?php echo $user['role'] == 'teknisi' ? 'selected' : ''; ?>>Teknisi</option>
                                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i>Batal
                                                        </button>
                                                        <button type="submit" name="edit_user" class="btn" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                                                            <i class="fas fa-save me-1"></i>Simpan Perubahan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Konfirmasi Hapus -->
                                    <div class="modal fade" id="hapusModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Apakah Anda yakin ingin menghapus user <strong><?php echo htmlspecialchars($user['nama']); ?></strong>?</p>
                                                    <p class="text-danger mb-0">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Semua pengaduan milik user ini juga akan ikut terhapus!
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="hapus_user" class="btn btn-danger">
                                                            <i class="fas fa-trash-alt me-1"></i> Ya, Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Summary -->
        <div class="user-card">
            <h5 class="mb-3">Ringkasan User</h5>
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total User</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-number">
                        <?php
                        $admin_count = array_filter($users, function($u) {
                            return $u['role'] == 'admin';
                        });
                        echo count($admin_count);
                        ?>
                    </div>
                    <div class="stat-label">Admin</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-number">
                        <?php
                        $teknisi_count = array_filter($users, function($u) {
                            return $u['role'] == 'teknisi';
                        });
                        echo count($teknisi_count);
                        ?>
                    </div>
                    <div class="stat-label">Teknisi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="tambahUserModal" tabindex="-1" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                    <h5 class="modal-title" id="tambahUserModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Tambah User Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="formTambahUser">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nama" class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           placeholder="Masukkan nama lengkap" required>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Masukkan email" required>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="password" class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Minimal 6 karakter" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordTambah('password')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrengthTambah" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthTextTambah">Kekuatan password: -</small>
                            </div>
                            
                            <div class="col-12">
                                <label for="role" class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="pegawai">Pegawai</option>
                                    <option value="teknisi">Teknisi</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                        <button type="submit" name="tambah_user" class="btn" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                            <i class="fas fa-save me-1"></i>Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fungsi toggle password untuk modal tambah user
        function togglePasswordTambah(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('button i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Fungsi toggle password untuk modal edit
        function togglePasswordEdit(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                const icon = field.parentNode.querySelector('button i');
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }
        
        // Password strength checker untuk modal tambah user
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('passwordStrengthTambah');
                const strengthText = document.getElementById('passwordStrengthTextTambah');
                
                let strength = 0;
                let text = 'Kekuatan password: ';
                
                if (password.length >= 6) strength += 25;
                if (password.length >= 8) strength += 25;
                if (/[a-z]/.test(password)) strength += 12.5;
                if (/[A-Z]/.test(password)) strength += 12.5;
                if (/[0-9]/.test(password)) strength += 12.5;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
                
                strengthBar.style.width = strength + '%';
                
                if (strength < 50) {
                    strengthBar.className = 'progress-bar bg-danger';
                    text += 'lemah';
                } else if (strength < 75) {
                    strengthBar.className = 'progress-bar bg-warning';
                    text += 'cukup';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    text += 'kuat';
                }
                
                strengthText.textContent = text;
            });
        }
        
        // Validasi form tambah user
        document.getElementById('formTambahUser')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    title: 'Password terlalu pendek!',
                    text: 'Password minimal 6 karakter',
                    icon: 'error',
                    confirmButtonColor: '#09637E'
                });
            }
        });
        
        // Animasi modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('tambahUserModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function() {
                    this.classList.add('animate__animated', 'animate__fadeIn');
                });
                modal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('formTambahUser')?.reset();
                    const strengthBar = document.getElementById('passwordStrengthTambah');
                    const strengthText = document.getElementById('passwordStrengthTextTambah');
                    if (strengthBar) strengthBar.style.width = '0%';
                    if (strengthText) strengthText.textContent = 'Kekuatan password: -';
                });
            }
        });
    </script>
</body>
</html>