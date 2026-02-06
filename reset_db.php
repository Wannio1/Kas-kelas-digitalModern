<?php
require 'config.php';

try {
    // 1. Re-create Tables
    $pdo->exec("DROP TABLE IF EXISTS transactions");
    $pdo->exec("DROP TABLE IF EXISTS users");

    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('bendahara', 'murid') NOT NULL DEFAULT 'murid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            status ENUM('pending', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'qris',
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // 2. Insert Users with CORRECT hashes
    $pass = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:user, :pass, :name, :role)");
    
    // Bendahara
    $stmt->execute([
        ':user' => 'bendahara',
        ':pass' => $pass,
        ':name' => 'Bendahara Kelas',
        ':role' => 'bendahara'
    ]);

    // Murid
    $stmt->execute([
        ':user' => 'murid',
        ':pass' => $pass,
        ':name' => 'Siswa Teladan',
        ':role' => 'murid'
    ]);

    echo "<h1>Database Reset Berhasil!</h1>";
    echo "<p>User 'bendahara' dan 'murid' telah dibuat ulang.</p>";
    echo "<p>Password untuk keduanya adalah: <strong>password123</strong></p>";
    echo "<a href='index.php'>Kembali ke Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
