<?php
require_once '../../config/database.php';
$db = new Database();

// Cek login dan role admin
if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['error' => 'Unauthorized']));
}

// Get pending count
$result = $db->query("SELECT COUNT(*) as pending FROM pengaduan WHERE status = 'Menunggu'");
$data = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['pending' => $data['pending']]);