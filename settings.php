<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role']]);
    } elseif ($_POST['action'] === 'edit') {
        $stmt = $pdo->prepare('UPDATE users SET username=?, role=?' . (!empty($_POST['password']) ? ', password=?' : '') . ' WHERE id=?');
        $params = [$_POST['username'], $_POST['role']];
        if (!empty($_POST['password'])) {
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $params[] = $_POST['id'];
        $stmt->execute($params);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$_POST['id']]);
    }
    header('Location: settings.php');
    exit;
}

$users = $pdo->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);
$role_options = ['kasir','produksi','gudang','lain-lain'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seting User - Kasir Risoles & Soes</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 1100px; margin: 24px auto; padding: 16px; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .header h1 { margin:0; color:#2d3748; font-size:1.6rem; }

    .card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; box-shadow:0 6px 24px rgba(0,0,0,.05); }
    .card + .card { margin-top:1rem; }

    .table-responsive { width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling:touch; touch-action: pan-x; will-change: scroll-position; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
    table { width:100%; border-collapse: collapse; min-width: 620px; }
    th, td { padding:12px; border-bottom:1px solid #edf2f7; text-align:left; }
    thead th { background:#f7fafc; position: sticky; top:0; z-index:1; }
    tbody tr:nth-child(even){ background:#fafafa; }

    .btn { display:inline-flex; align-items:center; justify-content:center; height:40px; padding:0 1rem; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
    .btn-danger { background:#e53e3e; color:#fff; }
    .btn-edit { background:#3498db; color:#fff; }
    .btn-add { background:#38a169; color:#fff; }
    .btn-secondary { background:#edf2f7; color:#2d3748; }
    .btn + .btn { margin-left:.5rem; }

    .form-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:.75rem; }
    .form-group { display:flex; flex-direction:column; }
    .form-group label { font-weight:700; color:#4a5568; margin-bottom:.35rem; }
    .form-control { padding:.6rem .7rem; border:1px solid #cbd5e0; border-radius:8px; }

    .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index: 1000; }
    .modal.active { display:block; }
    .modal-content { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:1.25rem; border-radius:12px; width:92%; max-width:540px; box-shadow:0 18px 60px rgba(0,0,0,.25); }
    .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem; }
    .modal-close { background:#e53e3e; color:#fff; border:none; width:36px; height:36px; border-radius:10px; font-size:20px; line-height:0; cursor:pointer; }

    @media (max-width: 768px){ thead th { position: static; } .card { box-shadow:0 2px 8px rgba(0,0,0,.08);} }
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
                    <li><a href="settings.php" class="active" onclick="closeSidebar()">Seting</a></li>
                    <li><a href="absen.php" onclick="closeSidebar()">Absen</a></li>
                    <li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
                </ul>
            </nav>
        </div>
        <div class="main-content">
            <div class="container">
                <div class="header">
                    <h1>Manajemen User</h1>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>Username</th><th>Role</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus user?')">Hapus</button>
                                    </form>
                                    <button class="btn btn-edit" onclick="showEditForm(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['role']) ?>')">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-top:0">Tambah User</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username</label>
                                <input name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input name="password" type="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input name="role" class="form-control" placeholder="Role (misal: kasir, produksi, dll)" required>
                            </div>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap:.5rem; margin-top:.75rem;">
                            <button type="submit" class="btn btn-add">Tambah</button>
                        </div>
                    </form>
                </div>
</div>
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0">Edit User</h3>
            <button class="modal-close" onclick="closeEditForm()">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-group">
                <label>Username</label>
                <input name="username" id="editUsername" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password baru (opsional)</label>
                <input name="password" type="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="editRole" class="form-control" required>
                    <?php foreach ($role_options as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars(ucfirst($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:.5rem; margin-top:.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeEditForm()">Batal</button>
                <button type="submit" class="btn btn-edit">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script>
function showEditForm(id, username, role) {
    const modal = document.getElementById('editModal');
    modal.classList.add('active');
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editRole').value = role;
}
function closeEditForm() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e){ if(e.target === this) closeEditForm(); });
window.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeEditForm(); }});
</script>
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