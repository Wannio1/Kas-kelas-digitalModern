<?php
require 'config.php';

try {
    $pass = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:user, :pass, :name, 'murid')");
    
    echo "Mulai membuat 33 akun murid...\n";
    
    for ($i = 1; $i <= 33; $i++) {
        $username = "siswa$i";
        $fullName = "Murid Ke-$i";
        
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->rowCount() == 0) {
            $stmt->execute([
                ':user' => $username,
                ':pass' => $pass,
                ':name' => $fullName
            ]);
            echo "Dibuat: $username ($fullName)\n";
        } else {
            echo "Dilewati (sudah ada): $username\n";
        }
    }
    
    echo "\nSelesai! 33 Akun berhasil diproses.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
