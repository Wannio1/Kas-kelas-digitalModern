<?php
require 'config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            created_by INT NOT NULL,
            expense_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "Tabel 'expenses' berhasil dibuat!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
