<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$error = '';
$success = '';
$search = isset($_GET['search']) ? $db->escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $db->escape_string($_GET['status']) : '';

// Ambil semua teknisi
$query = "SELECT * FROM users WHERE role='teknisi'";
if ($search) {
    $query .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%')";
}
$query .= " ORDER BY created_at DESC";
$result = $db->query($query);
$teknisi_list = $result->fetch_all(MYSQLI_ASSOC);

// Hitung statistik tugas per teknisi
foreach ($teknisi_list as $key => $teknisi) {
    $stat_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
                    SUM(CASE WHEN status = 'Proses' THEN 1 ELSE 0 END) as proses,
                    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
                    FROM pengaduan WHERE teknisi_id = " . $teknisi['id'];
    $stat_result = $db->query($stat_query);
    $teknisi_list[$key]['stats'] = $stat_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar & Riwayat Tugas Teknisi - AssetCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #09637E;
            --secondary: #088395;
            --accent: #7AB2B2;
            --light: #EBF4F6;
        }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 250px; padding: 20px; }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin: -20px -20px 20px;
            border-radius: 0 0 15px 15px;
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
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 20px;
        }
        .teknisi-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s;
            height: 100%;
        }
        .teknisi-card:hover {
            transform: translateY(-5px);
        }
        .teknisi-avatar {
            width: 70px;
            height: 70px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        .badge-teknisi { background: #17a2b8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .stat-mini {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 15px;
        }
        .stat-menunggu { background: #fff3cd; color: #856404; }
        .stat-proses { background: #d1ecf1; color: #0c5460; }
        .stat-selesai { background: #d4edda; color: #155724; }
        .btn-tugas {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-tugas:hover {
            color: white;
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="navbar-top d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-primary">
                <i class="fas fa-users me-2"></i>Daftar & Riwayat Tugas Teknisi
            </h4>
            <div class="d-flex align-items-center gap-3">
                <a href="user.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Manajemen User
                </a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="profile-avatar me-2"><?php echo generateInitials($_SESSION['nama']); ?></div>
                        <div><span class="fw-bold"><?php echo $_SESSION['nama']; ?></span><br><small class="text-muted">Admin</small></div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="card-custom p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="search" placeholder="Cari teknisi berdasarkan nama atau email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari Teknisi
                    </button>
                </div>
                <?php if ($search): ?>
                    <div class="col-12">
                        <a href="daftar_tugas_teknisi.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i> Hapus Filter
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Daftar Teknisi -->
        <div class="row">
            <?php if (empty($teknisi_list)): ?>
                <div class="col-12">
                    <div class="card-custom text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada teknisi</h5>
                        <p class="mb-0">Tambahkan teknisi melalui menu <a href="user.php">Manajemen User</a></p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($teknisi_list as $teknisi): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="teknisi-card">
                            <div class="teknisi-avatar">
                                <?php echo generateInitials($teknisi['nama']); ?>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-1"><?php echo htmlspecialchars($teknisi['nama']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-envelope me-1"></i> <?php echo $teknisi['email']; ?>
                                </p>
                                <span class="badge-teknisi mb-3 d-inline-block">
                                    <i class="fas fa-wrench me-1"></i> Teknisi
                                </span>
                            </div>
                            
                            <!-- Statistik Tugas -->
                            <div class="row text-center mt-3 pt-3 border-top">
                                <div class="col-4">
                                    <div class="fw-bold text-primary"><?php echo $teknisi['stats']['total'] ?? 0; ?></div>
                                    <small class="text-muted">Total Tugas</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-warning"><?php echo $teknisi['stats']['menunggu'] ?? 0; ?></div>
                                    <small class="text-muted">Menunggu</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-success"><?php echo $teknisi['stats']['selesai'] ?? 0; ?></div>
                                    <small class="text-muted">Selesai</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="riwayat_tugas_teknisi.php?user_id=<?php echo $teknisi['id']; ?>" class="btn-tugas w-100 text-center">
                                    <i class="fas fa-clipboard-list me-1"></i> Lihat Daftar & Riwayat Tugas
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>