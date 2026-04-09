<?php
// user/galeri.php - Riwayat Perbaikan (tanpa perubahan struktur DB)
require_once '../config/database.php';
$db = new Database();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
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

// HANDLE PENGAJUAN BARU (CREATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_pengaduan'])) {
    $tanggal_kejadian = $db->escape_string($_POST['tanggal_kejadian']);
    $judul = $db->escape_string($_POST['judul']);
    $kategori = trim($db->escape_string($_POST['kategori']));
    $prioritas = $db->escape_string($_POST['prioritas']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);

    $tanggal_kejadian_time = strtotime($tanggal_kejadian);
    $seven_days_ago = strtotime('-7 days');
    $today = strtotime(date('Y-m-d'));
    
    if ($tanggal_kejadian_time < $seven_days_ago) {
        $error = "Tanggal kejadian tidak boleh lebih dari 7 hari ke belakang!";
    } elseif ($tanggal_kejadian_time > $today) {
        $error = "Tanggal kejadian tidak boleh lebih dari hari ini!";
    } elseif (!in_array($kategori, $valid_kategori)) {
        $error = "Kategori yang dipilih tidak valid!";
    } else {
        $lampiran = null;

        if (!isset($_FILES['lampiran']) || $_FILES['lampiran']['error'] != 0) {
            $error = "Lampiran foto wajib diisi!";
        } else {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = time() . '_' . basename($_FILES['lampiran']['name']);
            $target_file = $upload_dir . $filename;

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
                header('Location: galeri.php');
                exit;
            } else {
                $error = "Gagal membuat pengaduan: " . $db->conn->error;
            }
        }
    }
}

// HANDLE RATING (dengan nilai dari bintang)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rate'])) {
    $galeri_id = (int)$_POST['galeri_id'];
    $rating = (int)$_POST['rating'];
    
    if ($rating >= 1 && $rating <= 5) {
        $check = $db->query("SELECT id FROM rating_galeri WHERE galeri_id = $galeri_id AND user_id = $user_id");
        if ($check->num_rows == 0) {
            $insert = "INSERT INTO rating_galeri (galeri_id, user_id, rating) VALUES ($galeri_id, $user_id, $rating)";
            if ($db->conn->query($insert)) {
                $_SESSION['success'] = "Rating berhasil disimpan!";
            } else {
                $_SESSION['error'] = "Gagal menyimpan rating: " . $db->conn->error;
            }
        } else {
            $_SESSION['error'] = "Anda sudah memberikan rating untuk riwayat ini.";
        }
    }
    header('Location: galeri.php');
    exit;
}

// HANDLE KOMENTAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_komentar'])) {
    $galeri_id = (int)$_POST['galeri_id'];
    $komentar = trim($_POST['komentar']);
    
    if (empty($komentar)) {
        $_SESSION['error'] = "Komentar tidak boleh kosong!";
    } else {
        $komentar = $db->escape_string($komentar);
        $query = "INSERT INTO komentar_galeri (galeri_id, user_id, komentar) VALUES ($galeri_id, $user_id, '$komentar')";
        
        if ($db->conn->query($query)) {
            $_SESSION['success'] = "Komentar berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal: " . $db->conn->error;
        }
    }
    header('Location: galeri.php#riwayat-' . $galeri_id);
    exit;
}

// HANDLE HAPUS KOMENTAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_komentar'])) {
    $komentar_id = (int)$_POST['komentar_id'];
    $galeri_id = (int)$_POST['galeri_id'];
    
    $check = $db->query("SELECT user_id FROM komentar_galeri WHERE id = $komentar_id");
    if ($check && $check->num_rows > 0) {
        $data = $check->fetch_assoc();
        if ($_SESSION['role'] == 'admin' || $data['user_id'] == $user_id) {
            $db->conn->query("DELETE FROM komentar_galeri WHERE id = $komentar_id");
            $_SESSION['success'] = "Komentar dihapus.";
        }
    }
    header('Location: galeri.php#riwayat-' . $galeri_id);
    exit;
}

// Fungsi untuk mendapatkan URL foto yang benar
function getFotoUrl($filename) {
    if (empty($filename)) {
        return '../assets/images/no-image.jpg';
    }
    
    $paths = [
        '../assets/uploads/galeri/' . $filename,
        '../assets/uploads/admin_foto/' . $filename,
        '../assets/uploads/' . $filename
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return '../assets/images/no-image.jpg';
}

// AMBIL DATA RIWAYAT PERBAIKAN (SEMUA DATA GALERI)
$query = "SELECT g.*, 
          u.nama as uploader_nama,
          COALESCE(AVG(r.rating), 0) as avg_rating, 
          COUNT(DISTINCT r.id) as total_rating,
          MAX(CASE WHEN r.user_id = $user_id THEN r.rating END) as user_rating
          FROM galeri g
          LEFT JOIN users u ON g.user_id = u.id
          LEFT JOIN rating_galeri r ON g.id = r.galeri_id
          GROUP BY g.id
          ORDER BY g.created_at DESC";
$result = $db->query($query);
$galeri_list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// AMBIL KOMENTAR PER GALERI
$komentar_per_galeri = [];
foreach ($galeri_list as $item) {
    $gid = $item['id'];
    $q = "SELECT k.*, u.nama, u.role FROM komentar_galeri k 
          JOIN users u ON k.user_id = u.id 
          WHERE k.galeri_id = $gid 
          ORDER BY k.created_at DESC";
    $res = $db->query($q);
    $komentar_per_galeri[$gid] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// AMBIL SESSION MESSAGES
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Perbaikan - AssetCare</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
            box-shadow: 0 4px 20px rgba(9, 99, 126, 0.15);
            padding: 12px 0;
        }
        .navbar-custom .navbar-brand { color: white !important; font-weight: 700; font-size: 1.8rem; }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            margin: 0 4px;
        }
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        .profile-avatar {
            width: 45px; height: 45px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        
        .main-content { flex: 1; padding: 30px 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid var(--primary);
        }
        .header-card h1 { color: var(--primary); font-weight: 700; font-size: 2rem; margin-bottom: 10px; }
        .header-card p { color: #6c757d; margin: 0; }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card i { font-size: 2rem; color: var(--primary); margin-bottom: 10px; }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .stat-card .stat-label { color: #6c757d; font-size: 0.85rem; }
        
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .galeri-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            scroll-margin-top: 100px;
        }
        .galeri-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(9,99,126,0.12); }
        
        .foto-container {
            display: flex;
            height: 180px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: #f5f5f5;
        }
        .foto-container img { 
            width: 50%; 
            object-fit: cover; 
            transition: transform 0.3s ease;
        }
        .foto-container:hover img { transform: scale(1.05); }
        .foto-label {
            position: absolute;
            bottom: 8px;
            background: rgba(0,0,0,0.65);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            z-index: 2;
            backdrop-filter: blur(2px);
        }
        .foto-label.before { left: 20%; transform: translateX(-50%); }
        .foto-label.after { left: 70%; transform: translateX(-50%); }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 3;
        }
        
        .card-content { padding: 16px; }
        .card-title { 
            font-size: 1rem; 
            font-weight: 700; 
            color: var(--primary); 
            margin: 0 0 6px 0;
        }
        .card-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            color: #6c757d;
            font-size: 0.75rem;
        }
        .card-meta i { width: 14px; margin-right: 3px; }
        
        /* ========== RATING BINTANG SEDERHANA ========== */
        .rating-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .rating-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .rating-stars-display { color: #ffc107; font-size: 0.85rem; letter-spacing: 2px; }
        .rating-count { font-size: 0.7rem; color: #6c757d; }
        
        /* Bintang untuk rating input (klik langsung) */
        .star-rating {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 10px;
        }
        .star-rating .star {
            font-size: 1.5rem;
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s;
        }
        .star-rating .star:hover,
        .star-rating .star.active,
        .star-rating .star.selected {
            color: #ffc107;
        }
        
        .user-rating-badge {
            background: #d4edda;
            color: #155724;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .btn-rate-simple {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-rate-simple:hover { opacity: 0.9; color: white; }
        .btn-rate-simple:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .komentar-form { margin-bottom: 12px; }
        .komentar-form .input-group-sm textarea { font-size: 0.75rem; border-radius: 20px; resize: none; }
        .komentar-form .btn-sm { border-radius: 20px; font-size: 0.7rem; padding: 0 12px; }
        
        .btn-lihat-komentar {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.7rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-lihat-komentar:hover { background: var(--primary); color: white; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            grid-column: 1/-1;
        }
        .empty-state i { font-size: 3rem; color: #dee2e6; margin-bottom: 15px; }
        .empty-state h4 { font-size: 1.2rem; margin-bottom: 8px; }
        
        .modal-header-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 20px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header-custom .btn-close {
            margin: 0;
            padding: 0.5rem;
            position: relative;
            right: 0;
            top: 0;
        }
        .modal-content-custom { border-radius: 16px; overflow: hidden; }
        
        @media (max-width: 768px) {
            .galeri-grid { grid-template-columns: 1fr; }
            .navbar-custom .nav-link { margin: 4px 0; padding: 8px 12px; font-size: 0.85rem; }
            .header-card h1 { font-size: 1.5rem; }
            .star-rating .star { font-size: 1.2rem; }
        }
        
        @media (max-width: 480px) {
            .container { padding: 0 12px; }
            .card-content { padding: 12px; }
            .foto-container { height: 150px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <!-- Notifikasi -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Header Card -->
            <div class="header-card animate__animated animate__fadeIn">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div><i class="fas fa-clipboard-list fa-3x" style="color: var(--primary); opacity: 0.5;"></i></div>
                    <div>
                        <h1>Laporan Riwayat Perbaikan</h1>
                        <p>Rekam jejak perbaikan sarana dan prasarana yang telah ditangani</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <div class="stat-number">
                        <?php 
                        $total_rating = 0; $count_rating = 0;
                        foreach ($galeri_list as $item) {
                            if ($item['total_rating'] > 0) {
                                $total_rating += $item['avg_rating'];
                                $count_rating++;
                            }
                        }
                        echo $count_rating > 0 ? round($total_rating / $count_rating, 1) : '0';
                        ?>
                    </div>
                    <div class="stat-label">Rata-rata Rating</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-comments"></i>
                    <div class="stat-number">
                        <?php 
                        $total_komentar = 0;
                        foreach ($komentar_per_galeri as $komentar) $total_komentar += count($komentar);
                        echo $total_komentar;
                        ?>
                    </div>
                    <div class="stat-label">Total Umpan Balik</div>
                </div>
            </div>

            <!-- Grid Riwayat Perbaikan -->
            <?php if (empty($galeri_list)): ?>
                <div class="empty-state animate__animated animate__fadeIn">
                    <i class="fas fa-tools"></i>
                    <h4>Belum Ada Riwayat Perbaikan</h4>
                    <p class="text-muted mb-0">Riwayat perbaikan akan muncul setelah admin mendokumentasikan hasil perbaikan.</p>
                </div>
            <?php else: ?>
                <div class="galeri-grid">
                    <?php foreach ($galeri_list as $item): 
                        $total_komentar = count($komentar_per_galeri[$item['id']] ?? []);
                        $foto_before_url = getFotoUrl($item['foto_before']);
                        $foto_after_url = getFotoUrl($item['foto_after']);
                        $rating_value = round($item['avg_rating']);
                    ?>
                        <div class="galeri-card animate__animated animate__fadeInUp" id="riwayat-<?php echo $item['id']; ?>">
                            <!-- Foto Container -->
                            <div class="foto-container" data-bs-toggle="modal" data-bs-target="#modalDetail<?php echo $item['id']; ?>">
                                <img src="<?php echo $foto_before_url; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                                <img src="<?php echo $foto_after_url; ?>" onerror="this.src='../assets/images/no-image.jpg'">
                                <span class="foto-label before">Sebelum</span>
                                <span class="foto-label after">Sesudah</span>
                                <span class="status-badge"><i class="fas fa-check-circle me-1"></i>Selesai</span>
                            </div>
                            
                            <div class="card-content">
                                <div class="card-title">
                                    <span><?php echo htmlspecialchars(substr($item['judul'], 0, 40)) . (strlen($item['judul']) > 40 ? '...' : ''); ?></span>
                                </div>
                                <div class="card-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['uploader_nama'] ?? 'Admin'); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($item['created_at'])); ?></span>
                                </div>
                                
                                <!-- Rating Section - SEDERHANA dengan BINTANG KLIK -->
                                <div class="rating-section">
                                    <div class="rating-display">
                                        <div class="rating-stars-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php echo $i <= $rating_value ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-count"><?php echo $item['total_rating']; ?> ulasan</span>
                                    </div>
                                    
                                    <?php if (!$item['user_rating']): ?>
                                        <!-- Form Rating dengan Bintang Klik -->
                                        <form method="POST" class="rating-form" id="ratingForm<?php echo $item['id']; ?>">
                                            <input type="hidden" name="galeri_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="rating" id="ratingValue<?php echo $item['id']; ?>" value="0">
                                            <div class="star-rating" id="starRating<?php echo $item['id']; ?>">
                                                <i class="far fa-star star" data-value="1"></i>
                                                <i class="far fa-star star" data-value="2"></i>
                                                <i class="far fa-star star" data-value="3"></i>
                                                <i class="far fa-star star" data-value="4"></i>
                                                <i class="far fa-star star" data-value="5"></i>
                                            </div>
                                            <button type="submit" name="rate" class="btn-rate-simple" id="submitBtn<?php echo $item['id']; ?>" disabled>
                                                Kirim Rating
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="user-rating-badge">
                                            <i class="fas fa-star me-1"></i> Rating Anda: <?php echo $item['user_rating']; ?> ⭐
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Komentar Form -->
                                <div class="komentar-form">
                                    <form method="POST">
                                        <input type="hidden" name="galeri_id" value="<?php echo $item['id']; ?>">
                                        <div class="input-group input-group-sm">
                                            <textarea name="komentar" class="form-control" rows="1" placeholder="Tulis umpan balik..." required style="font-size: 0.75rem;"></textarea>
                                            <button class="btn btn-primary" type="submit" name="submit_komentar" style="border-radius: 20px; font-size: 0.7rem;">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Tombol Lihat Semua Komentar -->
                                <button type="button" class="btn-lihat-komentar" data-bs-toggle="modal" data-bs-target="#modalKomentar<?php echo $item['id']; ?>">
                                    <i class="fas fa-comments me-1"></i> Lihat Umpan Balik (<?php echo $total_komentar; ?>)
                                </button>
                            </div>
                        </div>

                        <!-- Modal Detail Foto -->
                        <div class="modal fade" id="modalDetail<?php echo $item['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content modal-content-custom">
                                    <div class="modal-header-custom">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($item['judul']); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            <div class="col-md-6 text-center">
                                                <img src="<?php echo getFotoUrl($item['foto_before']); ?>" class="img-fluid rounded" style="max-height: 250px; width: 100%; object-fit: cover;" onerror="this.src='../assets/images/no-image.jpg'">
                                                <p class="mt-2 fw-bold text-muted small">Sebelum Perbaikan</p>
                                            </div>
                                            <div class="col-md-6 text-center">
                                                <img src="<?php echo getFotoUrl($item['foto_after']); ?>" class="img-fluid rounded" style="max-height: 250px; width: 100%; object-fit: cover;" onerror="this.src='../assets/images/no-image.jpg'">
                                                <p class="mt-2 fw-bold text-muted small">Sesudah Perbaikan</p>
                                            </div>
                                        </div>
                                        <?php if (!empty($item['deskripsi'])): ?>
                                            <div class="mt-4 p-3 bg-light rounded">
                                                <h6 class="fw-bold mb-2">Deskripsi Perbaikan</h6>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($item['deskripsi'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-3 text-muted small">
                                            <i class="fas fa-user me-1"></i> Didokumentasikan oleh: <?php echo htmlspecialchars($item['uploader_nama'] ?? 'Admin'); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-calendar me-1"></i> <?php echo date('d F Y H:i', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Semua Komentar -->
                        <div class="modal fade" id="modalKomentar<?php echo $item['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content modal-content-custom">
                                    <div class="modal-header-custom">
                                        <h5 class="modal-title"><i class="fas fa-comments me-2"></i>Umpan Balik</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                                        <?php if (empty($komentar_per_galeri[$item['id']])): ?>
                                            <p class="text-muted text-center py-3">Belum ada umpan balik.</p>
                                        <?php else: ?>
                                            <?php foreach ($komentar_per_galeri[$item['id']] as $k): ?>
                                                <div class="border-bottom pb-2 mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold small">
                                                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($k['nama']); ?>
                                                            <?php if ($k['role'] == 'admin'): ?>
                                                                <span class="badge bg-primary ms-1" style="font-size: 9px;">Admin</span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="text-muted small"><?php echo date('d M H:i', strtotime($k['created_at'])); ?></span>
                                                    </div>
                                                    <div class="mt-1 small"><?php echo nl2br(htmlspecialchars($k['komentar'])); ?></div>
                                                    <?php if ($_SESSION['role'] == 'admin' || $k['user_id'] == $user_id): ?>
                                                        <div class="text-end mt-1">
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="komentar_id" value="<?php echo $k['id']; ?>">
                                                                <input type="hidden" name="galeri_id" value="<?php echo $item['id']; ?>">
                                                                <button type="submit" name="hapus_komentar" class="btn btn-link btn-sm text-danger p-0" style="font-size: 0.7rem;" onclick="return confirm('Hapus komentar ini?')">
                                                                    <i class="fas fa-trash-alt me-1"></i>Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
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
    </div>

    <!-- Modal Buat Pengaduan -->
    <div class="modal fade" id="buatPengaduanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-custom">
                <div class="modal-header-custom">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Buat Pengaduan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal Kejadian <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_kejadian" required 
                                       min="<?php echo date('Y-m-d', strtotime('-7 days')); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Maksimal 7 hari ke belakang</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Judul Pengaduan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="judul" placeholder="Contoh: Komputer tidak bisa menyala" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prioritas <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="prioritas" id="rendah" value="Rendah" autocomplete="off" checked>
                                    <label class="btn btn-outline-success btn-sm" for="rendah">Rendah</label>
                                    <input type="radio" class="btn-check" name="prioritas" id="sedang" value="Sedang" autocomplete="off">
                                    <label class="btn btn-outline-warning btn-sm" for="sedang">Sedang</label>
                                    <input type="radio" class="btn-check" name="prioritas" id="tinggi" value="Tinggi" autocomplete="off">
                                    <label class="btn btn-outline-danger btn-sm" for="tinggi">Tinggi</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Lampiran Foto <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="lampiran" accept="image/*" required>
                                <small class="text-muted">Maksimal 2MB (JPG, PNG, GIF)</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="deskripsi" rows="4" placeholder="Jelaskan kerusakan secara detail..." required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 pe-4">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_pengaduan" class="btn" style="background: linear-gradient(135deg, #09637E, #088395); color: white;">Simpan Pengaduan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========== SCRIPT UNTUK RATING BINTANG ==========
        document.querySelectorAll('.star-rating').forEach(function(starContainer) {
            const formId = starContainer.closest('form').id;
            const ratingInput = starContainer.closest('form').querySelector('input[name="rating"]');
            const submitBtn = starContainer.closest('form').querySelector('button[type="submit"]');
            const stars = starContainer.querySelectorAll('.star');
            
            let selectedRating = 0;
            
            // Fungsi update tampilan bintang
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    const starValue = parseInt(star.getAttribute('data-value'));
                    if (starValue <= rating) {
                        star.className = 'fas fa-star star';
                        star.classList.add('active');
                    } else {
                        star.className = 'far fa-star star';
                        star.classList.remove('active');
                    }
                });
            }
            
            // Event hover
            stars.forEach(star => {
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    updateStars(value);
                });
                
                star.addEventListener('mouseleave', function() {
                    updateStars(selectedRating);
                });
                
                star.addEventListener('click', function() {
                    selectedRating = parseInt(this.getAttribute('data-value'));
                    ratingInput.value = selectedRating;
                    updateStars(selectedRating);
                    submitBtn.disabled = false;
                });
            });
        });
        
        // Auto hide alert
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