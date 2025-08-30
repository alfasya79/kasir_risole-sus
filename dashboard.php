
<?php

require_once 'config.php';
startSession();
if (isset($_SESSION['username'])) {
    if (!isset($_SESSION['login_date'])) {
        $_SESSION['login_date'] = date('Y-m-d');
    } else {
        if ($_SESSION['login_date'] !== date('Y-m-d')) {
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }
    if (!isset($_SESSION['role'])) {
        $_SESSION['role'] = 'cashier'; 
    }
} else {
    header('Location: login.php');
    exit();
}

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_transactions FROM transactions WHERE DATE(transaction_date) = CURDATE()");
$today_transactions = $stmt->fetch()['total_transactions'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM transactions WHERE DATE(transaction_date) = CURDATE()");
$today_revenue = $stmt->fetch()['today_revenue'];

$stmt = $pdo->query("SELECT SUM(stock) as total_stock FROM products");
$total_stock = $stmt->fetch()['total_stock'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Dashboard - Risoles & Soes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <div class="sidebar-layout">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Risoles & Soes</h2>
            </div>

            <nav class="nav">
                <ul>
                        <li><a href="dashboard.php" class="active" onclick="closeSidebar()">Dashboard</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="settings.php" onclick="closeSidebar()">Seting</a></li>
                            <li><a href="absen.php" onclick="closeSidebar()">Absen</a></li>
                        <?php elseif ($_SESSION['role'] === 'cashier'): ?>
                            <li><a href="transaction.php" onclick="closeSidebar()">Transaksi</a></li>
                        <?php elseif ($_SESSION['role'] === 'production'): ?>
                            <li><a href="products.php" onclick="closeSidebar()">Kelola Produk</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
                        <?php endif; ?>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="container">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h1>Dashboard</h1>
                    <a href="logout.php" class="btn btn-danger" style="margin-left:auto;">Logout</a>
                </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Produk</h3>
                <div class="number"><?php echo $total_products; ?></div>
                <p>Jenis produk tersedia</p>
            </div>
            
            <div class="dashboard-card">
                <h3>Transaksi Hari Ini</h3>
                <div class="number"><?php echo $today_transactions; ?></div>
                <p>Transaksi berhasil</p>
            </div>
            
            <div class="dashboard-card">
                <h3>Pendapatan Hari Ini</h3>
                <div class="number">Rp <?php echo number_format($today_revenue, 0, ',', '.'); ?></div>
                <p>Total penjualan</p>
            </div>
            
            <div class="dashboard-card" <?php if ($_SESSION['role'] === 'admin') echo 'onclick="window.location.href=\'stock.php\'" style="cursor:pointer" title="Lihat stok barang"'; ?>>
                <h3>Total Stok</h3>
                <div class="number"><?php echo $total_stock; ?></div>
                <p>Item tersedia</p>
            </div>
        </div>

        <?php
        $stmt = $pdo->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC");
        $low_stock = $stmt->fetchAll();
        
        if ($low_stock):
        ?>
        <div class="card">
            <h2>⚠️ Peringatan Stok Rendah</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Stok Tersisa</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><span style="color: #e53e3e; font-weight: bold;"><?php echo $product['stock']; ?></span></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
        
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-nav-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>