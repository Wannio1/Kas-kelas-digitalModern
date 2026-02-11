<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    // $password = $_POST['password'] ?? ''; 
    // Note: For this demo/default data, we are using the hash of 'password123'
    // In a real app, verify password hash.
    $passwordInput = $_POST['password'] ?? '';

    if (empty($username) || empty($passwordInput)) {
        echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($passwordInput, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            echo json_encode(['success' => true, 'role' => $user['role']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username atau Password salah']);
        }
    } catch (PDOException $e) {
        // Log error ideally
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
}
?>

