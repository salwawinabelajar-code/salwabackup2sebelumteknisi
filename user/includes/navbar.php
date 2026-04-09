<?php
// File: user/includes/navbar.php
// Pastikan session sudah dimulai di file yang meng-include ini
if (!isset($_SESSION['user_id'])) {
    // Jika tidak login, jangan tampilkan navbar (redirect atau abort)
    return;
}
$user_name = $_SESSION['nama'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-cube me-2"></i>AssetCare</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"><i class="fas fa-bars text-white"></i></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php"><i class="fas fa-home me-1"></i> Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#buatPengaduanModal"><i class="fas fa-plus-circle me-1"></i> Buat Pengaduan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>" href="riwayat.php"><i class="fas fa-history me-1"></i> Riwayat</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'galeri.php' ? 'active' : ''; ?>" href="galeri.php"><i class="fas fa-images me-1"></i> Laporan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'bantuan.php' ? 'active' : ''; ?>" href="bantuan.php"><i class="fas fa-question-circle me-1"></i> Bantuan</a>
                </li>
            </ul>
            <!-- Avatar (link ke profil) & Logout terpisah -->
            <div class="d-flex align-items-center mt-3 mt-lg-0 ms-lg-3">
                <a href="profil.php" class="d-flex align-items-center text-decoration-none me-2">
                    <div class="profile-avatar me-2"><?php echo generateInitials($user_name); ?></div>
                    <div class="text-white d-none d-lg-block">
                        <span class="fw-bold"><?php echo htmlspecialchars($user_name); ?></span><br>
                        <small class="opacity-75"><?php echo ucfirst($user_role); ?></small>
                    </div>
                </a>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm" title="Keluar">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- CSS khusus navbar -->
<style>
    .navbar-custom {
        background: linear-gradient(135deg, #09637E 0%, #088395 100%) !important;
        box-shadow: 0 4px 20px rgba(9,99,126,0.15);
        padding: 12px 0;
    }
    .navbar-custom .navbar-brand {
        color: white !important;
        font-weight: 700;
        font-size: 1.8rem;
    }
    .navbar-custom .nav-link {
        color: rgba(255,255,255,0.9) !important;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s;
        margin: 0 4px;
    }
    .navbar-custom .nav-link:hover,
    .navbar-custom .nav-link.active {
        background: rgba(255,255,255,0.15);
        color: white !important;
        transform: translateY(-2px);
    }
    .profile-avatar {
        width: 45px; height: 45px;
        background: white;
        color: #09637E;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        border: 3px solid rgba(255,255,255,0.3);
        transition: 0.3s;
    }
    .profile-avatar:hover {
        transform: scale(1.1);
        border-color: white;
    }
    .btn-outline-light {
        border-color: rgba(255,255,255,0.5);
    }
    .btn-outline-light:hover {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    @media (max-width: 991.98px) {
        .navbar-custom .navbar-collapse {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            margin-top: 15px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .navbar-custom .nav-link {
            padding: 12px 15px !important;
            margin: 3px 0;
            text-align: left;
        }
        .navbar-custom .nav-link i {
            width: 25px;
        }
        .d-flex.align-items-center {
            width: 100%;
            justify-content: space-between;
        }
        .d-flex.align-items-center a:first-child {
            flex-grow: 1;
        }
    }
    @media (max-width: 576px) {
        .navbar-custom .navbar-brand {
            font-size: 1.3rem;
        }
        .profile-avatar {
            width: 40px; height: 40px;
            font-size: 1rem;
        }
    }
</style>