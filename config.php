<?php
$host = 'localhost';
$dbname = 'kasir_risoles_soes';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

startSession();

if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = '';
}

if (!empty($_SESSION['username']) && empty($_SESSION['expire_at'])) {
    $now = new DateTime('now');
    $expire = (clone $now)->modify('tomorrow')->setTime(1,0,0);
    $_SESSION['expire_at'] = $expire->getTimestamp();
}
if (!empty($_SESSION['expire_at']) && time() >= $_SESSION['expire_at']) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_unset();
    session_destroy();
    header('Location: login.php?logout=1');
    exit;
}
?>