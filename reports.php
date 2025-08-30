<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$group_by = $_GET['group_by'] ?? 'day';
$allowed_groups = ['day','week','month','year'];
if (!in_array($group_by, $allowed_groups, true)) { $group_by = 'day'; }

$today = new DateTime('today');
$preset = $_GET['preset'] ?? null;
$sd = $_GET['start_date'] ?? null;
$ed = $_GET['end_date'] ?? null;

if ($preset === null) {
    if ($sd && $ed) { $preset = 'custom'; }
    else { $preset = 'today'; }
}

switch ($preset) {
    case 'weekly':
        $start_date = (new DateTime('today'))->modify('-6 days')->format('Y-m-d');
        $end_date   = (new DateTime('today'))->format('Y-m-d');
        $preset_label = 'Mingguan';
        break;
    case 'monthly':
        $start_date = (new DateTime('today'))->modify('-29 days')->format('Y-m-d');
        $end_date   = (new DateTime('today'))->format('Y-m-d');
        $preset_label = 'Bulanan';
        break;
    case 'yearly':
        $start_date = (new DateTime('today'))->modify('-364 days')->format('Y-m-d');
        $end_date   = (new DateTime('today'))->format('Y-m-d');
        $preset_label = 'Tahunan';
        break;
    case 'custom':
        $start_date = $sd ?: date('Y-m-d');
        $end_date   = $ed ?: date('Y-m-d');
        $preset_label = 'Pilih Tanggal';
        break;
    case 'today':
    default:
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
        $preset_label = 'Hari Ini';
        break;
}

$stmt = $pdo->prepare("
    SELECT t.*
    FROM transactions t 
    WHERE DATE(t.transaction_date) BETWEEN ? AND ? 
    ORDER BY t.transaction_date DESC
");
$stmt->execute([$start_date, $end_date]);
$transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_revenue = $stmt->fetch()['total_revenue'];

$stmt = $pdo->prepare("
    SELECT p.name, SUM(ti.quantity) as total_sold, SUM(ti.quantity * ti.price_at_transaction) as revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.transaction_date) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$best_sellers = $stmt->fetchAll();

$prodLabels = [];
$prodValues = [];
foreach ($best_sellers as $r) { $prodLabels[] = $r['name']; $prodValues[] = (int)$r['total_sold']; }

$chart_labels = [];
$chart_values = [];
if ($group_by === 'day') {
    try {
        $stmt = $pdo->prepare("SELECT DATE(transaction_date) as k, SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) BETWEEN ? AND ? GROUP BY DATE(transaction_date) ORDER BY DATE(transaction_date)");
        $stmt->execute([$start_date, $end_date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) { $map[$r['k']] = (float)$r['total']; }
        $period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), (new DateTime($end_date))->modify('+1 day'));
        foreach ($period as $dt) {
            $d = $dt->format('Y-m-d');
            $chart_labels[] = date('d/m', strtotime($d));
            $chart_values[] = isset($map[$d]) ? $map[$d] : 0;
        }
    } catch (Exception $e) {
        $chart_labels = [$start_date];
        $chart_values = [ (float)$total_revenue ];
    }
} elseif ($group_by === 'week') {
    $stmt = $pdo->prepare("SELECT YEARWEEK(transaction_date, 1) as k, SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) BETWEEN ? AND ? GROUP BY YEARWEEK(transaction_date,1) ORDER BY YEARWEEK(transaction_date,1)");
    $stmt->execute([$start_date, $end_date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $k = (string)$r['k'];
        $year = substr($k, 0, 4);
        $week = (int)substr($k, -2);
        $chart_labels[] = 'Minggu ' . $week . ' ' . $year;
        $chart_values[] = (float)$r['total'];
    }
} elseif ($group_by === 'month') {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(transaction_date, '%Y-%m') as k, SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) BETWEEN ? AND ? GROUP BY DATE_FORMAT(transaction_date, '%Y-%m') ORDER BY DATE_FORMAT(transaction_date, '%Y-%m')");
    $stmt->execute([$start_date, $end_date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $label = DateTime::createFromFormat('Y-m', $r['k']);
        $chart_labels[] = $label ? $label->format('m/Y') : $r['k'];
        $chart_values[] = (float)$r['total'];
    }
} else { 
    $stmt = $pdo->prepare("SELECT YEAR(transaction_date) as k, SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) BETWEEN ? AND ? GROUP BY YEAR(transaction_date) ORDER BY YEAR(transaction_date)");
    $stmt->execute([$start_date, $end_date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $chart_labels[] = (string)$r['k'];
        $chart_values[] = (float)$r['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Laporan - Risoles & Soes</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
            .filter-bar { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
            .filter-left { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
            .filter-right { margin-left:auto; display:flex; align-items:center; gap:.5rem; }
            .btn-chip { padding: .55rem .9rem; border-radius: 999px; }
            .btn-chip .chev { opacity:.8; margin-left:.25rem; }
            .period { color:#718096; font-size:.95rem; }
            .chart-wrap { position: relative; height: 220px; }
            .chart-wrap.small { height: 260px; }
            .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; }
            .table { min-width: 460px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .empty-state { text-align: center; color: #718096; padding: 1.25rem; }
            /* Modal */
            .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; }
            .modal.open { display: flex; align-items: center; justify-content: center; }
            .modal-content { position: relative; transform: none; top: auto; left: auto; background: #fff; padding: 1.25rem; border-radius: 14px; width: 92%; max-width: 640px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
            .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
            .modal-close { background:#e53e3e; color:#fff; border:none; width:36px; height:36px; border-radius:10px; font-size:20px; line-height:0; cursor:pointer; }
            @media (max-width:768px){ .filter-right{ width:100%; justify-content:flex-start; margin-left:0; } }
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
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="settings.php" onclick="closeSidebar()">Seting</a></li>
                            <li><a href="absen.php" onclick="closeSidebar()">Absen</a></li>
                        <?php elseif ($_SESSION['role'] === 'cashier'): ?>
                            <li><a href="transaction.php" onclick="closeSidebar()">Transaksi</a></li>
                        <?php elseif ($_SESSION['role'] === 'production'): ?>
                            <li><a href="products.php" onclick="closeSidebar()">Kelola Produk</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="reports.php" class="active" onclick="closeSidebar()">Laporan</a></li>
                        <?php endif; ?>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="container">
                <div class="header">
                    <h1>Laporan Penjualan</h1>
                </div>

        <div class="card">
            <div class="filter-bar">
                <div class="filter-left">
                    <button type="button" class="btn btn-primary btn-chip" onclick="openFilterModal()">
                        <span id="currentPresetLabel"><?php echo htmlspecialchars($preset_label); ?></span>
                        <span class="chev">▼</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Transaksi</h3>
                <div class="number"><?php echo count($transactions); ?></div>
                <?php if (($preset ?? 'today') !== 'today'): ?>
                <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Pendapatan</h3>
                <div class="number">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                <p>Periode yang dipilih</p>
            </div>
            
            <div class="dashboard-card">
                <h3>Rata-rata per Transaksi</h3>
                <div class="number">Rp <?php echo count($transactions) > 0 ? number_format($total_revenue / count($transactions), 0, ',', '.') : '0'; ?></div>
                <p>Nilai rata-rata</p>
            </div>
        </div>

        <div class="card">
            <h2>Grafik Penjualan (<?php echo ($group_by==='day'?'Harian':($group_by==='week'?'Mingguan':($group_by==='month'?'Bulanan':'Tahunan'))); ?>)</h2>
            <div class="chart-wrap">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <?php if (!empty($best_sellers)) : ?>
        <div class="card">
            <h2>Komposisi Produk Terjual</h2>
            <div class="chart-wrap small">
                <canvas id="productChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($best_sellers): ?>
        <div class="card">
            <h2>Produk Terlaris</h2>
            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th class="text-center">Jumlah Terjual</th>
                        <th class="text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($best_sellers as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="text-center"><?php echo $product['total_sold']; ?> pcs</td>
                        <td class="text-right">Rp <?php echo number_format($product['revenue'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Riwayat Transaksi</h2>
            <?php if ($transactions): ?>
            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>#<?php echo $transaction['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="text-right">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                        <td>
                            <button onclick="showTransactionDetail(<?php echo $transaction['id']; ?>)" class="btn btn-primary btn-sm">
                                Lihat Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p class="empty-state">Tidak ada transaksi pada periode ini</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">Detail Transaksi</h3>
            <div id="detail-content">
                <div class="loading"></div>
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button onclick="closeDetailModal()" class="btn btn-secondary">Tutup</button>
            </div>
        </div>
    </div>

    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;">Pilih Filter</h3>
                <button class="modal-close" onclick="closeFilterModal()">×</button>
            </div>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:.5rem;">
                <button class="btn btn-primary" onclick="applyPreset('today')">Hari Ini</button>
                <button class="btn btn-primary" onclick="applyPreset('weekly')">Mingguan (7 hari)</button>
                <button class="btn btn-primary" onclick="applyPreset('monthly')">Bulanan (30 hari)</button>
                <button class="btn btn-primary" onclick="applyPreset('yearly')">Tahunan (365 hari)</button>
            </div>
            <hr style="margin:1rem 0; border:none; border-top:1px solid #e2e8f0;">
            <div>
                <h4 style="margin:0 0 .5rem; color:#2d3748;">Pilih Tanggal Bebas</h4>
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="custom_start">Tanggal Mulai</label>
                        <input type="date" id="custom_start" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label for="custom_end">Tanggal Akhir</label>
                        <input type="date" id="custom_end" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group" style="align-self:end;">
                        <button class="btn btn-primary" onclick="applyCustomRange()">Terapkan</button>
                    </div>
                </div>
            </div>
            <div style="margin-top:1rem; text-align:right;">
                <button class="btn btn-secondary" onclick="closeFilterModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const currentGroupBy = '<?php echo $group_by; ?>';
        const baseUrl = 'reports.php';
        function openFilterModal(){ document.getElementById('filterModal').classList.add('open'); }
        function closeFilterModal(){ document.getElementById('filterModal').classList.remove('open'); }
        function applyPreset(preset){
            const params = new URLSearchParams();
            params.set('preset', preset);
            params.set('group_by', currentGroupBy);
            window.location.href = baseUrl + '?' + params.toString();
        }
        function applyCustomRange(){
            let s = document.getElementById('custom_start').value;
            let e = document.getElementById('custom_end').value;
            if(!s || !e) return;
            if (s > e) { const tmp = s; s = e; e = tmp; }
            const params = new URLSearchParams();
            params.set('preset', 'custom');
            params.set('start_date', s);
            params.set('end_date', e);
            params.set('group_by', currentGroupBy);
            window.location.href = baseUrl + '?' + params.toString();
        }
        function applyGroupBy(val){
            const url = new URL(window.location.href);
            url.searchParams.set('group_by', val);
            if(!url.searchParams.get('preset')){
                url.searchParams.set('preset','custom');
                url.searchParams.set('start_date','<?php echo $start_date; ?>');
                url.searchParams.set('end_date','<?php echo $end_date; ?>');
            }
            window.location.href = url.toString();
        }
        function showTransactionDetail(transactionId) {
            document.getElementById('detailModal').classList.add('open');
            document.getElementById('detail-content').innerHTML = '<div class="loading"></div>';
            
            fetch(`transaction_detail.php?id=${transactionId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detail-content').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('detail-content').innerHTML = '<p style="color: #e53e3e;">Gagal memuat detail transaksi</p>';
                });
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('open');
        }

    document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailModal();
            }
        });
        document.getElementById('filterModal').addEventListener('click', function(e){ if(e.target === this) closeFilterModal(); });

        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesLabels = <?php echo json_encode($chart_labels); ?>;
        const salesValues = <?php echo json_encode($chart_values); ?>;
        const dataset = {
            label: 'Pendapatan (Rp)',
            data: salesValues,
            borderColor: 'rgba(102, 126, 234, 1)',
            backgroundColor: 'transparent',
            tension: 0.3,
            fill: false,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: 'rgba(102, 126, 234, 1)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
        };

        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: { labels: salesLabels, datasets: [dataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 4, right: 8, bottom: 4, left: 8 } },
                scales: {
                    x: { ticks: { font: { size: 10 } }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { font: { size: 10 }, callback: (v)=> 'Rp ' + Number(v).toLocaleString('id-ID') } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => 'Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID') } }
                }
            }
        });

        <?php if (!empty($best_sellers)) : ?>
        const prodCtx = document.getElementById('productChart').getContext('2d');
        const prodLabels = <?php echo json_encode($prodLabels); ?>;
        const prodValues = <?php echo json_encode($prodValues); ?>;
        const prodColors = ['#667eea','#764ba2','#48bb78','#ed8936','#e53e3e','#38b2ac','#ecc94b'];
        const productChart = new Chart(prodCtx, {
            type: 'doughnut',
            data: {
                labels: prodLabels,
                datasets: [{ data: prodValues, backgroundColor: prodColors.slice(0, prodValues.length) }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} pcs` } }
                }
            }
        });
        <?php endif; ?>
    </script>

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

        window.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                closeDetailModal();
                closeFilterModal();
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
            </div>
        </div>
    </div>
</body>
</html>