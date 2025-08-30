<?php
require_once 'config.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
// Filter preset ala laporan
$group_by   = $_GET['group_by']   ?? 'day';
$allowed_groups = ['day','week','month','year'];
if (!in_array($group_by, $allowed_groups, true)) { $group_by = 'day'; }

$today = new DateTime('today');
$preset = $_GET['preset'] ?? null;
$sd = $_GET['start_date'] ?? null;
$ed = $_GET['end_date'] ?? null;
if ($preset === null) { $preset = ($sd && $ed) ? 'custom' : 'today'; }
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

$label = 'Periode ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Rekap Absen</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 1100px; margin: 24px auto; padding: 16px; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .header h1 { margin:0; color:#2d3748; font-size:1.6rem; }
    .header .actions { display:flex; gap:.5rem; }

    .card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; box-shadow:0 6px 24px rgba(0,0,0,.05); }
    .card + .card { margin-top:1rem; }

    .filter-bar { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .filter-left { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .btn-chip { padding: .55rem .9rem; border-radius: 999px; }
    .btn-chip .chev { opacity:.8; margin-left:.25rem; }

    .btn { display:inline-flex; align-items:center; justify-content:center; height:40px; padding:0 1rem; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
    .btn-secondary { background:#edf2f7; color:#2d3748; }
    .btn-primary { background:#667eea; color:#fff; }

    .table-responsive { width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; touch-action: pan-x; will-change: scroll-position; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
    table { width:100%; border-collapse: collapse; min-width: 580px; }
    th, td { padding:12px; border-bottom:1px solid #edf2f7; text-align:left; }
    thead th { background:#f7fafc; position: sticky; top:0; z-index:1; }
    tbody tr:nth-child(even){ background:#fafafa; }

    .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; }
    .modal.open { display:flex; align-items:center; justify-content:center; }
    .modal-content { position:relative; transform:none; top:auto; left:auto; background:#fff; padding:1.25rem; border-radius:14px; width:92%; max-width:640px; max-height:80vh; overflow-y:auto; box-shadow:0 10px 30px rgba(0,0,0,.15); }
    .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
    .modal-close { background:#e53e3e; color:#fff; border:none; width:36px; height:36px; border-radius:10px; font-size:20px; line-height:0; cursor:pointer; }
    @media (max-width: 768px) { thead th { position: static; } .card { box-shadow: 0 2px 8px rgba(0,0,0,.08); } }
    </style>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rekap Absen <?php if (($preset ?? 'today') !== 'today'): ?><span style="font-size:1rem;color:#718096;">(<?= htmlspecialchars($label) ?>)</span><?php endif; ?></h1>
            <div class="actions">
                <a href="absen.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
        <div class="card" style="margin-top: .75rem;">
            <div class="filter-bar">
                <div class="filter-left">
                    <button type="button" class="btn btn-primary btn-chip" onclick="openFilterModal()">
                        <span id="currentPresetLabel"><?= htmlspecialchars($preset_label) ?></span>
                        <span class="chev">▼</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Hadir</th>
                            <th>Ijin</th>
                            <th>Libur</th>
                            <th>Grafik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_users = $pdo->query("SELECT * FROM users WHERE role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);
                        $charts = [];
                        foreach ($all_users as $user) {
                            $sql = "SELECT status, COUNT(*) as jumlah FROM attendance WHERE user_id = ? AND DATE(tanggal) BETWEEN ? AND ? GROUP BY status";
                            $rekap = $pdo->prepare($sql);
                            $rekap->execute([$user['id'], $start_date, $end_date]);
                            $stat = ['hadir'=>0,'ijin'=>0,'libur'=>0];
                            foreach ($rekap as $row) { $stat[$row['status']] = $row['jumlah']; }
                            $chart_id = 'chart_' . $user['id'];
                            echo '<tr>';
                            echo '<td>'.htmlspecialchars($user['username']).'</td>';
                            echo '<td>'.htmlspecialchars($user['role']).'</td>';
                            echo '<td>'.$stat['hadir'].'</td>';
                            echo '<td>'.$stat['ijin'].'</td>';
                            echo '<td>'.$stat['libur'].'</td>';
                            echo '<td style="width:160px"><canvas id="'.$chart_id.'" height="80" style="max-width:140px;"></canvas></td>';
                            echo '</tr>';
                            $charts[] = [
                                'id' => $chart_id,
                                'hadir' => (int)$stat['hadir'],
                                'ijin' => (int)$stat['ijin'],
                                'libur' => (int)$stat['libur'],
                            ];
                        }
                        ?>
                    </tbody>
                </table>
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
                <div class="filter-bar" style="gap:1rem;">
                    <div class="form-group" style="min-width:180px;">
                        <label for="custom_start">Tanggal Mulai</label>
                        <input type="date" id="custom_start" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group" style="min-width:180px;">
                        <label for="custom_end">Tanggal Akhir</label>
                        <input type="date" id="custom_end" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
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
    const baseUrl = 'absen_report.php';
    const currentGroupBy = '<?= $group_by ?>';
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
        if (s > e) { const t = s; s = e; e = t; }
        const params = new URLSearchParams();
        params.set('preset', 'custom');
        params.set('start_date', s);
        params.set('end_date', e);
        params.set('group_by', currentGroupBy);
        window.location.href = baseUrl + '?' + params.toString();
    }
    window.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeFilterModal(); }});
    (function(){
        const charts = <?= json_encode($charts ?? []) ?>;
        charts.forEach(c => {
            const el = document.getElementById(c.id);
            if (!el) return;
            new Chart(el.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Hadir','Ijin','Libur'],
                    datasets: [{
                        data: [c.hadir, c.ijin, c.libur],
                        backgroundColor: ['#38a169','#ed8936','#e53e3e'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    animation: { duration: 300 }
                }
            });
        });
    })();
    </script>
</body>
</html>