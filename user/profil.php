<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil daftar kategori
$kategori_result = $db->query("SELECT nama FROM kategori ORDER BY nama");
$kategori_list = [];
if ($kategori_result) {
    while ($row = $kategori_result->fetch_assoc()) {
        $kategori_list[] = trim($row['nama']);
    }
}

// Proses pembuatan pengaduan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_pengaduan'])) {
    $tanggal_kejadian = $db->escape_string($_POST['tanggal_kejadian']);
    $judul = $db->escape_string($_POST['judul']);
    $kategori = trim($db->escape_string($_POST['kategori']));
    $prioritas = $db->escape_string($_POST['prioritas']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);
    
    // ========== VALIDASI TANGGAL MAX 7 HARI ==========
    $tanggal_kejadian_time = strtotime($tanggal_kejadian);
    $seven_days_ago = strtotime('-7 days');
    $today = strtotime(date('Y-m-d'));
    
    if ($tanggal_kejadian_time < $seven_days_ago) {
        $error = "Tanggal kejadian tidak boleh lebih dari 7 hari ke belakang!";
    } elseif ($tanggal_kejadian_time > $today) {
        $error = "Tanggal kejadian tidak boleh lebih dari hari ini!";
    } elseif (!in_array($kategori, $kategori_list)) {
        $error = "Kategori tidak valid!";
    } else {
        // Upload file
        $lampiran = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
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
        } else {
            $error = "Lampiran foto wajib diisi!";
        }
        
        if (!$error) {
            $query = "INSERT INTO pengaduan (user_id, tanggal_kejadian, judul, kategori, prioritas, lampiran, deskripsi) 
                      VALUES ('$user_id', '$tanggal_kejadian', '$judul', '$kategori', '$prioritas', '$lampiran', '$deskripsi')";
            if ($db->conn->query($query)) {
                $_SESSION['success'] = "Pengaduan berhasil dibuat!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = "Gagal membuat pengaduan: " . $db->conn->error;
            }
        }
    }
}

// Ambil data user
$result = $db->query("SELECT * FROM users WHERE id = '$user_id'");
$user = $result->fetch_assoc();

// Handle edit profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_profil'])) {
        $nama = $db->escape_string($_POST['nama']);
        $email = $db->escape_string($_POST['email']);
        
        // Validasi email unik (kecuali email sendiri)
        $check = $db->query("SELECT id FROM users WHERE email = '$email' AND id != '$user_id'");
        if ($check->num_rows > 0) {
            $error = "Email sudah digunakan oleh user lain!";
        } else {
            $query = "UPDATE users SET nama = '$nama', email = '$email' WHERE id = '$user_id'";
            if ($db->conn->query($query)) {
                // Update session
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
        
        // Validasi
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            $error = "Semua field password harus diisi!";
        } elseif ($password_baru !== $konfirmasi_password) {
            $error = "Password baru dan konfirmasi password tidak cocok!";
        } elseif (strlen($password_baru) < 6) {
            $error = "Password baru minimal 6 karakter!";
        } else {
            // Verifikasi password lama
            $check = $db->query("SELECT password FROM users WHERE id = '$user_id'");
            if ($check->num_rows > 0) {
                $user_data = $check->fetch_assoc();
                if (password_verify($password_lama, $user_data['password'])) {
                    // Hash password baru
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - AssetCare</title>
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
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .profile-avatar-large {
            width: 150px;
            height: 150px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 3.5rem;
            margin: 0 auto 20px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }
        
        .profile-avatar-large:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
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
        
        .card-profile {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .card-profile:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-profile .card-header {
            background: var(--light);
            border-bottom: 2px solid var(--accent);
            color: var(--primary);
            font-weight: 700;
            padding: 20px;
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
            min-width: 120px;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
            text-align: right;
            flex: 1;
        }
        
        .badge-role {
            background: var(--accent);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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
        
        .password-input {
            position: relative;
        }
        
        .toggle-password {
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
        
        .file-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #eee;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 40px 0;
            }
            
            .profile-avatar-large {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }
            
            .navbar-custom .nav-link {
                margin: 5px 0;
                text-align: center;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">

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
                        <!-- Tanggal Kejadian -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" name="tanggal_kejadian" required 
                                       id="tanggalKejadian"
                                       min="<?php echo date('Y-m-d', strtotime('-7 days')); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <small class="text-muted">Maksimal 7 hari ke belakang dari hari ini</small>
                        </div>
                        
                        <!-- Kategori -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                <select class="form-select" name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo htmlspecialchars($kat); ?>"><?php echo htmlspecialchars($kat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Judul -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Judul Pengaduan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" class="form-control" name="judul" placeholder="Contoh: Komputer tidak bisa menyala" required>
                            </div>
                        </div>
                        
                        <!-- Prioritas -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioritas <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="prioritas" id="rendah" value="Rendah" autocomplete="off" checked>
                                <label class="btn btn-outline-success" for="rendah"><i class="fas fa-arrow-down me-1"></i>Rendah</label>
                                <input type="radio" class="btn-check" name="prioritas" id="sedang" value="Sedang" autocomplete="off">
                                <label class="btn btn-outline-warning" for="sedang"><i class="fas fa-minus me-1"></i>Sedang</label>
                                <input type="radio" class="btn-check" name="prioritas" id="tinggi" value="Tinggi" autocomplete="off">
                                <label class="btn btn-outline-danger" for="tinggi"><i class="fas fa-arrow-up me-1"></i>Tinggi</label>
                            </div>
                        </div>
                        
                        <!-- Lampiran Foto -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lampiran Foto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="file" class="form-control" name="lampiran" accept="image/*" id="lampiranInput" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('lampiranInput').click()">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                            <small class="text-muted">Wajib diisi. Maksimal 2MB (JPG, PNG, GIF)</small>
                            <div id="previewBuat" class="mt-2 text-center"></div>
                        </div>
                        
                        <!-- Deskripsi -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" name="deskripsi" rows="5" placeholder="Jelaskan kerusakan secara detail..." required></textarea>
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

<!-- Notifikasi -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown mt-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown mt-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Profile Header -->
<div class="profile-header animate__animated animate__fadeIn">
    <div class="container">
        <div class="profile-avatar-large" id="avatarPreview">
            <?php echo generateInitials($user['nama']); ?>
        </div>
        <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($user['nama']); ?></h1>
        <p class="lead mb-0">
            <span class="badge-role me-2"><?php echo ucfirst($user['role']); ?></span>
            <span class="opacity-75">Bergabung sejak <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
        </p>
    </div>
</div>

<!-- Profile Information -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card-profile animate__animated animate__fadeInLeft">
            <div class="card-header">
                <i class="fas fa-user-circle me-2"></i>Informasi Profil
            </div>
            <div class="card-body">
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
                        <span class="badge-role"><?php echo ucfirst($user['role']); ?></span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Bergabung</span>
                    <span class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">ID User</span>
                    <span class="info-value font-monospace">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card-profile animate__animated animate__fadeInRight">
            <div class="card-header">
                <i class="fas fa-cogs me-2"></i>Aksi Profil
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <button class="btn btn-primary-custom btn-lg" data-bs-toggle="modal" data-bs-target="#editProfilModal">
                        <i class="fas fa-edit me-2"></i>Edit Profil
                    </button>
                    
                    <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#gantiPasswordModal">
                        <i class="fas fa-key me-2"></i>Ganti Password
                    </button>
                    
                    <a href="riwayat.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-history me-2"></i>Riwayat Pengaduan
                    </a>
                    
                    <a href="../auth/logout.php" class="btn btn-outline-danger btn-lg">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Terakhir login: <?php echo date('d/m/Y H:i'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Penutup container utama -->

<!-- Modal Edit Profil -->
<div class="modal fade" id="editProfilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Profil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formEditProfil">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="profile-avatar-large d-inline-block" id="modalAvatarPreview">
                            <?php echo generateInitials($user['nama']); ?>
                        </div>
                        <p class="text-muted mt-2 mb-0">Inisial akan berubah sesuai nama</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="nama" 
                                       value="<?php echo htmlspecialchars($user['nama']); ?>" 
                                       required 
                                       id="inputNama"
                                       oninput="updateAvatarPreview()">
                            </div>
                            <small class="text-muted">Inisial: <span id="initialsPreview"><?php echo generateInitials($user['nama']); ?></span></small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bergabung Sejak</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" name="edit_profil" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal fade" id="gantiPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>Ganti Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formGantiPassword">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Password Lama <span class="text-danger">*</span></label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="password_lama" required id="password_lama">
                                <button type="button" class="toggle-password" onclick="togglePassword('password_lama')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Password Baru <span class="text-danger">*</span></label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="password_baru" required id="password_baru">
                                <button type="button" class="toggle-password" onclick="togglePassword('password_baru')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="password-input">
                                <input type="password" class="form-control" name="konfirmasi_password" required id="konfirmasi_password">
                                <button type="button" class="toggle-password" onclick="togglePassword('konfirmasi_password')">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="progress mb-2" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="passwordStrengthText">Kekuatan password: sangat lemah</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" name="ganti_password" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Ganti Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Fungsi preview gambar
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
    
    // Attach preview untuk lampiran buat
    var lampiranInput = document.getElementById('lampiranInput');
    if (lampiranInput) {
        lampiranInput.addEventListener('change', function() {
            previewImage(this, 'previewBuat');
        });
    }
    
    // Validasi tanggal maksimal 7 hari ke belakang
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
            
            var judul = document.querySelector('input[name="judul"]').value;
            var kategori = document.querySelector('select[name="kategori"]').value;
            var deskripsi = document.querySelector('textarea[name="deskripsi"]').value;
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
    
    // Fungsi toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.parentNode.querySelector('i');
        
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
    
    // Fungsi untuk update avatar preview berdasarkan nama
    function updateAvatarPreview() {
        const namaInput = document.getElementById('inputNama');
        const modalAvatar = document.getElementById('modalAvatarPreview');
        const initialsPreview = document.getElementById('initialsPreview');
        
        if (namaInput && namaInput.value.trim() !== '') {
            // Generate initials dari nama
            const words = namaInput.value.trim().split(' ');
            let initials = '';
            for (let word of words) {
                if (word.length > 0) {
                    initials += word[0].toUpperCase();
                }
            }
            initials = initials.substring(0, 2);
            
            // Update modal avatar
            if (modalAvatar) modalAvatar.textContent = initials;
            
            // Update text preview
            if (initialsPreview) initialsPreview.textContent = initials;
        } else {
            if (modalAvatar) modalAvatar.textContent = '??';
            if (initialsPreview) initialsPreview.textContent = '??';
        }
    }
    
    // Password strength checker
    const passwordBaru = document.getElementById('password_baru');
    if (passwordBaru) {
        passwordBaru.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            
            if (!strengthBar || !strengthText) return;
            
            let strength = 0;
            let text = 'Kekuatan password: ';
            
            // Length check
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            
            // Complexity checks
            if (/[a-z]/.test(password)) strength += 12.5;
            if (/[A-Z]/.test(password)) strength += 12.5;
            if (/[0-9]/.test(password)) strength += 12.5;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
            
            // Set progress bar
            strengthBar.style.width = strength + '%';
            
            // Set color and text
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
    
    // Validasi form ganti password
    const formGantiPassword = document.getElementById('formGantiPassword');
    if (formGantiPassword) {
        formGantiPassword.addEventListener('submit', function(e) {
            const passwordBaru = document.getElementById('password_baru').value;
            const konfirmasi = document.getElementById('konfirmasi_password').value;
            
            if (passwordBaru !== konfirmasi) {
                e.preventDefault();
                Swal.fire({
                    title: 'Password tidak cocok!',
                    text: 'Password baru dan konfirmasi password harus sama',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
            if (passwordBaru.length < 6) {
                e.preventDefault();
                Swal.fire({
                    title: 'Password terlalu pendek!',
                    text: 'Password baru minimal 6 karakter',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
        });
    }
    
    // Animasi saat modal dibuka
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            modal.addEventListener('show.bs.modal', function() {
                this.classList.add('animate__animated', 'animate__fadeIn');
            });
        });
        
        // Tambahkan event listener untuk update avatar real-time
        const namaInput = document.getElementById('inputNama');
        if (namaInput) {
            namaInput.addEventListener('input', updateAvatarPreview);
        }
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>