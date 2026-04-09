<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$error = '';
$success = '';

// Handle update status pengaduan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $pengaduan_id = $db->escape_string($_POST['pengaduan_id']);
    $new_status = $db->escape_string($_POST['new_status']);
    $catatan_admin = $db->escape_string($_POST['catatan_admin'] ?? '');
    $foto_admin = null;
    
    // Upload foto dari admin jika ada
    if (isset($_FILES['foto_admin']) && $_FILES['foto_admin']['error'] == 0) {
        $upload_dir = '../assets/uploads/admin_foto/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES['foto_admin']['name']);
        $target_file = $upload_dir . $filename;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['foto_admin']['type'];
        
        if (in_array($file_type, $allowed_types) && $_FILES['foto_admin']['size'] <= 2097152) {
            if (move_uploaded_file($_FILES['foto_admin']['tmp_name'], $target_file)) {
                $foto_admin = $filename;
            } else {
                $error = "Gagal mengupload foto";
            }
        } else {
            $error = "File harus gambar (JPG/PNG/GIF) dan maksimal 2MB";
        }
    }
    
    if (!$error) {
        $query = "UPDATE pengaduan SET status = '$new_status', catatan_admin = '$catatan_admin'";
        if ($foto_admin) {
            $query .= ", foto_admin = '$foto_admin'";
        }
        $query .= " WHERE id = '$pengaduan_id'";
        
        if ($db->conn->query($query)) {
            $_SESSION['success'] = "Status pengaduan berhasil diupdate!";
            redirect('pengaduan.php');
        } else {
            $error = "Gagal mengupdate status: " . $db->conn->error;
        }
    }
}

// Get filter values
$filter_status = isset($_GET['status']) ? $db->escape_string($_GET['status']) : '';
$filter_kategori = isset($_GET['kategori']) ? $db->escape_string($_GET['kategori']) : '';
$filter_prioritas = isset($_GET['prioritas']) ? $db->escape_string($_GET['prioritas']) : '';
$search = isset($_GET['search']) ? $db->escape_string($_GET['search']) : '';
$filter_user_id = isset($_GET['user_id']) ? $db->escape_string($_GET['user_id']) : '';

// Ambil kategori
$kategori_result = $db->query("SELECT nama FROM kategori ORDER BY nama");
$kategori_list = [];
if ($kategori_result && $kategori_result->num_rows > 0) {
    while ($row = $kategori_result->fetch_assoc()) {
        $kategori_list[] = $row['nama'];
    }
}

// Build query
$query = "SELECT p.*, u.nama as nama_user, u.email as email_user FROM pengaduan p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
if ($filter_user_id) $query .= " AND p.user_id = '$filter_user_id'";
if ($filter_status) $query .= " AND p.status = '$filter_status'";
if ($filter_kategori) $query .= " AND p.kategori = '$filter_kategori'";
if ($filter_prioritas) $query .= " AND p.prioritas = '$filter_prioritas'";
if ($search) $query .= " AND (p.judul LIKE '%$search%' OR p.deskripsi LIKE '%$search%' OR u.nama LIKE '%$search%')";
$query .= " ORDER BY p.created_at DESC";
$result = $db->query($query);
$pengaduan_data = $result->fetch_all(MYSQLI_ASSOC);

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manajemen Pengaduan - AssetCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #09637E; --secondary: #088395; --accent: #7AB2B2; --light: #EBF4F6; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 250px; padding: 20px; transition: all 0.3s; }
        
        /* Top Navbar Style - Konsisten dengan pengaturan.php */
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
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
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
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #f1f1f1;
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
        .admin-foto-badge {
            background: #6f42c1;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 5px;
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-top: 56px; padding: 15px; }
            .top-navbar { flex-direction: column; text-align: center; }
            .top-navbar .dropdown { margin-top: 10px; }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .table th, .table td { padding: 8px 10px; font-size: 0.85rem; }
            .filter-card .row .col-md-3, .filter-card .row .col-md-4 { margin-bottom: 10px; }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .badge-status { padding: 3px 8px; font-size: 0.7rem; }
            .btn-sm { padding: 4px 8px; font-size: 0.7rem; }
            .table-responsive { font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <h4><i class="fas fa-clipboard-list me-2"></i>Manajemen Pengaduan</h4>
                <p>Kelola dan pantau status pengaduan</p>
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

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="filter-card">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Pengaduan</h5>
            <form method="GET" class="row g-3">
                <?php if ($filter_user_id): ?><input type="hidden" name="user_id" value="<?php echo $filter_user_id; ?>"><?php endif; ?>
                <div class="col-md-3 col-sm-6"><select class="form-select" name="status"><option value="">Semua Status</option><option value="Menunggu" <?php echo $filter_status=='Menunggu'?'selected':''; ?>>Menunggu</option><option value="Diproses" <?php echo $filter_status=='Diproses'?'selected':''; ?>>Diproses</option><option value="Selesai" <?php echo $filter_status=='Selesai'?'selected':''; ?>>Selesai</option><option value="Ditolak" <?php echo $filter_status=='Ditolak'?'selected':''; ?>>Ditolak</option></select></div>
                <div class="col-md-3 col-sm-6"><select class="form-select" name="kategori"><option value="">Semua Kategori</option><?php foreach ($kategori_list as $kat): ?><option value="<?php echo htmlspecialchars($kat); ?>" <?php echo $filter_kategori==$kat?'selected':''; ?>><?php echo htmlspecialchars($kat); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 col-sm-6"><select class="form-select" name="prioritas"><option value="">Semua Prioritas</option><option value="Rendah" <?php echo $filter_prioritas=='Rendah'?'selected':''; ?>>Rendah</option><option value="Sedang" <?php echo $filter_prioritas=='Sedang'?'selected':''; ?>>Sedang</option><option value="Tinggi" <?php echo $filter_prioritas=='Tinggi'?'selected':''; ?>>Tinggi</option></select></div>
                <div class="col-md-3 col-sm-6"><div class="input-group"><input type="text" class="form-control" name="search" placeholder="Cari..." value="<?php echo htmlspecialchars($search); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button></div></div>
                <div class="col-12"><div class="d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Filter</button><a href="pengaduan.php<?php echo $filter_user_id?'?user_id='.$filter_user_id:''; ?>" class="btn btn-outline-secondary">Reset</a></div></div>
            </form>
        </div>

        <div class="table-container">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h5 class="mb-0">Daftar Pengaduan</h5><span class="badge bg-light text-dark">Total: <?php echo count($pengaduan_data); ?></span></div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pelapor</th>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pengaduan_data)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data pengaduan</td></tr>
                            <?php else: ?>
                                <?php foreach ($pengaduan_data as $index => $p): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo $p['nama_user']; ?></strong><br>
                                            <small class="text-muted"><?php echo $p['email_user']; ?></small>
                                            <?php if (!empty($p['foto_admin'])): ?>
                                                <span class="admin-foto-badge" title="Ada foto dari admin" onclick="showAdminFoto('<?php echo $p['foto_admin']; ?>')">
                                                    <i class="fas fa-camera"></i> Foto
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($p['tanggal_kejadian'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['judul']); ?></strong><br>
                                            <small class="text-muted"><?php echo substr($p['deskripsi'], 0, 50); ?>...</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?php echo $p['kategori']; ?></span></td>
                                        <td><span class="badge bg-<?php echo getPriorityColor($p['prioritas']); ?>"><?php echo $p['prioritas']; ?></span></td>
                                        <td><span class="badge-status badge-<?php echo strtolower($p['status']); ?>"><?php echo $p['status']; ?></span></td>
                                        <td><a href="detail_pengaduan.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Detail</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview Foto Admin -->
    <div class="modal fade" id="adminFotoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">
                    <h5 class="modal-title">Foto dari Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="adminFotoPreview" src="" alt="Foto Admin" class="img-fluid rounded shadow-sm" style="max-height: 500px;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAdminFoto(filename) {
            document.getElementById('adminFotoPreview').src = '../assets/uploads/admin_foto/' + filename;
            new bootstrap.Modal(document.getElementById('adminFotoModal')).show();
        }
    </script>
</body>
</html>