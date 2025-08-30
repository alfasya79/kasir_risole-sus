<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Kelola Produk - Risoles & Soes</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 1100px; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .header-actions { display:flex; align-items:center; gap:.5rem; }
        .header h1 { margin: 0; }

        .card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; background: #fff; box-shadow: 0 6px 24px rgba(0,0,0,.05); margin-bottom: 1rem; }
        .card h2 { margin-top: 0; margin-bottom: .75rem; }

        .form-group { display:flex; flex-direction:column; }
        .form-group label { font-weight: 600; color: #4a5568; margin-bottom: .35rem; }
        .form-control { padding: .6rem .7rem; border: 1px solid #cbd5e0; border-radius: 8px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; height:40px; padding:0 1rem; border-radius:8px; font-weight:700; line-height:1; cursor: pointer; }
    .btn-sm { height:40px; padding:0 .9rem; font-size:.95rem; }
    .btn + .btn { margin-left: .5rem; }

        .alert { border-radius: 10px; padding: .75rem 1rem; margin: .75rem 0; font-weight: 600; }
        .alert-success { background:#f0fff4; color:#22543d; border:1px solid #c6f6d5; }
        .alert-error { background:#fff5f5; color:#742a2a; border:1px solid #fed7d7; }

    .table-responsive { width:100%; overflow-x:auto; overflow-y:visible; -webkit-overflow-scrolling: touch; touch-action: pan-x; will-change: scroll-position; border-radius: 10px; border:1px solid #e2e8f0; background:#fff; }
    .table { width:100%; border-collapse: collapse; min-width:580px; }
        .table th, .table td { padding: .65rem .75rem; border-bottom: 1px solid #edf2f7; }
        .table thead th { background:#f7fafc; text-align:left; position: sticky; top: 0; z-index: 1; }
        .table tbody tr:nth-child(even) { background:#fafafa; }
    .table td .btn { min-width: 60px; }

        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index: 1000; }
        .modal-overlay.active { display:block; }
        .modal-content { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:1.25rem; border-radius:14px; width:92%; max-width:520px; box-shadow:0 18px 60px rgba(0,0,0,.25); }
        .modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem; }
        .modal-close { background:#e53e3e; color:#fff; border:none; width:36px; height:36px; border-radius:10px; font-size:20px; line-height:0; cursor:pointer; }

        .stock-badge { font-weight:700; }
        .stock-low { color:#e53e3e; }
        .stock-ok { color:#38a169; }

        @media (max-width: 768px) {
            .header { flex-wrap: wrap; }
            .card-add { display: none; }
            .table thead th { position: static; }
            .card { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
            .table-responsive { transform: translateZ(0); -webkit-transform: translateZ(0); }
            #btn-open-add { display: inline-flex; }
        }
    #btn-open-add { display: none; }

    #fab-add-product { display:none; position:fixed; right:16px; bottom:16px; width:56px; height:56px; border-radius:50%; background:#38a169; color:#fff; border:none; box-shadow:0 10px 24px rgba(0,0,0,.18); z-index:900; font-size:28px; line-height:0; align-items:center; justify-content:center; }
    #fab-add-product:active { transform: translateY(1px); }
    @media (max-width:768px){
        #fab-add-product{ display:flex; }
    }
    </style>
</head>
<body>
    <?php if ($_SESSION['role'] !== 'production'): ?>
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
                    <li><a href="products.php" class="active" onclick="closeSidebar()">Kelola Produk</a></li>
                    <li><a href="transaction.php" onclick="closeSidebar()">Transaksi</a></li>
                    <li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

        <div class="main-content" <?php if ($_SESSION['role'] === 'production') echo 'style="max-width:1200px;margin:0 auto;padding:1rem;"'; ?>>
            <div class="container" <?php if ($_SESSION['role'] === 'production') echo 'style="width:100%;"'; ?>>
                <div class="header">
                        <h1>Kelola Produk</h1>
                        <div class="header-actions">
                            <button id="btn-open-add" class="btn btn-success" type="button">Tambah Produk</button>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>
                </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                switch($_GET['success']) {
                    case 'add': echo 'Produk berhasil ditambahkan!'; break;
                    case 'edit': 
                        if (isset($_GET['message'])) {
                            echo htmlspecialchars($_GET['message']);
                        } else {
                            echo 'Produk berhasil diperbarui!';
                        }
                        break;
                    case 'delete': echo 'Produk berhasil dihapus!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                Terjadi kesalahan: <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

    <div class="card card-add">
            <h2>Tambah Produk Baru</h2>
            <form method="POST" action="product_action.php">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .75rem; align-items:end;">
                    <div class="form-group">
                        <label for="name">Nama Produk:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Harga:</label>
                        <input type="number" id="price" name="price" class="form-control" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stok:</label>
                        <input type="number" id="stock" name="stock" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label style="visibility:hidden">Aksi</label>
                        <button type="submit" class="btn btn-success">Tambah Produk</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Daftar Produk</h2>
            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Total Stock</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="stock-badge <?php echo $product['stock'] < 10 ? 'stock-low' : 'stock-ok'; ?>"><?php echo $product['stock']; ?></span>
                        </td>
                        <td>
                            <button onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock']; ?>)" class="btn btn-warning btn-sm">Edit</button>
                            <a href="product_action.php?action=delete&id=<?php echo $product['id']; ?>" 
                               onclick="return confirm('Yakin ingin menghapus produk ini?')" 
                               class="btn btn-danger btn-sm">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <button id="fab-add-product" type="button" aria-label="Tambah Produk" onclick="openAddModal()">＋</button>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0">Edit Produk</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <form method="POST" action="product_action.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_name">Nama Produk:</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Harga:</label>
                    <input type="number" id="edit_price" name="price" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_current_stock">Total Stock Saat Ini:</label>
                    <input type="number" id="edit_current_stock" name="current_stock" class="form-control" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                </div>
                
                <div class="form-group">
                    <label for="edit_add_stock">Tambah Stock:</label>
                    <input type="number" id="edit_add_stock" name="add_stock" class="form-control" min="0" placeholder="Masukkan jumlah stock yang akan ditambahkan">
                    <small style="color: #718096; font-size: 0.875rem;">* Kosongkan jika tidak ada penambahan stock</small>
                </div>
                
                <div style="display: flex; gap: .5rem; justify-content: flex-end; margin-top: .5rem;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0">Tambah Produk</h3>
                <button type="button" class="modal-close" onclick="closeAddModal()">×</button>
            </div>
            <form method="POST" action="product_action.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="add_name">Nama Produk:</label>
                    <input type="text" id="add_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="add_price">Harga:</label>
                    <input type="number" id="add_price" name="price" class="form-control" step="1" required>
                </div>
                <div class="form-group">
                    <label for="add_stock">Stok:</label>
                    <input type="number" id="add_stock" name="stock" class="form-control" required>
                </div>
                <div style="display:flex; gap:.5rem; justify-content:flex-end; margin-top:.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editProduct(id, name, price, stock) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_current_stock').value = stock;
            document.getElementById('edit_add_stock').value = ''; 
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        window.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeEditModal(); }});

    const btnOpenAdd = document.getElementById('btn-open-add');
    const addModal = document.getElementById('addModal');
    function openAddModal(){ addModal.classList.add('active'); }
    function closeAddModal(){ addModal.classList.remove('active'); }
    if (btnOpenAdd) btnOpenAdd.addEventListener('click', openAddModal);
    addModal.addEventListener('click', function(e){ if(e.target === this) closeAddModal(); });
    window.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeAddModal(); }});
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