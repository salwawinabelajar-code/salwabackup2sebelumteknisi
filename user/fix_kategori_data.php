<?php
require_once '../config/database.php';
$db = new Database();

// Ambil semua kategori valid
$valid = [];
$res = $db->query("SELECT TRIM(nama) as nama FROM kategori");
while ($row = $res->fetch_assoc()) {
    $valid[] = $row['nama'];
}

// Cari pengaduan dengan kategori NULL atau kosong, atau kategori tidak valid
$pengaduan = $db->query("SELECT id, user_id, kategori FROM pengaduan")->fetch_all(MYSQLI_ASSOC);
$updated = 0;
foreach ($pengaduan as $p) {
    $kategori_clean = trim($p['kategori']);
    if (empty($kategori_clean) || !in_array($kategori_clean, $valid)) {
        // Jika kosong atau tidak valid, kita bisa set ke NULL atau ke kategori default (misal 'Lainnya')
        // Tapi lebih baik set ke NULL agar user bisa edit
        $db->query("UPDATE pengaduan SET kategori = NULL WHERE id = '{$p['id']}'");
        $updated++;
    }
}
echo "Berhasil memperbarui $updated data pengaduan dengan kategori bermasalah.";