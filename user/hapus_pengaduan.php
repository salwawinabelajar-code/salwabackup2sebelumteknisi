<?php
require_once '../config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

if (isset($_GET['id'])) {
    $id = $db->escape_string($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Cek apakah pengaduan masih menunggu
    $check = $db->query("SELECT status FROM pengaduan WHERE id='$id' AND user_id='$user_id'");
    if ($check->num_rows > 0) {
        $pengaduan = $check->fetch_assoc();
        if ($pengaduan['status'] == 'Menunggu') {
            $query = "DELETE FROM pengaduan WHERE id='$id' AND user_id='$user_id'";
            if ($db->conn->query($query)) {
                $_SESSION['success'] = "Pengaduan berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus pengaduan: " . $db->conn->error;
            }
        } else {
            $_SESSION['error'] = "Pengaduan tidak bisa dihapus karena status sudah " . $pengaduan['status'];
        }
    } else {
        $_SESSION['error'] = "Pengaduan tidak ditemukan!";
    }
}

redirect('index.php');
?>