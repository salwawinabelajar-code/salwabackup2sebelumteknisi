<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

if (!isset($_GET['id'])) {
    redirect('pengaduan.php');
}

$pengaduan_id = $db->escape_string($_GET['id']);
$error = '';
$success = '';

// Get pengaduan details dengan JOIN users
$query = "SELECT p.*, u.nama as nama_user, u.email as email_user FROM pengaduan p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = '$pengaduan_id'";
$result = $db->query($query);

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Pengaduan tidak ditemukan!";
    redirect('pengaduan.php');
}

$pengaduan = $result->fetch_assoc();

// Fungsi untuk menambah ke galeri
function tambahKeGaleri($db, $pengaduan_id, $user_id, $judul, $foto_before, $foto_after, $deskripsi) {
    // Cek apakah sudah ada di galeri
    $check = $db->query("SELECT id FROM galeri WHERE foto_before = '$foto_before'");
    if ($check && $check->num_rows > 0) {
        return false; // Sudah ada
    }
    
    // Pastikan folder galeri ada
    $galeri_dir = '../assets/uploads/galeri/';
    if (!is_dir($galeri_dir)) {
        mkdir($galeri_dir, 0777, true);
    }
    
    // Copy foto ke folder galeri jika file ada
    if ($foto_before && file_exists('../assets/uploads/' . $foto_before)) {
        copy('../assets/uploads/' . $foto_before, $galeri_dir . $foto_before);
    }
    if ($foto_after && file_exists('../assets/uploads/admin_foto/' . $foto_after)) {
        copy('../assets/uploads/admin_foto/' . $foto_after, $galeri_dir . $foto_after);
    }
    
    $insert = "INSERT INTO galeri (user_id, judul, foto_before, foto_after, deskripsi, created_at) 
               VALUES ('$user_id', '$judul', '$foto_before', '$foto_after', '$deskripsi', NOW())";
    return $db->conn->query($insert);
}

// Handle update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $db->escape_string($_POST['new_status']);
    $catatan_admin = $db->escape_string($_POST['catatan_admin'] ?? '');
    $foto_admin = $pengaduan['foto_admin']; // foto lama
    $upload_foto_baru = false;
    
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
                $upload_foto_baru = true;
            } else {
                $error = "Gagal mengupload foto";
            }
        } else {
            $error = "File harus gambar (JPG/PNG/GIF) dan maksimal 2MB";
        }
    }
    
    if (!$error) {
        $query = "UPDATE pengaduan SET status = '$new_status', catatan_admin = '$catatan_admin'";
        if ($upload_foto_baru && $foto_admin) {
            $query .= ", foto_admin = '$foto_admin'";
        }
        $query .= " WHERE id = '$pengaduan_id'";
        
        if ($db->conn->query($query)) {
            // AMBIL DATA TERBARU SETELAH UPDATE
            $query_refresh = "SELECT p.*, u.nama as nama_user, u.email as email_user 
                              FROM pengaduan p 
                              LEFT JOIN users u ON p.user_id = u.id 
                              WHERE p.id = '$pengaduan_id'";
            $result_refresh = $db->query($query_refresh);
            $pengaduan_terbaru = $result_refresh->fetch_assoc();
            
            // ========== TAMBAHKAN KE GALERI JIKA STATUS SELESAI DAN ADA FOTO ==========
            if ($new_status == 'Selesai' && $foto_admin) {
                // Buat judul untuk galeri
                $judul_galeri = "Perbaikan: " . $pengaduan_terbaru['judul'];
                $deskripsi_galeri = "Pengaduan dari " . $pengaduan_terbaru['nama_user'] . " telah selesai diperbaiki.\n\n" .
                                    "Deskripsi perbaikan: " . $catatan_admin;
                
                // Tambahkan ke tabel galeri
                if (tambahKeGaleri($db, $pengaduan_id, $pengaduan_terbaru['user_id'], $judul_galeri, 
                                  $pengaduan_terbaru['lampiran'], $foto_admin, $deskripsi_galeri)) {
                    $_SESSION['success'] = "Status pengaduan berhasil diupdate! Dokumentasi perbaikan telah ditambahkan ke Laporan Riwayat Perbaikan.";
                } else {
                    // Cek apakah sudah ada
                    $cek_galeri = $db->query("SELECT id FROM galeri WHERE foto_before = '" . $pengaduan_terbaru['lampiran'] . "'");
                    if ($cek_galeri && $cek_galeri->num_rows > 0) {
                        $_SESSION['success'] = "Status pengaduan berhasil diupdate! Dokumentasi sudah ada di Laporan Riwayat Perbaikan.";
                    } else {
                        $_SESSION['warning'] = "Status berhasil diupdate, tetapi gagal menambahkan ke galeri.";
                    }
                }
            } else {
                $_SESSION['success'] = "Status pengaduan berhasil diupdate!";
            }
            
            redirect('detail_pengaduan.php?id=' . $pengaduan_id);
        } else {
            $error = "Gagal mengupdate status: " . $db->conn->error;
        }
    }
}

// Ambil data terbaru untuk ditampilkan
$query = "SELECT p.*, u.nama as nama_user, u.email as email_user FROM pengaduan p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = '$pengaduan_id'";
$result = $db->query($query);
$pengaduan = $result->fetch_assoc();

// Cek apakah sudah ada di galeri
$sudah_di_galeri = false;
if ($pengaduan['lampiran']) {
    $cek_galeri = $db->query("SELECT id FROM galeri WHERE foto_before = '" . $pengaduan['lampiran'] . "'");
    $sudah_di_galeri = ($cek_galeri && $cek_galeri->num_rows > 0);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengaduan - AssetCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #09637E; --secondary: #088395; --accent: #7AB2B2; --light: #EBF4F6; }
        body { background-color: #f8f9fa; }
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
        .detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
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
        .image-preview { max-width: 300px; border-radius: 10px; }
        @media (max-width: 768px) { .main-content { margin-left: 70px; } }
        @media (max-width: 576px) { .main-content { margin-left: 0; margin-top: 56px; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="navbar-top d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="pengaduan.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                <h4 class="mb-0 text-primary">Detail Pengaduan</h4>
            </div>
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="detail-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Informasi Pengaduan</h5>
                        <span class="badge-status badge-<?php echo strtolower($pengaduan['status']); ?>"><?php echo $pengaduan['status']; ?></span>
                    </div>
                    
                    <!-- Status Galeri -->
                    <?php if ($pengaduan['status'] == 'Selesai'): ?>
                        <div class="alert <?php echo $sudah_di_galeri ? 'alert-success' : 'alert-info'; ?> mb-4">
                            <i class="fas <?php echo $sudah_di_galeri ? 'fa-check-circle' : 'fa-info-circle'; ?> me-2"></i>
                            <?php if ($sudah_di_galeri): ?>
                                <strong>Sudah Didokumentasikan!</strong> Pengaduan ini sudah masuk ke Laporan Riwayat Perbaikan.
                            <?php else: ?>
                                <strong>Belum Didokumentasikan!</strong> Jika pengaduan ini selesai dan memiliki foto hasil perbaikan, akan otomatis masuk ke Laporan Riwayat Perbaikan.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Judul Pengaduan</div><div class="h5"><?php echo htmlspecialchars($pengaduan['judul']); ?></div></div>
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Tanggal Kejadian</div><div><?php echo date('d F Y', strtotime($pengaduan['tanggal_kejadian'])); ?></div></div>
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Kategori</div><div><span class="badge bg-light text-dark"><?php echo $pengaduan['kategori']; ?></span></div></div>
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Prioritas</div><div><span class="badge bg-<?php echo getPriorityColor($pengaduan['prioritas']); ?>"><?php echo $pengaduan['prioritas']; ?></span></div></div>
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Dibuat Pada</div><div><?php echo date('d/m/Y H:i', strtotime($pengaduan['created_at'])); ?></div></div>
                        <div class="col-md-6 mb-3"><div class="fw-bold text-muted">Terakhir Diupdate</div><div><?php echo date('d/m/Y H:i', strtotime($pengaduan['updated_at'])); ?></div></div>
                    </div>
                    
                    <div class="mb-4"><div class="fw-bold text-muted mb-2">Deskripsi Kerusakan</div><div class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars($pengaduan['deskripsi'])); ?></div></div>
                    
                    <!-- Foto dari User (Sebelum Perbaikan) - PERBAIKAN PATH -->
                    <?php if ($pengaduan['lampiran']): ?>
                    <div class="mb-4">
                        <div class="fw-bold text-muted mb-2">Foto Sebelum Perbaikan (Dari User)</div>
                        <?php 
                        $foto_user_path = '../assets/uploads/' . $pengaduan['lampiran'];
                        if (!file_exists($foto_user_path)) {
                            echo '<div class="alert alert-warning">File foto tidak ditemukan di: ' . $foto_user_path . '</div>';
                        }
                        ?>
                        <img src="../assets/uploads/<?php echo $pengaduan['lampiran']; ?>" class="image-preview img-fluid" onerror="this.src='../assets/images/no-image.jpg'; this.onerror=null;">
                        <p class="text-muted small mt-1">Foto ini akan menjadi dokumentasi "Sebelum" di Laporan Riwayat Perbaikan</p>
                    </div>
                    <?php else: ?>
                    <div class="mb-4">
                        <div class="fw-bold text-muted mb-2">Foto Sebelum Perbaikan</div>
                        <div class="alert alert-secondary">Tidak ada foto lampiran dari user</div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Foto dari Admin (Sesudah Perbaikan) -->
                    <?php if ($pengaduan['foto_admin']): ?>
                    <div class="mb-4">
                        <div class="fw-bold text-muted mb-2">Foto Sesudah Perbaikan (Dari Admin)</div>
                        <img src="../assets/uploads/admin_foto/<?php echo $pengaduan['foto_admin']; ?>" class="image-preview img-fluid" onerror="this.src='../assets/images/no-image.jpg'; this.onerror=null;">
                        <p class="text-muted small mt-1">Foto ini akan menjadi dokumentasi "Sesudah" di Laporan Riwayat Perbaikan</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pengaduan['catatan_admin']): ?>
                    <div class="mb-4">
                        <div class="fw-bold text-muted mb-2">Catatan Admin</div>
                        <div class="alert alert-info"><?php echo nl2br(htmlspecialchars($pengaduan['catatan_admin'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="detail-card mb-4">
                    <h5 class="mb-4">Informasi Pelapor</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="profile-avatar me-3" style="width: 60px; height: 60px; font-size: 1.5rem;"><?php echo generateInitials($pengaduan['nama_user']); ?></div>
                        <div><div class="h6 mb-1"><?php echo $pengaduan['nama_user']; ?></div><div class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo $pengaduan['email_user']; ?></div></div>
                    </div>
                    <a href="profil_user.php?id=<?php echo $pengaduan['user_id']; ?>" class="btn btn-outline-primary w-100"><i class="fas fa-user me-1"></i> Lihat Profil User</a>
                </div>
                
                <div class="detail-card">
                    <h5 class="mb-4">Aksi Admin</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Ubah Status</label>
                            <select class="form-select" name="new_status" required>
                                <?php if ($pengaduan['status'] == 'Menunggu'): ?>
                                    <option value="Diproses">Diproses</option>
                                    <option value="Ditolak">Ditolak</option>
                                <?php elseif ($pengaduan['status'] == 'Diproses'): ?>
                                    <option value="Selesai">Selesai</option>
                                <?php else: ?>
                                    <option value="<?php echo $pengaduan['status']; ?>" selected><?php echo $pengaduan['status']; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tambahkan Catatan</label>
                            <textarea class="form-control" name="catatan_admin" rows="3" placeholder="Tambahkan catatan untuk pelapor..."><?php echo $pengaduan['catatan_admin']; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Foto Hasil Perbaikan</label>
                            <input type="file" class="form-control" name="foto_admin" accept="image/*">
                            <small class="text-muted">Maksimal 2MB (JPG, PNG, GIF). <strong class="text-success">Upload foto saat mengubah status menjadi Selesai untuk dokumentasi otomatis ke Laporan Riwayat Perbaikan.</strong></small>
                            
                            <?php if ($pengaduan['status'] == 'Selesai' && !$pengaduan['foto_admin']): ?>
                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Perhatian!</strong> Pengaduan ini berstatus Selesai namun belum memiliki dokumentasi foto hasil perbaikan. 
                                    Upload foto untuk menambahkannya ke Laporan Riwayat Perbaikan.
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($sudah_di_galeri): ?>
                                <div class="alert alert-success mt-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Sudah Terdokumentasi!</strong> Pengaduan ini sudah ada di Laporan Riwayat Perbaikan.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" name="update_status" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>