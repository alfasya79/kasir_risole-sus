<?php
require_once 'config.php';
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.php?error=2');
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=1');
    exit;
}

$_SESSION['username'] = $user['username'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
if ($user['role'] !== 'admin') {
    $today = date('Y-m-d');
    $cek = $pdo->prepare('SELECT id FROM attendance WHERE user_id = ? AND tanggal = ?');
    $cek->execute([$user['id'], $today]);
    if (!$cek->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO attendance (user_id, tanggal, status) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $today, 'hadir']);
    }
}

if ($user['role'] === 'production') {
    header('Location: products.php');
    exit;
}

if ($user['role'] === '	cashier') {
    header('Location: transaction.php');
    exit;
}

header('Location: dashboard.php');
exit;
?>