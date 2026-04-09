<?php
require_once 'config/database.php';
$db = new Database();

echo "<h2>Fix Foto Galeri - AssetCare</h2>";
echo "<pre>";

// 1. Buat folder galeri jika belum ada
$galeri_dir = 'assets/uploads/galeri/';
if (!is_dir($galeri_dir)) {
    mkdir($galeri_dir, 0777, true);
    echo "✓ Folder galeri dibuat\n";
}

// 2. Ambil semua data dari tabel galeri
$result = $db->query("SELECT * FROM galeri");
$galeri_data = $result->fetch_all(MYSQLI_ASSOC);

if (empty($galeri_data)) {
    echo "\n⚠️ Belum ada data di tabel galeri.\n";
    echo "Pastikan admin sudah mengubah status pengaduan menjadi Selesai dan upload foto.\n";
} else {
    echo "\nMenemukan " . count($galeri_data) . " data galeri\n";
    echo str_repeat("-", 50) . "\n";
    
    $fixed = 0;
    foreach ($galeri_data as $g) {
        echo "\n📁 ID: " . $g['id'] . " - " . $g['judul'] . "\n";
        
        // Copy foto_before ke folder galeri
        $foto_before_source = 'assets/uploads/' . $g['foto_before'];
        $foto_before_dest = 'assets/uploads/galeri/' . $g['foto_before'];
        
        if (file_exists($foto_before_source)) {
            if (!file_exists($foto_before_dest)) {
                copy($foto_before_source, $foto_before_dest);
                echo "   ✅ Copy foto before: " . $g['foto_before'] . "\n";
                $fixed++;
            } else {
                echo "   ⚠️ Foto before sudah ada\n";
            }
        } else {
            echo "   ❌ Foto before tidak ditemukan: " . $g['foto_before'] . "\n";
        }
        
        // Copy foto_after ke folder galeri (cek dari admin_foto dulu)
        $foto_after_source_admin = 'assets/uploads/admin_foto/' . $g['foto_after'];
        $foto_after_source_uploads = 'assets/uploads/' . $g['foto_after'];
        $foto_after_dest = 'assets/uploads/galeri/' . $g['foto_after'];
        
        if (file_exists($foto_after_source_admin)) {
            if (!file_exists($foto_after_dest)) {
                copy($foto_after_source_admin, $foto_after_dest);
                echo "   ✅ Copy foto after (dari admin_foto): " . $g['foto_after'] . "\n";
                $fixed++;
            } else {
                echo "   ⚠️ Foto after sudah ada\n";
            }
        } elseif (file_exists($foto_after_source_uploads)) {
            if (!file_exists($foto_after_dest)) {
                copy($foto_after_source_uploads, $foto_after_dest);
                echo "   ✅ Copy foto after (dari uploads): " . $g['foto_after'] . "\n";
                $fixed++;
            } else {
                echo "   ⚠️ Foto after sudah ada\n";
            }
        } else {
            echo "   ❌ Foto after tidak ditemukan: " . $g['foto_after'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
    echo "✅ Selesai! $fixed file foto disalin ke folder galeri.\n";
}

echo "</pre>";
echo "<br>";
echo "<a href='admin/galeri.php' style='background: #09637E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 Kembali ke Galeri Admin</a> ";
echo "<a href='user/galeri.php' style='background: #088395; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👁️ Lihat Galeri User</a>";
?>