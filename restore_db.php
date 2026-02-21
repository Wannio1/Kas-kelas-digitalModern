<?php
session_start();
require 'koneksi.php';

// Increase limits for large restores
set_time_limit(0);
ini_set('memory_limit', '512M');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    
    $file = $_FILES['backup_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed with error code " . $file['error']);
    }

    $sql = file_get_contents($file['tmp_name']);
    
    try {
        // Disable foreign key checks to prevent errors during drop/create
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $pdo->exec($sql);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "Database berhasil direstore!";
        
    } catch (PDOException $e) {
        echo "Error restoring database: " . $e->getMessage();
    }

} else {
    echo "No file uploaded";
}
?>

