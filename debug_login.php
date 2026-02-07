<?php
require 'config.php';

echo "<h2>Database & Login Debugger</h2>";

try {
    // Check connection
    echo "<p>Database Connected: OK</p>";

    // Get Users
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Match Test ('password123')</th></tr>";

    foreach ($users as $user) {
        $isMatch = password_verify('password123', $user['password']) ? '<span style="color:green;font-weight:bold;">MATCH</span>' : '<span style="color:red;font-weight:bold;">FAIL</span>';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . $isMatch . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($users) === 0) {
        echo "<p style='color:red;'>No users found! Please run <a href='reset_db.php'>reset_db.php</a> again.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
