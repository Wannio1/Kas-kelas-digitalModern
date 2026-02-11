<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bendahara') {
    die("Access denied");
}

$type = $_POST['type'] ?? '';

try {
    // Disable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    if ($type === 'transactions') {
        $pdo->exec("TRUNCATE TABLE transactions");
        $pdo->exec("TRUNCATE TABLE expenses");
        echo "Semua transaksi dan pengeluaran berhasil dihapus.";
    } elseif ($type === 'full') {
        $pdo->exec("TRUNCATE TABLE transactions");
        $pdo->exec("TRUNCATE TABLE expenses");
        // Delete all users except current admin
        $stmt = $pdo->prepare("DELETE FROM users WHERE id != ?");
        $stmt->execute([$_SESSION['user_id']]);
        echo "Reset total berhasil. Semua data siswa dihapus.";
    } else {
        echo "Tipe reset tidak valid.";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

