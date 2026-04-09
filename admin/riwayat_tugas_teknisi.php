<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Ambil data teknisi
$query_teknisi = "SELECT * FROM users WHERE id = $user_id AND role = 'teknisi'";
$result_teknisi = $db->query($query_teknisi);
$teknisi = $result_teknisi->fetch_assoc();

if (!$teknisi) {
    redirect('user.php');
}

// Filter status
$status_filter = isset($_GET['status']) ? $db->escape_string($_GET['status']) : '';
$search = isset($_GET['search']) ? $db->escape_string($_GET['search']) : '';

// Query untuk mengambil tugas-tugas teknisi (pengaduan yang ditugaskan)
$query = "SELECT p.*, u.nama as user_nama, 
          (SELECT COUNT(*) FROM tanggapan WHERE pengaduan_id = p.id) as total_tanggapan
          FROM pengaduan p 
          LEFT JOIN users u ON p.user_id = u.id
          WHERE p.teknisi_id = $user_id";

if ($status_filter) {
    $query .= " AND p.status = '$status_filter'";
}
if ($search) {
    $query .= " AND (p.judul LIKE '%$search%' OR p.deskripsi LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";
$result = $db->query($query);
$tugas_list = $result->fetch_all(MYSQLI_ASSOC);

// Hitung statistik
$stat_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'Proses' THEN 1 ELSE 0 END) as proses,
                SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
                FROM pengaduan WHERE teknisi_id = $user_id";
$stat_result = $db->query($stat_query);
$stats = $stat_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas Teknisi - <?php echo htmlspecialchars($teknisi['nama']); ?> - AssetCare Admin</title>
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
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-menunggu { background: #ffc107; color: #000; }
        .badge-proses { background: #17a2b8; color: white; }
        .badge-selesai { background: #28a745; color: white; }
        .btn-back {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-back:hover {
            color: white;
            opacity: 0.9;
        }
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
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="navbar-top d-flex justify-content-between align-items-center">
            <div>
                <a href="user.php" class="btn-back me-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
                <h4 class="mb-0 d-inline-block text-primary">
                    <i class="fas fa-tasks me-2"></i>Daftar Tugas: <?php echo htmlspecialchars($teknisi['nama']); ?>
                </h4>
            </div>
            <div class="d-flex align-items-center gap-3">
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

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Tugas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <div class="stat-number"><?php echo $stats['menunggu'] ?? 0; ?></div>
                    <div class="stat-label">Menunggu</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <div class="stat-number"><?php echo $stats['proses'] ?? 0; ?></div>
                    <div class="stat-label">Diproses</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="stat-number"><?php echo $stats['selesai'] ?? 0; ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Informasi Teknisi -->
        <div class="card-custom p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="user-avatar" style="width: 80px; height: 80px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto;">
                        <?php echo generateInitials($teknisi['nama']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4><?php echo htmlspecialchars($teknisi['nama']); ?></h4>
                    <p class="text-muted mb-1"><i class="fas fa-envelope me-2"></i><?php echo $teknisi['email']; ?></p>
                    <p class="text-muted mb-0"><i class="fas fa-calendar me-2"></i>Bergabung: <?php echo date('d F Y', strtotime($teknisi['created_at'])); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge-teknisi" style="background: #17a2b8; color: white; padding: 8px 20px; border-radius: 20px;">
                        <i class="fas fa-wrench me-1"></i> Teknisi
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter dan Tabel Tugas -->
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Riwayat Tugas</h5>
            </div>
            
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-4">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Cari judul atau deskripsi..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="Menunggu" <?php echo $status_filter == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Proses" <?php echo $status_filter == 'Proses' ? 'selected' : ''; ?>>Proses</option>
                        <option value="Selesai" <?php echo $status_filter == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="?user_id=<?php echo $user_id; ?>" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal Kejadian</th>
                                <th>Judul</th>
                                <th>Pelapor</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Tanggal Tugas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tugas_list)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                                        Belum ada tugas untuk teknisi ini
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tugas_list as $index => $tugas): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($tugas['tanggal_kejadian'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($tugas['judul']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($tugas['deskripsi'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($tugas['user_nama']); ?></td>
                                        <td>
                                            <?php
                                            $prioritas_class = '';
                                            if ($tugas['prioritas'] == 'Tinggi') $prioritas_class = 'danger';
                                            elseif ($tugas['prioritas'] == 'Sedang') $prioritas_class = 'warning';
                                            else $prioritas_class = 'success';
                                            ?>
                                            <span class="badge bg-<?php echo $prioritas_class; ?>">
                                                <?php echo $tugas['prioritas']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status badge-<?php 
                                                echo strtolower($tugas['status']) == 'menunggu' ? 'menunggu' : 
                                                    (strtolower($tugas['status']) == 'proses' ? 'proses' : 'selesai'); 
                                            ?>">
                                                <?php echo $tugas['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($tugas['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $tugas['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Detail Tugas -->
                                    <div class="modal fade" id="detailModal<?php echo $tugas['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-clipboard-list me-2"></i>Detail Tugas
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Judul Pengaduan</label>
                                                                <p><?php echo htmlspecialchars($tugas['judul']); ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Pelapor</label>
                                                                <p><?php echo htmlspecialchars($tugas['user_nama']); ?></p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Tanggal Kejadian</label>
                                                                <p><?php echo date('d F Y', strtotime($tugas['tanggal_kejadian'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Prioritas</label>
                                                                <p>
                                                                    <span class="badge bg-<?php 
                                                                        echo $tugas['prioritas'] == 'Tinggi' ? 'danger' : 
                                                                            ($tugas['prioritas'] == 'Sedang' ? 'warning' : 'success'); 
                                                                    ?>">
                                                                        <?php echo $tugas['prioritas']; ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Status</label>
                                                                <p>
                                                                    <span class="badge-status badge-<?php 
                                                                        echo strtolower($tugas['status']) == 'menunggu' ? 'menunggu' : 
                                                                            (strtolower($tugas['status']) == 'proses' ? 'proses' : 'selesai'); 
                                                                    ?>">
                                                                        <?php echo $tugas['status']; ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Tanggal Ditugaskan</label>
                                                                <p><?php echo date('d F Y H:i', strtotime($tugas['created_at'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="mb-3">
                                                                <label class="fw-bold text-primary">Deskripsi Kerusakan</label>
                                                                <p class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($tugas['deskripsi'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <?php if ($tugas['lampiran']): ?>
                                                            <div class="col-12">
                                                                <div class="mb-3">
                                                                    <label class="fw-bold text-primary">Lampiran</label><br>
                                                                    <a href="../assets/uploads/<?php echo $tugas['lampiran']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-image me-1"></i> Lihat Lampiran
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>