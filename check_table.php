<?php
require 'koneksi.php';
try {
    $stmt = $pdo->query("DESCRIBE transactions");
    echo "Table 'transactions' exists.\nColumns:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo $col['Field'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

