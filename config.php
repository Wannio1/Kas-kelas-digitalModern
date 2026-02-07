<?php
// Database Configuration
$host = 'localhost';
$dbname = 'kas_kelas';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If the database doesn't exist, we might be running this for the first time.
    // Let's try to connect without dbname and create it.
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if DB exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        if (!$stmt->fetchColumn()) {
            die("Database '$dbname' not found. Please import 'kas_kelas.sql' into your MySQL server.");
        } else {
             die("Connection failed: " . $e->getMessage());
        }
    } catch (PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}
?>
