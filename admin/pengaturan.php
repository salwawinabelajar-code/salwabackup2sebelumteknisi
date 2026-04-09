<?php
require_once '../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$error = '';
$success = '';

// Handle kategori update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tambah kategori
    if (isset($_POST['tambah_kategori'])) {
        $kategori = $db->escape_string(trim($_POST['nama_kategori']));
        
        if (empty($kategori)) {
            $error = "Nama kategori tidak boleh kosong!";
        } else {
            // Cek apakah kategori sudah ada
            $check = $db->query("SELECT id FROM kategori WHERE nama = '$kategori'");
            if ($check->num_rows > 0) {
                $error = "Kategori sudah ada!";
            } else {
                $query = "INSERT INTO kategori (nama) VALUES ('$kategori')";
                if ($db->conn->query($query)) {
                    $_SESSION['success'] = "Kategori berhasil ditambahkan!";
                    redirect('pengaturan.php');
                } else {
                    $error = "Gagal menambahkan kategori: " . $db->conn->error;
                }
            }
        }
    }
    
    // Edit kategori
    if (isset($_POST['edit_kategori'])) {
        $kategori_id = $db->escape_string($_POST['kategori_id']);
        $kategori_baru = $db->escape_string(trim($_POST['nama_kategori_edit']));
        
        if (empty($kategori_baru)) {
            $error = "Nama kategori tidak boleh kosong!";
        } else {
            // Cek apakah nama baru sudah ada (kecuali untuk ID yang sama)
            $check = $db->query("SELECT id FROM kategori WHERE nama = '$kategori_baru' AND id != '$kategori_id'");
            if ($check->num_rows > 0) {
                $error = "Nama kategori sudah digunakan!";
            } else {
                $query = "UPDATE kategori SET nama = '$kategori_baru' WHERE id = '$kategori_id'";
                if ($db->conn->query($query)) {
                    $_SESSION['success'] = "Kategori berhasil diubah!";
                    redirect('pengaturan.php');
                } else {
                    $error = "Gagal mengubah kategori: " . $db->conn->error;
                }
            }
        }
    }
    
    // Hapus kategori
    if (isset($_POST['hapus_kategori'])) {
        $kategori_id = $db->escape_string($_POST['kategori_id']);
        
        // Cek apakah kategori sedang digunakan di pengaduan
        $check = $db->query("SELECT id FROM pengaduan WHERE kategori = (SELECT nama FROM kategori WHERE id = '$kategori_id') LIMIT 1");
        if ($check->num_rows > 0) {
            $error = "Kategori tidak bisa dihapus karena sedang digunakan pada pengaduan!";
        } else {
            $query = "DELETE FROM kategori WHERE id = '$kategori_id'";
            if ($db->conn->query($query)) {
                $_SESSION['success'] = "Kategori berhasil dihapus!";
                redirect('pengaturan.php');
            } else {
                $error = "Gagal menghapus kategori: " . $db->conn->error;
            }
        }
    }
    
    if (isset($_POST['update_kontak'])) {
        $telepon = $db->escape_string($_POST['telepon']);
        $email = $db->escape_string($_POST['email']);
        $alamat = $db->escape_string($_POST['alamat']);
        $jam_kerja = $db->escape_string($_POST['jam_kerja']);
        $petugas1 = $db->escape_string($_POST['petugas1']);
        $petugas2 = $db->escape_string($_POST['petugas2']);
        
        $queries = [
            "INSERT INTO settings (setting_key, setting_value) VALUES ('telepon', '$telepon') ON DUPLICATE KEY UPDATE setting_value = '$telepon'",
            "INSERT INTO settings (setting_key, setting_value) VALUES ('email', '$email') ON DUPLICATE KEY UPDATE setting_value = '$email'",
            "INSERT INTO settings (setting_key, setting_value) VALUES ('alamat', '$alamat') ON DUPLICATE KEY UPDATE setting_value = '$alamat'",
            "INSERT INTO settings (setting_key, setting_value) VALUES ('jam_kerja', '$jam_kerja') ON DUPLICATE KEY UPDATE setting_value = '$jam_kerja'",
            "INSERT INTO settings (setting_key, setting_value) VALUES ('petugas1', '$petugas1') ON DUPLICATE KEY UPDATE setting_value = '$petugas1'",
            "INSERT INTO settings (setting_key, setting_value) VALUES ('petugas2', '$petugas2') ON DUPLICATE KEY UPDATE setting_value = '$petugas2'"
        ];
        
        $success_all = true;
        foreach ($queries as $q) {
            if (!$db->conn->query($q)) {
                $success_all = false;
                $error = "Gagal menyimpan data: " . $db->conn->error;
                break;
            }
        }
        
        if ($success_all) {
            $_SESSION['success'] = "Informasi kontak berhasil diperbarui!";
            redirect('pengaturan.php');
        }
    }
}

// Get kategori data
$kategori_result = $db->query("SELECT * FROM kategori ORDER BY nama");
$kategori_data = $kategori_result->fetch_all(MYSQLI_ASSOC);

// Ambil data kontak dari tabel settings
$kontak_default = [
    'telepon' => '(021) 1234-5678',
    'email' => 'helpdesk@assetcare.com',
    'alamat' => 'Gedung Utama Lt. 3, Ruang IT Support',
    'jam_kerja' => 'Senin-Jumat, 08:00-17:00 WIB',
    'petugas1' => 'Budi Santoso (Ext. 123)',
    'petugas2' => 'Siti Rahayu (Ext. 124)'
];

$kontak = [];
$result = $db->query("SELECT setting_key, setting_value FROM settings");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kontak[$row['setting_key']] = $row['setting_value'];
    }
} else {
    foreach ($kontak_default as $key => $value) {
        $db->conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
        $kontak[$key] = $value;
    }
}

$kontak_telepon = $kontak['telepon'];
$kontak_email = $kontak['email'];
$kontak_alamat = $kontak['alamat'];
$kontak_jam_kerja = $kontak['jam_kerja'];
$kontak_petugas1 = $kontak['petugas1'];
$kontak_petugas2 = $kontak['petugas2'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Pengaturan Sistem - AssetCare Admin</title>
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
        
        /* Top Navbar Style */
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
        
        .setting-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .kategori-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: var(--light);
            border-radius: 8px;
            border-left: 4px solid var(--accent);
        }
        
        .kontak-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .kontak-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .modal-edit-kategori .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-top: 56px; padding: 15px; }
            .top-navbar { flex-direction: column; text-align: center; }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .kontak-info { flex-direction: column; text-align: center; }
            .kontak-icon { margin-right: 0; margin-bottom: 10px; }
            .setting-card { padding: 15px; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .kategori-item { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <h4><i class="fas fa-cog me-2"></i>Pengaturan Sistem</h4>
                <p>Kelola kategori pengaduan dan informasi kontak bantuan</p>
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

        <!-- Pengaturan Kategori -->
        <div class="setting-card">
            <h5 class="mb-4"><i class="fas fa-tags me-2"></i>Pengaturan Kategori Pengaduan</h5>
            
            <!-- Form Tambah Kategori -->
            <form method="POST" class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label">Tambah Kategori Baru</label>
                    <input type="text" class="form-control" name="nama_kategori" 
                           placeholder="Masukkan nama kategori baru" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" name="tambah_kategori" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </button>
                </div>
            </form>
            
            <!-- Daftar Kategori -->
            <h6 class="mb-3">Daftar Kategori Tersedia</h6>
            <?php if (empty($kategori_data)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Belum ada kategori. Tambahkan kategori baru di atas.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($kategori_data as $kategori): ?>
                        <div class="col-md-6 mb-3">
                            <div class="kategori-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($kategori['nama']); ?></strong>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editKategoriModal"
                                            data-id="<?php echo $kategori['id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($kategori['nama']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="kategori_id" value="<?php echo $kategori['id']; ?>">
                                        <button type="submit" name="hapus_kategori" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Hapus kategori <?php echo htmlspecialchars($kategori['nama']); ?>? Pengaduan dengan kategori ini tidak akan terpengaruh.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informasi Kontak Bantuan -->
        <div class="setting-card">
            <h5 class="mb-4"><i class="fas fa-headset me-2"></i>Informasi Kontak Bantuan</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <strong>Telepon</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_telepon); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <strong>Email</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_email); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <strong>Alamat</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_alamat); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <strong>Jam Kerja</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_jam_kerja); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <strong>Petugas Teknis 1</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_petugas1); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="kontak-info">
                        <div class="kontak-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <strong>Petugas Teknis 2</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($kontak_petugas2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-outline-primary w-100" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#kontakForm">
                <i class="fas fa-edit me-2"></i> Edit Informasi Kontak
            </button>
            
            <div class="collapse mt-4" id="kontakForm">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="telepon" 
                                   value="<?php echo htmlspecialchars($kontak_telepon); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($kontak_email); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" required><?php echo htmlspecialchars($kontak_alamat); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Jam Kerja</label>
                            <input type="text" class="form-control" name="jam_kerja" 
                                   value="<?php echo htmlspecialchars($kontak_jam_kerja); ?>" required>
                            <small class="text-muted">Contoh: Senin-Jumat, 08:00-17:00 WIB</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Petugas Teknis 1</label>
                            <input type="text" class="form-control" name="petugas1" 
                                   value="<?php echo htmlspecialchars($kontak_petugas1); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Petugas Teknis 2</label>
                            <input type="text" class="form-control" name="petugas2" 
                                   value="<?php echo htmlspecialchars($kontak_petugas2); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_kontak" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informasi Sistem -->
        <div class="setting-card">
            <h5 class="mb-4"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="alert alert-light">
                        <h6><i class="fas fa-code me-2"></i> Versi Sistem</h6>
                        <p class="mb-0">AssetCare v1.0.0</p>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="alert alert-light">
                        <h6><i class="fas fa-calendar me-2"></i> Tahun Pengembangan</h6>
                        <p class="mb-0">2026</p>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="alert alert-light">
                        <h6><i class="fas fa-database me-2"></i> Status Database</h6>
                        <p class="mb-0">
                            <span class="badge bg-success">Connected</span>
                            <?php echo $db->conn->host_info; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="alert alert-light">
                        <h6><i class="fas fa-user-shield me-2"></i> Keamanan</h6>
                        <p class="mb-0">
                            <span class="badge bg-success">Active</span>
                            Role-based Access Control
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-lightbulb me-2"></i> Tips Penggunaan</h6>
                <ul class="mb-0">
                    <li>Pastikan informasi kontak selalu terupdate</li>
                    <li>Hapus kategori yang tidak digunakan</li>
                    <li>Backup database secara berkala</li>
                    <li>Monitor log aktivitas sistem</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div class="modal fade modal-edit-kategori" id="editKategoriModal" tabindex="-1" aria-labelledby="editKategoriModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editKategoriModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Kategori
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="kategori_id" id="edit_kategori_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="nama_kategori_edit" id="edit_kategori_nama" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_kategori" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal edit kategori
        const editKategoriModal = document.getElementById('editKategoriModal');
        if (editKategoriModal) {
            editKategoriModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const kategoriId = button.getAttribute('data-id');
                const kategoriNama = button.getAttribute('data-nama');
                
                const idInput = editKategoriModal.querySelector('#edit_kategori_id');
                const namaInput = editKategoriModal.querySelector('#edit_kategori_nama');
                
                idInput.value = kategoriId;
                namaInput.value = kategoriNama;
            });
        }
        
        // Auto-focus input when form is shown
        const kontakForm = document.getElementById('kontakForm');
        if (kontakForm) {
            kontakForm.addEventListener('shown.bs.collapse', function() {
                const input = this.querySelector('input[name="telepon"]');
                if (input) input.focus();
            });
        }
    </script>
</body>
</html>