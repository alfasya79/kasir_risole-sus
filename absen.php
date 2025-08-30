<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'absen') {
        $stmt = $pdo->prepare('INSERT INTO attendance (user_id, tanggal, status) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['user_id'], date('Y-m-d'), 'hadir']);
    } elseif ($_POST['action'] === 'libur') {
        $stmt = $pdo->prepare('INSERT INTO attendance (user_id, tanggal, status) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['user_id'], date('Y-m-d'), 'libur']);
    } elseif ($_POST['action'] === 'ijin') {
        $stmt = $pdo->prepare('INSERT INTO attendance (user_id, tanggal, status) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['user_id'], date('Y-m-d'), 'ijin']);
    }
    header('Location: absen.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users WHERE role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);
$today = date('Y-m-d');
$absen = $pdo->query("SELECT * FROM attendance WHERE tanggal='$today'")->fetchAll(PDO::FETCH_ASSOC);
$absen_map = [];
foreach ($absen as $a) {
    $absen_map[$a['user_id']] = $a['status'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absen & Ijin User - Kasir Risoles & Soes</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 1100px; margin: 24px auto; padding: 16px; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap: wrap; }
    .header h1 { margin:0; color:#2d3748; font-size:1.6rem; }
    .header .actions { display:flex; gap:.5rem; align-items:center; }

    .card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; box-shadow:0 6px 24px rgba(0,0,0,.05); }
    .card + .card { margin-top: 1rem; }

    .table-responsive { width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; touch-action: pan-x; will-change: scroll-position; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
    table { width: 100%; border-collapse: collapse; min-width: 560px; }
    th, td { padding: 12px; border-bottom: 1px solid #edf2f7; text-align: left; }
    thead th { background: #f7fafc; position: sticky; top: 0; z-index: 1; }
    tbody tr:nth-child(even) { background:#fafafa; }

    .btn { display:inline-flex; align-items:center; justify-content:center; height:40px; padding:0 1rem; border:none; border-radius:8px; cursor:pointer; font-weight:700; }
    .btn-info { background:#3498db; color:#fff; }
    .btn-libur { background: #e67e22; color: #fff; }
    .btn-ijin { background: #3498db; color: #fff; }
    .btn + .btn { margin-left:.5rem; }
    .actions .btn { min-width: 140px; }
    .aksi-group { display:flex; gap:.5rem; flex-wrap: wrap; }

    .badge { padding: 6px 12px; border-radius: 999px; color: #fff; font-size: 13px; font-weight: 700; }
    .badge-hadir { background: #27ae60; }
    .badge-libur { background: #e67e22; }
    .badge-ijin { background: #3498db; }
    .badge-belum { background: #e2e8f0; color: #2d3748; }

    @media (max-width: 768px) {
        .container { padding: 1rem; }
        thead th { position: static; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    }
    </style>
</head>
<body>
    <button class="mobile-nav-toggle" id="mobileNavToggle">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar-layout">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Risoles & Soes</h2>
            </div>
            <nav class="nav">
                <ul>
                    <li><a href="dashboard.php" onclick="closeSidebar()">Dashboard</a></li>
                    <li><a href="settings.php" onclick="closeSidebar()">Seting</a></li>
                    <li><a href="absen.php" class="active" onclick="closeSidebar()">Absen</a></li>
                    <li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
                </ul>
            </nav>
        </div>

        

    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>Absen</h1>
                <div class="actions">
                    <a href="absen_report.php" class="btn btn-info">Lihat Rekap Absen</a>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                <table>
                    <thead>
                <tr style="background:#f0f0f0">
                    <th style="width:120px">Username</th>
                    <th style="width:120px">Role</th>
                    <th style="width:120px">Status</th>
                    <th style="width:180px">Aksi</th>
                </tr>
                    </thead>
                    <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td style="font-weight:600;color:#2d3748;">&nbsp;<?= htmlspecialchars($user['username']) ?></td>
                    <td style="color:#718096;">&nbsp;<?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <?php if (isset($absen_map[$user['id']])): ?>
                            <?php if ($absen_map[$user['id']] === 'hadir'): ?>
                                <span class="badge badge-hadir">Hadir</span>
                            <?php elseif ($absen_map[$user['id']] === 'libur'): ?>
                                <span class="badge badge-libur">Libur</span>
                            <?php elseif ($absen_map[$user['id']] === 'ijin'): ?>
                                <span class="badge badge-ijin">Ijin</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-belum">Belum Absen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!isset($absen_map[$user['id']])): ?>
                        <div class="aksi-group">
                            <form method="post">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="action" value="libur">
                                <button type="submit" class="btn btn-libur">Tandai Libur</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="action" value="ijin">
                                <button type="submit" class="btn btn-ijin">Tandai Ijin</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggleBtn = document.getElementById('mobileNavToggle');

function openSidebar() {
    sidebar.classList.add('mobile-open');
    overlay.classList.add('active');
}
function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
}
function toggleSidebar() {
    if (sidebar.classList.contains('mobile-open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}
toggleBtn.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', closeSidebar);
</script>
</body>
</html>