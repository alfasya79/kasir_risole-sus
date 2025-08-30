<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        
        if (empty($name) || $price <= 0 || $stock < 0) {
            header('Location: products.php?error=Data tidak valid');
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $stock]);
            header('Location: products.php?success=add');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                header('Location: products.php?error=Nama produk sudah ada');
            } else {
                header('Location: products.php?error=Gagal menambah produk');
            }
        }
        break;
        
    case 'edit':
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $add_stock = $_POST['add_stock'] ?? 0;
        
        if ($id <= 0 || empty($name) || $price <= 0) {
            header('Location: products.php?error=Data tidak valid');
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                header('Location: products.php?error=Produk tidak ditemukan');
                exit();
            }

            $new_stock = $current_stock + intval($add_stock);

            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->execute([$name, $price, $new_stock, $id]);

            if ($add_stock > 0) {
                $message = "Produk berhasil diperbarui. Stock bertambah dari $current_stock menjadi $new_stock (+$add_stock)";
                header('Location: products.php?success=edit&message=' . urlencode($message));
            } else {
                header('Location: products.php?success=edit');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                header('Location: products.php?error=Nama produk sudah ada');
            } else {
                header('Location: products.php?error=Gagal mengupdate produk');
            }
        }
        break;
        
    case 'delete':
        $id = $_GET['id'] ?? 0;
        
        if ($id <= 0) {
            header('Location: products.php?error=ID tidak valid');
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_items WHERE product_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                header('Location: products.php?error=Produk tidak dapat dihapus karena sudah pernah digunakan dalam transaksi');
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: products.php?success=delete');
        } catch (PDOException $e) {
            header('Location: products.php?error=Gagal menghapus produk');
        }
        break;
        
    default:
        header('Location: products.php');
        break;
}
?>