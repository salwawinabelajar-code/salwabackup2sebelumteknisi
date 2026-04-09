<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil daftar kategori dari database
$kategori_result = $db->query("SELECT nama FROM kategori ORDER BY nama");
$kategori_list = [];
$valid_kategori = [];
if ($kategori_result) {
    while ($row = $kategori_result->fetch_assoc()) {
        $kat_trim = trim($row['nama']);
        $kategori_list[] = $kat_trim;
        $valid_kategori[] = $kat_trim;
    }
}

// Handle pengaduan baru
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_pengaduan'])) {
        $tanggal_kejadian = $db->escape_string($_POST['tanggal_kejadian']);
        $judul = $db->escape_string($_POST['judul']);
        $kategori = trim($db->escape_string($_POST['kategori']));
        $prioritas = $db->escape_string($_POST['prioritas']);
        $deskripsi = $db->escape_string($_POST['deskripsi']);

        // Validasi tanggal kejadian (maksimal 7 hari ke belakang)
        $tanggal_kejadian_time = strtotime($tanggal_kejadian);
        $seven_days_ago = strtotime('-7 days');
        $today = strtotime(date('Y-m-d'));

        if ($tanggal_kejadian_time < $seven_days_ago) {
            $error = "Tanggal kejadian tidak boleh lebih dari 7 hari ke belakang!";
        } elseif ($tanggal_kejadian_time > $today) {
            $error = "Tanggal kejadian tidak boleh lebih dari hari ini!";
        } else {
            // Validasi kategori
            if (!in_array($kategori, $valid_kategori)) {
                $error = "Kategori yang dipilih tidak valid!";
            } else {
                // Handle file upload
                $lampiran = null;
                
                // Validasi lampiran wajib
                if (!isset($_FILES['lampiran']) || $_FILES['lampiran']['error'] != 0) {
                    $error = "Lampiran foto wajib diisi!";
                } else {
                    $upload_dir = '../assets/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = time() . '_' . basename($_FILES['lampiran']['name']);
                    $target_file = $upload_dir . $filename;
                    
                    // Validasi file (maks 2MB, hanya gambar)
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['lampiran']['type'];
                    
                    if (in_array($file_type, $allowed_types) && $_FILES['lampiran']['size'] <= 2097152) {
                        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
                            $lampiran = $filename;
                        } else {
                            $error = "Gagal mengupload gambar";
                        }
                    } else {
                        $error = "File harus gambar (JPG/PNG/GIF) dan maksimal 2MB";
                    }
                }
                
                if (!$error) {
                    $query = "INSERT INTO pengaduan (user_id, tanggal_kejadian, judul, kategori, prioritas, lampiran, deskripsi) 
                              VALUES ('$user_id', '$tanggal_kejadian', '$judul', '$kategori', '$prioritas', '$lampiran', '$deskripsi')";
                    
                    if ($db->conn->query($query)) {
                        $_SESSION['success'] = "Pengaduan berhasil dibuat!";
                        redirect('index.php');
                    } else {
                        $error = "Gagal membuat pengaduan: " . $db->conn->error;
                    }
                }
            }
        }
    }
    
    // Handle edit pengaduan
    if (isset($_POST['edit_pengaduan'])) {
        $id = $db->escape_string($_POST['edit_id']);
        $tanggal_kejadian = $db->escape_string($_POST['edit_tanggal_kejadian']);

        // ========== VALIDASI TANGGAL MAX 7 HARI ==========
        $tanggal_kejadian_time = strtotime($tanggal_kejadian);
        $seven_days_ago = strtotime('-7 days');
        $today = strtotime(date('Y-m-d'));
        
        if ($tanggal_kejadian_time < $seven_days_ago) {
            $error = "Tanggal kejadian tidak boleh lebih dari 7 hari ke belakang!";
        } elseif ($tanggal_kejadian_time > $today) {
            $error = "Tanggal kejadian tidak boleh lebih dari hari ini!";
        } else {
            $judul = $db->escape_string($_POST['edit_judul']);
            $kategori = trim($db->escape_string($_POST['edit_kategori']));
            $prioritas = $db->escape_string($_POST['edit_prioritas']);
            $deskripsi = $db->escape_string($_POST['edit_deskripsi']);
            
            // Validasi kategori
            if (!in_array($kategori, $valid_kategori)) {
                $error = "Kategori tidak valid!";
            } else {
                // Cek apakah pengaduan masih menunggu
                $check = $db->query("SELECT * FROM pengaduan WHERE id='$id' AND user_id='$user_id'");
                if ($check->num_rows > 0) {
                    $pengaduan = $check->fetch_assoc();
                    if ($pengaduan['status'] == 'Menunggu') {
                        
                        // Handle file upload untuk edit (opsional)
                        $lampiran = $pengaduan['lampiran'];
                        if (isset($_FILES['edit_lampiran']) && $_FILES['edit_lampiran']['error'] == 0) {
                            $upload_dir = '../assets/uploads/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            
                            // Hapus file lama jika ada
                            if ($lampiran && file_exists($upload_dir . $lampiran)) {
                                unlink($upload_dir . $lampiran);
                            }
                            
                            $filename = time() . '_' . basename($_FILES['edit_lampiran']['name']);
                            $target_file = $upload_dir . $filename;
                            
                            // Validasi file (maks 2MB, hanya gambar)
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                            $file_type = $_FILES['edit_lampiran']['type'];
                            
                            if (in_array($file_type, $allowed_types) && $_FILES['edit_lampiran']['size'] <= 2097152) {
                                if (move_uploaded_file($_FILES['edit_lampiran']['tmp_name'], $target_file)) {
                                    $lampiran = $filename;
                                } else {
                                    $error = "Gagal mengupload gambar";
                                }
                            } else {
                                $error = "File harus gambar (JPG/PNG/GIF) dan maksimal 2MB";
                            }
                        }
                        
                        if (!$error) {
                            $query = "UPDATE pengaduan SET 
                                      tanggal_kejadian='$tanggal_kejadian',
                                      judul='$judul',
                                      kategori='$kategori',
                                      prioritas='$prioritas',
                                      lampiran='$lampiran',
                                      deskripsi='$deskripsi'
                                      WHERE id='$id' AND user_id='$user_id'";
                            
                            if ($db->conn->query($query)) {
                                $_SESSION['success'] = "Pengaduan berhasil diupdate!";
                                redirect('index.php');
                            } else {
                                $error = "Gagal mengupdate pengaduan: " . $db->conn->error;
                            }
                        }
                    } else {
                        $error = "Pengaduan tidak bisa diedit karena status sudah " . $pengaduan['status'];
                    }
                }
            }
        }
    }
}

// Handle hapus pengaduan via GET
if (isset($_GET['delete_id'])) {
    $id = $db->escape_string($_GET['delete_id']);
    
    // Cek apakah pengaduan masih menunggu
    $check = $db->query("SELECT status, lampiran FROM pengaduan WHERE id='$id' AND user_id='$user_id'");
    if ($check->num_rows > 0) {
        $pengaduan = $check->fetch_assoc();
        if ($pengaduan['status'] == 'Menunggu') {
            // Hapus file lampiran jika ada
            if ($pengaduan['lampiran'] && file_exists('../assets/uploads/' . $pengaduan['lampiran'])) {
                unlink('../assets/uploads/' . $pengaduan['lampiran']);
            }
            
            $query = "DELETE FROM pengaduan WHERE id='$id' AND user_id='$user_id'";
            if ($db->conn->query($query)) {
                $_SESSION['success'] = "Pengaduan berhasil dihapus!";
                redirect('index.php');
            } else {
                $error = "Gagal menghapus pengaduan: " . $db->conn->error;
            }
        } else {
            $error = "Pengaduan tidak bisa dihapus karena status sudah " . $pengaduan['status'];
        }
    }
}

// Get statistics
$stats = [];
$result = $db->query("SELECT 
    COUNT(*) as total,
    SUM(status = 'Menunggu') as menunggu,
    SUM(status = 'Diproses') as diproses,
    SUM(status = 'Selesai') as selesai,
    SUM(status = 'Ditolak') as ditolak
    FROM pengaduan WHERE user_id = '$user_id'");
$stats = $result->fetch_assoc();

// Get recent pengaduan (maksimal 5)
$result = $db->query("SELECT * FROM pengaduan WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5");
$recent_pengaduan = $result->fetch_all(MYSQLI_ASSOC);

// Get all pengaduan for modal
$all_pengaduan = $db->query("SELECT * FROM pengaduan WHERE user_id = '$user_id' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AssetCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #09637E;
            --secondary: #088395;
            --accent: #7AB2B2;
            --light: #EBF4F6;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
            box-shadow: 0 4px 20px rgba(9, 99, 126, 0.15);
            padding: 15px 0;
        }
        
        .navbar-custom .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .navbar-custom .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 15px !important;
            border-radius: 8px;
            transition: all 0.3s;
            margin: 0 5px;
        }
        
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .hero-section {
            background: linear-gradient(rgba(9, 99, 126, 0.85), rgba(8, 131, 149, 0.85)),
                        url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            border-radius: 20px;
            padding: 60px 40px;
            margin: 20px 0 40px;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .hero-section h1 {
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        
        .card-stat:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(9, 99, 126, 0.3);
            color: white;
        }
        
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: white;
        }
        
        .table-responsive-custom table {
            min-width: 800px;
            margin-bottom: 0;
            width: 100%;
        }
        
        .table-responsive-custom th {
            background: var(--light);
            border-bottom: 2px solid var(--accent);
            font-weight: 700;
            color: var(--primary);
            padding: 16px 12px;
            white-space: nowrap;
        }
        
        .table-responsive-custom td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .judul-deskripsi {
            max-width: 300px;
        }
        
        .judul-deskripsi h6 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.3;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .judul-deskripsi small {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        
        .priority-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        
        .action-buttons .btn {
            padding: 6px 12px;
            border-radius: 8px;
            margin: 2px;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .action-buttons .btn:hover {
            transform: scale(1.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(122, 178, 178, 0.25);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .aksi-cepat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .aksi-cepat-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent);
            box-shadow: 0 15px 30px rgba(9, 99, 126, 0.1);
        }
        
        .aksi-cepat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.8rem;
        }
        
        .aksi-cepat-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .aksi-cepat-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .bg-menunggu { background-color: #ffc107 !important; color: #212529 !important; }
        .bg-diproses { background-color: #0dcaf0 !important; color: white !important; }
        .bg-selesai { background-color: #198754 !important; color: white !important; }
        .bg-ditolak { background-color: #dc3545 !important; color: white !important; }
        
        .bg-rendah { background-color: #198754 !important; color: white !important; }
        .bg-sedang { background-color: #ffc107 !important; color: #212529 !important; }
        .bg-tinggi { background-color: #dc3545 !important; color: white !important; }
        
        .file-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #eee;
        }
        
        .card-view {
            display: none;
        }
        
        .pengaduan-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid var(--primary);
        }
        
        .pengaduan-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .pengaduan-card-body {
            margin-bottom: 15px;
        }
        
        .pengaduan-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .badge-catatan {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 5px;
            cursor: help;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 20px;
                text-align: center;
                margin: 10px 0 25px;
            }
            
            .hero-section h1 {
                font-size: 1.6rem;
            }
            
            .hero-section .lead {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .navbar-custom .nav-link {
                margin: 5px 0;
                text-align: center;
                padding: 10px 15px !important;
            }
            
            .table-view {
                display: none;
            }
            
            .card-view {
                display: block;
            }
            
            .aksi-cepat-card {
                margin-bottom: 20px;
                padding: 20px;
            }
            
            .aksi-cepat-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .btn-primary-custom {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .judul-deskripsi h6 {
                font-size: 0.9rem;
            }
            
            .judul-deskripsi small {
                font-size: 0.8rem;
            }
        }
        
        @media (min-width: 769px) {
            .table-responsive-custom {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Konten utama -->
<div class="container mt-4">
    
    <!-- Notifikasi -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="hero-section animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5">Selamat Datang, <?php echo $_SESSION['nama']; ?>!</h1>
                <p class="lead mb-4">Kelola pengaduan sarana dan prasarana kantor dengan mudah dan efisien. Buat pengaduan baru atau pantau status pengaduan Anda.</p>
                <button class="btn btn-primary-custom btn-lg" data-bs-toggle="modal" data-bs-target="#buatPengaduanModal">
                    <i class="fas fa-plus me-2"></i>Buat Pengaduan Baru
                </button>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <i class="fas fa-building fa-10x opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="row mb-5">
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h2 class="stat-number text-primary"><?php echo $stats['total'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Total Pengaduan</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="stat-number text-warning"><?php echo $stats['menunggu'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Menunggu</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon text-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h2 class="stat-number text-info"><?php echo $stats['diproses'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Diproses</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="stat-number text-success"><?php echo $stats['selesai'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Selesai</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="stat-number text-danger"><?php echo $stats['ditolak'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Ditolak</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-2 mb-4">
            <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
                <div class="card-body text-center py-4">
                    <div class="stat-icon" style="color: var(--accent);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2 class="stat-number" style="color: var(--accent);">
                        <?php echo $stats['total'] > 0 ? round(($stats['selesai'] / $stats['total']) * 100) : 0; ?>%
                    </h2>
                    <p class="text-muted mb-0">Terselesaikan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Aksi Cepat -->
    <div class="row mb-5">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="aksi-cepat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="aksi-cepat-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h4 class="aksi-cepat-title">Buat Pengaduan</h4>
                <p class="aksi-cepat-desc">Laporkan kerusakan atau masalah yang terjadi</p>
                <button class="btn btn-primary-custom w-100" data-bs-toggle="modal" data-bs-target="#buatPengaduanModal">
                    Buat Baru
                </button>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="aksi-cepat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="aksi-cepat-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h4 class="aksi-cepat-title">Riwayat</h4>
                <p class="aksi-cepat-desc">Lihat semua pengaduan yang telah dibuat</p>
                <a href="riwayat.php" class="btn btn-outline-primary w-100">
                    Lihat Riwayat
                </a>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="aksi-cepat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="aksi-cepat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h4 class="aksi-cepat-title">Profil</h4>
                <p class="aksi-cepat-desc">Kelola data profil dan pengaturan akun</p>
                <a href="profil.php" class="btn btn-outline-primary w-100">
                    Lihat Profil
                </a>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="aksi-cepat-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                <div class="aksi-cepat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h4 class="aksi-cepat-title">Bantuan</h4>
                <p class="aksi-cepat-desc">Panduan penggunaan sistem AssetCare</p>
                <a href="bantuan.php" class="btn btn-outline-primary w-100">
                    Dapatkan Bantuan
                </a>
            </div>
        </div>
    </div>

    <!-- Pengaduan Terbaru -->
    <div class="card border-0 shadow-lg mb-5 animate__animated animate__fadeIn">
        <div class="card-header bg-transparent border-0 pt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0" style="color: var(--primary);">
                    <i class="fas fa-history me-2"></i>Pengaduan Terbaru
                    <?php if ($stats['total'] > 5): ?>
                        <span class="badge bg-primary ms-2">5 Terbaru dari <?php echo $stats['total']; ?> Total</span>
                    <?php endif; ?>
                </h3>
                <a href="riwayat.php" class="btn btn-primary-custom">
                    <i class="fas fa-list me-2"></i>Lihat Semua
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            
            <!-- Desktop View -->
            <div class="table-view p-0">
                <div class="table-responsive-custom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_pengaduan)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="empty-state p-4">
                                            <i class="fas fa-inbox"></i>
                                            <h5 class="text-muted mt-3">Belum ada pengaduan</h5>
                                            <p class="mb-0">Mulai dengan membuat pengaduan baru</p>
                                            <button class="btn btn-primary-custom mt-3" data-bs-toggle="modal" data-bs-target="#buatPengaduanModal">
                                                <i class="fas fa-plus me-2"></i>Buat Pengaduan
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_pengaduan as $index => $p): 
                                    $kategori_tampil = trim($p['kategori'] ?? '');
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $index + 1; ?></td>
                                        <td class="fw-semibold"><?php echo date('d/m/Y', strtotime($p['tanggal_kejadian'])); ?></td>
                                        <td>
                                            <div class="judul-deskripsi">
                                                <h6 title="<?php echo htmlspecialchars($p['judul']); ?>"><?php echo htmlspecialchars($p['judul']); ?></h6>
                                                <small title="<?php echo htmlspecialchars($p['deskripsi']); ?>"><?php echo htmlspecialchars(substr($p['deskripsi'], 0, 80)); ?>...</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php if ($kategori_tampil !== ''): ?>
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($kategori_tampil); ?>
                                                <?php else: ?>
                                                    <em class="text-muted">-</em>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="priority-badge bg-<?php echo strtolower($p['prioritas']); ?>">
                                                <i class="fas fa-exclamation-circle me-1"></i><?php echo $p['prioritas']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?php echo strtolower($p['status']); ?>">
                                                <i class="fas fa-circle me-1" style="font-size: 0.7rem;"></i><?php echo $p['status']; ?>
                                            </span>
                                            <?php if (!empty($p['catatan_admin'])): ?>
                                                <span class="badge-catatan" title="Ada catatan dari admin">
                                                    <i class="fas fa-comment"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center action-buttons">
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $p['id']; ?>" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($p['status'] == 'Menunggu'): ?>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $p['id']; ?>)" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning" disabled title="Tidak dapat diedit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" disabled title="Tidak dapat dihapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Mobile Card View -->
            <div class="card-view">
                <div class="p-3">
                    <?php if (empty($recent_pengaduan)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5 class="text-muted">Belum ada pengaduan</h5>
                            <p>Mulai dengan membuat pengaduan baru</p>
                            <button class="btn btn-primary-custom mt-2" data-bs-toggle="modal" data-bs-target="#buatPengaduanModal">
                                <i class="fas fa-plus me-2"></i>Buat Pengaduan
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_pengaduan as $index => $p): 
                            $kategori_tampil = trim($p['kategori'] ?? '');
                        ?>
                            <div class="pengaduan-card">
                                <div class="pengaduan-card-header">
                                    <div>
                                        <span class="fw-bold text-primary">#<?php echo $index + 1; ?></span>
                                        <span class="ms-2 fw-semibold"><?php echo date('d/m/Y', strtotime($p['tanggal_kejadian'])); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <?php if (!empty($p['catatan_admin'])): ?>
                                            <span class="badge-catatan" title="Ada catatan dari admin">
                                                <i class="fas fa-comment"></i>
                                            </span>
                                        <?php endif; ?>
                                        <span class="status-badge bg-<?php echo strtolower($p['status']); ?>">
                                            <?php echo $p['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="pengaduan-card-body">
                                    <h6 class="mb-2" title="<?php echo htmlspecialchars($p['judul']); ?>"><?php echo htmlspecialchars($p['judul']); ?></h6>
                                    <p class="text-muted small mb-3" title="<?php echo htmlspecialchars($p['deskripsi']); ?>"><?php echo htmlspecialchars(substr($p['deskripsi'], 0, 100)); ?>...</p>
                                    
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Kategori</small>
                                            <span class="badge bg-light text-dark border">
                                                <?php if ($kategori_tampil !== ''): ?>
                                                    <?php echo htmlspecialchars($kategori_tampil); ?>
                                                <?php else: ?>
                                                    <em class="text-muted">-</em>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Prioritas</small>
                                            <span class="priority-badge bg-<?php echo strtolower($p['prioritas']); ?>">
                                                <?php echo $p['prioritas']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pengaduan-card-footer">
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $p['id']; ?>" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($p['status'] == 'Menunggu'): ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $p['id']; ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning" disabled title="Tidak dapat diedit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" disabled title="Tidak dapat dihapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($stats['total'] > 5): ?>
                        <div class="text-center mt-4">
                            <a href="riwayat.php" class="btn btn-primary-custom">
                                <i class="fas fa-list me-2"></i>Lihat <?php echo $stats['total'] - 5; ?> Pengaduan Lainnya
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> <!-- Penutup container utama -->

<!-- Modal Buat Pengaduan -->
<div class="modal fade" id="buatPengaduanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Buat Pengaduan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="formBuatPengaduan">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" name="tanggal_kejadian" required 
                                       min="<?php echo date('Y-m-d', strtotime('-7 days')); ?>"
                                       max="<?php echo date('Y-m-d'); ?>" 
                                       id="tanggalKejadian">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                <select class="form-select" name="kategori" required id="kategori">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Judul Pengaduan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" class="form-control" name="judul" placeholder="Contoh: Komputer tidak bisa menyala" required id="judul">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioritas <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="prioritas" id="rendah" value="Rendah" autocomplete="off">
                                <label class="btn btn-outline-success" for="rendah">
                                    <i class="fas fa-arrow-down me-1"></i>Rendah
                                </label>
                                
                                <input type="radio" class="btn-check" name="prioritas" id="sedang" value="Sedang" autocomplete="off" checked>
                                <label class="btn btn-outline-warning" for="sedang">
                                    <i class="fas fa-minus me-1"></i>Sedang
                                </label>
                                
                                <input type="radio" class="btn-check" name="prioritas" id="tinggi" value="Tinggi" autocomplete="off">
                                <label class="btn btn-outline-danger" for="tinggi">
                                    <i class="fas fa-arrow-up me-1"></i>Tinggi
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lampiran Foto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="file" class="form-control" name="lampiran" accept="image/*" id="lampiranInput" onchange="previewImage(this, 'previewBuat')" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('lampiranInput').click()">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                            <small class="text-muted">Wajib diisi. Maksimal 2MB (JPG, PNG, GIF)</small>
                            <div id="previewBuat" class="mt-2 text-center"></div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" name="deskripsi" rows="5" placeholder="Jelaskan kerusakan secara detail..." required id="deskripsi"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" name="create_pengaduan" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Simpan Pengaduan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail Modals & Edit Modals -->
<?php foreach ($all_pengaduan as $p): 
    $kategori_tampil = trim($p['kategori'] ?? '');
?>
    <!-- Modal Detail -->
    <!-- Modal Detail -->
<div class="modal fade" id="detailModal<?php echo $p['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengaduan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <h6 class="text-primary mb-2">Informasi Umum</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td width="40%"><strong>Judul</strong></td><td>: <?php echo htmlspecialchars($p['judul']); ?></td></tr>
                            <tr><td><strong>Tanggal Kejadian</strong></td><td>: <?php echo date('d F Y', strtotime($p['tanggal_kejadian'])); ?></td></tr>
                            <tr><td><strong>Kategori</strong></td><td>: <?php echo ($kategori_tampil !== '') ? htmlspecialchars($kategori_tampil) : '<em class="text-muted">-</em>'; ?></td></tr>
                            <tr><td><strong>Prioritas</strong></td><td>: <span class="badge bg-<?php echo strtolower($p['prioritas']); ?>"><?php echo $p['prioritas']; ?></span></td></tr>
                            <tr><td><strong>Status</strong></td><td>: <span class="badge bg-<?php echo strtolower($p['status']); ?>"><?php echo $p['status']; ?></span></td></tr>
                            <tr><td><strong>Dibuat</strong></td><td>: <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></td></tr>
                        </table>
                    </div>
                    
                    <!-- ===== FOTO LAMPIRAN DARI USER ===== -->
                    <?php if ($p['lampiran']): ?>
                    <div class="col-12 mb-3">
                        <h6 class="text-primary mb-2">Lampiran Foto (Dari User)</h6>
                        <div class="text-center">
                            <img src="../assets/uploads/<?php echo $p['lampiran']; ?>" alt="Lampiran" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== FOTO DARI ADMIN (LETAKKAN DISINI) ===== -->
                    <?php if ($p['foto_admin']): ?>
                    <div class="col-12 mb-3">
                        <h6 class="text-primary mb-2">Foto dari Admin</h6>
                        <div class="text-center">
                            <img src="../assets/uploads/admin_foto/<?php echo $p['foto_admin']; ?>" alt="Foto Admin" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== DESKRIPSI ===== -->
                    <div class="col-12 mb-3">
                        <h6 class="text-primary mb-2">Deskripsi Kerusakan</h6>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($p['deskripsi'])); ?>
                        </div>
                    </div>
                    
                    <!-- ===== CATATAN ADMIN ===== -->
                    <?php if ($p['catatan_admin']): ?>
                    <div class="col-12">
                        <h6 class="text-primary mb-2">Catatan Admin</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo nl2br(htmlspecialchars($p['catatan_admin'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Edit (hanya untuk status Menunggu) -->
    <?php if ($p['status'] == 'Menunggu'): ?>
    <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Pengaduan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="formEditPengaduan">
                    <input type="hidden" name="edit_id" value="<?php echo $p['id']; ?>">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal Kejadian</label>
                                <input type="date" class="form-control" name="edit_tanggal_kejadian" 
                                       value="<?php echo $p['tanggal_kejadian']; ?>" required
                                       min="<?php echo date('Y-m-d', strtotime('-7 days')); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kategori</label>
                                <select class="form-select" name="edit_kategori" required>
                                    <?php 
                                    $kategori_saat_ini = trim($p['kategori'] ?? '');
                                    foreach ($kategori_list as $kat): 
                                        $selected = ($kategori_saat_ini == $kat) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($kat); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($kat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Judul Pengaduan</label>
                                <input type="text" class="form-control" name="edit_judul" 
                                       value="<?php echo htmlspecialchars($p['judul']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prioritas</label>
                                <select class="form-select" name="edit_prioritas" required>
                                    <option value="Rendah" <?php echo $p['prioritas'] == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                                    <option value="Sedang" <?php echo $p['prioritas'] == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                    <option value="Tinggi" <?php echo $p['prioritas'] == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <input type="text" class="form-control" value="<?php echo $p['status']; ?>" disabled>
                                <small class="text-muted">Status hanya bisa diubah oleh admin</small>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Lampiran Foto Saat Ini</label>
                                <?php if ($p['lampiran']): ?>
                                    <div class="mb-3">
                                        <img src="../assets/uploads/<?php echo $p['lampiran']; ?>" alt="Lampiran Saat Ini" class="file-preview">
                                        <p class="text-muted small mt-1">Foto akan diganti jika memilih file baru</p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada lampiran</p>
                                <?php endif; ?>
                                
                                <label class="form-label fw-bold">Ganti Foto (Opsional)</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="edit_lampiran" accept="image/*" id="editLampiran<?php echo $p['id']; ?>" onchange="previewImage(this, 'previewEdit<?php echo $p['id']; ?>')">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('editLampiran<?php echo $p['id']; ?>').click()">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Maksimal 2MB (JPG, PNG, GIF)</small>
                                <div id="previewEdit<?php echo $p['id']; ?>" class="mt-2 text-center"></div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Deskripsi Kerusakan</label>
                                <textarea class="form-control" name="edit_deskripsi" rows="5" required><?php echo htmlspecialchars($p['deskripsi']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                        <button type="submit" name="edit_pengaduan" class="btn btn-primary-custom">
                            <i class="fas fa-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Pengaduan akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'animate__animated animate__fadeIn'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?delete_id=' + id;
            }
        });
    }
    
    // Set tanggal hari ini sebagai default untuk form buat
    if (document.querySelector('#tanggalKejadian')) {
        document.querySelector('#tanggalKejadian').value = '<?php echo date("Y-m-d"); ?>';
    }
    
    // Animasi saat modal dibuka
    document.addEventListener('DOMContentLoaded', function() {
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            modal.addEventListener('show.bs.modal', function() {
                this.classList.add('animate__animated', 'animate__fadeIn');
            });
        });
    });
    
    // Preview gambar sebelum upload
    function previewImage(input, previewId) {
        var preview = document.getElementById(previewId);
        if (preview) preview.innerHTML = '';
        
        if (input.files && input.files[0]) {
            var file = input.files[0];
            
            if (file.size > 2097152) {
                Swal.fire({ title: 'File terlalu besar!', text: 'Ukuran file maksimal 2MB', icon: 'error' });
                input.value = '';
                return;
            }
            
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({ title: 'Format file tidak didukung!', text: 'Hanya file JPG, PNG, dan GIF yang diizinkan', icon: 'error' });
                input.value = '';
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                if (preview) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'file-preview';
                    img.style.maxHeight = '150px';
                    preview.appendChild(img);
                }
            }
            reader.readAsDataURL(file);
        }
    }
    
    // ========== VALIDASI TANGGAL UNTUK FORM BUAT ==========
    function validateTanggalKejadian() {
        const tanggalInput = document.getElementById('tanggalKejadian');
        if (!tanggalInput) return true;
        
        const selectedDate = new Date(tanggalInput.value);
        const today = new Date();
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(today.getDate() - 7);
        
        selectedDate.setHours(0, 0, 0, 0);
        sevenDaysAgo.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < sevenDaysAgo) {
            Swal.fire({
                title: 'Tanggal Tidak Valid!',
                text: 'Tanggal kejadian maksimal 7 hari ke belakang dari hari ini',
                icon: 'error'
            });
            tanggalInput.value = '';
            return false;
        }
        
        if (selectedDate > today) {
            Swal.fire({
                title: 'Tanggal Tidak Valid!',
                text: 'Tanggal kejadian tidak boleh lebih dari hari ini',
                icon: 'error'
            });
            tanggalInput.value = '';
            return false;
        }
        
        return true;
    }
    
    // Validasi form buat pengaduan
    const formBuat = document.getElementById('formBuatPengaduan');
    if (formBuat) {
        formBuat.addEventListener('submit', function(e) {
            if (!validateTanggalKejadian()) {
                e.preventDefault();
                return false;
            }
            
            var judul = document.getElementById('judul').value;
            var kategori = document.getElementById('kategori').value;
            var deskripsi = document.getElementById('deskripsi').value;
            var lampiran = document.getElementById('lampiranInput').value;
            
            if (!judul || !kategori || !deskripsi || !lampiran) {
                e.preventDefault();
                Swal.fire({
                    title: 'Form tidak lengkap!',
                    text: 'Harap isi semua field yang wajib diisi termasuk lampiran foto',
                    icon: 'warning'
                });
            }
        });
        
        document.getElementById('tanggalKejadian').addEventListener('change', function() {
            validateTanggalKejadian();
        });
    }
    
    // ========== VALIDASI EDIT TANGGAL MAX 7 HARI ==========
    function validateEditTanggal(modalId) {
        const tanggalInput = document.querySelector('#editModal' + modalId + ' input[name="edit_tanggal_kejadian"]');
        if (!tanggalInput) return true;
        
        const selectedDate = new Date(tanggalInput.value);
        const today = new Date();
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(today.getDate() - 7);
        
        selectedDate.setHours(0, 0, 0, 0);
        sevenDaysAgo.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < sevenDaysAgo) {
            Swal.fire({
                title: 'Tanggal Tidak Valid!',
                text: 'Tanggal kejadian maksimal 7 hari ke belakang dari hari ini',
                icon: 'error'
            });
            tanggalInput.value = '';
            return false;
        }
        
        if (selectedDate > today) {
            Swal.fire({
                title: 'Tanggal Tidak Valid!',
                text: 'Tanggal kejadian tidak boleh lebih dari hari ini',
                icon: 'error'
            });
            tanggalInput.value = '';
            return false;
        }
        
        return true;
    }
    
    // Event listener untuk semua form edit
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.querySelector('input[name="edit_id"]')) {
            const editId = e.target.querySelector('input[name="edit_id"]').value;
            if (!validateEditTanggal(editId)) {
                e.preventDefault();
            }
        }
    });
    
    // Validasi real-time saat user mengubah tanggal edit
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'edit_tanggal_kejadian') {
            const modal = e.target.closest('.modal');
            if (modal && modal.id) {
                const modalId = modal.id.replace('editModal', '');
                validateEditTanggal(modalId);
            }
        }
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>