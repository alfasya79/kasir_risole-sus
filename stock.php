<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->query("SELECT id, name, price, stock FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$agg = [];
try {
    $movStmt = $pdo->query("SELECT product_id,
        SUM(CASE WHEN `change` > 0 THEN `change` ELSE 0 END) AS added,
        SUM(CASE WHEN `change` < 0 THEN -`change` ELSE 0 END) AS reduced
      FROM stock_movements GROUP BY product_id");
    foreach ($movStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $agg[(int)$row['product_id']] = [
            'added' => (int)$row['added'],
            'reduced' => (int)$row['reduced']
        ];
    }
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Stok Barang - Risoles & Soes</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body{ background:#f7fafc; font-family:'Segoe UI', Arial, sans-serif; }
    .container{ max-width:1100px; margin:24px auto; padding:16px; }
    .header{ display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .header h1{ margin:0; color:#2d3748; font-size:1.6rem; }
    .card{ background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; box-shadow:0 6px 24px rgba(0,0,0,.05); }
    .card + .card{ margin-top:1rem; }

    .btn{ display:inline-flex; align-items:center; justify-content:center; height:40px; padding:0 1rem; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
    .btn-danger{ background:#e53e3e; color:#fff; }

    .table-responsive{ width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; touch-action:pan-x; will-change:scroll-position; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
    .table{ width:100%; border-collapse:collapse; min-width:580px; }
    .table th, .table td{ padding:12px; border-bottom:1px solid #edf2f7; text-align:left; }
    .table thead th{ background:#f7fafc; position:sticky; top:0; z-index:1; }
    .table tbody tr:nth-child(even){ background:#fafafa; }
    .num-pos{ color:#2f855a; font-weight:700; }
    .num-neg{ color:#e53e3e; font-weight:700; }

    @media (max-width:768px){
      .table thead th{ position:static; }
      .card{ box-shadow:0 2px 8px rgba(0,0,0,.08); }
      .table-responsive{ transform:translateZ(0); -webkit-transform:translateZ(0); }
    }
  </style>
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
                    <li><a href="dashboard.php" onclick="closeSidebar()">Dashboard</a></li>
                    <li><a href="settings.php" onclick="closeSidebar()">Seting</a></li>
                    <li><a href="absen.php" onclick="closeSidebar()">Absen</a></li>
                    <li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="container">
                <div class="header">
                    <h1>Stok Barang</h1>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>

                <div class="card">
                  <h2 style="margin-top:0">Daftar Stok</h2>
                  <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok Awal</th>
                        <th>Ditambah</th>
                        <th>Berkurang</th>
                        <th>Stok Akhir</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($products as $p): ?>
                        <?php 
                          $added = $agg[$p['id']]['added'] ?? 0; 
                          $reduced = $agg[$p['id']]['reduced'] ?? 0; 
                          $stock_akhir = (int)$p['stock'];
                          $stock_awal = $stock_akhir - $added + $reduced;
                        ?>
                        <tr>
                          <td><?php echo htmlspecialchars($p['name']); ?></td>
                          <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                          <td><?php echo $stock_awal; ?></td>
                          <td class="num-pos">+<?php echo $added; ?></td>
                          <td class="num-neg">-<?php echo $reduced; ?></td>
                          <td><?php echo $stock_akhir; ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  </div>
                </div>

            </div>
        </div>
    </div>

<script>
function toggleSidebar(){
  const sb=document.getElementById('sidebar');
  const ov=document.querySelector('.sidebar-overlay');
  sb.classList.toggle('mobile-open');
  ov.classList.toggle('active');
}
function closeSidebar(){
  const sb=document.getElementById('sidebar');
  const ov=document.querySelector('.sidebar-overlay');
  sb.classList.remove('mobile-open');
  ov.classList.remove('active');
}
</script>
</body>
</html>