<?php
// index.php (di folder root)
require_once 'config/database.php';
$db = new Database();

// Ambil data statistik realtime
$stats = [
    'total' => 0,
    'menunggu' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

$result = $db->query("SELECT 
    COUNT(*) as total,
    SUM(status = 'Menunggu') as menunggu,
    SUM(status = 'Diproses') as diproses,
    SUM(status = 'Selesai') as selesai,
    SUM(status = 'Ditolak') as ditolak
    FROM pengaduan");
if ($result) {
    $stats = $result->fetch_assoc();
}

// Ambil data laporan riwayat perbaikan dari tabel galeri
$riwayat_perbaikan = [];
$query = "SELECT g.*, 
          u.nama as uploader_nama,
          COALESCE(AVG(r.rating), 0) as avg_rating, 
          COUNT(r.id) as total_rating
          FROM galeri g
          LEFT JOIN users u ON g.user_id = u.id
          LEFT JOIN rating_galeri r ON g.id = r.galeri_id
          WHERE g.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY g.id
          ORDER BY g.created_at DESC
          LIMIT 4";
$result = $db->query($query);
if ($result) {
    $riwayat_perbaikan = $result->fetch_all(MYSQLI_ASSOC);
}

// Ambil data kontak dari tabel settings
$kontak_default = [
    'telepon' => '(021) 1234-5678',
    'email' => 'helpdesk@assetcare.com',
    'alamat' => 'Gedung Utama Lt. 3, Ruang IT Support',
    'jam_kerja' => 'Senin-Jumat, 08:00-17:00 WIB'
];

$kontak = [];
$result = $db->query("SELECT setting_key, setting_value FROM settings");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kontak[$row['setting_key']] = $row['setting_value'];
    }
} else {
    $kontak = $kontak_default;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssetCare - Sistem Pengaduan Sarana & Prasarana Kantor</title>
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
        
        /* Navbar */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%) !important;
            box-shadow: 0 4px 20px rgba(9, 99, 126, 0.15);
            padding: 15px 0;
            transition: padding 0.3s ease;
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
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        
        .navbar-custom .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .navbar-custom .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        
        /* Style untuk tombol login */
        .btn-login {
            background: transparent !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 8px;
            padding: 8px 20px !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            margin: 0 5px;
        }
        
        .btn-login:hover {
            background: white !important;
            color: var(--primary) !important;
            border-color: white !important;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
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
        
        .hero-section .lead {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        /* Card Statistik */
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
        
        /* Button Custom */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(9, 99, 126, 0.3);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            background: transparent;
            padding: 10px 23px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        /* Section Title */
        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--accent);
        }
        
        /* Card untuk fitur */
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #eee;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(9, 99, 126, 0.1);
            border-color: var(--accent);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 2rem;
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .feature-card p {
            color: #666;
            margin-bottom: 0;
            line-height: 1.6;
        }
        
        /* Card untuk aksi cepat */
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
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }
        
        .aksi-cepat-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .aksi-cepat-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        /* Card Riwayat Perbaikan */
        .riwayat-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .riwayat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(9, 99, 126, 0.15);
        }
        
        .riwayat-img-container {
            display: flex;
            height: 140px;
            position: relative;
        }
        
        .riwayat-img {
            width: 50%;
            object-fit: cover;
        }
        
        .riwayat-label {
            position: absolute;
            bottom: 5px;
            left: 20%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        
        .riwayat-label.after {
            left: 70%;
        }
        
        .riwayat-content {
            padding: 12px;
        }
        
        .riwayat-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 0.8rem;
        }
        
        .rating-stars i {
            margin-right: 2px;
        }
        
        /* Card Kontak */
        .contact-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #eee;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(9, 99, 126, 0.1);
        }
        
        .contact-icon-large {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .contact-card h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .contact-card p {
            color: #666;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .contact-card small {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* Footer sederhana */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            margin-top: 50px;
            border-radius: 30px 30px 0 0;
        }
        
        .footer p {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 20px;
                text-align: center;
            }
            
            .hero-section h1 {
                font-size: 1.8rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .navbar-custom .nav-link {
                margin: 5px 0;
                text-align: center;
            }
            
            .navbar-collapse {
                background: rgba(9, 99, 126, 0.98);
                padding: 15px;
                border-radius: 10px;
                margin-top: 10px;
            }
            
            .btn-login {
                display: block;
                text-align: center;
                margin: 5px 0;
            }
            
            .riwayat-img-container {
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cube me-2"></i>AssetCare
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon">
                    <i class="fas fa-bars text-white"></i>
                </span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#statistik">Statistik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fitur">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#riwayat">Riwayat Perbaikan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                </ul>
                
                <div class="d-flex ms-lg-3 mt-3 mt-lg-0">
                    <a href="auth/login.php" class="btn-login nav-link">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <section id="home" class="hero-section animate__animated animate__fadeIn">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5">Selamat Datang di AssetCare</h1>
                    <p class="lead mb-4">Sistem pengaduan sarana dan prasarana kantor yang memudahkan Anda melaporkan kerusakan dan memantau status penanganannya.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="auth/login.php" class="btn-primary-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                        </a>
                        <a href="#fitur" class="btn-outline-custom">
                            <i class="fas fa-play me-2"></i>Lihat Fitur
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="fas fa-building fa-10x opacity-25"></i>
                </div>
            </div>
        </section>

        <!-- Statistik Real-time -->
        <section id="statistik" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-chart-pie me-2"></i>Statistik Pengaduan
            </h2>
            <div class="row">
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon text-primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h2 class="stat-number text-primary"><?php echo $stats['total'] ?? 0; ?></h2>
                            <p class="text-muted mb-0">Total</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h2 class="stat-number text-warning"><?php echo $stats['menunggu'] ?? 0; ?></h2>
                            <p class="text-muted mb-0">Menunggu</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon text-info">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h2 class="stat-number text-info"><?php echo $stats['diproses'] ?? 0; ?></h2>
                            <p class="text-muted mb-0">Diproses</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2 class="stat-number text-success"><?php echo $stats['selesai'] ?? 0; ?></h2>
                            <p class="text-muted mb-0">Selesai</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon text-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h2 class="stat-number text-danger"><?php echo $stats['ditolak'] ?? 0; ?></h2>
                            <p class="text-muted mb-0">Ditolak</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4">
                    <div class="card card-stat animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon" style="color: var(--accent);">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h2 class="stat-number" style="color: var(--accent);">
                                <?php echo ($stats['total'] > 0) ? round(($stats['selesai'] / $stats['total']) * 100) : 0; ?>%
                            </h2>
                            <p class="text-muted mb-0">Terselesaikan</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fitur Unggulan -->
        <section id="fitur" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-star me-2"></i>Fitur Unggulan
            </h2>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3>Buat Pengaduan</h3>
                        <p>Laporkan kerusakan atau masalah dengan mudah. Isi formulir dan upload foto pendukung.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3>Riwayat Pengaduan</h3>
                        <p>Pantau semua pengaduan yang telah dibuat dengan status real-time.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Riwayat Perbaikan</h3>
                        <p>Lihat dokumentasi hasil perbaikan dan berikan rating untuk evaluasi kualitas layanan.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Profil Pengguna</h3>
                        <p>Kelola data profil dan pengaturan akun dengan mudah dan aman.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3>Pusat Bantuan</h3>
                        <p>Temukan panduan penggunaan dan informasi kontak untuk bantuan lebih lanjut.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Dukungan 24/7</h3>
                        <p>Tim support siap membantu melalui telepon, email, atau datang langsung.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Aksi Cepat -->
        <section class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-rocket me-2"></i>Aksi Cepat
            </h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="aksi-cepat-card">
                        <div class="aksi-cepat-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <h4 class="aksi-cepat-title">Login Akun</h4>
                        <p class="aksi-cepat-desc">Login untuk mulai membuat pengaduan</p>
                        <a href="auth/login.php" class="btn btn-primary-custom w-100">
                            Login Sekarang
                        </a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="aksi-cepat-card">
                        <div class="aksi-cepat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4 class="aksi-cepat-title">Lihat Riwayat Perbaikan</h4>
                        <p class="aksi-cepat-desc">Jelajahi dokumentasi hasil perbaikan</p>
                        <a href="#riwayat" class="btn btn-outline-custom w-100" style="border: 2px solid var(--primary); color: var(--primary); background: transparent;">
                            Lihat Riwayat
                        </a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="aksi-cepat-card">
                        <div class="aksi-cepat-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="aksi-cepat-title">Hubungi Kami</h4>
                        <p class="aksi-cepat-desc">Butuh bantuan? Tim kami siap membantu</p>
                        <a href="#kontak" class="btn btn-outline-custom w-100" style="border: 2px solid var(--primary); color: var(--primary); background: transparent;">
                            Kontak Kami
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Laporan Riwayat Perbaikan -->
        <?php if (!empty($riwayat_perbaikan)): ?>
        <section id="riwayat" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-tools me-2"></i>Laporan Riwayat Perbaikan
            </h2>
            
            <div class="row g-4">
                <?php foreach ($riwayat_perbaikan as $item): ?>
                <div class="col-md-3">
                    <div class="riwayat-card">
                        <div class="riwayat-img-container">
                            <img src="assets/uploads/galeri/<?php echo $item['foto_before']; ?>" 
                                 class="riwayat-img" 
                                 onerror="this.src='assets/images/no-image.jpg'"
                                 alt="Sebelum">
                            <img src="assets/uploads/galeri/<?php echo $item['foto_after']; ?>" 
                                 class="riwayat-img" 
                                 onerror="this.src='assets/images/no-image.jpg'"
                                 alt="Sesudah">
                            <span class="riwayat-label">Sebelum</span>
                            <span class="riwayat-label after">Sesudah</span>
                        </div>
                        <div class="riwayat-content">
                            <div class="riwayat-title" title="<?php echo htmlspecialchars($item['judul']); ?>">
                                <?php echo htmlspecialchars(substr($item['judul'], 0, 30)) . (strlen($item['judul']) > 30 ? '...' : ''); ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rating-stars">
                                    <?php 
                                    $avg = round($item['avg_rating'] * 2) / 2;
                                    for ($i=1; $i<=5; $i++):
                                        if ($i <= floor($avg)) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $avg) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    endfor;
                                    ?>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-star text-warning me-1" style="font-size: 0.7rem;"></i>
                                    <?php echo $item['total_rating']; ?>
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['uploader_nama'] ?? 'Admin'); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('d/m', strtotime($item['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="auth/login.php" class="btn btn-primary-custom">
                    <i class="fas fa-sign-in-alt me-2"></i>Login untuk Lihat Selengkapnya
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Kontak -->
        <section id="kontak" class="mb-5">
            <h2 class="section-title">
                <i class="fas fa-headset me-2"></i>Hubungi Kami
            </h2>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="contact-card text-center">
                        <div class="contact-icon-large mx-auto">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4>Telepon</h4>
                        <p><?php echo htmlspecialchars($kontak['telepon'] ?? $kontak_default['telepon']); ?></p>
                        <small><?php echo htmlspecialchars($kontak['jam_kerja'] ?? $kontak_default['jam_kerja']); ?></small>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="contact-card text-center">
                        <div class="contact-icon-large mx-auto">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email</h4>
                        <p><?php echo htmlspecialchars($kontak['email'] ?? $kontak_default['email']); ?></p>
                        <small>Respon 1-2 jam kerja</small>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="contact-card text-center">
                        <div class="contact-icon-large mx-auto">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Alamat</h4>
                        <p><?php echo htmlspecialchars($kontak['alamat'] ?? $kontak_default['alamat']); ?></p>
                        <small>Ruang IT Support</small>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="contact-card text-center">
                        <div class="contact-icon-large mx-auto">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Jam Kerja</h4>
                        <p><?php echo htmlspecialchars($kontak['jam_kerja'] ?? $kontak_default['jam_kerja']); ?></p>
                        <small>Senin - Jumat</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include 'user/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 100) {
                navbar.style.padding = '10px 0';
            } else {
                navbar.style.padding = '15px 0';
            }
        });

        // Add active class to nav items on scroll
        const sections = document.querySelectorAll('section[id]');
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            document.querySelectorAll('.navbar-custom .nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>