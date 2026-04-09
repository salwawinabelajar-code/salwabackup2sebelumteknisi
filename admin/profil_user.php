<?php
require_once '../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('user.php');
}

$user_id = $db->escape_string($_GET['id']);

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = $db->query($query_user);
if ($result_user->num_rows == 0) {
    $_SESSION['error'] = "User tidak ditemukan!";
    redirect('user.php');
}
$user = $result_user->fetch_assoc();

// Ambil daftar pengaduan user ini
$query_pengaduan = "SELECT * FROM pengaduan WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result_pengaduan = $db->query($query_pengaduan);
$pengaduan_list = $result_pengaduan->fetch_all(MYSQLI_ASSOC);

// Hitung total pengaduan
$total_pengaduan = count($pengaduan_list);

// Hitung pengaduan per status
$status_counts = [
    'Menunggu' => 0,
    'Diproses' => 0,
    'Selesai' => 0,
    'Ditolak' => 0
];
foreach ($pengaduan_list as $p) {
    if (isset($status_counts[$p['status']])) {
        $status_counts[$p['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil User - AssetCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #09637E;
            --secondary: #088395;
            --accent: #7AB2B2;
            --light: #EBF4F6;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .sidebar .nav-link i {
            width: 25px;
            font-size: 1.1rem;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin: -20px -20px 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .user-avatar-large {
            width: 120px;
            height: 120px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .info-label {
            color: #666;
            font-weight: 600;
            min-width: 120px;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .stat-card {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-menunggu { background: #ffc107; color: #212529; }
        .badge-diproses { background: #0dcaf0; color: white; }
        .badge-selesai { background: #198754; color: white; }
        .badge-ditolak { background: #dc3545; color: white; }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background: var(--light);
            color: var(--primary);
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="navbar-top d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:history.back()" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
                <h4 class="mb-0 text-primary">Profil User</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                       data-bs-toggle="dropdown">
                        <div class="profile-avatar me-2">
                            <?php echo generateInitials($_SESSION['nama']); ?>
                        </div>
                        <div>
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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Kolom Kiri: Informasi User -->
            <div class="col-lg-4 mb-4">
                <div class="profile-card">
                    <div class="user-avatar-large">
                        <?php echo generateInitials($user['nama']); ?>
                    </div>
                    <h4 class="text-center mb-3"><?php echo htmlspecialchars($user['nama']); ?></h4>
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'primary' : 'secondary'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        <span class="badge bg-<?php echo ($user['status'] ?? 'aktif') == 'aktif' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($user['status'] ?? 'aktif'); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex mb-2">
                            <div class="info-label"><i class="fas fa-envelope me-2"></i>Email</div>
                            <div class="info-value"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="info-label"><i class="fas fa-id-card me-2"></i>User ID</div>
                            <div class="info-value">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="d-flex mb-2">
                            <div class="info-label"><i class="fas fa-calendar-alt me-2"></i>Bergabung</div>
                            <div class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_pengaduan; ?></div>
                                <div class="stat-label">Total Laporan</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $status_counts['Selesai']; ?></div>
                                <div class="stat-label">Selesai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kolom Kanan: Daftar Pengaduan User -->
            <div class="col-lg-8 mb-4">
                <div class="profile-card">
                    <h5 class="mb-4">Daftar Pengaduan <?php echo htmlspecialchars($user['nama']); ?></h5>
                    
                    <?php if (empty($pengaduan_list)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <h5>Tidak ada pengaduan</h5>
                            <p class="mb-0">User ini belum pernah membuat pengaduan</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Prioritas</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengaduan_list as $index => $p): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($p['tanggal_kejadian'])); ?><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($p['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($p['judul']); ?></strong><br>
                                                <small class="text-muted"><?php echo substr($p['deskripsi'], 0, 50); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo $p['kategori']; ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $priority_color = '';
                                                switch($p['prioritas']) {
                                                    case 'Tinggi': $priority_color = 'danger'; break;
                                                    case 'Sedang': $priority_color = 'warning'; break;
                                                    case 'Rendah': $priority_color = 'success'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $priority_color; ?>"><?php echo $p['prioritas']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge-status badge-<?php echo strtolower($p['status']); ?>">
                                                    <?php echo $p['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="detail_pengaduan.php?id=<?php echo $p['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>