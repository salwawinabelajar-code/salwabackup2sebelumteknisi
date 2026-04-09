<?php
require_once '../config/database.php';
$db = new Database();

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$error = '';
$success = '';

// ========== FUNGSI UPLOAD FOTO ==========
function uploadFoto($file, $prefix) {
    if ($file['error'] != 0) {
        return ['error' => 'File ' . $prefix . ' gagal diupload'];
    }
    
    $target_dir = "../assets/uploads/galeri/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowed)) {
        return ['error' => 'Format file harus JPG/PNG/GIF'];
    }
    
    if ($file['size'] > 5242880) {
        return ['error' => 'File maksimal 5MB'];
    }
    
    $filename = time() . '_' . $prefix . '_' . uniqid() . '.' . $extension;
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => $filename];
    } else {
        return ['error' => 'Gagal upload file'];
    }
}

// ========== TAMBAH MANUAL DOKUMENTASI ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_tambah'])) {
    $judul = $db->escape_string($_POST['judul']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);
    
    // Validasi file foto_before dan foto_after
    if (!isset($_FILES['foto_before']) || $_FILES['foto_before']['error'] != 0) {
        $error = "Foto sebelum perbaikan wajib diupload!";
    } elseif (!isset($_FILES['foto_after']) || $_FILES['foto_after']['error'] != 0) {
        $error = "Foto sesudah perbaikan wajib diupload!";
    } else {
        // Upload foto_before
        $uploadBefore = uploadFoto($_FILES['foto_before'], 'before');
        if (isset($uploadBefore['error'])) {
            $error = $uploadBefore['error'];
        } else {
            // Upload foto_after
            $uploadAfter = uploadFoto($_FILES['foto_after'], 'after');
            if (isset($uploadAfter['error'])) {
                $error = $uploadAfter['error'];
                // Hapus foto_before yang sudah terupload jika gagal
                if (file_exists("../assets/uploads/galeri/" . $uploadBefore['success'])) {
                    unlink("../assets/uploads/galeri/" . $uploadBefore['success']);
                }
            } else {
                $foto_before = $uploadBefore['success'];
                $foto_after = $uploadAfter['success'];
                $user_id = (int)$_SESSION['user_id'];
                
                $insert = "INSERT INTO galeri (user_id, judul, foto_before, foto_after, deskripsi, created_at) 
                           VALUES ($user_id, '$judul', '$foto_before', '$foto_after', '$deskripsi', NOW())";
                
                if ($db->conn->query($insert)) {
                    $_SESSION['success'] = "Dokumentasi perbaikan berhasil ditambahkan secara manual!";
                } else {
                    $error = "Gagal: " . $db->conn->error;
                    // Hapus file yang sudah terupload jika insert gagal
                    if (file_exists("../assets/uploads/galeri/$foto_before")) unlink("../assets/uploads/galeri/$foto_before");
                    if (file_exists("../assets/uploads/galeri/$foto_after")) unlink("../assets/uploads/galeri/$foto_after");
                }
            }
        }
    }
    
    if (!empty($error)) {
        $_SESSION['error'] = $error;
    }
    header('Location: galeri.php');
    exit;
}

// ========== TAMBAH DOKUMENTASI DARI PENGADUAN SELESAI ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_dari_pengaduan'])) {
    $pengaduan_id = (int)$_POST['pengaduan_id'];
    $judul = $db->escape_string($_POST['judul']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);
    
    // Ambil foto_before dari pengaduan (foto awal user)
    $query = "SELECT lampiran, user_id FROM pengaduan WHERE id = $pengaduan_id AND status = 'Selesai'";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $pengaduan = $result->fetch_assoc();
        
        // Validasi file foto_after
        if (!isset($_FILES['foto_after']) || $_FILES['foto_after']['error'] != 0) {
            $error = "Foto hasil perbaikan wajib diupload!";
        } else {
            // Upload foto_after (foto hasil perbaikan)
            $uploadAfter = uploadFoto($_FILES['foto_after'], 'after');
            if (isset($uploadAfter['error'])) {
                $error = $uploadAfter['error'];
            } else {
                $foto_before = $pengaduan['lampiran']; // Foto awal dari user
                $foto_after = $uploadAfter['success'];
                
                // Cek apakah sudah ada dokumentasi untuk pengaduan ini
                $check_existing = $db->query("SELECT id FROM galeri WHERE foto_before = '$foto_before'");
                if ($check_existing && $check_existing->num_rows > 0) {
                    $error = "Dokumentasi untuk pengaduan ini sudah ada!";
                } else {
                    $insert = "INSERT INTO galeri (user_id, judul, foto_before, foto_after, deskripsi, created_at) 
                               VALUES ({$pengaduan['user_id']}, '$judul', '$foto_before', '$foto_after', '$deskripsi', NOW())";
                    
                    if ($db->conn->query($insert)) {
                        // Update pengaduan bahwa sudah didokumentasikan
                        $db->conn->query("UPDATE pengaduan SET foto_admin = '$foto_after' WHERE id = $pengaduan_id");
                        $_SESSION['success'] = "Dokumentasi perbaikan berhasil ditambahkan!";
                    } else {
                        $error = "Gagal: " . $db->conn->error;
                    }
                }
            }
        }
    } else {
        $error = "Pengaduan tidak ditemukan atau belum selesai!";
    }
    header('Location: galeri.php');
    exit;
}

// ========== EDIT DOKUMENTASI ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $judul = $db->escape_string($_POST['judul']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);
    $foto_before_lama = $_POST['foto_before_lama'];
    $foto_after_lama = $_POST['foto_after_lama'];
    $foto_before_baru = $foto_before_lama;
    $foto_after_baru = $foto_after_lama;
    
    // Upload foto before baru jika ada
    if (isset($_FILES['foto_before']) && $_FILES['foto_before']['error'] == 0) {
        $uploadBefore = uploadFoto($_FILES['foto_before'], 'before');
        if (isset($uploadBefore['error'])) {
            $_SESSION['error'] = $uploadBefore['error'];
            header('Location: galeri.php');
            exit;
        }
        $foto_before_baru = $uploadBefore['success'];
        if (file_exists("../assets/uploads/galeri/$foto_before_lama") && $foto_before_lama != $foto_before_baru) {
            unlink("../assets/uploads/galeri/$foto_before_lama");
        }
    }
    
    // Upload foto after baru jika ada
    if (isset($_FILES['foto_after']) && $_FILES['foto_after']['error'] == 0) {
        $uploadAfter = uploadFoto($_FILES['foto_after'], 'after');
        if (isset($uploadAfter['error'])) {
            $_SESSION['error'] = $uploadAfter['error'];
            header('Location: galeri.php');
            exit;
        }
        $foto_after_baru = $uploadAfter['success'];
        if (file_exists("../assets/uploads/galeri/$foto_after_lama") && $foto_after_lama != $foto_after_baru) {
            unlink("../assets/uploads/galeri/$foto_after_lama");
        }
    }
    
    $query = "UPDATE galeri SET 
              judul = '$judul',
              foto_before = '$foto_before_baru',
              foto_after = '$foto_after_baru',
              deskripsi = '$deskripsi'
              WHERE id = $id";
    
    if ($db->conn->query($query)) {
        $_SESSION['success'] = "Dokumentasi berhasil diupdate!";
    } else {
        $_SESSION['error'] = "Gagal: " . $db->conn->error;
    }
    header('Location: galeri.php');
    exit;
}

// ========== HAPUS DOKUMENTASI ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['id'];
    
    $result = $db->query("SELECT foto_before, foto_after FROM galeri WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if (file_exists("../assets/uploads/galeri/" . $data['foto_before'])) {
            unlink("../assets/uploads/galeri/" . $data['foto_before']);
        }
        if (file_exists("../assets/uploads/galeri/" . $data['foto_after'])) {
            unlink("../assets/uploads/galeri/" . $data['foto_after']);
        }
    }
    
    $db->conn->query("DELETE FROM rating_galeri WHERE galeri_id = $id");
    $db->conn->query("DELETE FROM komentar_galeri WHERE galeri_id = $id");
    $db->conn->query("DELETE FROM galeri WHERE id = $id");
    
    $_SESSION['success'] = "Dokumentasi berhasil dihapus!";
    header('Location: galeri.php');
    exit;
}

// ========== HAPUS KOMENTAR ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_komentar'])) {
    $komentar_id = (int)$_POST['komentar_id'];
    
    if ($db->conn->query("DELETE FROM komentar_galeri WHERE id = $komentar_id")) {
        $_SESSION['success'] = "Komentar berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus komentar: " . $db->conn->error;
    }
    header('Location: galeri.php');
    exit;
}

// ========== AMBIL DATA GALERI ==========
$query = "SELECT g.*, u.nama as uploader_nama,
          COUNT(DISTINCT r.id) as total_rating,
          COUNT(DISTINCT k.id) as total_komentar,
          COALESCE(AVG(r.rating), 0) as avg_rating
          FROM galeri g
          LEFT JOIN users u ON g.user_id = u.id
          LEFT JOIN rating_galeri r ON g.id = r.galeri_id
          LEFT JOIN komentar_galeri k ON g.id = k.galeri_id
          GROUP BY g.id
          ORDER BY g.created_at DESC";
$result = $db->query($query);
$galeri = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ========== AMBIL PENGADUAN SELESAI UNTUK DITAMBAHKAN ==========
// Cari pengaduan yang sudah selesai, memiliki foto_admin, dan belum ada di galeri
$pengaduan_selesai = [];
$query_pending = "SELECT p.id, p.judul, p.deskripsi, p.lampiran, p.foto_admin, u.nama as user_nama
                  FROM pengaduan p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.status = 'Selesai' 
                  AND p.foto_admin IS NOT NULL 
                  AND p.foto_admin != ''
                  AND p.id NOT IN (
                      SELECT DISTINCT p2.id 
                      FROM pengaduan p2 
                      INNER JOIN galeri g ON g.foto_before = p2.lampiran
                  )
                  ORDER BY p.created_at DESC";

$result_pending = $db->query($query_pending);
if ($result_pending && $result_pending->num_rows > 0) {
    $pengaduan_selesai = $result_pending->fetch_all(MYSQLI_ASSOC);
}

// ========== AMBIL KOMENTAR PER GALERI ==========
$komentar_per_galeri = [];
$rating_per_galeri = [];

foreach ($galeri as $g) {
    $gid = $g['id'];
    
    $q = "SELECT k.*, u.nama, u.email, u.role 
          FROM komentar_galeri k 
          JOIN users u ON k.user_id = u.id 
          WHERE k.galeri_id = $gid 
          ORDER BY k.created_at DESC";
    $res = $db->query($q);
    $komentar_per_galeri[$gid] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    
    $qr = "SELECT r.*, u.nama, u.email 
           FROM rating_galeri r 
           JOIN users u ON r.user_id = u.id 
           WHERE r.galeri_id = $gid 
           ORDER BY r.created_at DESC";
    $resr = $db->query($qr);
    $rating_per_galeri[$gid] = $resr ? $resr->fetch_all(MYSQLI_ASSOC) : [];
}

// Ambil session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin - Laporan Riwayat Perbaikan | AssetCare</title>
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
        
        /* Main Content */
        .main-content { margin-left: 280px; padding: 20px 30px; transition: all 0.3s; }
        
        /* Top Navbar - Konsisten dengan halaman lain */
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(9,99,126,0.15); }
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .stat-icon i { font-size: 1.5rem; color: white; }
        .stat-info h3 { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin: 0; }
        .stat-info p { margin: 0; color: #6c757d; font-size: 0.85rem; }
        
        /* Action Bar */
        .action-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(9,99,126,0.3); color: white; }
        .btn-outline-primary-custom {
            border: 1px solid var(--primary);
            background: transparent;
            color: var(--primary);
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-outline-primary-custom:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: transparent;
            color: white;
        }
        
        /* Galeri Grid */
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }
        .galeri-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .galeri-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(9,99,126,0.15); }
        
        .foto-container {
            display: flex;
            height: 220px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .foto-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.6) 100%);
            z-index: 1;
            pointer-events: none;
        }
        .foto-container img { width: 50%; object-fit: cover; transition: transform 0.5s; }
        .foto-container:hover img { transform: scale(1.05); }
        .foto-label {
            position: absolute;
            bottom: 12px;
            background: rgba(0,0,0,0.75);
            color: white;
            padding: 5px 14px;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }
        .foto-label.before { left: 20%; transform: translateX(-50%); }
        .foto-label.after { left: 70%; transform: translateX(-50%); }
        
        .card-content { padding: 20px; }
        .card-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
            margin: 0 0 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rating-stars { color: #ffc107; font-size: 0.85rem; letter-spacing: 1px; }
        .card-meta {
            display: flex;
            gap: 15px;
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .stats-badge {
            background: var(--light);
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-around;
        }
        .stat-item { text-align: center; }
        .stat-value { font-weight: 800; font-size: 1.1rem; color: var(--primary); }
        .stat-label { font-size: 0.7rem; color: #6c757d; }
        
        .btn-group-aksi { display: flex; gap: 8px; margin-bottom: 10px; }
        .btn-aksi { font-size: 0.8rem; padding: 6px 0; border-radius: 10px; font-weight: 500; }
        
        /* Modal Styles - Konsisten */
        .modal-content { border-radius: 20px; overflow: hidden; }
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 20px 25px;
        }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-img-preview { display: flex; gap: 15px; margin-bottom: 20px; }
        .modal-img-preview img { width: 50%; border-radius: 12px; object-fit: cover; max-height: 200px; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: transparent;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .main-content { padding: 15px 20px; }
        }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-top: 56px; padding: 15px; }
            .top-navbar { flex-direction: column; text-align: center; }
            .page-title { text-align: center; }
            .galeri-grid { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .modal-img-preview { flex-direction: column; }
            .modal-img-preview img { width: 100%; }
            .action-bar { flex-direction: column; text-align: center; }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .galeri-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .btn-aksi { font-size: 0.7rem; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Navbar - Konsisten -->
        <div class="top-navbar">
            <div class="page-title">
                <h4><i class="fas fa-images me-2"></i>Laporan Riwayat Perbaikan</h4>
                <p>Kelola dokumentasi sebelum dan sesudah perbaikan sarana prasarana</p>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="profile-avatar me-2">
                        <?php echo isset($_SESSION['nama']) ? strtoupper(substr($_SESSION['nama'], 0, 1)) : 'A'; ?>
                    </div>
                    <div class="d-none d-md-block">
                        <span class="fw-bold"><?php echo $_SESSION['nama'] ?? 'Admin'; ?></span><br>
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
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <?php if (!empty($galeri)): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-images"></i></div>
                <div class="stat-info"><h3><?php echo count($galeri); ?></h3><p>Total Dokumentasi</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?php 
                        $total_rating = 0; $count_rating = 0;
                        foreach ($galeri as $g) {
                            if ($g['total_rating'] > 0) {
                                $total_rating += $g['avg_rating'];
                                $count_rating++;
                            }
                        }
                        echo $count_rating > 0 ? round($total_rating / $count_rating, 1) : '0';
                    ?></h3>
                    <p>Rata-rata Rating</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div class="stat-info">
                    <h3><?php 
                        $total_komentar = 0;
                        foreach ($komentar_per_galeri as $k) $total_komentar += count($k);
                        echo $total_komentar;
                    ?></h3>
                    <p>Total Umpan Balik</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="action-bar">
            <div>
                <h5 class="mb-0 fw-bold">Manajemen Laporan Riwayat Perbaikan</h5>
                <small class="text-muted">Dokumentasi hasil perbaikan dari pengaduan yang telah selesai atau tambah manual</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="../user/galeri.php" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-eye me-2"></i>Lihat User View
                </a>
                <button class="btn-outline-primary-custom" data-bs-toggle="modal" data-bs-target="#tambahManualModal">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Manual
                </button>
                <?php if (!empty($pengaduan_selesai)): ?>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#tambahDariPengaduanModal">
                    <i class="fas fa-plus me-2"></i>Tambah dari Pengaduan Selesai
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grid Galeri -->
        <?php if (empty($galeri)): ?>
            <div class="empty-state">
                <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                <h5 class="mb-2">Belum Ada Dokumentasi Riwayat Perbaikan</h5>
                <p class="text-muted mb-3">Dokumentasi akan muncul setelah pengaduan selesai dan admin mengunggah foto hasil perbaikan, atau tambah secara manual</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-outline-primary-custom" data-bs-toggle="modal" data-bs-target="#tambahManualModal">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Manual
                    </button>
                    <?php if (!empty($pengaduan_selesai)): ?>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#tambahDariPengaduanModal">
                        <i class="fas fa-plus me-2"></i>Tambah dari Pengaduan
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="galeri-grid">
                <?php foreach ($galeri as $g): ?>
                    <div class="galeri-card">
                        <div class="foto-container" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $g['id']; ?>">
                            <img src="../assets/uploads/galeri/<?php echo $g['foto_before']; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                            <img src="../assets/uploads/galeri/<?php echo $g['foto_after']; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                            <span class="foto-label before"><i class="fas fa-image me-1"></i>Sebelum</span>
                            <span class="foto-label after"><i class="fas fa-check-circle me-1"></i>Sesudah</span>
                        </div>
                        <div class="card-content">
                            <div class="card-title">
                                <span><?php echo htmlspecialchars(substr($g['judul'], 0, 35)) . (strlen($g['judul']) > 35 ? '...' : ''); ?></span>
                                <span class="rating-stars">
                                    <?php $avg = round($g['avg_rating'] ?? 0); ?>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= $avg ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <div class="card-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($g['uploader_nama'] ?? 'Admin'); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($g['created_at'])); ?></span>
                            </div>
                            <div class="stats-badge">
                                <div class="stat-item"><div class="stat-value"><?php echo $g['total_rating']; ?></div><div class="stat-label">Rating</div></div>
                                <div class="stat-item"><div class="stat-value"><?php echo $g['total_komentar']; ?></div><div class="stat-label">Komentar</div></div>
                            </div>
                            <div class="btn-group-aksi">
                                <button class="btn btn-outline-primary btn-aksi flex-fill" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $g['id']; ?>">
                                    <i class="fas fa-eye me-1"></i> Detail
                                </button>
                                <button class="btn btn-outline-warning btn-aksi flex-fill" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $g['id']; ?>">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-outline-danger btn-aksi flex-fill" data-bs-toggle="modal" data-bs-target="#hapusModal<?php echo $g['id']; ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Hapus
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-info btn-aksi flex-fill" data-bs-toggle="modal" data-bs-target="#ratingModal<?php echo $g['id']; ?>">
                                    <i class="fas fa-star me-1"></i> Rating (<?php echo $g['total_rating']; ?>)
                                </button>
                                <button class="btn btn-outline-secondary btn-aksi flex-fill" data-bs-toggle="modal" data-bs-target="#komentarModal<?php echo $g['id']; ?>">
                                    <i class="fas fa-comments me-1"></i> Komentar (<?php echo $g['total_komentar']; ?>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL DETAIL -->
                    <div class="modal fade" id="detailModal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Dokumentasi</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="modal-img-preview">
                                        <div>
                                            <img src="../assets/uploads/galeri/<?php echo $g['foto_before']; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                                            <p class="text-center mt-2 fw-bold text-muted small">Sebelum Perbaikan</p>
                                        </div>
                                        <div>
                                            <img src="../assets/uploads/galeri/<?php echo $g['foto_after']; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                                            <p class="text-center mt-2 fw-bold text-muted small">Sesudah Perbaikan</p>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mt-3" style="color: var(--primary);"><?php echo htmlspecialchars($g['judul']); ?></h6>
                                    <div class="text-muted small mb-3">
                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($g['uploader_nama'] ?? 'Admin'); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-calendar me-1"></i> <?php echo date('d F Y H:i', strtotime($g['created_at'])); ?>
                                    </div>
                                    <?php if (!empty($g['deskripsi'])): ?>
                                        <div class="p-3 bg-light rounded">
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($g['deskripsi'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL EDIT -->
                    <div class="modal fade" id="editModal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Dokumentasi</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                    <input type="hidden" name="foto_before_lama" value="<?php echo $g['foto_before']; ?>">
                                    <input type="hidden" name="foto_after_lama" value="<?php echo $g['foto_after']; ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Judul</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                                <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($g['judul']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Foto Sebelum</label>
                                                <img src="../assets/uploads/galeri/<?php echo $g['foto_before']; ?>" class="d-block mb-2" style="width:100%; height:120px; object-fit:cover; border-radius:10px;">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                                    <input type="file" class="form-control" name="foto_before" accept="image/*">
                                                </div>
                                                <small class="text-muted">Kosongkan jika tidak ingin mengganti</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Foto Sesudah</label>
                                                <img src="../assets/uploads/galeri/<?php echo $g['foto_after']; ?>" class="d-block mb-2" style="width:100%; height:120px; object-fit:cover; border-radius:10px;">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                                    <input type="file" class="form-control" name="foto_after" accept="image/*">
                                                </div>
                                                <small class="text-muted">Kosongkan jika tidak ingin mengganti</small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Deskripsi</label>
                                            <textarea class="form-control" name="deskripsi" rows="4"><?php echo htmlspecialchars($g['deskripsi']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="edit" class="btn" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL HAPUS -->
                    <div class="modal fade" id="hapusModal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                    <div class="modal-body">
                                        <p>Apakah Anda yakin ingin menghapus dokumentasi:</p>
                                        <p class="fw-bold text-primary">"<?php echo htmlspecialchars($g['judul']); ?>"?</p>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Semua rating dan komentar yang terkait juga akan ikut terhapus!
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="hapus" class="btn btn-danger">Ya, Hapus</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL RATING -->
                    <div class="modal fade" id="ratingModal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-star me-2"></i>Rating dari Pengguna</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($rating_per_galeri[$g['id']])): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Belum ada rating untuk dokumentasi ini</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($rating_per_galeri[$g['id']] as $r): ?>
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($r['nama']); ?></span>
                                                    <span class="text-muted small"><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></span>
                                                </div>
                                                <div class="mt-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php echo $i <= $r['rating'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>'; ?>
                                                    <?php endfor; ?>
                                                    <span class="ms-2">(<?php echo $r['rating']; ?>/5)</span>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['email']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL KOMENTAR -->
                    <div class="modal fade" id="komentarModal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-comments me-2"></i>Umpan Balik Pengguna</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($komentar_per_galeri[$g['id']])): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-comment fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Belum ada komentar untuk dokumentasi ini</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($komentar_per_galeri[$g['id']] as $k): ?>
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold">
                                                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($k['nama']); ?>
                                                        <?php if ($k['role'] == 'admin'): ?>
                                                            <span class="badge bg-primary ms-1" style="font-size: 9px;">Admin</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="text-muted small"><?php echo date('d M Y H:i', strtotime($k['created_at'])); ?></span>
                                                </div>
                                                <div class="mt-1"><?php echo nl2br(htmlspecialchars($k['komentar'])); ?></div>
                                                <div class="text-end mt-1">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="komentar_id" value="<?php echo $k['id']; ?>">
                                                        <button type="submit" name="hapus_komentar" class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Hapus komentar ini?')">
                                                            <i class="fas fa-trash-alt me-1"></i>Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL TAMBAH MANUAL -->
    <div class="modal fade" id="tambahManualModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Dokumentasi Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Dokumentasi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" class="form-control" name="judul" required placeholder="Contoh: Perbaikan Jalan Utama">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Foto Sebelum Perbaikan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                    <input type="file" class="form-control" name="foto_before" accept="image/*" required>
                                </div>
                                <small class="text-muted">Maksimal 5MB (JPG, PNG, GIF)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Foto Sesudah Perbaikan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                    <input type="file" class="form-control" name="foto_after" accept="image/*" required>
                                </div>
                                <small class="text-muted">Maksimal 5MB (JPG, PNG, GIF)</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi Perbaikan</label>
                            <textarea class="form-control" name="deskripsi" rows="4" placeholder="Jelaskan detail perbaikan yang dilakukan..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Dokumentasi akan ditambahkan dengan nama admin sebagai pengunggah.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="manual_tambah" class="btn-primary-custom">Simpan Dokumentasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH DARI PENGADUAN SELESAI -->
    <div class="modal fade" id="tambahDariPengaduanModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Dokumentasi dari Pengaduan Selesai</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Pengaduan Selesai</label>
                            <select class="form-select" name="pengaduan_id" required onchange="updateJudul(this)">
                                <option value="">-- Pilih Pengaduan --</option>
                                <?php foreach ($pengaduan_selesai as $ps): ?>
                                    <option value="<?php echo $ps['id']; ?>" data-judul="<?php echo htmlspecialchars($ps['judul']); ?>" data-deskripsi="<?php echo htmlspecialchars($ps['deskripsi']); ?>">
                                        <?php echo htmlspecialchars($ps['judul']); ?> - (<?php echo htmlspecialchars($ps['user_nama']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Dokumentasi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" class="form-control" name="judul" id="judul_dokumentasi" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Foto Hasil Perbaikan (After) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                <input type="file" class="form-control" name="foto_after" accept="image/*" required>
                            </div>
                            <small class="text-muted">Maksimal 5MB (JPG, PNG, GIF)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi Perbaikan</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi_dokumentasi" rows="4" placeholder="Jelaskan detail perbaikan yang dilakukan..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Foto sebelum perbaikan akan diambil dari lampiran pengaduan user.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_dari_pengaduan" class="btn-primary-custom">Simpan Dokumentasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateJudul(select) {
            const option = select.options[select.selectedIndex];
            const judul = option.getAttribute('data-judul') || '';
            const deskripsi = option.getAttribute('data-deskripsi') || '';
            document.getElementById('judul_dokumentasi').value = judul;
            document.getElementById('deskripsi_dokumentasi').value = deskripsi;
        }

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>