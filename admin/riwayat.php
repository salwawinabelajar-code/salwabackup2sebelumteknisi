<?php
require_once '../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

// Get filter values
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'custom';
$start_date = isset($_GET['start_date']) ? $db->escape_string($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? $db->escape_string($_GET['end_date']) : '';
$status_filter = isset($_GET['status']) ? $db->escape_string($_GET['status']) : '';
$kategori_filter = isset($_GET['kategori']) ? $db->escape_string($_GET['kategori']) : '';
$search = isset($_GET['search']) ? $db->escape_string($_GET['search']) : '';

// Jika periode 'all', kosongkan tanggal agar tidak dipakai di query
if ($periode == 'all') {
    $start_date = '';
    $end_date = '';
}

// Set default tanggal jika periode bukan 'all' dan tanggal belum diisi (misal pertama kali buka halaman)
if ($periode != 'all' && empty($start_date) && empty($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');
}

// Build query
$query = "SELECT p.*, u.nama as nama_user 
          FROM pengaduan p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE 1=1";

if ($periode != 'all' && !empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'";
}
if ($status_filter) {
    $query .= " AND p.status = '$status_filter'";
}
if ($kategori_filter) {
    $query .= " AND p.kategori = '$kategori_filter'";
}
if ($search) {
    $query .= " AND (p.judul LIKE '%$search%' OR p.deskripsi LIKE '%$search%' OR u.nama LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";
$result = $db->query($query);
$riwayat = $result->fetch_all(MYSQLI_ASSOC);

// ========== AMBIL KATEGORI DARI DATABASE ==========
$kategori_result = $db->query("SELECT nama FROM kategori ORDER BY nama");
$kategori_list = [];
if ($kategori_result && $kategori_result->num_rows > 0) {
    while ($row = $kategori_result->fetch_assoc()) {
        $kategori_list[] = $row['nama'];
    }
}
// ========== END AMBIL KATEGORI ==========

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="riwayat_pengaduan_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th { background-color: #09637E; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }';
    echo 'td { padding: 8px; border: 1px solid #ddd; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>Riwayat Pengaduan AssetCare</h2>';
    echo '<p>Tanggal Export: ' . date('d F Y H:i:s') . '</p>';
    
    if ($periode == 'all') {
        echo '<p>Periode: Semua Data</p>';
    } else {
        echo '<p>Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>';
    }
    echo '<p>Filter Status: ' . ($status_filter ?: 'Semua') . '</p>';
    echo '<p>Filter Kategori: ' . ($kategori_filter ?: 'Semua') . '</p>';
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Tanggal</th>';
    echo '<th>Pelapor</th>';
    echo '<th>Judul</th>';
    echo '<th>Kategori</th>';
    echo '<th>Prioritas</th>';
    echo '<th>Status</th>';
    echo '<th>Deskripsi</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($riwayat as $item) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($item['tanggal_kejadian'])) . '</td>';
        echo '<td>' . htmlspecialchars($item['nama_user']) . '</td>';
        echo '<td>' . htmlspecialchars($item['judul']) . '</td>';
        echo '<td>' . $item['kategori'] . '</td>';
        echo '<td>' . $item['prioritas'] . '</td>';
        echo '<td>' . $item['status'] . '</td>';
        echo '<td>' . htmlspecialchars($item['deskripsi']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit();
}

// ========== FITUR PRINT LAPORAN ==========
if (isset($_GET['print']) && $_GET['print'] == 'true') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Riwayat Pengaduan - AssetCare</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 20px;
                background: white;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #09637E;
                padding-bottom: 15px;
            }
            .header h1 {
                color: #09637E;
                font-size: 24px;
                margin-bottom: 5px;
            }
            .header p {
                color: #666;
                font-size: 14px;
            }
            .filter-info {
                background: #f5f5f5;
                padding: 12px;
                margin-bottom: 20px;
                border-radius: 8px;
                font-size: 13px;
            }
            .filter-info table {
                width: 100%;
                border-collapse: collapse;
            }
            .filter-info td {
                padding: 4px 8px;
            }
            .filter-info td:first-child {
                font-weight: bold;
                width: 120px;
            }
            table.data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                font-size: 12px;
            }
            table.data-table th {
                background: #09637E;
                color: white;
                padding: 10px 8px;
                text-align: center;
                border: 1px solid #ddd;
            }
            table.data-table td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: left;
                vertical-align: top;
            }
            table.data-table td.center {
                text-align: center;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 11px;
                color: #999;
                border-top: 1px solid #eee;
                padding-top: 15px;
            }
            .badge-status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: bold;
            }
            .badge-menunggu { background: #ffc107; color: #212529; }
            .badge-diproses { background: #0dcaf0; color: white; }
            .badge-selesai { background: #198754; color: white; }
            .badge-ditolak { background: #dc3545; color: white; }
            @media print {
                body {
                    padding: 0;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>LAPORAN RIWAYAT PENGADUAN</h1>
            <p>AssetCare - Sistem Manajemen Pengaduan Aset</p>
            <p>Tanggal Cetak: <?php echo date('d F Y H:i:s'); ?></p>
        </div>
        
        <div class="filter-info">
            <table>
                <tr>
                    <td>Periode:</td>
                    <td>
                        <?php 
                        if ($periode == 'all') {
                            echo 'Semua Data';
                        } else {
                            echo date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date));
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><?php echo $status_filter ?: 'Semua'; ?></td>
                </tr>
                <tr>
                    <td>Kategori:</td>
                    <td><?php echo $kategori_filter ?: 'Semua'; ?></td>
                </tr>
                <?php if ($search): ?>
                <tr>
                    <td>Pencarian:</td>
                    <td><?php echo htmlspecialchars($search); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Total Data:</td>
                    <td><strong><?php echo count($riwayat); ?> pengaduan</strong></td>
                </tr>
            </table>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="80">Tgl Kejadian</th>
                    <th width="120">Pelapor</th>
                    <th width="200">Judul</th>
                    <th width="100">Kategori</th>
                    <th width="70">Prioritas</th>
                    <th width="80">Status</th>
                    <th width="250">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Tidak ada data riwayat pengaduan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($riwayat as $index => $r): ?>
                    <tr>
                        <td class="center"><?php echo $index + 1; ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($r['tanggal_kejadian'])); ?></td>
                        <td><?php echo htmlspecialchars($r['nama_user']); ?></td>
                        <td><strong><?php echo htmlspecialchars($r['judul']); ?></strong></td>
                        <td><?php echo $r['kategori']; ?></td>
                        <td class="center"><?php echo $r['prioritas']; ?></td>
                        <td class="center">
                            <span class="badge-status badge-<?php echo strtolower($r['status']); ?>">
                                <?php echo $r['status']; ?>
                            </span>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars(substr($r['deskripsi'], 0, 150))); ?><?php echo strlen($r['deskripsi']) > 150 ? '...' : ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Dicetak melalui sistem AssetCare | Laporan ini bersifat resmi dan dapat dipertanggungjawabkan</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}
// ========== END FITUR PRINT LAPORAN ==========
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Riwayat Pengaduan - AssetCare Admin</title>
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
        
        /* Top Navbar Style - Konsisten dengan index.php */
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
            padding: 25px;
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
        
        .summary-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .export-btn {
            background: #28a745;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            color: white;
        }
        
        .print-btn {
            background: #17a2b8;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .print-btn:hover {
            background: #138496;
            transform: translateY(-2px);
            color: white;
        }
        
        @media (max-width: 1200px) {
            .main-content { padding: 15px 20px; }
        }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-top: 56px; padding: 15px; }
            .top-navbar { flex-direction: column; text-align: center; }
            .page-title { text-align: center; }
            .filter-card .row .col-md-3, .filter-card .row .col-md-4 { margin-bottom: 10px; }
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 12px; }
            .summary-card { margin-bottom: 15px; }
            .table th, .table td { padding: 8px 10px; font-size: 0.85rem; }
            .export-btn, .print-btn { padding: 8px 15px; font-size: 0.85rem; }
            .profile-avatar { width: 40px; height: 40px; font-size: 1rem; }
            .d-flex.gap-2 { gap: 8px !important; }
        }
        
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .top-navbar h4 { font-size: 1.2rem; }
            .badge-status { padding: 3px 8px; font-size: 0.7rem; }
            .btn-sm { padding: 4px 8px; font-size: 0.7rem; }
            .table-responsive { font-size: 0.75rem; }
            .export-btn, .print-btn { padding: 6px 12px; font-size: 0.75rem; }
            .d-flex.gap-2 { flex-direction: column; width: 100%; }
            .d-flex.gap-2 .btn, .d-flex.gap-2 a { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <h4><i class="fas fa-history me-2"></i>Riwayat Pengaduan</h4>
                <p>Lihat dan kelola riwayat seluruh pengaduan</p>
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

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="summary-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="summary-number"><?php echo count($riwayat); ?></div>
                            <div>Total Pengaduan</div>
                        </div>
                        <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="summary-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffdb6d 100%); color: #212529;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="summary-number">
                                <?php
                                $menunggu = array_filter($riwayat, function($r) {
                                    return $r['status'] == 'Menunggu';
                                });
                                echo count($menunggu);
                                ?>
                            </div>
                            <div>Menunggu</div>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="summary-card" style="background: linear-gradient(135deg, #0dcaf0 0%, #6edff6 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="summary-number">
                                <?php
                                $diproses = array_filter($riwayat, function($r) {
                                    return $r['status'] == 'Diproses';
                                });
                                echo count($diproses);
                                ?>
                            </div>
                            <div>Diproses</div>
                        </div>
                        <i class="fas fa-cogs fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="summary-card" style="background: linear-gradient(135deg, #198754 0%, #4caf50 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="summary-number">
                                <?php
                                $selesai = array_filter($riwayat, function($r) {
                                    return $r['status'] == 'Selesai';
                                });
                                echo count($selesai);
                                ?>
                            </div>
                            <div>Selesai</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <h5 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Riwayat</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" 
                           value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" name="end_date" id="end_date" 
                           value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select class="form-select" name="periode" id="periodeSelect">
                        <option value="custom" <?php echo $periode == 'custom' ? 'selected' : ''; ?>>Kustom</option>
                        <option value="today" <?php echo $periode == 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                        <option value="week" <?php echo $periode == 'week' ? 'selected' : ''; ?>>Minggu Ini</option>
                        <option value="month" <?php echo $periode == 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                        <option value="year" <?php echo $periode == 'year' ? 'selected' : ''; ?>>Tahun Ini</option>
                        <option value="all" <?php echo $periode == 'all' ? 'selected' : ''; ?>>Semua Data</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="Menunggu" <?php echo $status_filter == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Diproses" <?php echo $status_filter == 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                        <option value="Selesai" <?php echo $status_filter == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="Ditolak" <?php echo $status_filter == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?php echo htmlspecialchars($kategori); ?>" <?php echo $kategori_filter == $kategori ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Cari judul, deskripsi, atau nama pelapor..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="riwayat.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Export & Print Options -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="mb-0">Data Riwayat Pengaduan</h5>
                <small class="text-muted">
                    <?php
                    if ($periode == 'all') {
                        echo 'Periode: Semua Data';
                    } else {
                        echo 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
                    }
                    ?>
                    <?php if ($status_filter): ?> | Status: <?php echo $status_filter; ?><?php endif; ?>
                    <?php if ($kategori_filter): ?> | Kategori: <?php echo $kategori_filter; ?><?php endif; ?>
                </small>
            </div>
            
            <div class="d-flex gap-2">
                <a href="riwayat.php?print=true&<?php echo http_build_query($_GET); ?>" 
                   class="print-btn" target="_blank">
                    <i class="fas fa-print me-2"></i> Print Laporan
                </a>
                <a href="riwayat.php?export=excel&<?php echo http_build_query($_GET); ?>" 
                   class="export-btn">
                    <i class="fas fa-file-excel me-2"></i> Export Excel
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Pelapor</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <h5>Tidak ada data riwayat</h5>
                                    <p class="mb-0">Coba gunakan filter yang berbeda</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $index => $r): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($r['tanggal_kejadian'])); ?><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($r['created_at'])); ?></small>
                                    </td>
                                    <td><?php echo $r['nama_user']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['judul']); ?></strong><br>
                                        <small class="text-muted"><?php echo substr($r['deskripsi'], 0, 50); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo $r['kategori']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priority_color = '';
                                        switch($r['prioritas']) {
                                            case 'Tinggi': $priority_color = 'danger'; break;
                                            case 'Sedang': $priority_color = 'warning'; break;
                                            case 'Rendah': $priority_color = 'success'; break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $priority_color; ?>">
                                            <?php echo $r['prioritas']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-<?php echo strtolower($r['status']); ?>">
                                            <?php echo $r['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="detail_pengaduan.php?id=<?php echo $r['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination (optional) -->
        <?php if (count($riwayat) > 0): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Menampilkan <?php echo count($riwayat); ?> data</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk mengatur tanggal berdasarkan periode yang dipilih
        function setDatesFromPeriode(periode) {
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const today = new Date();
            let startDate, endDate;

            function formatDate(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            startInput.disabled = false;
            endInput.disabled = false;

            switch (periode) {
                case 'today':
                    startDate = endDate = today;
                    break;
                case 'week': {
                    const day = today.getDay();
                    const diffToMonday = (day === 0 ? 6 : day - 1);
                    const monday = new Date(today);
                    monday.setDate(today.getDate() - diffToMonday);
                    startDate = monday;
                    endDate = today;
                    break;
                }
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = today;
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = today;
                    break;
                case 'all':
                    startInput.value = '';
                    endInput.value = '';
                    return;
                default:
                    return;
            }

            startInput.value = formatDate(startDate);
            endInput.value = formatDate(endDate);
        }

        document.getElementById('periodeSelect').addEventListener('change', function(e) {
            setDatesFromPeriode(this.value);
        });

        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('periodeSelect').value = 'custom';
        });
        document.getElementById('end_date').addEventListener('change', function() {
            document.getElementById('periodeSelect').value = 'custom';
        });

        document.addEventListener('DOMContentLoaded', function() {
            const periodeSelect = document.getElementById('periodeSelect');
            setDatesFromPeriode(periodeSelect.value);
        });
    </script>
</body>
</html>