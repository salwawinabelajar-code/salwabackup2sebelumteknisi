<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top d-lg-none">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">AssetCare Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarMenu" data-bs-backdrop="false">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white">AssetCare Admin</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="text-center mb-4">
            <h3 class="brand-text">AssetCare</h3>
            <small class="text-white-50">Admin Panel</small>
        </div>
        
        <ul class="nav flex-column flex-grow-1">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-home"></i> <span>Beranda</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'pengaduan.php' ? 'active' : ''; ?>" href="pengaduan.php">
                    <i class="fas fa-clipboard-list"></i> <span>Manajemen Pengaduan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'galeri.php' ? 'active' : ''; ?>" href="galeri.php">
                    <i class="fas fa-images"></i> <span>Laporan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'user.php' ? 'active' : ''; ?>" href="user.php">
                    <i class="fas fa-users"></i> <span>Manajemen User</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>" href="riwayat.php">
                    <i class="fas fa-history"></i> <span>Riwayat Pengaduan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'pengaturan.php' ? 'active' : ''; ?>" href="pengaturan.php">
                    <i class="fas fa-cog"></i> <span>Pengaturan</span>
                </a>
            </li>
        </ul>
        
        <div class="mt-auto pt-3 border-top border-white-10">
            <a href="profil.php" class="nav-link <?php echo $current_page == 'profil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> <span>Profil</span>
            </a>
            <a href="../auth/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> <span>Keluar</span>
            </a>
        </div>
    </div>
</div>

<style>
    .sidebar { 
        background: linear-gradient(180deg, #09637E 0%, #088395 100%); 
        color: white; 
        width: 280px;
        transition: all 0.3s;
    }
    .sidebar .offcanvas-header { 
        background: rgba(0,0,0,0.1); 
        border-bottom: 1px solid rgba(255,255,255,0.1); 
    }
    .sidebar .offcanvas-title { font-weight: 700; }
    .sidebar .nav-link { 
        color: rgba(255,255,255,0.8); 
        padding: 12px 20px; 
        margin: 5px 0; 
        border-radius: 8px; 
        transition: all 0.3s; 
        font-weight: 500; 
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { 
        background: rgba(255,255,255,0.15); 
        color: white; 
    }
    .sidebar .nav-link i { 
        width: 25px; 
        font-size: 1.1rem; 
    }
    .brand-text { font-weight: 700; margin-bottom: 0; }
    
    @media (min-width: 992px) {
        .offcanvas.sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            bottom: 0; 
            transform: none !important; 
            visibility: visible !important; 
            z-index: 1000; 
        }
        .offcanvas-backdrop { display: none; }
        .offcanvas.sidebar .offcanvas-header .btn-close { display: none; }
        .main-content { margin-left: 280px; margin-top: 0; padding-top: 20px; }
        body { padding-top: 0; }
    }
    
    @media (max-width: 991.98px) {
        .sidebar { width: 300px; }
        .main-content { margin-left: 0; margin-top: 56px; }
        .sidebar .offcanvas-header .btn-close { display: block; }
        .navbar-dark.bg-dark { background: linear-gradient(135deg, #09637E 0%, #088395 100%) !important; }
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 280px; }
        .sidebar .nav-link { padding: 10px 16px; font-size: 0.9rem; }
        .sidebar .nav-link i { width: 22px; font-size: 1rem; }
        .brand-text { font-size: 1.2rem; }
    }
    
    @media (max-width: 576px) {
        .sidebar { width: 260px; }
        .sidebar .nav-link { padding: 8px 14px; font-size: 0.85rem; }
    }
</style>