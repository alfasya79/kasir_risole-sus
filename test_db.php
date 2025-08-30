<?php
require 'config.php';
echo 'Database connection successful!';

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$result = $stmt->fetch();
echo "\nUsers count: " . $result['count'];
?>